<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['SISTEMA']['nombre'] ?? 'Usuario');

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$fechainicial = $_GET['fechainicial'] ?? '';
$fechafinal = $_GET['fechafinal'] ?? '';
$cliente_busqueda = $_GET['cliente'] ?? '';

$total_registros = 0;
$total_paginas = 0;

function cargarVentasPaginado($fechai, $fechaf, $cliente, $offset, $limit) {
    global $conn;
    
    $where_conditions = [];
    $params = [];
    
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

$resultados = cargarVentasPaginado($fechainicial, $fechafinal, $cliente_busqueda, $offset, $registros_por_pagina);
$ventas = $resultados['ventas'];
$total_registros = $resultados['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Ventas - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #2d2d2d;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            color: #ffffff; 
        }
        .container-main {
            padding: 20px;
        }
        .ventas-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #444;
        }
        .ventas-section h3 {
            text-align: center;
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .btn-buscar {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-buscar:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .btn-limpiar {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-limpiar:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: #ffffff;
        }
        .btn-warning {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-warning:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .filtros-section {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            background-color: #1a1a1a;
            border: 1px solid #444;
            color: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2d2d2d;
            border-color: #ffd700;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        .form-label {
            color: #ffd700 !important; 
            font-weight: bold;
        }
        .table {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .table th {
            background-color: #ffd700;
            color: #000000;
            border: none;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            font-size: 1rem;
        }
        .table td {
            background-color: #ffffff;
            color: #333333;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
            padding: 12px;
            vertical-align: middle;
        }
        .table-hover tbody tr:hover td {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            font-size: 0.8rem;
        }
        .modal-header {
            background-color: #2d2d2d;
            border-bottom: 2px solid #ffd700;
            color: #ffd700;
        }
        .modal-content {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .btn-custom-modal {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-custom-modal:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .search-box {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #444;
        }
        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .search-header h5 {
            color: #ffd700 !important; 
            margin: 0;
        }
        .vista-info {
            background-color: #3d3d3d;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ffd700;
            color: #ffffff;
        }
        .vista-info strong {
            color: #ffd700;
        }
        .user-info {
            background-color: #3d3d3d;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-bottom: 20px;
            color: #ffffff;
        }
        .user-info strong {
            color: #ffd700;
        }
        .text-light {
            color: #ffffff !important;
        }
        .text-warning {
            color: #ffd700 !important;
        }
        .text-muted {
            color: #cccccc !important;
        }
        .form-control::placeholder {
            color: #999999;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .page-link {
            background-color: #2d2d2d;
            border-color: #444;
            color: #ffd700;
            font-weight: bold;
        }
        .page-link:hover {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
        }
        .page-item.active .page-link {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
        }
        .page-item.disabled .page-link {
            background-color: #1a1a1a;
            border-color: #444;
            color: #666;
        }
        .pagination-info {
            text-align: center;
            color: #ffd700;
            margin-top: 10px;
            font-weight: bold;
        }
        .pagination {
            flex-wrap: wrap;
            gap: 5px;
        }
        .page-link {
            min-width: 45px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .page-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(255, 215, 0, 0.3);
        }
        .form-select {
            background-color: #2d2d2d !important;
            border: 1px solid #444 !important;
            color: #ffd700 !important;
        }
        .form-select:focus {
            border-color: #ffd700 !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25) !important;
        }

        .modal-content {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
        }
        
        .modal-content table {
            width: 100%;
            color: #ffffff !important;
        }
        
        .modal-content th {
            color: #ffd700 !important;
            background-color: #3d3d3d !important;
            border-color: #555 !important;
        }
        
        .modal-content td {
            color: #ffffff !important;
            background-color: #2d2d2d !important;
            border-color: #555 !important;
        }
        
        .modal-content .table-dark {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
        }
        
        .modal-content .table-dark th {
            background-color: #3d3d3d !important;
            color: #ffd700 !important;
            border-color: #555 !important;
        }
        
        .modal-content .table-dark td {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
            border-color: #555 !important;
        }
        
        .modal-body {
            color: #ffffff !important;
        }
        
        .modal-body h5 {
            color: #ffd700 !important;
        }
        
        .modal-body strong {
            color: #ffd700 !important;
        }
        
        .modal-body .text-success {
            color: #28a745 !important;
            font-weight: bold;
        }
        
        .modal-body .text-warning {
            color: #ffd700 !important;
        }
        
        .modal-body .badge {
            color: #000000 !important;
        }
        
        #modalDetallesContent table,
        #modalDetallesContent th,
        #modalDetallesContent td {
            color: #ffffff !important;
            border-color: #555 !important;
        }
        
        #modalDetallesContent th {
            color: #ffd700 !important;
        }

        @media (max-width: 768px) {
            .container-main {
                padding: 10px;
            }
            .ventas-section h3 {
                font-size: 1.8rem;
            }
            .pagination {
                justify-content: center;
            }
            .page-item .page-link {
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
                min-width: 40px;
            }
            .pagination-info {
                text-align: center !important;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="ventas-section">
            <h3><i class="fas fa-calendar-week me-2"></i> Consulta de Ventas</h3>
            
            <div class="user-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-user me-2"></i>Usuario:</strong> <?php echo $nombreUsuario; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong><i class="fas fa-calendar me-2"></i>Fecha:</strong> <span id="fecha-actual"></span>
                    </div>
                </div>
            </div>

            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                    <?php if ($fechainicial || $fechafinal || $cliente_busqueda): ?>
                        <span class="badge bg-warning text-dark">Filtros Activos</span>
                    <?php else: ?>
                        <span class="badge bg-success">Mostrando todas las ventas</span>
                    <?php endif; ?>
                </div>
                
                <div class="vista-info">
                    <strong><i class="fas fa-info-circle me-2"></i>Consulta de ventas</strong>
                    <p class="mb-0 text-light"><small>Las fechas son opcionales. Si no seleccionas fechas, se mostrarán todas las ventas.</small></p>
                </div>

                <form method="get" action="detventai.php" id="formFiltros">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><strong><i class="fas fa-calendar-start me-2"></i>Fecha Inicial (Opcional)</strong></label>
                            <input type="date" class="form-control" name="fechainicial" id="fechainicial" value="<?php echo $fechainicial; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong><i class="fas fa-calendar-end me-2"></i>Fecha Final (Opcional)</strong></label>
                            <input type="date" class="form-control" name="fechafinal" id="fechafinal" value="<?php echo $fechafinal; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong><i class="fas fa-users me-2"></i>Cliente (Opcional)</strong></label>
                            <input list="datosClientes" autocomplete="off" class="form-control" name="cliente" id="clientes" placeholder="Buscar clientes..." value="<?php echo htmlspecialchars($cliente_busqueda); ?>">
                            <datalist id="datosClientes">
                                <?php
                                $result = pg_query($conn, "SELECT nombre FROM tcliente ORDER BY nombre");
                                while ($cliente = pg_fetch_assoc($result)) {
                                    echo "<option value='{$cliente['nombre']}'>";
                                }
                                ?>
                            </datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>&nbsp;</strong></label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-buscar">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                                <?php if ($fechainicial || $fechafinal || $cliente_busqueda): ?>
                                    <a href="detventai.php" class="btn btn-limpiar">
                                        <i class="fas fa-times me-2"></i>Limpiar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table id="tablaVentas" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Usuario</th>
                            <th class="text-right">Importe</th>
                            <th class="text-center">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="resultadosVentas">
                        <?php if (empty($ventas)): ?>
                            <tr><td colspan="6" class="text-center"><i class="fas fa-info-circle me-2"></i>No se encontraron ventas</td></tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $row): ?>
                                <?php
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
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación de ventas">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=1<?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>" aria-label="Primera Página">
                                <span aria-hidden="true">««</span>
                            </a>
                        </li>

                        <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>" aria-label="Anterior">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>

                        <?php
                        $paginas_mostrar = 5;
                        $inicio = max(1, $pagina_actual - floor($paginas_mostrar / 2));
                        $fin = min($total_paginas, $inicio + $paginas_mostrar - 1);
                        
                        $inicio = max(1, $fin - $paginas_mostrar + 1);
                        
                        if ($inicio > 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($fin < $total_paginas): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>" aria-label="Siguiente">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>

                        <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>" aria-label="Última Página">
                                <span aria-hidden="true">»»</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="row align-items-center mt-3">
                    <div class="col-md-6">
                        <div class="pagination-info text-center text-md-start">
                            <small>
                                Mostrando <strong><?= count($ventas) ?></strong> de <strong><?= $total_registros ?></strong> ventas - 
                                Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                <?php if ($fechainicial || $fechafinal || $cliente_busqueda): ?>
                                    <span class="badge bg-warning text-dark ms-2">Filtros Activos</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2">Todas las ventas</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex justify-content-center justify-content-md-end align-items-center">
                            <label for="selectorPagina" class="form-label text-warning me-2 mb-0"><small>Ir a página:</small></label>
                            <select class="form-select form-select-sm" id="selectorPagina" style="max-width: 100px; background-color: #2d2d2d; color: #ffd700; border-color: #444;">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $pagina_actual ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning"><i class="fas fa-receipt me-2"></i>Detalles de Venta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetallesContent">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-modal" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function actualizarFecha() {
            const ahora = new Date();
            document.getElementById('fecha-actual').textContent = ahora.toLocaleDateString('es-MX');
        }
        actualizarFecha();

        function verDetalles(id_venta) {
            $.ajax({
                type: "POST",
                url: "detventaf.php",
                data: {
                    funcion: "Ver_Detalles",
                    id_venta: id_venta
                },
                dataType: "html",
                success: function(msg) {
                    $('#modalDetallesContent').html(msg);
                    
                    setTimeout(function() {
                        $('#modalDetallesContent').find('td').css({
                            'color': '#ffffff',
                            'background-color': '#2d2d2d'
                        });
                        $('#modalDetallesContent').find('th').css({
                            'color': '#ffd700',
                            'background-color': '#3d3d3d'
                        });
                        $('#modalDetallesContent').find('table').css('color', '#ffffff');
                        $('#modalDetallesContent').css('color', '#ffffff');
                    }, 100);
                    
                    $('#modalDetalles').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar detalles:', error);
                    $('#modalDetallesContent').html('<div class="alert alert-danger">Error al cargar los detalles de la venta</div>');
                    $('#modalDetalles').modal('show');
                }
            });
        }

        function reimprimirTicket(folio) {
            window.open('ticket_venta.php?folio=' + folio, '_blank', 'width=400,height=600');
            
            Swal.fire({
                title: 'Ticket Generado',
                text: 'El ticket para el folio ' + folio + ' se ha abierto en una nueva ventana para impresión.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }

        document.getElementById('selectorPagina')?.addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'detventai.php';
            const parametros = [];
            
            <?php if ($fechainicial): ?>
                parametros.push('fechainicial=<?= $fechainicial ?>');
            <?php endif; ?>
            <?php if ($fechafinal): ?>
                parametros.push('fechafinal=<?= $fechafinal ?>');
            <?php endif; ?>
            <?php if ($cliente_busqueda): ?>
                parametros.push('cliente=<?= urlencode($cliente_busqueda) ?>');
            <?php endif; ?>
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
        });

        document.addEventListener('keydown', function(e) {
            <?php if ($total_paginas > 1): ?>
                if (e.key === 'ArrowLeft' && <?= $pagina_actual > 1 ? 'true' : 'false' ?>) {
                    const url = '?pagina=<?= $pagina_actual - 1 ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>';
                    window.location.href = url;
                } else if (e.key === 'ArrowRight' && <?= $pagina_actual < $total_paginas ? 'true' : 'false' ?>) {
                    const url = '?pagina=<?= $pagina_actual + 1 ?><?= $fechainicial ? '&fechainicial=' . $fechainicial : '' ?><?= $fechafinal ? '&fechafinal=' . $fechafinal : '' ?><?= $cliente_busqueda ? '&cliente=' . urlencode($cliente_busqueda) : '' ?>';
                    window.location.href = url;
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>