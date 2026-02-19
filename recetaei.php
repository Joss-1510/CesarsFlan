<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'recetaef.php';
require_once 'conexion.php';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_activa = false;
$termino_busqueda = '';

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $termino_busqueda = trim($_GET['buscar']);
    $busqueda_activa = true;
}

try {
    $recetaEF = new RecetaEF($conn);
    
    $productos_finales = $recetaEF->obtenerProductosFinales();
    $semielaborados = $recetaEF->obtenerSemielaboradosDisponibles();
    $materiales = $recetaEF->obtenerMateriales();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['agregar_receta_empaquetado'])) {
            if ($recetaEF->verificarRecetaExistente(
                $_POST['id_producto_final'],
                $_POST['id_semielaborado'], 
                $_POST['id_material']
            )) {
                $error = "Ya existe una receta para esta combinación de producto, semielaborado y material";
            } else {
                $resultado = $recetaEF->agregarRecetaEmpaquetado([
                    'id_producto_final' => $_POST['id_producto_final'],
                    'id_semielaborado' => $_POST['id_semielaborado'],
                    'id_material' => $_POST['id_material'],
                    'cantidad_semielaborados' => $_POST['cantidad_semielaborados'],
                    'cantidad_material' => $_POST['cantidad_material'],
                    'cantidad_productos' => $_POST['cantidad_productos']
                ]);
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Receta de empaquetado agregada exitosamente";
                    $url = "recetaei.php";
                    $params = [];
                    if ($busqueda_activa) {
                        $params[] = 'buscar=' . urlencode($termino_busqueda);
                    }
                    if ($pagina_actual > 1) $params[] = 'pagina=' . $pagina_actual;
                    
                    if (!empty($params)) {
                        $url .= '?' . implode('&', $params);
                    }
                    header("Location: $url");
                    exit();
                } else {
                    $error = "Error al agregar receta";
                }
            }
        }
        
        if (isset($_POST['editar_receta_empaquetado'])) {
            $resultado = $recetaEF->editarRecetaEmpaquetado($_POST['id_receta_editar'], [
                'id_producto_final' => $_POST['id_producto_final_editar'],
                'id_semielaborado' => $_POST['id_semielaborado_editar'],
                'id_material' => $_POST['id_material_editar'],
                'cantidad_semielaborados' => $_POST['cantidad_semielaborados_editar'],
                'cantidad_material' => $_POST['cantidad_material_editar'],
                'cantidad_productos' => $_POST['cantidad_productos_editar']
            ]);
            
            if ($resultado) {
                $_SESSION['mensaje_exito'] = "Receta actualizada exitosamente";
                $url = "recetaei.php";
                $params = [];
                if ($busqueda_activa) {
                    $params[] = 'buscar=' . urlencode($termino_busqueda);
                }
                if ($pagina_actual > 1) $params[] = 'pagina=' . $pagina_actual;
                
                if (!empty($params)) {
                    $url .= '?' . implode('&', $params);
                }
                header("Location: $url");
                exit();
            } else {
                $error = "Error al actualizar receta";
            }
        }
        
        if (isset($_POST['eliminar_receta_empaquetado'])) {
            $resultado = $recetaEF->eliminarRecetaEmpaquetado($_POST['id_receta_eliminar']);
            
            if ($resultado) {
                $_SESSION['mensaje_exito'] = "Receta eliminada exitosamente";
                $url = "recetaei.php";
                $params = [];
                if ($busqueda_activa) {
                    $params[] = 'buscar=' . urlencode($termino_busqueda);
                }
                if ($pagina_actual > 1) $params[] = 'pagina=' . $pagina_actual;
                
                if (!empty($params)) {
                    $url .= '?' . implode('&', $params);
                }
                header("Location: $url");
                exit();
            } else {
                $error = "Error al eliminar receta";
            }
        }
    }
    
    if ($busqueda_activa) {
        $recetas_paginadas = $recetaEF->buscarRecetasEmpaquetadoPaginado(
            $termino_busqueda, 
            $offset, 
            $registros_por_pagina
        );
    } else {
        $recetas_paginadas = $recetaEF->obtenerRecetasEmpaquetadoPaginado($offset, $registros_por_pagina);
    }
    
    $recetas = $recetas_paginadas['recetas'];
    $total_registros = $recetas_paginadas['total_registros'];
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}

$tituloPagina = "Gestión de Recetas de Empaquetado";
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
        .recipe-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .recipe-section h3 {
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
        .badge-producto {
            background-color: #6f42c1;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .badge-semielaborado {
            background-color: #17a2b8;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
        }
        .badge-material {
            background-color: #28a745;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
        }
        .badge-cantidad {
            background-color: #ffc107;
            color: #000000;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .pagination-container {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-top: 20px;
        }
        .pagination {
            justify-content: center;
            margin-top: 0;
            flex-wrap: wrap;
            gap: 5px;
        }
        .page-link {
            background-color: #3d3d3d;
            border-color: #555;
            color: #ffd700;
            font-weight: bold;
            min-width: 45px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .page-link:hover {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(255, 215, 0, 0.3);
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
        .pagination-info {
            text-align: center;
            color: #ffd700;
            margin-top: 10px;
            font-weight: bold;
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
            .table th, .table td {
                padding: 8px 5px;
                font-size: 0.9rem;
            }
            .search-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        .no-records {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            font-style: italic;
            background: #ffffff;
        }
        
        .btn-action-group {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="recipe-section">
            <h3>📦 Recetas de Empaquetado</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?= $mensaje_exito ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar me-2" data-bs-toggle="modal" data-bs-target="#agregarRecetaModal">
                        ➕ Agregar Receta
                    </button>
                    <a href="empaquetadoi.php" class="btn btn-regresar">
                        ← Regresar a Empaquetado
                    </a>
                </div>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Recetas</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> receta(s) encontrada(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="get" action="recetaei.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar" 
                               placeholder="Buscar por producto, semielaborado o material..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button type="submit" class="btn btn-buscar">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="recetaei.php" class="btn btn-ver-todos">
                            🔄 Ver Todas las Recetas
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto Final</th>
                            <th>Semielaborado</th>
                            <th>Material</th>
                            <th>Cantidades por Lote</th>
                            <th>Productos Resultantes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recetas)): ?>
                            <?php foreach ($recetas as $receta): ?>
                                <tr>
                                    <td>
                                        <span class="badge-producto">
                                            🎁 <?= htmlspecialchars($receta['nombre_producto_final']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-semielaborado">
                                            🍮 <?= htmlspecialchars($receta['nombre_semielaborado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-material">
                                            📦 <?= htmlspecialchars($receta['nombre_material']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-cantidad">
                                            🍮 <?= $receta['cantidad_semielaborados_necesarios'] ?> + 
                                            📦 <?= $receta['cantidad_material_necesario'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-cantidad">
                                            🎁 <?= $receta['cantidad_productos_resultantes'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class='btn btn-editar' data-bs-toggle='modal' data-bs-target='#editarRecetaModal' 
                                              data-id='<?= $receta["id_receta_empaq"] ?>'
                                              data-producto-id='<?= $receta["id_producto_final"] ?>'
                                              data-semielaborado-id='<?= $receta["id_semielaborado"] ?>'
                                              data-material-id='<?= $receta["id_material"] ?>'
                                              data-cantidad-semielaborados='<?= $receta["cantidad_semielaborados_necesarios"] ?>'
                                              data-cantidad-material='<?= $receta["cantidad_material_necesario"] ?>'
                                              data-cantidad-productos='<?= $receta["cantidad_productos_resultantes"] ?>'
                                              data-producto-nombre='<?= htmlspecialchars($receta["nombre_producto_final"]) ?>'
                                              data-semielaborado-nombre='<?= htmlspecialchars($receta["nombre_semielaborado"]) ?>'
                                              data-material-nombre='<?= htmlspecialchars($receta["nombre_material"]) ?>'>
                                              ✏️ Editar
                                            </button>
                                            <button class='btn btn-eliminar' data-bs-toggle='modal' data-bs-target='#eliminarRecetaModal' 
                                              data-id='<?= $receta["id_receta_empaq"] ?>'
                                              data-producto='<?= htmlspecialchars($receta["nombre_producto_final"]) ?>'
                                              data-semielaborado='<?= htmlspecialchars($receta["nombre_semielaborado"]) ?>'
                                              data-material='<?= htmlspecialchars($receta["nombre_material"]) ?>'>
                                              🗑️ Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='6' class='no-records'>
                                    <i class="fas fa-info-circle fa-2x mb-3" style="color: #6c757d;"></i>
                                    <p style="color: #6c757d;">No se encontraron recetas de empaquetado</p>
                                    <small style="color: #6c757d;">
                                        <?php if ($busqueda_activa): ?>
                                            Intenta con otros términos de búsqueda o <a href="recetaei.php">ver todas las recetas</a>
                                        <?php else: ?>
                                            Comienza agregando recetas para configurar el empaquetado de productos
                                        <?php endif; ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Paginación de recetas">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=1<?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>" aria-label="Primera Página">
                                    <span aria-hidden="true">««</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>" aria-label="Anterior">
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
                                    <a class="page-link" href="?pagina=<?= $i ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>">
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
                                <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>

                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>" aria-label="Última Página">
                                    <span aria-hidden="true">»»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-6">
                            <div class="pagination-info text-center text-md-start">
                                <small>
                                    Mostrando <strong><?= count($recetas) ?></strong> de <strong><?= $total_registros ?></strong> recetas - 
                                    Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                                    <?php if ($busqueda_activa): ?>
                                        <span class="badge bg-warning text-dark ms-2">Búsqueda: "<?= htmlspecialchars($termino_busqueda) ?>"</span>
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

    <div class="modal fade" id="agregarRecetaModal" tabindex="-1" aria-labelledby="agregarRecetaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarRecetaModalLabel">➕ Agregar Receta de Empaquetado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="recetaei.php" id="formAgregarReceta">
                        <?php if ($busqueda_activa): ?>
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <?php endif; ?>
                        <?php if ($pagina_actual > 1): ?>
                            <input type="hidden" name="pagina" value="<?= $pagina_actual ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="id_producto_final" class="form-label required-field">Producto Final</label>
                            <select class="form-select" id="id_producto_final" name="id_producto_final" required>
                                <option value="">Seleccionar producto final...</option>
                                <?php foreach ($productos_finales as $producto): ?>
                                    <option value="<?= $producto['id_producto'] ?>">
                                        🎁 <?= htmlspecialchars($producto['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_semielaborado" class="form-label required-field">Semielaborado</label>
                            <select class="form-select" id="id_semielaborado" name="id_semielaborado" required>
                                <option value="">Seleccionar semielaborado...</option>
                                <?php foreach ($semielaborados as $semielaborado): ?>
                                    <option value="<?= $semielaborado['id_semielaborado'] ?>">
                                        🍮 <?= htmlspecialchars($semielaborado['nombre']) ?> 
                                        (Stock: <?= $semielaborado['cantidad'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_material" class="form-label required-field">Material</label>
                            <select class="form-select" id="id_material" name="id_material" required>
                                <option value="">Seleccionar material...</option>
                                <?php foreach ($materiales as $material): ?>
                                    <option value="<?= $material['id_material'] ?>">
                                        📦 <?= htmlspecialchars($material['nombre']) ?> 
                                        (<?= $material['tipo'] ?>, Stock: <?= $material['cantidad_stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_semielaborados" class="form-label required-field">Semielaborados por Lote</label>
                            <input type="number" class="form-control" id="cantidad_semielaborados" name="cantidad_semielaborados" min="1" required>
                            <small class="text-muted">Cantidad de semielaborados necesarios por cada lote</small>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_material" class="form-label required-field">Material por Lote</label>
                            <input type="number" class="form-control" id="cantidad_material" name="cantidad_material" min="1" required>
                            <small class="text-muted">Cantidad de material necesario por cada lote</small>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_productos" class="form-label required-field">Productos Resultantes por Lote</label>
                            <input type="number" class="form-control" id="cantidad_productos" name="cantidad_productos" min="1" required>
                            <small class="text-muted">Cantidad de productos finales que se crearán por cada lote</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" name="agregar_receta_empaquetado">Agregar Receta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarRecetaModal" tabindex="-1" aria-labelledby="editarRecetaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarRecetaModalLabel">✏️ Editar Receta de Empaquetado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="recetaei.php" id="formEditarReceta">
                        <input type="hidden" name="id_receta_editar" id="id_receta_editar">
                        <?php if ($busqueda_activa): ?>
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <?php endif; ?>
                        <?php if ($pagina_actual > 1): ?>
                            <input type="hidden" name="pagina" value="<?= $pagina_actual ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="id_producto_final_editar" class="form-label required-field">Producto Final</label>
                            <select class="form-select" id="id_producto_final_editar" name="id_producto_final_editar" required>
                                <option value="">Seleccionar producto final...</option>
                                <?php foreach ($productos_finales as $producto): ?>
                                    <option value="<?= $producto['id_producto'] ?>">
                                        🎁 <?= htmlspecialchars($producto['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_semielaborado_editar" class="form-label required-field">Semielaborado</label>
                            <select class="form-select" id="id_semielaborado_editar" name="id_semielaborado_editar" required>
                                <option value="">Seleccionar semielaborado...</option>
                                <?php foreach ($semielaborados as $semielaborado): ?>
                                    <option value="<?= $semielaborado['id_semielaborado'] ?>">
                                        🍮 <?= htmlspecialchars($semielaborado['nombre']) ?> 
                                        (Stock: <?= $semielaborado['cantidad'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_material_editar" class="form-label required-field">Material</label>
                            <select class="form-select" id="id_material_editar" name="id_material_editar" required>
                                <option value="">Seleccionar material...</option>
                                <?php foreach ($materiales as $material): ?>
                                    <option value="<?= $material['id_material'] ?>">
                                        📦 <?= htmlspecialchars($material['nombre']) ?> 
                                        (<?= $material['tipo'] ?>, Stock: <?= $material['cantidad_stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_semielaborados_editar" class="form-label required-field">Semielaborados por Lote</label>
                            <input type="number" class="form-control" id="cantidad_semielaborados_editar" name="cantidad_semielaborados_editar" min="1" required>
                            <small class="text-muted">Cantidad de semielaborados necesarios por cada lote</small>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_material_editar" class="form-label required-field">Material por Lote</label>
                            <input type="number" class="form-control" id="cantidad_material_editar" name="cantidad_material_editar" min="1" required>
                            <small class="text-muted">Cantidad de material necesario por cada lote</small>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_productos_editar" class="form-label required-field">Productos Resultantes por Lote</label>
                            <input type="number" class="form-control" id="cantidad_productos_editar" name="cantidad_productos_editar" min="1" required>
                            <small class="text-muted">Cantidad de productos finales que se crearán por cada lote</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" name="editar_receta_empaquetado">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eliminarRecetaModal" tabindex="-1" aria-labelledby="eliminarRecetaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarRecetaModalLabel">🗑️ Eliminar Receta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la receta de empaquetado?</p>
                    <p><strong>Producto:</strong> <span id="producto_eliminar" class="text-warning"></span></p>
                    <p><strong>Semielaborado:</strong> <span id="semielaborado_eliminar" class="text-warning"></span></p>
                    <p><strong>Material:</strong> <span id="material_eliminar" class="text-warning"></span></p>
                    <form method="post" action="recetaei.php">
                        <input type="hidden" name="id_receta_eliminar" id="id_receta_eliminar">
                        <?php if ($busqueda_activa): ?>
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <?php endif; ?>
                        <?php if ($pagina_actual > 1): ?>
                            <input type="hidden" name="pagina" value="<?= $pagina_actual ?>">
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger flex-fill" name="eliminar_receta_empaquetado">Eliminar</button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#eliminarRecetaModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var producto = button.data('producto');
                var semielaborado = button.data('semielaborado');
                var material = button.data('material');
                
                $(this).find('#id_receta_eliminar').val(id);
                $(this).find('#producto_eliminar').text(producto);
                $(this).find('#semielaborado_eliminar').text(semielaborado);
                $(this).find('#material_eliminar').text(material);
            });

            $('#editarRecetaModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                
                modal.find('#id_receta_editar').val(button.data('id'));
                modal.find('#id_producto_final_editar').val(button.data('producto-id'));
                modal.find('#id_semielaborado_editar').val(button.data('semielaborado-id'));
                modal.find('#id_material_editar').val(button.data('material-id'));
                modal.find('#cantidad_semielaborados_editar').val(button.data('cantidad-semielaborados'));
                modal.find('#cantidad_material_editar').val(button.data('cantidad-material'));
                modal.find('#cantidad_productos_editar').val(button.data('cantidad-productos'));
            });

            $('#selectorPagina').on('change', function() {
                const pagina = this.value;
                const baseUrl = 'recetaei.php';
                const parametros = [];
                
                <?php if ($busqueda_activa): ?>
                    parametros.push('buscar=' + encodeURIComponent('<?= $termino_busqueda ?>'));
                <?php endif; ?>
                
                parametros.push('pagina=' + pagina);
                
                const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
                window.location.href = urlCompleta;
            });

            $('#formAgregarReceta').on('submit', function(e) {
                let isValid = true;
                
                if ($('#id_producto_final').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un producto final');
                }
                
                if ($('#id_semielaborado').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un semielaborado');
                }
                
                if ($('#id_material').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un material');
                }
                
                if ($('#cantidad_semielaborados').val() < 1) {
                    isValid = false;
                    alert('La cantidad de semielaborados debe ser mayor a 0');
                }
                
                if ($('#cantidad_material').val() < 1) {
                    isValid = false;
                    alert('La cantidad de material debe ser mayor a 0');
                }
                
                if ($('#cantidad_productos').val() < 1) {
                    isValid = false;
                    alert('La cantidad de productos resultantes debe ser mayor a 0');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });

            $('#formEditarReceta').on('submit', function(e) {
                let isValid = true;
                
                if ($('#id_producto_final_editar').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un producto final');
                }
                
                if ($('#id_semielaborado_editar').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un semielaborado');
                }
                
                if ($('#id_material_editar').val() === '') {
                    isValid = false;
                    alert('Por favor selecciona un material');
                }
                
                if ($('#cantidad_semielaborados_editar').val() < 1) {
                    isValid = false;
                    alert('La cantidad de semielaborados debe ser mayor a 0');
                }
                
                if ($('#cantidad_material_editar').val() < 1) {
                    isValid = false;
                    alert('La cantidad de material debe ser mayor a 0');
                }
                
                if ($('#cantidad_productos_editar').val() < 1) {
                    isValid = false;
                    alert('La cantidad de productos resultantes debe ser mayor a 0');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });

            $('input[name="buscar"]').focus();
        });

        document.addEventListener('keydown', function(e) {
            <?php if ($total_paginas > 1): ?>
                if (e.key === 'ArrowLeft' && <?= $pagina_actual > 1 ? 'true' : 'false' ?>) {
                    window.location.href = '?pagina=<?= $pagina_actual - 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>';
                } else if (e.key === 'ArrowRight' && <?= $pagina_actual < $total_paginas ? 'true' : 'false' ?>) {
                    window.location.href = '?pagina=<?= $pagina_actual + 1 ?><?= $busqueda_activa ? '&buscar=' . urlencode($termino_busqueda) : '' ?>';
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>