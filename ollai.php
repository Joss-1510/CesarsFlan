<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'ollaf.php';
require_once 'conexion.php';

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_activa = false;
$vista_inactivos = (isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] == '1') ? true : false;
$termino_busqueda = '';

$parametro_vista = $vista_inactivos ? '&ver_inactivos=1' : '';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $ollaF = new OllaF($conn);
    
    $ollas = [];
    $total_registros = 0;
    
    if (isset($_GET['estado'])) {
        switch ($_GET['estado']) {
            case 'inactivos':
                $vista_inactivos = true;
                break;
            case 'activos':
                $vista_inactivos = false;
                break;
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ver_inactivos'])) {
        $vista_inactivos = true;
        $parametro_vista = '&ver_inactivos=1';
    }
    
    if (isset($_POST['buscar_numero']) && !empty($_POST['buscar_numero'])) {
        $termino_busqueda = $_POST['buscar_numero'];
        $busqueda_activa = true;
        $resultados = $ollaF->buscarOllasConPaginacion($termino_busqueda, $offset, $registros_por_pagina, $vista_inactivos);
        $ollas = $resultados['ollas'];
        $total_registros = $resultados['total'];
    } else {
        $resultados = $ollaF->obtenerOllasCompletasConPaginacion($offset, $registros_por_pagina, $vista_inactivos);
        $ollas = $resultados['ollas'];
        $total_registros = $resultados['total'];
    }
    
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['nuevo_numero_olla'])) {
            if ($ollaF->ollaExiste($_POST['nuevo_numero_olla'])) {
                $error = "❌ Ya existe una olla con el número " . $_POST['nuevo_numero_olla'];
            } else {
                $ollaData = [
                    'numero_olla' => $_POST['nuevo_numero_olla'],
                    'capacidad' => $_POST['nuevo_capacidad'] ?? 0,
                    'estado' => 'Disponible',
                    'baja' => 'false'
                ];
                
                $resultado = $ollaF->create('tolla', $ollaData);
                if ($resultado) {
                    header("Location: ollai.php" . ($vista_inactivos ? '?ver_inactivos=1' : ''));
                    exit();
                } else {
                    $error = "Error al agregar la olla";
                }
            }
        }
        
        if (isset($_POST['id_editar'])) {
            if ($ollaF->ollaExiste($_POST['numero_editar'], $_POST['id_editar'])) {
                $error = "❌ Ya existe otra olla con el número " . $_POST['numero_editar'];
            } else {
                $ollaData = [
                    'numero_olla' => $_POST['numero_editar'],
                    'capacidad' => $_POST['capacidad_editar'] ?? 0,
                    'estado' => 'Disponible'
                ];
                
                $resultado = $ollaF->update('tolla', $ollaData, ['id_olla' => $_POST['id_editar']]);
                if ($resultado) {
                    header("Location: ollai.php" . ($vista_inactivos ? '?ver_inactivos=1' : ''));
                    exit();
                } else {
                    $error = "Error al editar la olla";
                }
            }
        }
        
        if (isset($_POST['id_eliminar'])) {
            if ($ollaF->ollaTieneTemporizadoresActivos($_POST['id_eliminar'])) {
                $error = "❌ No se puede eliminar la olla porque tiene temporizadores activos asignados";
            } else {
                $ollaData = [
                    'baja' => 'true',
                    'fechabaja' => date('Y-m-d H:i:s')
                ];
                
                $resultado = $ollaF->update('tolla', $ollaData, ['id_olla' => $_POST['id_eliminar']]);
                if ($resultado) {
                    header("Location: ollai.php" . ($vista_inactivos ? '?ver_inactivos=1' : ''));
                    exit();
                } else {
                    $error = "Error al eliminar la olla";
                }
            }
        }

        if (isset($_POST['id_reactivar'])) {
            $ollaData = [
                'baja' => 'false',
                'fechabaja' => null
            ];
            
            $resultado = $ollaF->update('tolla', $ollaData, ['id_olla' => $_POST['id_reactivar']]);
            if ($resultado) {
                header("Location: ollai.php" . ($vista_inactivos ? '?ver_inactivos=1' : ''));
                exit();
            } else {
                $error = "Error al reactivar la olla";
            }
        }
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$tituloPagina = "Gestión de Ollas";
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
        .ollas-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .ollas-section h3 {
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
        .btn-regresar {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-regresar:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
        .olla-inactiva {
            background-color: #fff5f5 !important;
        }
        .olla-inactiva:hover td {
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
        .contact-info {
            font-size: 0.9em;
            color: #666;
        }
        .badge-capacidad {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
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
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="ollas-section">
            <h3>🍲 Gestión de Ollas</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar me-2" data-bs-toggle="modal" data-bs-target="#agregarOllaModal">
                        ➕ Agregar Olla
                    </button>
                    
                    <a href="temporizadori.php" class="btn btn-regresar">
                        ← Regresar a Temporizadores
                    </a>
                </div>
                
                <?php if ($vista_inactivos): ?>
                    <a href="ollai.php" class="btn btn-ver-activos">
                        👁️ Ver Ollas Activas
                    </a>
                <?php else: ?>
                    <a href="ollai.php?ver_inactivos=1" class="btn btn-ver-inactivos">
                        👁️ Ver Ollas Inactivas
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Ollas</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                    <?php if ($vista_inactivos): ?>
                        <span class="badge bg-danger">Vista: Ollas Inactivas</span>
                    <?php else: ?>
                        <span class="badge bg-success">Vista: Ollas Activas</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($vista_inactivos): ?>
                    <div class="vista-info">
                        <strong>⚠️ Vista de ollas inactivas</strong>
                        <p class="mb-0"><small>Estás viendo las ollas que han sido dadas de baja.</small></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> olla(s) encontrada(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="ollai.php?<?= $vista_inactivos ? 'ver_inactivos=1' : '' ?>">
                    <?php if ($vista_inactivos): ?>
                        <input type="hidden" name="ver_inactivos" value="1">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar_numero" 
                               placeholder="Buscar por número de olla..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button class="btn btn-buscar" type="submit">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="ollai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" class="btn btn-ver-todos">
                            🔄 Ver Todas las Ollas
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número de Olla</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th>Fecha de Baja</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ollas)): ?>
                            <?php foreach ($ollas as $olla): ?>
                                <?php 
                                $estaInactiva = ($olla['baja'] === 't' || $olla['baja'] === true || $olla['baja'] === 'true' || $olla['baja'] === 1);
                                ?>
                                <tr class="<?= $estaInactiva ? 'olla-inactiva' : '' ?>">
                                    <td class="fw-bold">🍲 Olla <?= htmlspecialchars($olla["numero_olla"] ?? '') ?></td>
                                    <td>
                                        <span class="badge-capacidad">
                                            📦 <?= htmlspecialchars($olla["capacidad"] ?? 0) ?> flanes
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?= $estaInactiva ? 'estado-inactivo' : 'estado-activo' ?>">
                                            <?php if ($estaInactiva): ?>
                                                ⛔ Inactiva
                                            <?php else: ?>
                                                ✅ Activa
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($olla['fechabaja']): ?>
                                            <span class="text-muted"><?= htmlspecialchars($olla['fechabaja']) ?></span>
                                        <?php else: ?>
                                            <span class="text-success">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($estaInactiva): ?>
                                            <form method="post" action="ollai.php" style="display: inline;">
                                                <input type="hidden" name="id_reactivar" value="<?= $olla['id_olla'] ?>">
                                                <?php if ($vista_inactivos): ?>
                                                    <input type="hidden" name="ver_inactivos" value="1">
                                                <?php endif; ?>
                                                <button type="submit" class="btn btn-reactivar">🔄 Reactivar</button>
                                            </form>
                                        <?php else: ?>
                                            <button class='btn btn-editar me-2' data-bs-toggle='modal' data-bs-target='#editarOllaModal' 
                                              data-id='<?= $olla["id_olla"] ?? '' ?>' 
                                              data-numero='<?= htmlspecialchars($olla["numero_olla"] ?? '') ?>'
                                              data-capacidad='<?= htmlspecialchars($olla["capacidad"] ?? 0) ?>'>✏️ Editar</button>
                                            <button class='btn btn-eliminar' data-bs-toggle='modal' data-bs-target='#eliminarOllaModal' 
                                              data-id='<?= $olla["id_olla"] ?? '' ?>'
                                              data-numero='<?= htmlspecialchars($olla["numero_olla"] ?? '') ?>'>🗑️ Eliminar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='5' class='text-center py-4'>
                                    <div class="text-muted">
                                        <h5>No se encontraron ollas</h5>
                                        <?php if ($busqueda_activa): ?>
                                            <p>Intenta con otros términos de búsqueda</p>
                                            <a href="ollai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" class="btn btn-ver-todos mt-2">
                                                🔄 Ver Todas las Ollas
                                            </a>
                                        <?php else: ?>
                                            <?php if ($vista_inactivos): ?>
                                                <p>No hay ollas inactivas en el sistema</p>
                                                <a href="ollai.php" class="btn btn-ver-activos mt-2">
                                                    👁️ Ver Ollas Activas
                                                </a>
                                            <?php else: ?>
                                                <p>No hay ollas activas en el sistema</p>
                                                <button class="btn btn-agregar mt-2" data-bs-toggle="modal" data-bs-target="#agregarOllaModal">
                                                    ➕ Agregar Primera Olla
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación de ollas">
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
                                Mostrando <strong><?= count($ollas) ?></strong> de <strong><?= $total_registros ?></strong> ollas - 
                                Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                <?php if ($busqueda_activa): ?>
                                    <span class="badge bg-warning text-dark ms-2">Búsqueda: "<?= htmlspecialchars($termino_busqueda) ?>"</span>
                                <?php endif; ?>
                                <?php if ($vista_inactivos): ?>
                                    <span class="badge bg-danger ms-2">Ollas Inactivas</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2">Ollas Activas</span>
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

    <div class="modal fade" id="agregarOllaModal" tabindex="-1" aria-labelledby="agregarOllaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarOllaModalLabel">➕ Agregar Nueva Olla</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ollai.php<?= $vista_inactivos ? '?ver_inactivos=1' : '' ?>" id="formAgregarOlla">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="nuevo_numero_olla" class="form-label required-field">Número de Olla</label>
                            <input type="number" class="form-control" id="nuevo_numero_olla" name="nuevo_numero_olla" min="1" required>
                            <div id="error-numero" class="error-message"></div>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_capacidad" class="form-label">Capacidad (flanes)</label>
                            <input type="number" class="form-control" id="nuevo_capacidad" name="nuevo_capacidad" value="0" min="0">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal">Agregar Olla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eliminarOllaModal" tabindex="-1" aria-labelledby="eliminarOllaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarOllaModalLabel">🗑️ Eliminar Olla</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la <strong id="olla_eliminar" class="text-warning"></strong>?</p>
                    <p class="text-muted"><small>Esta acción marcará la olla como inactiva.</small></p>
                    <form method="post" action="ollai.php">
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

    <div class="modal fade" id="editarOllaModal" tabindex="-1" aria-labelledby="editarOllaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarOllaModalLabel">✏️ Editar Olla</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ollai.php" id="formEditarOlla">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="numero_editar" class="form-label required-field">Número de Olla</label>
                            <input type="number" class="form-control" id="numero_editar" name="numero_editar" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacidad_editar" class="form-label">Capacidad (flanes)</label>
                            <input type="number" class="form-control" id="capacidad_editar" name="capacidad_editar" min="0">
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
        $('#eliminarOllaModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var olla = button.data('numero');
            $(this).find('#id_eliminar').val(id);
            $(this).find('#olla_eliminar').text('Olla ' + olla);
        });

        $('#editarOllaModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            
            modal.find('#id_editar').val(button.data('id'));
            modal.find('#numero_editar').val(button.data('numero'));
            modal.find('#capacidad_editar').val(button.data('capacidad'));
        });

        $('#formAgregarOlla input').on('blur', function() {
            validateField($(this));
        });

        function validateField(field) {
            const id = field.attr('id');
            const value = field.val();
            let isValid = true;
            let errorMessage = '';
            
            switch(id) {
                case 'nuevo_numero_olla':
                    if (value.trim() === '') {
                        errorMessage = 'Este campo es obligatorio';
                        isValid = false;
                    } else if (value < 1) {
                        errorMessage = 'El número de olla debe ser mayor a 0';
                        isValid = false;
                    }
                    break;
                case 'nuevo_capacidad':
                    if (value < 0) {
                        errorMessage = 'La capacidad no puede ser negativa';
                        isValid = false;
                    }
                    break;
            }
            
            $(`#error-${id.replace('nuevo_', '')}`).text(errorMessage);
            return isValid;
        }

        $('#formAgregarOlla').on('submit', function(e) {
            let isValid = true;
            
            $('#formAgregarOlla input[required]').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor corrija los errores en el formulario antes de enviar.');
            }
        });

        $(document).ready(function() {
            $('input[name="buscar_numero"]').focus();
        });

        document.getElementById('selectorPagina').addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'ollai.php';
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

        document.addEventListener('keydown', function(e) {
            <?php if ($total_paginas > 1): ?>
                if (e.key === 'ArrowLeft' && <?= $pagina_actual > 1 ? 'true' : 'false' ?>) {
                    window.location.href = '?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>';
                } else if (e.key === 'ArrowRight' && <?= $pagina_actual < $total_paginas ? 'true' : 'false' ?>) {
                    window.location.href = '?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?><?= $vista_inactivos ? '&ver_inactivos=1' : '' ?>';
                }
            <?php endif; ?>
        });

        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>