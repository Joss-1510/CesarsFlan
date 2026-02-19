<?php
include 'conexion.php';
include 'cajaf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['SISTEMA']['nombre'] ?? 'Usuario');
$id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? 1;

$gestionCaja = new GestionCaja($conn);

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

$fecha_desde = $_GET['fechaDesde'] ?? '';
$fecha_hasta = $_GET['fechaHasta'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';
$busqueda_activa = !empty($fecha_desde) || !empty($fecha_hasta) || !empty($tipo_filtro);

$resultados = $gestionCaja->getMovimientos($fecha_desde, $fecha_hasta, $tipo_filtro, $pagina_actual, $registros_por_pagina);
$movimientos = $resultados['movimientos'];
$total_registros = $resultados['total_registros'];
$total_paginas = $resultados['total_paginas'];

$estado_caja = $gestionCaja->getEstadoCaja();

$estadisticas = $estado_caja ? $gestionCaja->getEstadisticasActuales() : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Caja - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #2d2d2d;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
        }
        .container-main {
            padding: 20px;
        }
        .caja-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 25px;
            border-radius: 15px;
        }
        .caja-section h3 {
            text-align: center;
            font-size: 2.2rem;
            color: #ffd700;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
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
            border-color: #dab600;
            color: #000000;
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
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border-radius: 10px;
            border: 1px solid #444;
        }
        .filtros-container {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .table {
            margin-bottom: 0;
            color: #333333;
        }
        .table thead th {
            background-color: #ffd700;
            color: #000000;
            border: none;
            font-weight: bold;
            text-align: center;
            padding: 15px 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody td {
            background-color: #ffffff;
            border-color: #dee2e6;
            padding: 12px 10px;
            vertical-align: middle;
            color: #333333;
        }
        .table tbody tr:hover td {
            background-color: #f8f9fa;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }
        .table-striped tbody tr:nth-of-type(odd):hover td {
            background-color: #e9ecef;
        }
        .badge-tipo {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .badge-apertura {
            background-color: #28a745;
            color: white;
        }
        .badge-cierre {
            background-color: #dc3545;
            color: white;
        }
        .badge-venta {
            background-color: #17a2b8;
            color: white;
        }
        .badge-gasto {
            background-color: #ffc107;
            color: black;
        }
        .badge-abierta {
            background-color: #28a745;
            color: white;
        }
        .badge-cerrada {
            background-color: #6c757d;
            color: white;
        }
        .badge-pendiente {
            background-color: #ffc107;
            color: black;
        }
        .pagination-container {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-top: 20px;
        }
        .page-link {
            background-color: #3d3d3d;
            border-color: #555;
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
            border-color: #333;
            color: #666;
        }
        .descripcion-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #333333;
        }
        .descripcion-cell:hover {
            white-space: normal;
            overflow: visible;
            background-color: #f8f9fa;
            position: relative;
            z-index: 10;
            border: 1px solid #ffd700;
            border-radius: 4px;
            padding: 8px;
        }
        .no-records {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            font-style: italic;
            background: #ffffff;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            text-align: center;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #cccccc;
            font-size: 0.9rem;
        }
        .pagination-info {
            text-align: center;
            color: #ffd700;
            margin-top: 10px;
            font-weight: bold;
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
            color: #ffffff;
        }
        .search-results {
            background-color: #1a3a1a;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            color: #ffffff;
        }
        .estado-caja-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        .estado-titulo {
            color: #ffd700;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }
        .estado-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .estado-item {
            text-align: center;
            padding: 10px;
            background-color: #1a1a1a;
            border-radius: 5px;
        }
        .estado-label {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .estado-valor {
            color: #ffd700;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .modal-content {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .modal-header {
            border-bottom: 1px solid #444;
        }
        .modal-footer {
            border-top: 1px solid #444;
        }
        .movimiento-positivo {
            color: #28a745;
            font-weight: bold;
        }
        .movimiento-negativo {
            color: #dc3545;
            font-weight: bold;
        }
        .acciones-caja {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .campo-saldo-final {
            background-color: #1a1a1a !important;
            border: 2px solid #ffd700 !important;
            color: #ffd700 !important;
            font-weight: bold !important;
            font-size: 1.2rem !important;
            text-align: center !important;
            padding: 10px !important;
            border-radius: 8px !important;
        }
        
        .campo-saldo-final:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.5) !important;
            border-color: #ffd700 !important;
            outline: none !important;
        }
        
        .label-saldo-final {
            color: #ffd700 !important;
            font-weight: bold !important;
            font-size: 1.1rem !important;
            margin-bottom: 10px !important;
        }
        
        .resultado-diferencia {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
            font-weight: bold;
        }
        
        .resultado-diferencia.excedente {
            background-color: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .resultado-diferencia.faltante {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .resultado-diferencia.perfecto {
            background-color: rgba(40, 167, 69, 0.2);
            border-color: #28a745;
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .header-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .table-responsive {
                font-size: 0.9rem;
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
            .acciones-caja {
                flex-direction: column;
            }
        }
        .swal2-popup {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
        }
        .swal2-title {
            color: #ffd700 !important;
        }
        .swal2-html-container {
            color: #cccccc !important;
        }
        .swal2-confirm {
            background-color: #28a745 !important;
        }
        .swal2-cancel {
            background-color: #dc3545 !important;
        }
        .btn-secondary-custom {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-secondary-custom:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="caja-section">
            <h3>💰 Gestión de Caja</h3>
            
            <div class="header-info">
                <div class="text-warning">
                    <strong>Usuario:</strong> <?php echo $nombreUsuario; ?>
                </div>
                <div class="text-light">
                    <strong>Fecha:</strong> <span id="fecha-actual"><?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="text-light">
                    <strong>Hora:</strong> <span id="hora-actual"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>

            <div class="estado-caja-card">
                <div class="estado-titulo">
                    <?php if ($estado_caja): ?>
                        <span class="badge badge-abierta">CAJA ABIERTA</span>
                    <?php else: ?>
                        <span class="badge badge-cerrada">CAJA CERRADA</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($estado_caja): ?>
                    <div class="estado-info">
                        <div class="estado-item">
                            <div class="estado-label">Saldo Inicial</div>
                            <div class="estado-valor">$<?= number_format($estado_caja['saldo_inicial'], 2) ?></div>
                        </div>
                        <div class="estado-item">
                            <div class="estado-label">Saldo Actual</div>
                            <div class="estado-valor">$<?= number_format($estado_caja['saldo_final_calculado'], 2) ?></div>
                        </div>
                        <div class="estado-item">
                            <div class="estado-label">Apertura</div>
                            <div class="estado-valor">
                                <?php
                                $fecha_apertura = new DateTime($estado_caja['fecha']);
                                echo $fecha_apertura->format('d/m/Y H:i');
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="acciones-caja">
                    <?php if (!$estado_caja): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalApertura">
                            <i class="fas fa-lock-open"></i> Abrir Caja
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCierre">
                            <i class="fas fa-lock"></i> Cerrar Caja
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalGasto">
                            <i class="fas fa-exchange-alt"></i> Nuevo Gasto
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($estado_caja && $estadisticas): ?>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-number" style="color: #17a2b8;">
                            $<?= number_format($estadisticas['monto_ventas'], 2) ?>
                            <small class="stat-label" style="display: block; font-size: 0.8rem;">
                                (<?= $estadisticas['total_ventas'] ?> ventas)
                            </small>
                        </div>
                        <div class="stat-label">Total Ventas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ffc107;">
                            $<?= number_format($estadisticas['monto_gastos'], 2) ?>
                            <small class="stat-label" style="display: block; font-size: 0.8rem;">
                                (<?= $estadisticas['total_gastos'] ?> gastos)
                            </small>
                        </div>
                        <div class="stat-label">Total Gastos</div>
                    </div>
                </div>
            <?php elseif ($estado_caja): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> No hay movimientos registrados en esta caja
                </div>
            <?php endif; ?>

            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Filtros de Búsqueda</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Filtros aplicados:</strong>
                        <?php 
                        $filtros = [];
                        if (!empty($fecha_desde)) $filtros[] = "Desde: " . $fecha_desde;
                        if (!empty($fecha_hasta)) $filtros[] = "Hasta: " . $fecha_hasta;
                        if (!empty($tipo_filtro)) $filtros[] = "Tipo: " . $tipo_filtro;
                        echo implode(" | ", $filtros);
                        ?>
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> registro(s) encontrado(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="get" action="cajai.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-warning"><strong>📅 Fecha Desde</strong></label>
                            <input type="date" class="form-control" name="fechaDesde" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-warning"><strong>📅 Fecha Hasta</strong></label>
                            <input type="date" class="form-control" name="fechaHasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-warning"><strong>💰 Tipo</strong></label>
                            <select class="form-select" name="tipo">
                                <option value="">Todos los tipos</option>
                                <option value="APERTURA" <?= $tipo_filtro == 'APERTURA' ? 'selected' : '' ?>>Apertura</option>
                                <option value="CIERRE" <?= $tipo_filtro == 'CIERRE' ? 'selected' : '' ?>>Cierre</option>
                                <option value="VENTA" <?= $tipo_filtro == 'VENTA' ? 'selected' : '' ?>>Venta</option>
                                <option value="GASTO" <?= $tipo_filtro == 'GASTO' ? 'selected' : '' ?>>Gasto</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button class="btn btn-warning flex-fill" type="submit">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <?php if ($busqueda_activa): ?>
                                    <a href="cajai.php" class="btn btn-secondary-custom">🔄 Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Tipo</th>
                                <th>Usuario</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movimientos)): ?>
                                <?php 
                                $saldos_por_usuario = [];
                                ?>
                                <?php foreach ($movimientos as $mov): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php
                                            $fecha = new DateTime($mov['fecha']);
                                            echo $fecha->format('d/m/Y H:i:s');
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $badge_class = '';
                                            $badge_icon = '';
                                            $tipo_text = $mov['tipo'];
                                            
                                            switch($mov['tipo']) {
                                                case 'APERTURA':
                                                    $badge_class = 'badge-apertura';
                                                    $badge_icon = '🔓';
                                                    break;
                                                case 'CIERRE':
                                                    $badge_class = 'badge-cierre';
                                                    $badge_icon = '🔒';
                                                    break;
                                                case 'VENTA':
                                                    $badge_class = 'badge-venta';
                                                    $badge_icon = '🛒';
                                                    break;
                                                case 'GASTO':
                                                    $badge_class = 'badge-gasto';
                                                    $badge_icon = '💸';
                                                    break;
                                                default:
                                                    $badge_class = 'badge-secondary';
                                                    $badge_icon = '📝';
                                            }
                                            ?>
                                            <span class="badge-tipo <?= $badge_class ?>">
                                                <?= $badge_icon ?> <?= htmlspecialchars($tipo_text) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?= htmlspecialchars($mov['nombre_usuario'] ?? 'Sistema') ?>
                                        </td>
                                        <td class="descripcion-cell">
                                            <?= htmlspecialchars($mov['descripcion']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $clase_monto = '';
                                            if ($mov['tipo'] == 'VENTA') {
                                                $clase_monto = 'movimiento-positivo';
                                            } else if ($mov['tipo'] == 'GASTO') {
                                                $clase_monto = 'movimiento-negativo';
                                            }
                                            ?>
                                            <span class="<?= $clase_monto ?>">
                                                <?php 
                                                if ($mov['tipo'] == 'APERTURA') {
                                                    echo 'Monto inicial: $' . number_format($mov['monto'], 2);
                                                } else {
                                                    echo '$' . number_format($mov['monto'], 2);
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $id_usuario_mov = $mov['id_usuario'];
                                            $tipo_mov = $mov['tipo'];
                                            $monto_mov = $mov['monto'];
                                            
                                            if (!isset($saldos_por_usuario[$id_usuario_mov])) {
                                                $saldos_por_usuario[$id_usuario_mov] = 0;
                                            }
                                            
                                            $saldo_mostrar = 0;
                                            
                                            if ($tipo_mov == 'APERTURA') {
                                                $saldo_mostrar = $monto_mov;
                                                $saldos_por_usuario[$id_usuario_mov] = $monto_mov;
                                            } else if ($tipo_mov == 'CIERRE') {
                                                $saldo_mostrar = $mov['saldo_final_calculado'];
                                            } else {
                                                $saldo_mostrar = $mov['saldo_final_calculado'];
                                            }
                                            
                                            echo '$' . number_format($saldo_mostrar, 2);
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $estado = $mov['estado'];
                                            if ($estado == 'ABIERTA') {
                                                echo '<span class="badge badge-abierta">ABIERTA</span>';
                                            } else if ($estado == 'CERRADA') {
                                                echo '<span class="badge badge-cerrada">CERRADA</span>';
                                            } else if ($estado == 'PENDIENTE') {
                                                echo '<span class="badge badge-pendiente">PENDIENTE</span>';
                                            } else {
                                                echo '<span class="badge badge-secondary">' . htmlspecialchars($estado) . '</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-records">
                                        <i class="fas fa-info-circle fa-2x mb-3" style="color: #6c757d;"></i>
                                        <p style="color: #6c757d;">No se encontraron movimientos</p>
                                        <small style="color: #6c757d;"><?= $busqueda_activa ? 'Prueba con otros filtros de búsqueda' : 'No hay movimientos registrados' ?></small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Paginación de movimientos">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=1<?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&tipo=' . urlencode($tipo_filtro) : '' ?>">
                                    <span aria-hidden="true">««</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&tipo=' . urlencode($tipo_filtro) : '' ?>">
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
                                    <a class="page-link" href="?pagina=<?= $i ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&tipo=' . urlencode($tipo_filtro) : '' ?>">
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
                                <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&tipo=' . urlencode($tipo_filtro) : '' ?>">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&tipo=' . urlencode($tipo_filtro) : '' ?>">
                                    <span aria-hidden="true">»»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-6">
                            <div class="pagination-info text-center text-md-start">
                                <small>
                                    Mostrando <strong><?= count($movimientos) ?></strong> de <strong><?= $total_registros ?></strong> movimientos - 
                                    Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex justify-content-center justify-content-md-end align-items-center">
                                <label for="selectorPagina" class="form-label text-warning me-2 mb-0"><small>Ir a página:</small></label>
                                <select class="form-select form-select-sm" id="selectorPagina" style="max-width: 100px;">
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == $pagina_actual ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="modalApertura" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">🔓 Apertura de Caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="montoInicial" class="form-label">Monto Inicial</label>
                        <input type="number" class="form-control" id="montoInicial" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionApertura" class="form-label">Descripción (Opcional)</label>
                        <textarea class="form-control" id="descripcionApertura" rows="2" placeholder="Descripción de la apertura..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="abrirCaja()">Abrir Caja</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCierre" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">🔒 Cierre de Caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label label-saldo-final">Saldo Final Calculado</label>
                        <input type="text" class="form-control campo-saldo-final" id="saldoFinalCalculado" 
                               value="<?= $estado_caja ? '$' . number_format($estado_caja['saldo_final_calculado'], 2) : '0.00' ?>" 
                               readonly>
                        <small class="text-muted">Este es el total que debería haber en caja según los movimientos registrados</small>
                    </div>
                    <div class="mb-3">
                        <label for="efectivoReal" class="form-label text-warning">💰 Efectivo Real en Caja</label>
                        <input type="number" class="form-control" id="efectivoReal" step="0.01" min="0" placeholder="0.00" required>
                        <small class="text-muted">Ingrese la cantidad real de dinero que encuentra en caja</small>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionCierre" class="form-label text-warning">📝 Descripción (Opcional)</label>
                        <textarea class="form-control" id="descripcionCierre" rows="2" placeholder="Descripción del cierre..."></textarea>
                    </div>
                    <div id="resultadoDiferencia" class="d-none">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarCaja()">Cerrar Caja</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGasto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">💸 Nuevo Gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="montoGasto" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoGasto" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionGasto" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcionGasto" rows="3" placeholder="Descripción del gasto..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="registrarGasto()">Registrar Gasto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function actualizarHora() {
            const ahora = new Date();
            document.getElementById('hora-actual').textContent = ahora.toLocaleTimeString('es-MX');
        }
        setInterval(actualizarHora, 1000);

        document.getElementById('selectorPagina')?.addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'cajai.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('fechaDesde=<?= urlencode($fecha_desde) ?>');
                parametros.push('fechaHasta=<?= urlencode($fecha_hasta) ?>');
                parametros.push('tipo=<?= urlencode($tipo_filtro) ?>');
            <?php endif; ?>
            
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
        });

        document.getElementById('efectivoReal')?.addEventListener('input', function() {
            const efectivoReal = parseFloat(this.value) || 0;
            const saldoFinal = <?= $estado_caja ? $estado_caja['saldo_final_calculado'] : 0 ?>;
            const diferencia = efectivoReal - saldoFinal;
            const resultadoDiv = document.getElementById('resultadoDiferencia');
            
            resultadoDiv.className = 'resultado-diferencia';
            resultadoDiv.classList.remove('d-none', 'excedente', 'faltante', 'perfecto');
            
            if (diferencia === 0) {
                resultadoDiv.classList.add('perfecto');
                resultadoDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">¡Perfecto!</h5>
                            <p class="mb-0">No hay diferencia. El efectivo real coincide con el saldo calculado.</p>
                        </div>
                    </div>
                `;
            } else if (diferencia > 0) {
                resultadoDiv.classList.add('excedente');
                resultadoDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Excedente Detectado</h5>
                            <p class="mb-0">Hay un excedente de: <strong>$${diferencia.toFixed(2)}</strong></p>
                            <small>El efectivo real es mayor al saldo calculado.</small>
                        </div>
                    </div>
                `;
            } else {
                resultadoDiv.classList.add('faltante');
                resultadoDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Faltante Detectado</h5>
                            <p class="mb-0">Hay un faltante de: <strong>$${Math.abs(diferencia).toFixed(2)}</strong></p>
                            <small>El efectivo real es menor al saldo calculado.</small>
                        </div>
                    </div>
                `;
            }
        });

        function mostrarAlerta(icono, titulo, mensaje, callback = null) {
            Swal.fire({
                icon: icono,
                title: titulo,
                text: mensaje,
                confirmButtonColor: '#28a745',
                background: '#2d2d2d',
                color: '#ffffff',
                iconColor: icono === 'success' ? '#28a745' : icono === 'error' ? '#dc3545' : '#ffc107'
            }).then((result) => {
                if (callback && typeof callback === 'function') {
                    callback(result);
                }
            });
        }

        function abrirCaja() {
            const monto = document.getElementById('montoInicial').value;
            const descripcion = document.getElementById('descripcionApertura').value;

            if (!monto || parseFloat(monto) <= 0) {
                mostrarAlerta('error', 'Error', 'Por favor ingrese un monto válido');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'abrir_caja');
            formData.append('monto', monto);
            formData.append('descripcion', descripcion);

            Swal.fire({
                title: 'Abriendo caja...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('cajaf.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    mostrarAlerta('success', '¡Éxito!', data.message, () => {
                        location.reload();
                    });
                } else {
                    mostrarAlerta('error', 'Error', data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                mostrarAlerta('error', 'Error', 'Error al abrir la caja');
            });
        }

        function cerrarCaja() {
            const efectivoReal = document.getElementById('efectivoReal').value;
            const descripcion = document.getElementById('descripcionCierre').value;

            if (!efectivoReal) {
                mostrarAlerta('error', 'Error', 'Por favor ingrese el efectivo real en caja');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'cerrar_caja');
            formData.append('efectivo_real', efectivoReal);
            formData.append('descripcion', descripcion);

            Swal.fire({
                title: 'Cerrando caja...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('cajaf.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    let mensaje = `
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-warning">¡Caja Cerrada Correctamente!</h4>
                            <hr style="border-color: #ffd700;">
                            <div class="text-start">
                                <p><strong>${data.message}</strong></p>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="p-2 bg-dark rounded">
                                            <small class="text-muted">Total Ventas</small><br>
                                            <strong class="text-info">$${parseFloat(data.total_ventas).toFixed(2)}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-dark rounded">
                                            <small class="text-muted">Saldo Final</small><br>
                                            <strong class="text-warning">$${parseFloat(data.saldo_final).toFixed(2)}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <div class="p-2 bg-dark rounded">
                                            <small class="text-muted">Efectivo Real</small><br>
                                            <strong class="text-light">$${parseFloat(data.efectivo_real).toFixed(2)}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-dark rounded ${parseFloat(data.diferencia) >= 0 ? 'border-success' : 'border-danger'} border">
                                            <small class="text-muted">Diferencia</small><br>
                                            <strong class="${parseFloat(data.diferencia) >= 0 ? 'text-success' : 'text-danger'}">
                                                $${parseFloat(data.diferencia).toFixed(2)}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    Swal.fire({
                        title: '',
                        html: mensaje,
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        background: '#2d2d2d',
                        color: '#ffffff',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCierre'));
                        if (modal) {
                            modal.hide();
                        }
                        document.getElementById('efectivoReal').value = '';
                        document.getElementById('descripcionCierre').value = '';
                        const resultadoDiv = document.getElementById('resultadoDiferencia');
                        resultadoDiv.className = 'd-none';
                        resultadoDiv.innerHTML = '';
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    });
                } else {
                    mostrarAlerta('error', 'Error', data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                mostrarAlerta('error', 'Error', 'Error al cerrar la caja');
            });
        }

        function registrarGasto() {
            const monto = document.getElementById('montoGasto').value;
            const descripcion = document.getElementById('descripcionGasto').value;

            if (!monto || parseFloat(monto) <= 0) {
                mostrarAlerta('error', 'Error', 'Por favor ingrese un monto válido');
                return;
            }

            if (!descripcion.trim()) {
                mostrarAlerta('error', 'Error', 'Por favor ingrese una descripción');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'registrar_gasto');
            formData.append('monto', monto);
            formData.append('descripcion', descripcion);

            Swal.fire({
                title: 'Registrando gasto...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('cajaf.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                console.log('Respuesta:', data);
                
                if (data.success) {
                    Swal.fire({
                        title: '¡Gasto Registrado!',
                        html: `${data.message}<br><br>
                               <strong>Monto:</strong> $${parseFloat(monto).toFixed(2)}<br>
                               <strong>Descripción:</strong> ${descripcion}`,
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalGasto'));
                        if (modal) {
                            modal.hide();
                        }
                        document.getElementById('montoGasto').value = '';
                        document.getElementById('descripcionGasto').value = '';
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    });
                } else {
                    mostrarAlerta('error', 'Error', data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                mostrarAlerta('error', 'Error', 'Error al registrar el gasto: ' + error.message);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['modalApertura', 'modalCierre', 'modalGasto'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('hidden.bs.modal', function() {
                        const inputs = this.querySelectorAll('input, textarea');
                        inputs.forEach(input => {
                            if (input.id !== 'saldoFinalCalculado') { 
                                input.value = '';
                            }
                        });
                        
                        const diferenciaDiv = document.getElementById('resultadoDiferencia');
                        if (diferenciaDiv) {
                            diferenciaDiv.className = 'd-none';
                            diferenciaDiv.innerHTML = '';
                        }
                    });
                }
            });

            const hoy = new Date().toISOString().split('T')[0];
            const fechaDesdeInput = document.querySelector('input[name="fechaDesde"]');
            const fechaHastaInput = document.querySelector('input[name="fechaHasta"]');
            
            if (fechaDesdeInput && !fechaDesdeInput.value) {
                fechaDesdeInput.value = hoy;
            }
            if (fechaHastaInput && !fechaHastaInput.value) {
                fechaHastaInput.value = hoy;
            }
        });
    </script>
</body>
</html>