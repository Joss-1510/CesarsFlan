<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'rutaf.php';
require_once 'conexion.php';

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_activa = false;
$vista_inactivos = (isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] == '1') ? true : false;
$termino_busqueda = '';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $rutaF = new RutaF($conn);
    
    $usuarios = $rutaF->obtenerUsuariosActivos();
    $clientes = $rutaF->obtenerClientesActivos();
    $dias = $rutaF->obtenerDias();
    
    $rutas = [];
    $total_registros = 0;
    
    if (isset($_POST['buscar_termino']) && !empty($_POST['buscar_termino'])) {
        $termino_busqueda = trim($_POST['buscar_termino']);
        if (strlen($termino_busqueda) > 100) {
            $termino_busqueda = substr($termino_busqueda, 0, 100);
        }
        $busqueda_activa = true;
        $resultados = $rutaF->buscarRutasConPaginacion($termino_busqueda, $offset, $registros_por_pagina, $vista_inactivos);
        $rutas = $resultados['rutas'];
        $total_registros = $resultados['total'];
    } else {
        $resultados = $rutaF->obtenerRutasCompletasConPaginacion($offset, $registros_por_pagina, $vista_inactivos);
        $rutas = $resultados['rutas'];
        $total_registros = $resultados['total'];
    }
    
    $total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Error de seguridad. Por favor, recarga la página.";
        } else if (isset($_POST['nueva_ruta'])) {
            $id_usuario = filter_var($_POST['nuevo_usuario'], FILTER_VALIDATE_INT);
            $id_dia = filter_var($_POST['nuevo_dia'], FILTER_VALIDATE_INT);
            $id_cliente = filter_var($_POST['nuevo_cliente'], FILTER_VALIDATE_INT);
            
            if (!$id_usuario || !$id_dia || !$id_cliente) {
                $error = "Error: Datos inválidos en el formulario";
            } else {
                $rutaData = [
                    'id_usuario' => $id_usuario,
                    'id_dia' => $id_dia,
                    'id_cliente' => $id_cliente,
                    'baja' => false
                ];
                
                if ($rutaF->rutaExiste($rutaData['id_usuario'], $rutaData['id_dia'], $rutaData['id_cliente'])) {
                    $error = "Error: Ya existe una ruta para este usuario, día y cliente";
                } else {
                    $resultado = $rutaF->create('truta', $rutaData);
                    if ($resultado) {
                        echo "<script>window.location.href = 'rutai.php" . ($vista_inactivos ? '?ver_inactivos=1' : '') . "';</script>";
                        exit();
                    } else {
                        $error = "Error al agregar la ruta";
                    }
                }
            }
        } else if (isset($_POST['id_editar'])) {
            $id_editar = filter_var($_POST['id_editar'], FILTER_VALIDATE_INT);
            $id_usuario = filter_var($_POST['usuario_editar'], FILTER_VALIDATE_INT);
            $id_dia = filter_var($_POST['dia_editar'], FILTER_VALIDATE_INT);
            $id_cliente = filter_var($_POST['cliente_editar'], FILTER_VALIDATE_INT);
            
            if (!$id_editar || !$id_usuario || !$id_dia || !$id_cliente) {
                $error = "Error: Datos inválidos en el formulario";
            } else {
                $rutaData = [
                    'id_usuario' => $id_usuario,
                    'id_dia' => $id_dia,
                    'id_cliente' => $id_cliente
                ];
                
                if ($rutaF->rutaExiste($rutaData['id_usuario'], $rutaData['id_dia'], $rutaData['id_cliente'], $id_editar)) {
                    $error = "Error: Ya existe una ruta para este usuario, día y cliente";
                } else {
                    $resultado = $rutaF->update('truta', $rutaData, ['id_ruta' => $id_editar]);
                    if ($resultado) {
                        echo "<script>window.location.href = 'rutai.php" . ($vista_inactivos ? '?ver_inactivos=1' : '') . "';</script>";
                        exit();
                    } else {
                        $error = "Error al editar la ruta";
                    }
                }
            }
        } else if (isset($_POST['id_eliminar'])) {
            $id_eliminar = filter_var($_POST['id_eliminar'], FILTER_VALIDATE_INT);
            if (!$id_eliminar) {
                $error = "Error: ID inválido para eliminar";
            } else {
                $rutaData = [
                    'baja' => true,
                    'fechabaja' => date('Y-m-d H:i:s')
                ];
                
                $resultado = $rutaF->update('truta', $rutaData, ['id_ruta' => $id_eliminar]);
                if ($resultado) {
                    echo "<script>window.location.href = 'rutai.php" . ($vista_inactivos ? '?ver_inactivos=1' : '') . "';</script>";
                    exit();
                } else {
                    $error = "Error al eliminar la ruta";
                }
            }
        } else if (isset($_POST['id_reactivar'])) {
            $id_reactivar = filter_var($_POST['id_reactivar'], FILTER_VALIDATE_INT);
            if (!$id_reactivar) {
                $error = "Error: ID inválido para reactivar";
            } else {
                $rutaData = [
                    'baja' => false,
                    'fechabaja' => null
                ];
                
                $resultado = $rutaF->update('truta', $rutaData, ['id_ruta' => $id_reactivar]);
                if ($resultado) {
                    echo "<script>window.location.href = 'rutai.php" . ($vista_inactivos ? '?ver_inactivos=1' : '') . "';</script>";
                    exit();
                } else {
                    $error = "Error al reactivar la ruta";
                }
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$tituloPagina = "Gestión de Rutas";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
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
        .routes-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .routes-section h3 {
            text-align: center;
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .btn-agregar {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-agregar:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .btn-organizar {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-organizar:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: #ffffff;
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
        .btn-ver-todos {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-ver-todos:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
        .btn-ver-inactivos {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-ver-inactivos:hover {
            background-color: #c82333;
            border-color: #c82333;
            color: #ffffff;
        }
        .btn-ver-activos {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-ver-activos:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        .btn-editar {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-editar:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .btn-eliminar {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-eliminar:hover {
            background-color: #c82333;
            border-color: #c82333;
            color: #ffffff;
        }
        .btn-reactivar {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-reactivar:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
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
        .required-field::after {
            content: " *";
            color: #ff4444;
        }
        .error-message {
            color: #ff4444;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .ruta-inactiva {
            background-color: #fff5f5 !important;
        }
        .ruta-inactiva:hover td {
            background-color: #ffe6e6 !important;
        }
        .estado-activo {
            color: #28a745;
            font-weight: bold;
        }
        .estado-inactivo {
            color: #dc3545;
            font-weight: bold;
        }
        .alert {
            margin: 10px 0;
            border-radius: 8px;
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
        .vista-info {
            background-color: #2d2d2d;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ffd700;
            color: #ffffff;
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
        @media (max-width: 768px) {
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
            .search-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .btn-group {
                width: 100%;
            }
            .btn-group .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="routes-section">
            <h3>🗺️ Gestión de Rutas</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarRutaModal">
                        ➕ Agregar Ruta
                    </button>
                    <a href="organizador_rutai.php" class="btn btn-organizar">
                        🗓️ Organizar Rutas
                    </a>
                </div>
                
                <?php if ($vista_inactivos): ?>
                    <a href="rutai.php" class="btn btn-ver-activos">
                        👁️ Ver Rutas Activas
                    </a>
                <?php else: ?>
                    <a href="rutai.php?ver_inactivos=1" class="btn btn-ver-inactivos">
                        👁️ Ver Rutas Inactivas
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Rutas</h5>
                    <div class="d-flex gap-2">
                        <?php if ($busqueda_activa): ?>
                            <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                        <?php endif; ?>
                        <?php if ($vista_inactivos): ?>
                            <span class="badge bg-danger">Vista: Rutas Inactivas</span>
                        <?php else: ?>
                            <span class="badge bg-success">Vista: Rutas Activas</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($vista_inactivos): ?>
                    <div class="vista-info">
                        <strong>⚠️ Vista de rutas inactivas</strong>
                        <p class="mb-0"><small>Estás viendo las rutas que han sido dadas de baja.</small></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> ruta(s) encontrada(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="rutai.php?<?= $vista_inactivos ? 'ver_inactivos=1' : '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <?php if ($vista_inactivos): ?>
                        <input type="hidden" name="ver_inactivos" value="1">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar_termino" 
                               placeholder="Buscar por usuario, cliente o día..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button class="btn btn-buscar" type="submit">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="rutai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" class="btn btn-ver-todos">
                            🔄 Ver Todas las Rutas
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Cliente</th>
                            <th>Día</th>
                            <th>Estado</th>
                            <th>Fecha de Baja</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rutas)): ?>
                            <?php foreach ($rutas as $ruta): ?>
                                <?php 
                                $estaInactivo = ($ruta['baja'] === 't' || $ruta['baja'] === true || $ruta['baja'] === 'true' || $ruta['baja'] === 1);
                                ?>
                                <tr class="<?= $estaInactivo ? 'ruta-inactiva' : '' ?>">
                                    <td><?= htmlspecialchars($ruta["nombre_usuario"] ?? 'No asignado') ?></td>
                                    <td><?= htmlspecialchars($ruta["nombre_cliente"] ?? 'No asignado') ?></td>
                                    <td><?= htmlspecialchars($ruta["nombre_dia"] ?? 'No asignado') ?></td>
                                    <td>
                                        <span class="<?= $estaInactivo ? 'estado-inactivo' : 'estado-activo' ?>">
                                            <?php if ($estaInactivo): ?>
                                                ⛔ Inactivo
                                            <?php else: ?>
                                                ✅ Activo
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ruta['fechabaja']): ?>
                                            <span class="text-muted"><?= htmlspecialchars($ruta['fechabaja']) ?></span>
                                        <?php else: ?>
                                            <span class="text-success">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($estaInactivo): ?>
                                            <form method="post" action="rutai.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="id_reactivar" value="<?= $ruta['id_ruta'] ?>">
                                                <?php if ($vista_inactivos): ?>
                                                    <input type="hidden" name="ver_inactivos" value="1">
                                                <?php endif; ?>
                                                <button type="submit" class="btn btn-reactivar">🔄 Reactivar</button>
                                            </form>
                                        <?php else: ?>
                                            <button class='btn btn-editar me-2' data-bs-toggle='modal' data-bs-target='#editarRutaModal' 
                                              data-id='<?= $ruta["id_ruta"] ?? '' ?>' 
                                              data-usuario='<?= $ruta["id_usuario"] ?? '' ?>'
                                              data-cliente='<?= $ruta["id_cliente"] ?? '' ?>'
                                              data-dia='<?= $ruta["id_dia"] ?? '' ?>'>✏️ Editar</button>
                                            <button class='btn btn-eliminar' data-bs-toggle='modal' data-bs-target='#eliminarRutaModal' 
                                              data-id='<?= $ruta["id_ruta"] ?? '' ?>'
                                              data-info='Usuario: <?= htmlspecialchars($ruta["nombre_usuario"] ?? '') ?> - Cliente: <?= htmlspecialchars($ruta["nombre_cliente"] ?? '') ?> - Día: <?= htmlspecialchars($ruta["nombre_dia"] ?? '') ?>'>🗑️ Eliminar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='6' class='text-center py-4'>
                                    <div class="text-muted">
                                        <h5>No se encontraron rutas</h5>
                                        <?php if ($busqueda_activa): ?>
                                            <p>Intenta con otros términos de búsqueda</p>
                                            <a href="rutai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" class="btn btn-ver-todos mt-2">
                                                🔄 Ver Todas las Rutas
                                            </a>
                                        <?php else: ?>
                                            <p>No hay rutas registradas en el sistema</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación de rutas">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=1<?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>" aria-label="Primera Página">
                                <span aria-hidden="true">««</span>
                            </a>
                        </li>

                        <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>" aria-label="Anterior">
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
                                <a class="page-link" href="?pagina=<?= $i ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>">
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
                            <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>" aria-label="Siguiente">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>

                        <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>" aria-label="Última Página">
                                <span aria-hidden="true">»»</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="row align-items-center mt-3">
                    <div class="col-md-6">
                        <div class="pagination-info text-center text-md-start">
                            <small>
                                Mostrando <strong><?= count($rutas) ?></strong> de <strong><?= $total_registros ?></strong> rutas - 
                                Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                <?php if ($busqueda_activa): ?>
                                    <span class="badge bg-warning text-dark ms-2">Búsqueda: "<?= htmlspecialchars($termino_busqueda) ?>"</span>
                                <?php endif; ?>
                                <?php if ($vista_inactivos): ?>
                                    <span class="badge bg-danger ms-2">Rutas Inactivas</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2">Rutas Activas</span>
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

    <div class="modal fade" id="agregarRutaModal" tabindex="-1" aria-labelledby="agregarRutaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarRutaModalLabel">➕ Agregar Nueva Ruta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="rutai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" id="formAgregarRuta">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="nuevo_usuario" class="form-label required-field">Usuario (Empleado)</label>
                            <select class="form-select" id="nuevo_usuario" name="nuevo_usuario" required>
                                <option value="">Seleccionar usuario</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= $usuario['id_usuario'] ?>"><?= htmlspecialchars($usuario['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_cliente" class="form-label required-field">Cliente</label>
                            <select class="form-select" id="nuevo_cliente" name="nuevo_cliente" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_dia" class="form-label required-field">Día</label>
                            <select class="form-select" id="nuevo_dia" name="nuevo_dia" required>
                                <option value="">Seleccionar día</option>
                                <?php foreach ($dias as $dia): ?>
                                    <option value="<?= $dia['id_dia'] ?>"><?= htmlspecialchars($dia['dia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="nueva_ruta" class="btn btn-custom-modal">Agregar Ruta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eliminarRutaModal" tabindex="-1" aria-labelledby="eliminarRutaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarRutaModalLabel">🗑️ Eliminar Ruta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la ruta:</p>
                    <p><strong id="ruta_eliminar" class="text-warning"></strong></p>
                    <p class="text-muted"><small>Esta acción marcará la ruta como inactiva.</small></p>
                    <form method="post" action="rutai.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger flex-fill">Eliminar</button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarRutaModal" tabindex="-1" aria-labelledby="editarRutaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarRutaModalLabel">✏️ Editar Ruta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="rutai.php" id="formEditarRuta">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="usuario_editar" class="form-label required-field">Usuario (Empleado)</label>
                            <select class="form-select" id="usuario_editar" name="usuario_editar" required>
                                <option value="">Seleccionar usuario</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= $usuario['id_usuario'] ?>"><?= htmlspecialchars($usuario['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cliente_editar" class="form-label required-field">Cliente</label>
                            <select class="form-select" id="cliente_editar" name="cliente_editar" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="dia_editar" class="form-label required-field">Día</label>
                            <select class="form-select" id="dia_editar" name="dia_editar" required>
                                <option value="">Seleccionar día</option>
                                <?php foreach ($dias as $dia): ?>
                                    <option value="<?= $dia['id_dia'] ?>"><?= htmlspecialchars($dia['dia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $('#eliminarRutaModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var info = button.data('info');
            $(this).find('#id_eliminar').val(id);
            $(this).find('#ruta_eliminar').text(info);
        });

        $('#editarRutaModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            
            modal.find('#id_editar').val(button.data('id'));
            modal.find('#usuario_editar').val(button.data('usuario'));
            modal.find('#cliente_editar').val(button.data('cliente'));
            modal.find('#dia_editar').val(button.data('dia'));
        });

        $(document).ready(function() {
            $('input[name="buscar_termino"]').focus();
        });

        document.getElementById('selectorPagina').addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'rutai.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('buscar=<?= urlencode($termino_busqueda) ?>');
            <?php endif; ?>
            
            <?php if ($vista_inactivos): ?>
                parametros.push('ver_inactivos=1');
            <?php endif; ?>
            
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
        });

        $('#formAgregarRuta').on('submit', function(e) {
            const usuario = $('#nuevo_usuario').val();
            const cliente = $('#nuevo_cliente').val();
            const dia = $('#nuevo_dia').val();
            
            if (!usuario || !cliente || !dia) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios.');
                return false;
            }
            return true;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="buscar_termino"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>