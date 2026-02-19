<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$funcion = $_POST['funcion'] ?? '';

if ($funcion == 'Carga_Ventas' || $funcion == 'Ver_Detalles') {
} else {
    header('Content-Type: application/json; charset=utf-8');
}

switch ($funcion) {
    case 'Carga_Ventas':
        cargarVentas();
        break;
    case 'Ver_Detalles':
        verDetalles();
        break;
    case 'Cancelar_Venta':
        cancelarVenta();
        break;
    default:
        if ($funcion) {
            echo json_encode(['error' => 'Función no válida']);
        }
        break;
}

function cargarVentas() {
    global $conn;
    
    $fechai = $_POST['fechai'] ?? '';
    $fechaf = $_POST['fechaf'] ?? '';
    $cliente = $_POST['cliente'] ?? '';
    
    $where_conditions = [];
    
    if (!empty($fechai) && !empty($fechaf)) {
        $where_conditions[] = "v.fecha BETWEEN '$fechai 00:00:00' AND '$fechaf 23:59:59'";
    } elseif (!empty($fechai)) {
        $where_conditions[] = "v.fecha >= '$fechai 00:00:00'";
    } elseif (!empty($fechaf)) {
        $where_conditions[] = "v.fecha <= '$fechaf 23:59:59'";
    }
    
    if (!empty($cliente)) {
        $where_conditions[] = "c.nombre ILIKE '%" . pg_escape_string($cliente) . "%'";
    }
    
    $where = "";
    if (!empty($where_conditions)) {
        $where = "WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql = "
        SELECT 
            v.id_venta,
            v.folio,
            v.fecha,
            v.total,
            v.tipo_venta,
            v.efectivo,
            v.cambio,
            c.nombre as cliente,
            u.nombre as usuario
        FROM tventa v
        LEFT JOIN tcliente c ON v.id_cliente = c.id_cliente
        LEFT JOIN tusuario u ON v.id_usuario = u.id_usuario
        $where
        ORDER BY v.fecha DESC
    ";
    
    $result = pg_query($conn, $sql);
    
    if (!$result) {
        echo "Error al ejecutar consulta: " . pg_last_error($conn);
        exit;
    }
    
    if (pg_num_rows($result) === 0) {
        echo '<tr><td colspan="6" class="text-center"><i class="fas fa-info-circle me-2"></i>No se encontraron ventas</td></tr>';
        exit;
    }
    
    while ($row = pg_fetch_assoc($result)) {
        $fecha = explode(" ", $row["fecha"]);
        $fecha_parts = explode("-", $fecha[0]);
        $dia = $fecha_parts[2];
        $mes = $fecha_parts[1];
        $ano = $fecha_parts[0];
        
        $hora_parts = explode(":", $fecha[1]);
        $hora = $hora_parts[0];
        $minutos = $hora_parts[1];
        
        ?>
        <tr class="text-uppercase">
            <td class="text-center fw-bold"><?= str_pad($row["folio"], 6, "0", STR_PAD_LEFT) ?></td>
            <td class="text-center"><?= $dia . "/" . $mes . "/" . $ano . " " . $hora . ":" . $minutos ?></td>
            <td class="fw-bold"><?= htmlspecialchars($row['cliente'] ?? 'Cliente general') ?></td>
            <td><?= htmlspecialchars($row['usuario'] ?? 'No especificado') ?></td>
            <td class="text-right fw-bold text-success">$ <?= number_format($row['total'], 2) ?></td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class='btn btn-info me-1' onclick='verDetalles(<?= $row['id_venta'] ?>)' title='Ver Detalles'>
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    
                    <button class='btn btn-warning' onclick='reimprimirTicket("<?= $row['folio'] ?>")' title='Reimprimir Ticket'>
                        <i class="fas fa-receipt"></i> Ticket
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }
    
    exit;
}

function verDetalles() {
    global $conn;
    
    $id_venta = $_POST['id_venta'] ?? 0;
    
    if (!$id_venta) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>ID de venta no válido</div>";
        exit;
    }
    
    $sql_venta = "
        SELECT 
            v.*,
            c.nombre as cliente,
            c.telefono,
            u.nombre as vendedor
        FROM tventa v
        LEFT JOIN tcliente c ON v.id_cliente = c.id_cliente
        LEFT JOIN tusuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_venta = $id_venta
    ";
    
    $result_venta = pg_query($conn, $sql_venta);
    
    if (!$result_venta) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error al cargar venta: " . pg_last_error($conn) . "</div>";
        exit;
    }
    
    $venta = pg_fetch_assoc($result_venta);
    
    if (!$venta) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Venta no encontrada</div>";
        exit;
    }
    
    $sql_detalles = "
        SELECT 
            dv.*,
            p.nombre as producto
        FROM tdetalle_venta dv
        LEFT JOIN tproducto p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta = $id_venta
        ORDER BY dv.id_detalle
    ";
    
    $result_detalles = pg_query($conn, $sql_detalles);
    
    if (!$result_detalles) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error al cargar detalles: " . pg_last_error($conn) . "</div>";
        exit;
    }
    
    ?>
    <div class="container-fluid" style="color: #ffffff !important;">
        <div class="row">
            <div class="col-md-6">
                <h5 class="text-warning"><i class="fas fa-info-circle me-2"></i>Información de Venta</h5>
                <table class="table table-sm" style="background-color: #3d3d3d; color: #ffffff !important; border-color: #555;">
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;"><i class="fas fa-receipt me-2"></i>Folio:</th>
                        <td style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><strong class="text-warning"><?= str_pad($venta['folio'], 6, "0", STR_PAD_LEFT) ?></strong></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;"><i class="fas fa-calendar me-2"></i>Fecha:</th>
                        <td style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><?= $venta['fecha'] ?></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;"><i class="fas fa-user me-2"></i>Cliente:</th>
                        <td style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><?= htmlspecialchars($venta['cliente'] ?? 'Cliente general') ?></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;"><i class="fas fa-user-tie me-2"></i>Vendedor:</th>
                        <td style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><?= htmlspecialchars($venta['vendedor'] ?? 'No especificado') ?></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;"><i class="fas fa-tag me-2"></i>Tipo Venta:</th>
                        <td style="border-color: #555; background-color: #3d3d3d;"><span class="badge bg-warning text-dark"><?= $venta['tipo_venta'] ?? 'CONTADO' ?></span></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5 class="text-warning"><i class="fas fa-money-bill-wave me-2"></i>Resumen de Pago</h5>
                <table class="table table-sm" style="background-color: #3d3d3d; color: #ffffff !important; border-color: #555;">
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;">Total Venta:</th>
                        <td class="text-right" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><strong class="text-success">$ <?= number_format($venta['total'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;">Efectivo Recibido:</th>
                        <td class="text-right" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;">$ <?= number_format($venta['efectivo'], 2) ?></td>
                    </tr>
                    <tr>
                        <th style="color: #ffd700 !important; border-color: #555; background-color: #444;">Cambio:</th>
                        <td class="text-right" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;">$ <?= number_format($venta['cambio'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h5 class="text-warning"><i class="fas fa-boxes me-2"></i>Productos Vendidos</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" style="background-color: #3d3d3d; color: #ffffff !important; border-color: #555;">
                        <thead>
                            <tr>
                                <th style="color: #ffd700 !important; border-color: #555; background-color: #444;">#</th>
                                <th style="color: #ffd700 !important; border-color: #555; background-color: #444;">Producto</th>
                                <th class="text-center" style="color: #ffd700 !important; border-color: #555; background-color: #444;">Cantidad</th>
                                <th class="text-right" style="color: #ffd700 !important; border-color: #555; background-color: #444;">Precio Unit.</th>
                                <th class="text-right" style="color: #ffd700 !important; border-color: #555; background-color: #444;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_productos = 0;
                            $contador = 1;
                            while ($detalle = pg_fetch_assoc($result_detalles)) {
                                $subtotal = floatval($detalle['cantidad']) * floatval($detalle['precio_unitario']);
                                $total_productos += $subtotal;
                                ?>
                                <tr>
                                    <td class="text-center" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;"><?= $contador++ ?></td>
                                    <td style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;">
                                        <i class="fas fa-cube me-2"></i><?= htmlspecialchars($detalle['producto'] ?? 'Producto no disponible') ?>
                                    </td>
                                    <td class="text-center" style="border-color: #555; background-color: #3d3d3d;">
                                        <span class="badge bg-primary"><?= $detalle['cantidad'] ?></span>
                                    </td>
                                    <td class="text-right" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;">$ <?= number_format($detalle['precio_unitario'], 2) ?></td>
                                    <td class="text-right" style="color: #ffffff !important; border-color: #555; background-color: #3d3d3d;">$ <?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr style="background-color: #2d5a2d;">
                                <td colspan="4" class="text-right" style="border-color: #555;"><strong style="color: #ffffff !important;">Total Productos:</strong></td>
                                <td class="text-right" style="border-color: #555;"><strong class="text-success">$ <?= number_format($total_productos, 2) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12 text-center">
                <button class="btn btn-custom-modal" onclick='reimprimirTicket("<?= $venta['folio'] ?>")'>
                    <i class="fas fa-receipt me-2"></i>Reimprimir Ticket
                </button>
            </div>
        </div>
    </div>
    <?php
    exit;
}

function cancelarVenta() {
    echo json_encode([
        'success' => false, 
        'error' => 'La funcionalidad de cancelación no está disponible. La estructura de la base de datos no incluye campos para cancelación de ventas.'
    ]);
    exit;
}

function cargarVentasPaginado($fechai, $fechaf, $cliente, $offset, $limit) {
    global $conn;
    
    $where_conditions = [];
    
    if (!empty($fechai) && !empty($fechaf)) {
        $where_conditions[] = "v.fecha BETWEEN '$fechai 00:00:00' AND '$fechaf 23:59:59'";
    } elseif (!empty($fechai)) {
        $where_conditions[] = "v.fecha >= '$fechai 00:00:00'";
    } elseif (!empty($fechaf)) {
        $where_conditions[] = "v.fecha <= '$fechaf 23:59:59'";
    }
    
    if (!empty($cliente)) {
        $where_conditions[] = "c.nombre ILIKE '%" . pg_escape_string($cliente) . "%'";
    }
    
    $where = "";
    if (!empty($where_conditions)) {
        $where = "WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql_count = "
        SELECT COUNT(*) as total
        FROM tventa v
        LEFT JOIN tcliente c ON v.id_cliente = c.id_cliente
        LEFT JOIN tusuario u ON v.id_usuario = u.id_usuario
        $where
    ";
    
    $result_count = pg_query($conn, $sql_count);
    $total_row = pg_fetch_assoc($result_count);
    $total_registros = $total_row['total'];
    
    $sql = "
        SELECT 
            v.id_venta,
            v.folio,
            v.fecha,
            v.total,
            v.tipo_venta,
            v.efectivo,
            v.cambio,
            c.nombre as cliente,
            u.nombre as usuario
        FROM tventa v
        LEFT JOIN tcliente c ON v.id_cliente = c.id_cliente
        LEFT JOIN tusuario u ON v.id_usuario = u.id_usuario
        $where
        ORDER BY v.fecha DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $result = pg_query($conn, $sql);
    $ventas = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $ventas[] = $row;
        }
    }
    
    return [
        'ventas' => $ventas,
        'total' => $total_registros
    ];
}
?>