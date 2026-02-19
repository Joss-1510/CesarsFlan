<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    die("No autorizado");
}

require_once 'produccionf.php';
require_once 'conexion.php';

if (!isset($_GET['id_produccion']) || !is_numeric($_GET['id_produccion'])) {
    die("ID de producción inválido");
}

$id_produccion = $_GET['id_produccion'];

try {
    $produccionF = new ProduccionF($conn);
    
    $produccion = $produccionF->obtenerProduccionPorId($id_produccion);
    
    if (!$produccion) {
        echo '<div class="alert alert-danger">Producción no encontrada</div>';
        exit();
    }
    
    $detalles = $produccionF->obtenerDetallesProduccion($id_produccion);
    
    $costos = $produccionF->calcularCostosProduccion($id_produccion);
    
    ?>
    
    <div class="detalles-produccion">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card" style="background-color: #2d2d2d; border: 1px solid #444;">
                    <div class="card-header" style="background-color: #ffd700; color: #000000;">
                        <h6 class="mb-0">📋 Información de la Producción</h6>
                    </div>
                    <div class="card-body text-light">
                        <p><strong>Semielaborado:</strong> 
                            <?php if (!empty($produccion['nombre_semielaborado'])): ?>
                                <span class="badge bg-primary">🍮 <?= htmlspecialchars($produccion['nombre_semielaborado']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">No especificado</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Cantidad Producida:</strong> 
                            <span class="badge bg-success"><?= htmlspecialchars($produccion['cantidad'] ?? 0) ?> unidades</span>
                        </p>
                        <p><strong>Estado:</strong> 
                            <span class="badge <?= $produccion['estado'] === 'Terminada' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= htmlspecialchars($produccion['estado'] ?? 'En proceso') ?>
                            </span>
                        </p>
                        <p><strong>Fecha Inicio:</strong> <?= date('d/m/Y H:i', strtotime($produccion['fecha_inicio'])) ?></p>
                        <?php if ($produccion['fecha_fin']): ?>
                            <p><strong>Fecha Fin:</strong> <?= date('d/m/Y H:i', strtotime($produccion['fecha_fin'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produccion['observaciones'])): ?>
                            <p><strong>Observaciones:</strong> <?= htmlspecialchars($produccion['observaciones']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produccion['uso_receta']) && $produccion['uso_receta'] == 't'): ?>
                            <p><strong>Receta:</strong> <span class="badge bg-info">📋 Automática</span></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card" style="background-color: #2d2d2d; border: 1px solid #444;">
                    <div class="card-header" style="background-color: #28a745; color: #ffffff;">
                        <h6 class="mb-0">💰 Costos de Producción</h6>
                    </div>
                    <div class="card-body text-light">
                        <p><strong>Costo Ingredientes:</strong> 
                            <span class="text-info">$<?= number_format($costos['costo_ingredientes'] ?? 0, 2) ?></span>
                        </p>
                        <p><strong>Costo Materiales:</strong> 
                            <span class="text-warning">$<?= number_format($costos['costo_materiales'] ?? 0, 2) ?></span>
                        </p>
                        <p><strong>Costo Total:</strong> 
                            <span class="text-success fw-bold fs-5">$<?= number_format($costos['costo_total'] ?? 0, 2) ?></span>
                        </p>
                        <p><strong>Costo por Unidad:</strong> 
                            <?php if ($produccion['cantidad'] > 0): ?>
                                <span class="text-light fw-bold">$<?= number_format(($costos['costo_total'] ?? 0) / $produccion['cantidad'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-muted">$0.00</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden;">
            <div class="card-header" style="background-color: #17a2b8; color: #ffffff; border-bottom: 2px solid #117a8b;">
                <h6 class="mb-0">🧂 Detalles de Ingredientes Utilizados</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($detalles)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="background-color: #ffffff;">
                            <thead>
                                <tr>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Ingrediente</th>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Cantidad Usada</th>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Unidad</th>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Costo Unitario</th>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Costo Total</th>
                                    <th style="background-color: #ffd700; color: #000000; border-bottom: 2px solid #dee2e6; text-align: center; padding: 15px;">Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr style="background-color: #ffffff;">
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle; font-weight: bold;">
                                            <?= htmlspecialchars($detalle['nombre_item']) ?>
                                        </td>
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                            <?= number_format($detalle['cantidad_usada'], 2) ?>
                                        </td>
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                            <?= htmlspecialchars($detalle['unidad_medida']) ?>
                                        </td>
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                            $<?= number_format($detalle['costo_ingrediente'], 2) ?>
                                        </td>
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle; font-weight: bold;">
                                            $<?= number_format($detalle['cantidad_usada'] * $detalle['costo_ingrediente'], 2) ?>
                                        </td>
                                        <td style="border-bottom: 1px solid #e0e0e0; text-align: center; padding: 12px; color: #333333; vertical-align: middle;">
                                            <span class="badge <?= $detalle['es_automatico'] === 't' ? 'bg-success' : 'bg-secondary' ?>" style="padding: 6px 12px; border-radius: 10px;">
                                                <?= $detalle['es_automatico'] === 't' ? '🤖 Automático' : '👤 Manual' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning m-3">
                        <i class="fas fa-info-circle"></i> No hay ingredientes registrados para esta producción.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3" style="background-color: #2d2d2d; border: 1px solid #444;">
            <div class="card-header" style="background-color: #6c757d; color: #ffffff;">
                <h6 class="mb-0">ℹ️ Información sobre el Origen</h6>
            </div>
            <div class="card-body text-light">
                <div class="row">
                    <div class="col-md-6">
                        <p><span class="badge bg-success me-2">🤖 Automático</span> 
                        <small>Ingrediente agregado automáticamente mediante receta</small></p>
                    </div>
                    <div class="col-md-6">
                        <p><span class="badge bg-secondary me-2">👤 Manual</span> 
                        <small>Ingrediente agregado manualmente por el usuario</small></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($produccion['estado'] !== 'Terminada'): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#agregarIngredienteModal" data-id="<?= $id_produccion ?>">
                        🧂 Agregar Ingrediente Manual
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <style>
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
        
        .estado-terminada {
            color: #28a745;
            font-weight: bold;
        }
        .estado-en-proceso {
            color: #ffc107;
            font-weight: bold;
        }
    </style>

    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar los detalles: ' . $e->getMessage() . '</div>';
}
?>