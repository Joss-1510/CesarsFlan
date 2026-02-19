<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'empaquetadof.php';
require_once 'conexion.php';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

if (!isset($_GET['id_empaquetado']) || empty($_GET['id_empaquetado'])) {
    echo '<div class="alert alert-danger">No se especificó el empaquetado</div>';
    exit();
}

$id_empaquetado = $_GET['id_empaquetado'];

try {
    $empaquetadoF = new EmpaquetadoF($conn);
    
    $empaquetado = $empaquetadoF->obtenerEmpaquetadoPorId($id_empaquetado);
    
    if (!$empaquetado) {
        echo '<div class="alert alert-danger">No se encontró el empaquetado solicitado</div>';
        exit();
    }
    
    $detalles = $empaquetadoF->obtenerDetallesEmpaquetado($id_empaquetado);
    
    if (empty($detalles)) {
        echo '
        <div class="alert alert-warning">
            <h5>📦 Empaquetado #' . $empaquetado['id_empaquetado'] . '</h5>
            <p><strong>Estado:</strong> ' . ($empaquetado['estado'] ?? 'PENDIENTE') . '</p>
            <p><strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($empaquetado['fecha_empaquetado'])) . '</p>
            <p><strong>Observaciones:</strong> ' . ($empaquetado['observaciones'] ?: 'Ninguna') . '</p>
            <hr>
            <p class="mb-0">No se encontraron detalles de producción para este empaquetado.</p>
        </div>';
        exit();
    }
    
    $total_semielaborados = 0;
    $total_material = 0;
    $total_productos = 0;
    $producto_final_nombre = '';
    
    foreach ($detalles as $detalle) {
        $total_semielaborados += $detalle['cantidad_semielaborados_usados'];
        $total_material += $detalle['cantidad_material_usado'];
        $total_productos += $detalle['cantidad_productos_creados'];
        if (empty($producto_final_nombre) && !empty($detalle['nombre_producto_final'])) {
            $producto_final_nombre = $detalle['nombre_producto_final'];
        }
    }
    
    ?>
    
    <div class="detalles-empaquetado">
        <div class="card mb-4" style="background-color: #2d2d2d; border: 1px solid #444;">
            <div class="card-header" style="background-color: #ffd700; color: #000000;">
                <h5 class="mb-0">📦 Información del Empaquetado</h5>
            </div>
            <div class="card-body text-light">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID Empaquetado:</strong> #<?= $empaquetado['id_empaquetado'] ?></p>
                        <p><strong>Estado:</strong> 
                            <span class="badge <?= ($empaquetado['estado'] === 'FINALIZADO') ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $empaquetado['estado'] ?? 'PENDIENTE' ?>
                            </span>
                        </p>
                        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($empaquetado['fecha_empaquetado'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Usuario:</strong> <?= $empaquetado['nombre_usuario'] ?: 'No especificado' ?></p>
                        <p><strong>Observaciones:</strong> <?= $empaquetado['observaciones'] ?: 'Ninguna' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" style="background-color: #2d2d2d; border: 1px solid #444;">
            <div class="card-header" style="background-color: #28a745; color: #ffffff;">
                <h5 class="mb-0">📊 Resumen de Producción</h5>
            </div>
            <div class="card-body text-light">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?= $total_semielaborados ?></div>
                            <div class="stat-label">Semielaborados Usados</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number text-info"><?= $total_material ?></div>
                            <div class="stat-label">Material Usado</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?= $total_productos ?></div>
                            <div class="stat-label">Productos Creados</div>
                        </div>
                    </div>
                </div>
                <?php if ($producto_final_nombre): ?>
                    <div class="text-center mt-3">
                        <span class="badge bg-primary fs-6">🎁 Producto Final: <?= htmlspecialchars($producto_final_nombre) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden;">
            <div class="card-header" style="background-color: #17a2b8; color: #ffffff; border-bottom: 2px solid #117a8b;">
                <h5 class="mb-0">📋 Detalles de Ingredientes Utilizados</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="background-color: #ffffff;">
                        <thead>
                            <tr>
                                <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Semielaborado</th>
                                <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Material</th>
                                <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Cantidad Semielaborados</th>
                                <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Cantidad Material</th>
                                <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Productos Resultantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr style="background-color: #ffffff;">
                                    <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                        <span class="badge bg-purple" style="background-color: #6f42c1; color: #ffffff; padding: 6px 12px; border-radius: 10px;">
                                            🍮 <?= htmlspecialchars($detalle['nombre_semielaborado']) ?>
                                        </span>
                                    </td>
                                    <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                        <span class="badge bg-info" style="background-color: #17a2b8; color: #ffffff; padding: 6px 12px; border-radius: 10px;">
                                            📦 <?= htmlspecialchars($detalle['nombre_material']) ?>
                                        </span>
                                    </td>
                                    <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle; font-weight: bold; color: #ff6b35;">
                                        <?= $detalle['cantidad_semielaborados_usados'] ?>
                                    </td>
                                    <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle; font-weight: bold; color: #17a2b8;">
                                        <?= $detalle['cantidad_material_usado'] ?>
                                    </td>
                                    <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle; font-weight: bold; color: #28a745;">
                                        <?= $detalle['cantidad_productos_creados'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stat-card {
            padding: 15px;
            border-radius: 10px;
            background-color: #1a1a1a;
            border: 1px solid #444;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #cccccc;
            font-size: 0.9rem;
        }
        
        .table {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        
        .bg-purple {
            background-color: #6f42c1 !important;
            color: #ffffff !important;
        }
        
        .modal-dialog-detalles {
            max-width: 900px;
        }
        .card-header {
            border-bottom: 2px solid;
            font-weight: bold;
        }
    </style>

    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar los detalles: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>