<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

function formatearCantidad($cantidad) {
    if ($cantidad == floor($cantidad)) {
        return number_format($cantidad, 0);
    } else {
        return number_format($cantidad, 3); 
    }
}

require_once 'recetaf.php';
require_once 'conexion.php';

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_activa = false;
$termino_busqueda = '';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $recetaF = new RecetaF($conn);
    
    $recetas = [];
    $total_registros = 0;
    
    if (isset($_POST['buscar_semielaborado']) && !empty($_POST['buscar_semielaborado'])) {
        $termino_busqueda = $_POST['buscar_semielaborado'];
        $busqueda_activa = true;
        $resultados = $recetaF->buscarRecetasPorSemielaborado($termino_busqueda, $offset, $registros_por_pagina);
        $recetas = $resultados['recetas'];
        $total_registros = $resultados['total'];
    } else {
        $resultados = $recetaF->obtenerRecetasConPaginacion($offset, $registros_por_pagina);
        $recetas = $resultados['recetas'];
        $total_registros = $resultados['total'];
    }
    
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    $semielaborados = $recetaF->obtenerSemielaborados();
    $ingredientes = $recetaF->obtenerIngredientes();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['agregar_ingrediente_receta'])) {
            try {
                $resultado = $recetaF->agregarIngredienteReceta(
                    $_POST['id_semielaborado'],
                    $_POST['id_ingrediente'],
                    $_POST['cantidad_requerida'],
                    $_POST['observaciones'] ?? ''
                );
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Ingrediente agregado a la receta exitosamente";
                    header("Location: recetai.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al agregar ingrediente: " . $e->getMessage();
            }
        }
        
        if (isset($_POST['actualizar_cantidad_receta'])) {
            try {
                $resultado = $recetaF->actualizarCantidadReceta(
                    $_POST['id_receta'],
                    $_POST['nueva_cantidad']
                );
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Cantidad actualizada exitosamente";
                    header("Location: recetai.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al actualizar cantidad: " . $e->getMessage();
            }
        }
        
        if (isset($_POST['eliminar_ingrediente_receta'])) {
            try {
                $resultado = $recetaF->eliminarIngredienteReceta($_POST['id_receta']);
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Ingrediente eliminado de la receta exitosamente";
                    header("Location: recetai.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al eliminar ingrediente: " . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}

$tituloPagina = "Gestión de Recetas de Semielaborados";
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
        
        .table-sm th {
            padding: 10px 8px;
            font-size: 0.9rem;
        }
        .table-sm td {
            padding: 8px 6px;
            font-size: 0.9rem;
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
        .badge-semielaborado {
            background-color: #6f42c1;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .badge-ingrediente {
            background-color: #17a2b8;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
        }
        .badge-cantidad {
            background-color: #28a745;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .recipe-group {
            background-color: #1a1a1a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffd700;
        }
        .recipe-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
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
            .table th, .table td {
                padding: 8px 5px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="recipe-section">
            <h3>⚙️ Recetas de Semielaborados</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?= $mensaje_exito ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar me-2" data-bs-toggle="modal" data-bs-target="#agregarRecetaModal">
                        ➕ Agregar a Receta
                    </button>
                    
                    <a href="produccioni.php" class="btn btn-regresar">
                        ← Regresar a Producción
                    </a>
                </div>
                
                <div class="text-warning">
                    <strong>📊 Total: <?= $total_registros ?> ingrediente(s) en recetas</strong>
                </div>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Recetas por Semielaborado</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> ingrediente(s) encontrado(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="recetai.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar_semielaborado" 
                               placeholder="Buscar por nombre de semielaborado..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button class="btn btn-buscar" type="submit">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="recetai.php" class="btn btn-ver-todos">
                            🔄 Ver Todas las Recetas
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            $recetas_agrupadas = [];
            foreach ($recetas as $receta) {
                $semielaborado_id = $receta['id_semielaborado'];
                if (!isset($recetas_agrupadas[$semielaborado_id])) {
                    $recetas_agrupadas[$semielaborado_id] = [
                        'nombre_semielaborado' => $receta['nombre_semielaborado'],
                        'ingredientes' => []
                    ];
                }
                
                $recetas_agrupadas[$semielaborado_id]['ingredientes'][] = $receta;
            }
            ?>

            <?php if (!empty($recetas_agrupadas)): ?>
                <?php foreach ($recetas_agrupadas as $semielaborado_id => $grupo): ?>
                    <div class="recipe-group">
                        <div class="recipe-group-header">
                            <div>
                                <h5 class="text-warning mb-0">
                                    ⚙️ <?= htmlspecialchars($grupo['nombre_semielaborado']) ?>
                                </h5>
                            </div>
                            <span class="badge bg-primary">
                                <?= count($grupo['ingredientes']) ?> ingrediente(s)
                            </span>
                        </div>
                        
                        <?php if (!empty($grupo['ingredientes'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ingrediente</th>
                                            <th>Cantidad Requerida</th>
                                            <th>Unidad de Medida</th>
                                            <th>Observaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['ingredientes'] as $receta): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge-ingrediente">
                                                        🧂 <?= htmlspecialchars($receta['nombre_ingrediente']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-cantidad">
                                                        📦 <?= formatearCantidad($receta['cantidad_requerida']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($receta['unidad_medida']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($receta['observaciones'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($receta['observaciones']) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class='btn btn-editar me-1' data-bs-toggle='modal' data-bs-target='#editarCantidadModal' 
                                                          data-id='<?= $receta["id_receta"] ?>'
                                                          data-cantidad-actual='<?= htmlspecialchars($receta["cantidad_requerida"]) ?>'
                                                          data-ingrediente='<?= htmlspecialchars($receta["nombre_ingrediente"]) ?>'>✏️ Cantidad</button>
                                                          
                                                        <button class='btn btn-eliminar' data-bs-toggle='modal' data-bs-target='#eliminarRecetaModal' 
                                                          data-id='<?= $receta["id_receta"] ?>'
                                                          data-semielaborado='<?= htmlspecialchars($grupo['nombre_semielaborado']) ?>'
                                                          data-ingrediente='<?= htmlspecialchars($receta["nombre_ingrediente"]) ?>'>🗑️ Eliminar</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <div class="text-muted">
                        <h5>No se encontraron recetas</h5>
                        <?php if ($busqueda_activa): ?>
                            <p>Intenta con otros términos de búsqueda</p>
                            <a href="recetai.php" class="btn btn-ver-todos mt-2">
                                🔄 Ver Todas las Recetas
                            </a>
                        <?php else: ?>
                            <p>Comienza agregando ingredientes a las recetas de tus semielaborados</p>
                            <button class="btn btn-agregar mt-2" data-bs-toggle="modal" data-bs-target="#agregarRecetaModal">
                                ➕ Agregar Primera Receta
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($total_paginas > 1): ?>
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
                                Mostrando <strong><?= count($recetas) ?></strong> de <strong><?= $total_registros ?></strong> ingredientes en recetas - 
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
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="agregarRecetaModal" tabindex="-1" aria-labelledby="agregarRecetaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarRecetaModalLabel">➕ Agregar Ingrediente a Receta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="recetai.php" id="formAgregarIngrediente">
                        <div class="mb-3">
                            <label for="id_semielaborado" class="form-label required-field">Semielaborado</label>
                            <select class="form-select" id="id_semielaborado" name="id_semielaborado" required>
                                <option value="">Seleccionar semielaborado...</option>
                                <?php foreach ($semielaborados as $semielaborado): ?>
                                    <option value="<?= $semielaborado['id_semielaborado'] ?>"><?= htmlspecialchars($semielaborado['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_ingrediente" class="form-label required-field">Ingrediente</label>
                            <select class="form-select" id="id_ingrediente" name="id_ingrediente" required>
                                <option value="">Seleccionar ingrediente...</option>
                                <?php foreach ($ingredientes as $ingrediente): ?>
                                    <option value="<?= $ingrediente['id_ingrediente'] ?>" data-unidad="<?= htmlspecialchars($ingrediente['unidad_medida']) ?>">
                                        <?= htmlspecialchars($ingrediente['nombre']) ?> (<?= htmlspecialchars($ingrediente['unidad_medida']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_requerida" class="form-label required-field">Cantidad Requerida</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_requerida" name="cantidad_requerida" min="0.01" step="0.01" required>
                                <span class="input-group-text" id="unidad_medida">-</span>
                            </div>
                            <small class="text-muted">Cantidad necesaria por unidad de semielaborado</small>
                        </div>
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Opcional - notas sobre este ingrediente en la receta"></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" name="agregar_ingrediente_receta">Agregar a Receta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarCantidadModal" tabindex="-1" aria-labelledby="editarCantidadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarCantidadModalLabel">✏️ Editar Cantidad</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Ingrediente: <strong id="ingrediente_editar" class="text-warning"></strong></p>
                    <form method="post" action="recetai.php">
                        <input type="hidden" name="id_receta" id="id_receta_editar">
                        <div class="mb-3">
                            <label for="nueva_cantidad" class="form-label required-field">Nueva Cantidad Requerida</label>
                            <input type="number" class="form-control" id="nueva_cantidad" name="nueva_cantidad" min="0.01" step="0.01" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" name="actualizar_cantidad_receta">Actualizar Cantidad</button>
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
                    <h5 class="modal-title" id="eliminarRecetaModalLabel">🗑️ Eliminar de Receta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar <strong id="ingrediente_eliminar" class="text-warning"></strong> de la receta de <strong id="semielaborado_eliminar" class="text-warning"></strong>?</p>
                    <p class="text-muted"><small>Esta acción no se puede deshacer.</small></p>
                    <form method="post" action="recetai.php">
                        <input type="hidden" name="id_receta" id="id_receta_eliminar">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger flex-fill" name="eliminar_ingrediente_receta">Eliminar</button>
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
        $('#id_ingrediente').on('change', function() {
            var unidad = $(this).find(':selected').data('unidad');
            $('#unidad_medida').text(unidad || '-');
        });

        $('#editarCantidadModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var cantidadActual = button.data('cantidad-actual');
            var ingrediente = button.data('ingrediente');
            
            $(this).find('#id_receta_editar').val(id);
            $(this).find('#nueva_cantidad').val(cantidadActual);
            $(this).find('#ingrediente_editar').text(ingrediente);
        });

        $('#eliminarRecetaModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var semielaborado = button.data('semielaborado');
            var ingrediente = button.data('ingrediente');
            
            $(this).find('#id_receta_eliminar').val(id);
            $(this).find('#semielaborado_eliminar').text(semielaborado);
            $(this).find('#ingrediente_eliminar').text(ingrediente);
        });

        $('#formAgregarIngrediente').on('submit', function(e) {
            let isValid = true;
            
            if ($('#id_semielaborado').val() === '') {
                isValid = false;
                alert('Por favor selecciona un semielaborado');
            }
            
            if ($('#id_ingrediente').val() === '') {
                isValid = false;
                alert('Por favor selecciona un ingrediente');
            }
            
            if ($('#cantidad_requerida').val() <= 0) {
                isValid = false;
                alert('La cantidad debe ser mayor a 0');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        $(document).ready(function() {
            $('input[name="buscar_semielaborado"]').focus();
        });

        document.getElementById('selectorPagina').addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'recetai.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('buscar=<?= urlencode($termino_busqueda) ?>');
            <?php endif; ?>
            
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
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