<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    die('No autenticado');
}

$folio = $_GET['folio'] ?? 0;

if (!$folio) {
    die('Folio no válido');
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
    WHERE v.folio = '$folio'
";

$result_venta = pg_query($conn, $sql_venta);
$venta = pg_fetch_assoc($result_venta);

if (!$venta) {
    die('Venta no encontrada');
}

$sql_detalles = "
    SELECT 
        dv.*,
        p.nombre as producto
    FROM tdetalle_venta dv
    LEFT JOIN tproducto p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = {$venta['id_venta']}
    ORDER BY dv.id_detalle
";

$result_detalles = pg_query($conn, $sql_detalles);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta - <?= $folio ?></title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            background: white;
            color: black;
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .empresa-nombre {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .empresa-direccion {
            font-size: 10px;
            margin: 3px 0;
        }
        
        .ticket-info {
            margin: 10px 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .ticket-info table {
            width: 100%;
        }
        
        .ticket-info td {
            padding: 2px 0;
        }
        
        .productos {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .productos th {
            border-bottom: 1px solid #000;
            padding: 5px 0;
            text-align: left;
        }
        
        .productos td {
            padding: 3px 0;
            vertical-align: top;
        }
        
        .producto-nombre {
            width: 60%;
        }
        
        .producto-cantidad {
            width: 10%;
            text-align: center;
        }
        
        .producto-precio {
            width: 30%;
            text-align: right;
        }
        
        .total-section {
            border-top: 2px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .total-grande {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        .pago-section {
            margin: 10px 0;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="ticket-header">
        <div class="empresa-nombre">CESAR'S FLAN</div>
        <div class="empresa-direccion">Paseo de los Nardos #3</div>
        <div class="empresa-direccion">Teléfono: 348 121 0416</div>
        <div class="empresa-direccion"><?= date('d/m/Y H:i:s') ?></div>
    </div>
    
    <div class="ticket-info">
        <table>
            <tr>
                <td><strong>FOLIO:</strong></td>
                <td><?= str_pad($venta['folio'], 6, "0", STR_PAD_LEFT) ?></td>
            </tr>
            <tr>
                <td><strong>FECHA:</strong></td>
                <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
            </tr>
            <tr>
                <td><strong>CLIENTE:</strong></td>
                <td><?= htmlspecialchars($venta['cliente'] ?? 'CLIENTE GENERAL') ?></td>
            </tr>
            <tr>
                <td><strong>VENDEDOR:</strong></td>
                <td><?= htmlspecialchars($venta['vendedor'] ?? 'SISTEMA') ?></td>
            </tr>
            <tr>
                <td><strong>TIPO:</strong></td>
                <td><?= $venta['tipo_venta'] ?></td>
            </tr>
        </table>
    </div>
    
    <table class="productos">
        <thead>
            <tr>
                <th class="producto-nombre">PRODUCTO</th>
                <th class="producto-cantidad">CANT</th>
                <th class="producto-precio">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_venta = 0;
            while ($detalle = pg_fetch_assoc($result_detalles)) {
                $subtotal = floatval($detalle['cantidad']) * floatval($detalle['precio_unitario']);
                $total_venta += $subtotal;
                ?>
                <tr>
                    <td class="producto-nombre"><?= htmlspecialchars($detalle['producto']) ?></td>
                    <td class="producto-cantidad"><?= $detalle['cantidad'] ?></td>
                    <td class="producto-precio">$<?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="font-size: 10px; padding-left: 10px;">
                        $<?= number_format($detalle['precio_unitario'], 2) ?> c/u
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    
    <div class="total-section">
        <div class="total-line total-grande">
            <span>TOTAL:</span>
            <span>$<?= number_format($total_venta, 2) ?></span>
        </div>
    </div>
    
    <div class="pago-section">
        <div class="total-line">
            <span>EFECTIVO:</span>
            <span>$<?= number_format($venta['efectivo'], 2) ?></span>
        </div>
        <div class="total-line">
            <span>CAMBIO:</span>
            <span>$<?= number_format($venta['cambio'], 2) ?></span>
        </div>
    </div>
    
    <div class="footer">
        <div>¡GRACIAS POR SU COMPRA!</div>
        <div>Vuelva pronto</div>
        <div>---</div>
        <div>Ticket generado el: <?= date('d/m/Y H:i:s') ?></div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button class="btn-print" onclick="window.print()">
            🖨️ IMPRIMIR TICKET
        </button>
        <br>
        <button class="btn-print" onclick="window.close()" style="background: #6c757d;">
            ❌ CERRAR
        </button>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        window.onafterprint = function() {
            setTimeout(function() {
            }, 1000);
        };
    </script>
</body>
</html>