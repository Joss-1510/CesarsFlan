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

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_activa = false;
$termino_busqueda = '';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $empaquetadoF = new EmpaquetadoF($conn);
    
    $empaquetados = [];
    $total_registros = 0;
    
    if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])) {
        $termino_busqueda = $_POST['buscar_nombre'];
        $busqueda_activa = true;
        $resultados = $empaquetadoF->buscarEmpaquetadosConPaginacion($termino_busqueda, $offset, $registros_por_pagina);
        $empaquetados = $resultados['empaquetados'];
        $total_registros = $resultados['total'];
    } else {
        $resultados = $empaquetadoF->obtenerEmpaquetadosConPaginacion($offset, $registros_por_pagina);
        $empaquetados = $resultados['empaquetados'];
        $total_registros = $resultados['total'];
    }
    
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    $productos_finales = $empaquetadoF->obtenerProductosFinales();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['nueva_cantidad_lotes']) && isset($_POST['usar_receta']) && $_POST['usar_receta'] == '1') {
            try {
                $resultado = $empaquetadoF->crearEmpaquetadoConReceta(
                    $_POST['nuevo_producto_final'],
                    $_POST['nueva_cantidad_lotes'],
                    $_POST['nueva_observaciones']
                );
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Empaquetado creado exitosamente con receta automática";
                    header("Location: empaquetadoi.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al crear empaquetado con receta: " . $e->getMessage();
            }
        }
        
        if (isset($_POST['finalizar_empaquetado'])) {
            try {
                if ($empaquetadoF->estaFinalizado($_POST['id_empaquetado'])) {
                    $error = "Este empaquetado ya ha sido finalizado anteriormente";
                } else {
                    $resultado = $empaquetadoF->finalizarEmpaquetado($_POST['id_empaquetado']);
                    if ($resultado) {
                        $total_productos = $resultado['total_productos'];
                        $id_producto_final = $resultado['id_producto_final'];
                        
                        $_SESSION['mensaje_exito'] = "✅ Empaquetado finalizado exitosamente. Se agregaron $total_productos productos al stock.";
                        header("Location: empaquetadoi.php");
                        exit();
                    } else {
                        $error = "Error al finalizar el empaquetado";
                    }
                }
            } catch (Exception $e) {
                $error = "Error al finalizar empaquetado: " . $e->getMessage();
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

$tituloPagina = "Gestión de Empaquetado";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> - Cesar's Flan</title>
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
        .empaquetado-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .empaquetado-section h3 {
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
        .btn-recetas {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-recetas:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        .btn-produccion {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-produccion:hover {
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
        .btn-finalizar {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-finalizar:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        .btn-finalizar:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
        }
        .btn-finalizado {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: default;
        }
        .btn-detalles {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-detalles:hover {
            background-color: #138496;
            border-color: #117a8b;
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
        .badge-usuario {
            background-color: #17a2b8;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .badge-fecha {
            background-color: #28a745;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .badge-estado {
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-pendiente {
            background-color: #ffc107;
            color: #000000;
        }
        .badge-finalizado {
            background-color: #28a745;
            color: #ffffff;
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
        .receta-info {
            background-color: #1a3a3a;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .receta-item {
            background-color: #2d2d2d;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #28a745;
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
        
        .btn-action-group {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        .btn-finalizar {
            margin-right: 8px;
        }
        .btn-detalles {
            margin-left: 8px;
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
            .btn-action-group {
                flex-direction: column;
                gap: 5px;
            }
            .btn-finalizar, .btn-detalles {
                margin: 2px 0;
                width: 100%;
            }
        }
        
        .no-records {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            font-style: italic;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="empaquetado-section">
            <h3>📦 Gestión de Empaquetado</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?= $mensaje_exito ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarEmpaquetadoModal">
                        📦 Nuevo Empaquetado
                    </button>
                    <a href="recetaei.php" class="btn btn-recetas ms-2">
                        🍮 Gestionar Recetas
                    </a>
                </div>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Empaquetados</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> empaquetado(s) encontrado(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="empaquetadoi.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar_nombre" 
                               placeholder="Buscar por observaciones o usuario..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button class="btn btn-buscar" type="submit">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="empaquetadoi.php" class="btn btn-ver-todos">
                            🔄 Ver Todos los Empaquetados
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Fecha Empaquetado</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($empaquetados)): ?>
                            <?php foreach ($empaquetados as $empaquetado): 
                                $estaFinalizado = ($empaquetado['estado'] === 'FINALIZADO');
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($empaquetado['nombre_usuario'])): ?>
                                            <span class="badge-usuario">
                                                👤 <?= htmlspecialchars($empaquetado['nombre_usuario']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-fecha">
                                            📅 <?= date('d/m/Y H:i', strtotime($empaquetado['fecha_empaquetado'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($estaFinalizado): ?>
                                            <span class="badge-estado badge-finalizado">
                                                ✅ Finalizado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-pendiente">
                                                ⏳ Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($empaquetado['observaciones']) ? htmlspecialchars($empaquetado['observaciones']) : '<span class="text-muted">Sin observaciones</span>' ?>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <?php if ($estaFinalizado): ?>
                                                <button class='btn btn-finalizado' disabled>
                                                    ✅ Finalizado
                                                </button>
                                            <?php else: ?>
                                                <button class='btn btn-finalizar' data-bs-toggle='modal' data-bs-target='#finalizarEmpaquetadoModal' 
                                                  data-id='<?= $empaquetado["id_empaquetado"] ?? '' ?>'
                                                  data-producto='Empaquetado #<?= $empaquetado["id_empaquetado"] ?? '' ?>'>
                                                  ✅ Finalizar
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class='btn btn-detalles' data-bs-toggle='modal' data-bs-target='#verDetallesEmpaquetadoModal' 
                                              data-id='<?= $empaquetado["id_empaquetado"] ?? '' ?>'>📊 Detalles</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='5' class='no-records'>
                                    <i class="fas fa-info-circle fa-2x mb-3" style="color: #6c757d;"></i>
                                    <p style="color: #6c757d;">No se encontraron empaquetados</p>
                                    <?php if ($busqueda_activa): ?>
                                        <small style="color: #6c757d;">Intenta con otros términos de búsqueda</small>
                                        <div class="mt-3">
                                            <a href="empaquetadoi.php" class="btn btn-ver-todos">
                                                🔄 Ver Todos los Empaquetados
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <small style="color: #6c757d;">Comienza creando un nuevo empaquetado</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Paginación de empaquetados">
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
                                    Mostrando <strong><?= count($empaquetados) ?></strong> de <strong><?= $total_registros ?></strong> empaquetados - 
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

    <!-- Modal Agregar Empaquetado -->
    <div class="modal fade" id="agregarEmpaquetadoModal" tabindex="-1" aria-labelledby="agregarEmpaquetadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarEmpaquetadoModalLabel">📦 Nuevo Empaquetado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="empaquetadoi.php" id="formAgregarEmpaquetado">
                        <div class="mb-3">
                            <label for="nuevo_producto_final" class="form-label required-field">Producto Final</label>
                            <select class="form-select" id="nuevo_producto_final" name="nuevo_producto_final" required>
                                <option value="">Seleccionar producto final...</option>
                                <?php if (!empty($productos_finales)): ?>
                                    <?php foreach ($productos_finales as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>">
                                            🎁 <?= htmlspecialchars($producto['nombre']) ?> 
                                            (Stock: <?= $producto['stock'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay productos finales disponibles</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($productos_finales)): ?>
                                <div class="alert alert-warning mt-2">
                                    <small>No se encontraron productos finales en la base de datos.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="nueva_cantidad_lotes" class="form-label required-field">Cantidad de Lotes</label>
                            <input type="number" class="form-control" id="nueva_cantidad_lotes" name="nueva_cantidad_lotes" min="1" required>
                            <small class="text-muted">Cantidad de lotes a empaquetar (cada lote produce varios productos según la receta)</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="usar_receta" name="usar_receta" value="1" checked>
                                <label class="form-check-label" for="usar_receta">
                                    📋 Usar receta automática
                                </label>
                            </div>
                            <small class="text-muted" id="texto_receta">
                                Los semielaborados y materiales se calcularán y descontarán automáticamente del stock
                            </small>
                        </div>

                        <div id="info_receta" class="receta-info">
                            <h6>📋 Receta del Producto Final</h6>
                            <div id="detalles_receta">
                                <div class="text-muted">Selecciona un producto final para ver su receta</div>
                            </div>
                            <small class="text-muted">* Los semielaborados y materiales se descontarán automáticamente del stock</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nueva_observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="nueva_observaciones" name="nueva_observaciones" rows="3" placeholder="Opcional - notas sobre este empaquetado"></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" id="btnCrearEmpaquetado">📋 Crear Empaquetado con Receta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="finalizarEmpaquetadoModal" tabindex="-1" aria-labelledby="finalizarEmpaquetadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarEmpaquetadoModalLabel">✅ Finalizar Empaquetado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas finalizar el empaquetado <strong id="empaquetado_finalizar" class="text-warning"></strong>?</p>
                    <p class="text-muted"><small>Al finalizar, se incrementará el stock del producto final y el empaquetado cambiará a estado "Finalizado".</small></p>
                    <form method="post" action="empaquetadoi.php">
                        <input type="hidden" name="id_empaquetado" id="id_empaquetado_finalizar">
                        <input type="hidden" name="finalizar_empaquetado" value="1">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-fill">✅ Sí, Finalizar</button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="verDetallesEmpaquetadoModal" tabindex="-1" aria-labelledby="verDetallesEmpaquetadoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verDetallesEmpaquetadoModalLabel">📊 Detalles de Empaquetado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesEmpaquetadoContenido">
                        <div class="text-center">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando detalles...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $('#finalizarEmpaquetadoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var producto = button.data('producto');
            
            $(this).find('#id_empaquetado_finalizar').val(id);
            $(this).find('#empaquetado_finalizar').text(producto);
        });

        $('#verDetallesEmpaquetadoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var idEmpaquetado = button.data('id');
            
            $.ajax({
                url: 'cargar_detalles_empaquetado.php',
                type: 'GET',
                data: { id_empaquetado: idEmpaquetado },
                success: function(response) {
                    $('#detallesEmpaquetadoContenido').html(response);
                },
                error: function() {
                    $('#detallesEmpaquetadoContenido').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                }
            });
        });

        function cargarRecetaEmpaquetado() {
            var idProductoFinal = $('#nuevo_producto_final').val();
            
            if (idProductoFinal) {
                $.ajax({
                    url: 'cargar_receta_empaquetado.php',
                    type: 'GET',
                    data: { 
                        id_producto_final: idProductoFinal
                    },
                    success: function(response) {
                        $('#detalles_receta').html(response);
                        if (response.trim() !== '' && !response.includes('alert-warning')) {
                            $('#info_receta').show();
                        } else {
                            $('#info_receta').hide();
                        }
                    },
                    error: function() {
                        $('#detalles_receta').html('<div class="alert alert-warning">No se pudo cargar la receta</div>');
                        $('#info_receta').show();
                    }
                });
            } else {
                $('#info_receta').hide();
            }
        }

        $('#nuevo_producto_final').on('change', cargarRecetaEmpaquetado);

        $('#usar_receta').on('change', function() {
            if ($(this).is(':checked')) {
                $('#btnCrearEmpaquetado').html('📋 Crear Empaquetado con Receta');
                $('#texto_receta').addClass('text-success').removeClass('text-muted');
            } else {
                $('#btnCrearEmpaquetado').html('Iniciar Empaquetado');
                $('#texto_receta').removeClass('text-success').addClass('text-muted');
            }
        });

        $('#formAgregarEmpaquetado').on('submit', function(e) {
            let isValid = true;
            
            if ($('#nuevo_producto_final').val() === '') {
                isValid = false;
                alert('Por favor selecciona un producto final');
            }
            
            if ($('#nueva_cantidad_lotes').val() < 1) {
                isValid = false;
                alert('La cantidad de lotes debe ser mayor a 0');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        $(document).ready(function() {
            $('input[name="buscar_nombre"]').focus();
            
            if ($('#nuevo_producto_final').val()) {
                cargarRecetaEmpaquetado();
            }
        });

        document.getElementById('selectorPagina').addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'empaquetadoi.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('buscar=' + encodeURIComponent('<?= $termino_busqueda ?>'));
            <?php endif; ?>
            
            parametros.push('pagina=' + pagina);
            
            const urlCompleta = baseUrl + (parametros.length > 0 ? '?' + parametros.join('&') : '');
            window.location.href = urlCompleta;
        });
    </script>
</body>
</html>