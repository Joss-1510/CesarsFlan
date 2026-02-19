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
$id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? 1;

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$fecha_desde = $_GET['fechaDesde'] ?? '';
$fecha_hasta = $_GET['fechaHasta'] ?? '';
$accion_filtro = $_GET['accion'] ?? '';
$busqueda_activa = !empty($fecha_desde) || !empty($fecha_hasta) || !empty($accion_filtro);

$sql = "SELECT l.*, u.nombre as nombre_usuario
        FROM tlog l 
        LEFT JOIN tusuario u ON l.id_usuario = u.id_usuario 
        WHERE 1=1";
$sql_count = "SELECT COUNT(*) as total 
              FROM tlog l 
              LEFT JOIN tusuario u ON l.id_usuario = u.id_usuario 
              WHERE 1=1";

$params = [];
$params_count = [];

if (!empty($fecha_desde)) {
    $sql .= " AND l.fecha >= $" . (count($params) + 1);
    $sql_count .= " AND l.fecha >= $" . (count($params_count) + 1);
    $params[] = $fecha_desde . ' 00:00:00';
    $params_count[] = $fecha_desde . ' 00:00:00';
}

if (!empty($fecha_hasta)) {
    $sql .= " AND l.fecha <= $" . (count($params) + 1);
    $sql_count .= " AND l.fecha <= $" . (count($params_count) + 1);
    $params[] = $fecha_hasta . ' 23:59:59';
    $params_count[] = $fecha_hasta . ' 23:59:59';
}

if (!empty($accion_filtro)) {
    $sql .= " AND l.accion = $" . (count($params) + 1);
    $sql_count .= " AND l.accion = $" . (count($params_count) + 1);
    $params[] = $accion_filtro;
    $params_count[] = $accion_filtro;
}

$sql .= " ORDER BY l.fecha DESC LIMIT $" . (count($params) + 1) . " OFFSET $" . (count($params) + 2);
$params[] = $registros_por_pagina;
$params[] = $offset;

try {
    if (!empty($params_count)) {
        $result_count = pg_query_params($conn, $sql_count, $params_count);
    } else {
        $result_count = pg_query($conn, $sql_count);
    }
    
    if ($result_count) {
        $row_count = pg_fetch_assoc($result_count);
        $total_registros = $row_count['total'];
    } else {
        $total_registros = 0;
    }
    
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    if (!empty($params)) {
        $result = pg_query_params($conn, $sql, $params);
    } else {
        $result = pg_query($conn, $sql);
    }
    
    $registros = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $registros[] = $row;
        }
    }
    
} catch (Exception $e) {
    $total_registros = 0;
    $total_paginas = 0;
    $registros = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros del Sistema - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .logs-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 25px;
            border-radius: 15px;
        }
        .logs-section h3 {
            text-align: center;
            font-size: 2.2rem;
            color: #ffd700;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .btn-success {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-success:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
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
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
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
        .badge-accion {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .badge-insert {
            background-color: #28a745;
            color: white;
        }
        .badge-update {
            background-color: #ffc107;
            color: black;
        }
        .badge-delete {
            background-color: #dc3545;
            color: white;
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
            max-width: 400px;
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
            font-size: 2rem;
            font-weight: bold;
            color: #ffd700;
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
        .pagination {
            justify-content: center;
            margin-top: 0;
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
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="logs-section">
            <h3>📊 Registros del Sistema</h3>
            
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

            <div class="stats-container">
                <?php
                $sql_stats = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN accion = 'INSERT' THEN 1 END) as inserts,
                    COUNT(CASE WHEN accion = 'UPDATE' THEN 1 END) as updates,
                    COUNT(CASE WHEN accion = 'DELETE' THEN 1 END) as deletes
                    FROM tlog";
                
                $result_stats = pg_query($conn, $sql_stats);
                $stats = pg_fetch_assoc($result_stats);
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total de Registros</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['inserts'] ?? 0; ?></div>
                    <div class="stat-label">Inserciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['updates'] ?? 0; ?></div>
                    <div class="stat-label">Actualizaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['deletes'] ?? 0; ?></div>
                    <div class="stat-label">Eliminaciones</div>
                </div>
            </div>

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
                        if (!empty($accion_filtro)) $filtros[] = "Acción: " . $accion_filtro;
                        echo implode(" | ", $filtros);
                        ?>
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> registro(s) encontrado(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="get" action="logi.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-warning"><strong>📅 Fecha Desde</strong></label>
                            <input type="date" class="form-control" name="fechaDesde" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning"><strong>📅 Fecha Hasta</strong></label>
                            <input type="date" class="form-control" name="fechaHasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning"><strong>⚡ Acción</strong></label>
                            <select class="form-select" name="accion">
                                <option value="">Todas las acciones</option>
                                <option value="INSERT" <?= $accion_filtro == 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                                <option value="UPDATE" <?= $accion_filtro == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                                <option value="DELETE" <?= $accion_filtro == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning flex-fill" type="submit">
                                    <i class="fas fa-search"></i> Buscar Registros
                                </button>
                                <?php if ($busqueda_activa): ?>
                                    <a href="logi.php" class="btn btn-secondary">🔄 Limpiar Filtros</a>
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
                                <th>Acción</th>
                                <th>Usuario</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($registros)): ?>
                                <?php foreach ($registros as $log): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php
                                            $fecha = new DateTime($log['fecha']);
                                            echo $fecha->format('d/m/Y H:i:s');
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $badge_class = '';
                                            $badge_icon = '';
                                            switch($log['accion']) {
                                                case 'INSERT':
                                                    $badge_class = 'badge-insert';
                                                    $badge_icon = '➕';
                                                    break;
                                                case 'UPDATE':
                                                    $badge_class = 'badge-update';
                                                    $badge_icon = '✏️';
                                                    break;
                                                case 'DELETE':
                                                    $badge_class = 'badge-delete';
                                                    $badge_icon = '❌';
                                                    break;
                                                default:
                                                    $badge_class = 'badge-secondary';
                                                    $badge_icon = '📝';
                                            }
                                            ?>
                                            <span class="badge-accion <?= $badge_class ?>">
                                                <?= $badge_icon ?> <?= htmlspecialchars($log['accion']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $usuarioMostrar = 'Sistema';
                                            if (!empty($log['nombre_usuario'])) {
                                                $usuarioMostrar = htmlspecialchars($log['nombre_usuario']);
                                            } elseif (!empty($log['id_usuario'])) {
                                                $usuarioMostrar = 'Usuario ' . htmlspecialchars($log['id_usuario']);
                                            }
                                            echo $usuarioMostrar;
                                            ?>
                                        </td>
                                        <td class="descripcion-cell" onclick="mostrarDetallesCompletos('<?= addslashes(htmlspecialchars($log['descripcion'])) ?>')">
                                            <?= htmlspecialchars($log['descripcion']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-records">
                                        <i class="fas fa-info-circle fa-2x mb-3" style="color: #6c757d;"></i>
                                        <p style="color: #6c757d;">No se encontraron registros</p>
                                        <small style="color: #6c757d;"><?= $busqueda_activa ? 'Prueba con otros filtros de búsqueda' : 'No hay registros en el sistema' ?></small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Paginación de registros">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=1<?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&accion=' . urlencode($accion_filtro) : '' ?>" aria-label="Primera Página">
                                    <span aria-hidden="true">««</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&accion=' . urlencode($accion_filtro) : '' ?>" aria-label="Anterior">
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
                                    <a class="page-link" href="?pagina=<?= $i ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&accion=' . urlencode($accion_filtro) : '' ?>">
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
                                <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&accion=' . urlencode($accion_filtro) : '' ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $busqueda_activa ? '&fechaDesde=' . urlencode($fecha_desde) . '&fechaHasta=' . urlencode($fecha_hasta) . '&accion=' . urlencode($accion_filtro) : '' ?>" aria-label="Última Página">
                                    <span aria-hidden="true">»»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-6">
                            <div class="pagination-info text-center text-md-start">
                                <small>
                                    Mostrando <strong><?= count($registros) ?></strong> de <strong><?= $total_registros ?></strong> registros - 
                                    Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                    <?php if ($busqueda_activa): ?>
                                        <span class="badge bg-warning text-dark ms-2">Búsqueda Activa</span>
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">Detalles Completos del Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="detallesCompletos" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: 'Roboto', sans-serif; color: #333333;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function actualizarHora() {
            const ahora = new Date();
            document.getElementById('hora-actual').textContent = ahora.toLocaleTimeString('es-MX');
        }
        setInterval(actualizarHora, 1000);

        function mostrarDetallesCompletos(descripcion) {
            document.getElementById('detallesCompletos').textContent = descripcion;
            const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
            modal.show();
        }

        document.getElementById('selectorPagina')?.addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'logi.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('fechaDesde=<?= urlencode($fecha_desde) ?>');
                parametros.push('fechaHasta=<?= urlencode($fecha_hasta) ?>');
                parametros.push('accion=<?= urlencode($accion_filtro) ?>');
            <?php endif; ?>
            
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
        });
    </script>
</body>
</html>