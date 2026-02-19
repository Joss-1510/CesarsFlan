<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'produccionf.php';
require_once 'ingredientef.php';
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
    $produccionF = new ProduccionF($conn);
    $ingredienteF = new IngredienteF($conn);
    
    $producciones = [];
    $total_registros = 0;
    
    if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])) {
        $termino_busqueda = $_POST['buscar_nombre'];
        $busqueda_activa = true;
        $resultados = $produccionF->buscarProduccionesConPaginacion($termino_busqueda, $offset, $registros_por_pagina);
        $producciones = $resultados['producciones'];
        $total_registros = $resultados['total'];
    } else {
        $resultados = $produccionF->obtenerProduccionesConPaginacion($offset, $registros_por_pagina);
        $producciones = $resultados['producciones'];
        $total_registros = $resultados['total'];
    }
    
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    $semielaborados = $produccionF->obtenerSemielaboradosParaProduccion();
    $ingredientes = $ingredienteF->obtenerIngredientesConPaginacion(0, 100)['ingredientes'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['nueva_cantidad']) && isset($_POST['usar_receta']) && $_POST['usar_receta'] == '1') {
            try {
                $resultado = $produccionF->crearProduccionConReceta(
                    $_POST['nuevo_semielaborado'],
                    $_POST['nueva_cantidad'],
                    $_POST['nueva_observaciones']
                );
                
                if ($resultado) {
                    $_SESSION['mensaje_exito'] = "Producción creada exitosamente con receta automática";
                    header("Location: produccioni.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al crear producción con receta: " . $e->getMessage();
            }
        }

        elseif (isset($_POST['nueva_cantidad'])) {
            $produccionData = [
                'cantidad' => $_POST['nueva_cantidad'],
                'estado' => 'En proceso',
                'id_semielaborado' => $_POST['nuevo_semielaborado'],
                'observaciones' => $_POST['nueva_observaciones'],
                'uso_receta' => false
            ];
            
            $resultado = $produccionF->crearProduccion($produccionData);
            if ($resultado) {
                $_SESSION['mensaje_exito'] = "Producción creada exitosamente";
                header("Location: produccioni.php");
                exit();
            } else {
                $error = "Error al crear la producción";
            }
        }
        
        if (isset($_POST['agregar_ingrediente'])) {
            $resultado = $produccionF->agregarDetalleProduccion([
                'id_produccion' => $_POST['id_produccion_ingrediente'],
                'tipo_item' => 'ingrediente',
                'id_ingrediente' => $_POST['id_ingrediente'],
                'cantidad_usada' => $_POST['cantidad_ingrediente'],
                'costo_unitario' => $_POST['costo_ingrediente'],
                'es_automatico' => false
            ]);
            
            if ($resultado) {
                header("Location: produccioni.php?ver_detalles=" . $_POST['id_produccion_ingrediente']);
                exit();
            } else {
                $error = "Error al agregar el ingrediente";
            }
        }
        
        if (isset($_POST['eliminar_detalle'])) {
            $resultado = $produccionF->eliminarDetalleProduccion($_POST['id_detalle']);
            if ($resultado) {
                header("Location: produccioni.php?ver_detalles=" . $_POST['id_produccion_eliminar_detalle']);
                exit();
            } else {
                $error = "Error al eliminar el item";
            }
        }
        
        if (isset($_POST['finalizar_produccion'])) {
            try {
                $resultado = $produccionF->cambiarEstadoProduccion($_POST['id_produccion'], 'Terminada');
                if ($resultado) {
                    $produccion = $produccionF->obtenerProduccionPorId($_POST['id_produccion']);
                    $semielaborado_nombre = $produccion['nombre_semielaborado'] ?? 'Semielaborado';
                    $cantidad = $produccion['cantidad'] ?? 0;
                    
                    $_SESSION['mensaje_exito'] = "✅ Producción finalizada exitosamente. Se agregaron $cantidad unidades al stock de '$semielaborado_nombre'.";
                    header("Location: produccioni.php");
                    exit();
                } else {
                    $error = "Error al finalizar la producción";
                }
            } catch (Exception $e) {
                $error = "Error al finalizar producción: " . $e->getMessage();
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

$tituloPagina = "Gestión de Producción";
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
        .production-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .production-section h3 {
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
        .btn-semielaborados {
            background-color: #ff6b35;
            border-color: #ff6b35;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-semielaborados:hover {
            background-color: #e55a2b;
            border-color: #e55a2b;
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
        .estado-en-proceso {
    background-color: #ffc107;
    color: #000000;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 0.85em;
    font-weight: bold;
    border: 2px solid #e0a800;
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
}
.estado-terminada {
    background-color: #28a745;
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 0.85em;
    font-weight: bold;
    border: 2px solid #1e7e34;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}
        .badge-elaboracion {
            background-color: #17a2b8;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
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
        .badge-cantidad {
            background-color: #17a2b8;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-costo {
            background-color: #28a745;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .badge-semielaborado {
            background-color: #6f42c1;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .descripcion-corta {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .detalles-section {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffd700;
        }
        .detalles-table {
            background-color: #2d2d2d;
            border-radius: 8px;
            overflow: hidden;
        }
        .detalles-table th {
            background-color: #444;
            color: #ffd700;
        }
        .detalles-table td {
            background-color: #2d2d2d;
            color: #ffffff;
            border-color: #444;
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
        .badge-automatico {
            background-color: #28a745;
        }
        .badge-manual {
            background-color: #6c757d;
        }
        .nav-tabs .nav-link {
            background-color: #2d2d2d;
            color: #ffd700;
            border: 1px solid #444;
        }
        .nav-tabs .nav-link.active {
            background-color: #ffd700;
            color: #000000;
            border-color: #ffd700;
            font-weight: bold;
        }
        .nav-tabs .nav-link:hover {
            background-color: #e6c200;
            color: #000000;
            border-color: #e6c200;
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
            .descripcion-corta {
                max-width: 150px;
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
        <div class="production-section">
            <h3>🏭 Gestión de Producción</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?= $mensaje_exito ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarProduccionModal">
                        ➕ Nueva Producción
                    </button>
                    <a href="recetai.php" class="btn btn-recetas ms-2">
                        🍮 Gestionar Recetas
                    </a>
                </div>
                <div>
                    <a href="semielaboradoi.php" class="btn btn-semielaborados">
                        ⚙️ Ver Semielaborados
                    </a>
                </div>
            </div>
            
            <div class="search-box">
                <div class="search-header">
                    <h5 class="mb-0">🔍 Buscar Producciones</h5>
                    <?php if ($busqueda_activa): ?>
                        <span class="badge bg-warning text-dark">Búsqueda Activa</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($busqueda_activa): ?>
                    <div class="search-results">
                        <strong>Resultados de búsqueda para:</strong> "<?= htmlspecialchars($termino_busqueda) ?>"
                        <span class="badge bg-primary ms-2"><?= $total_registros ?> producción(es) encontrada(s)</span>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="produccioni.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar_nombre" 
                               placeholder="Buscar por semielaborado o observaciones..." 
                               value="<?= htmlspecialchars($termino_busqueda) ?>">
                        <button class="btn btn-buscar" type="submit">Buscar</button>
                    </div>
                </form>
                
                <?php if ($busqueda_activa): ?>
                    <div class="mt-3">
                        <a href="produccioni.php" class="btn btn-ver-todos">
                            🔄 Ver Todas las Producciones
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Semielaborado</th>
                            <th>Cantidad</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($producciones)): ?>
                            <?php foreach ($producciones as $produccion): ?>
                                <?php 
                                $claseEstado = 'estado-' . strtolower(str_replace(' ', '-', $produccion['estado']));
                                $estaTerminada = ($produccion['estado'] === 'Terminada' || $produccion['estado'] === 'Cancelada');
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($produccion['nombre_semielaborado'])): ?>
                                            <span class="badge-semielaborado">
                                                🍮 <?= htmlspecialchars($produccion['nombre_semielaborado']) ?>
                                                <?php if ($produccion['uso_receta'] === 't' || $produccion['uso_receta'] === true): ?>
                                                    <span class="badge bg-success ms-1" title="Usó receta automática">🍮</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin semielaborado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-cantidad">
                                            📦 <?= htmlspecialchars($produccion['cantidad'] ?? 0) ?> unidades
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($produccion['fecha_inicio'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($produccion['fecha_fin']): ?>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($produccion['fecha_fin'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-warning">En curso</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $claseEstado ?>">
                                            <?= htmlspecialchars($produccion['estado'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (!$estaTerminada): ?>
                                                <button class='btn btn-finalizar me-1' data-bs-toggle='modal' data-bs-target='#finalizarProduccionModal' 
                                                  data-id='<?= $produccion["id_produccion"] ?? '' ?>'
                                                  data-semielaborado='<?= htmlspecialchars($produccion["nombre_semielaborado"] ?? 'Producción') ?>'>
                                                  ✅ Finalizar
                                                </button>
                                            <?php else: ?>
                                                <button class='btn btn-finalizar me-1' disabled title='Producción ya finalizada'>
                                                  ✅ Finalizada
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class='btn btn-editar' data-bs-toggle='modal' data-bs-target='#verDetallesModal' 
                                              data-id='<?= $produccion["id_produccion"] ?? '' ?>'>📊 Detalles</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='6' class='text-center py-4'>
                                    <div class="text-muted">
                                        <h5>No se encontraron producciones</h5>
                                        <?php if ($busqueda_activa): ?>
                                            <p>Intenta con otros términos de búsqueda</p>
                                            <a href="produccioni.php" class="btn btn-ver-todos mt-2">
                                                🔄 Ver Todas las Producciones
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación de producciones">
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
                                Mostrando <strong><?= count($producciones) ?></strong> de <strong><?= $total_registros ?></strong> producciones - 
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

    <div class="modal fade" id="agregarProduccionModal" tabindex="-1" aria-labelledby="agregarProduccionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProduccionModalLabel">➕ Nueva Producción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="produccioni.php" id="formAgregarProduccion">
                        <div class="mb-3">
                            <label for="nuevo_semielaborado" class="form-label required-field">Semielaborado</label>
                            <select class="form-select" id="nuevo_semielaborado" name="nuevo_semielaborado" required>
                                <option value="">Seleccionar semielaborado...</option>
                                <?php if (!empty($semielaborados)): ?>
                                    <?php foreach ($semielaborados as $semielaborado): ?>
                                        <option value="<?= $semielaborado['id_semielaborado'] ?>">
                                            🍮 <?= htmlspecialchars($semielaborado['nombre']) ?> 
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay semielaborados disponibles</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($semielaborados)): ?>
                                <div class="alert alert-warning mt-2">
                                    <small>No se encontraron semielaborados en la base de datos.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="nueva_cantidad" class="form-label required-field">Cantidad a Producir</label>
                            <input type="number" class="form-control" id="nueva_cantidad" name="nueva_cantidad" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="usar_receta" name="usar_receta" value="1">
                                <label class="form-check-label" for="usar_receta">
                                    🍮 Usar receta automática
                                </label>
                            </div>
                            <small class="text-muted" id="texto_receta">
                                Los ingredientes se calcularán y descontarán automáticamente del stock
                            </small>
                        </div>

                        <div id="info_receta" class="receta-info d-none">
                            <h6>📋 Receta del Semielaborado</h6>
                            <div id="detalles_receta">
                            </div>
                            <small class="text-muted">* Los ingredientes se descontarán automáticamente del stock</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nueva_observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="nueva_observaciones" name="nueva_observaciones" rows="3" placeholder="Opcional - notas sobre esta producción"></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" id="btnCrearProduccion">Iniciar Producción</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="finalizarProduccionModal" tabindex="-1" aria-labelledby="finalizarProduccionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarProduccionModalLabel">✅ Finalizar Producción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas finalizar la producción de <strong id="produccion_finalizar" class="text-warning"></strong>?</p>
                    <p class="text-muted"><small>Al finalizar, se incrementará el stock del semielaborado.</small></p>
                    <form method="post" action="produccioni.php">
                        <input type="hidden" name="id_produccion" id="id_produccion_finalizar">
                        <input type="hidden" name="finalizar_produccion" value="1">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-fill">✅ Sí, Finalizar</button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="verDetallesModal" tabindex="-1" aria-labelledby="verDetallesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verDetallesModalLabel">📊 Detalles de Producción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesContenido">
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

    <div class="modal fade" id="agregarIngredienteModal" tabindex="-1" aria-labelledby="agregarIngredienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarIngredienteModalLabel">🧂 Agregar Ingrediente Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="produccioni.php">
                        <input type="hidden" name="id_produccion_ingrediente" id="id_produccion_ingrediente">
                        <div class="mb-3">
                            <label for="id_ingrediente" class="form-label required-field">Ingrediente</label>
                            <select class="form-select" id="id_ingrediente" name="id_ingrediente" required>
                                <option value="">Seleccionar ingrediente...</option>
                                <?php foreach ($ingredientes as $ingrediente): ?>
                                    <option value="<?= $ingrediente['id_ingrediente'] ?>"><?= htmlspecialchars($ingrediente['nombre']) ?> (<?= htmlspecialchars($ingrediente['unidad_medida']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad_ingrediente" class="form-label required-field">Cantidad Usada</label>
                            <input type="number" class="form-control" id="cantidad_ingrediente" name="cantidad_ingrediente" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="costo_ingrediente" class="form-label required-field">Costo Unitario</label>
                            <input type="number" class="form-control" id="costo_ingrediente" name="costo_ingrediente" min="0" step="0.01" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal" name="agregar_ingrediente">Agregar Ingrediente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $('#finalizarProduccionModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var semielaborado = button.data('semielaborado');
            
            $(this).find('#id_produccion_finalizar').val(id);
            $(this).find('#produccion_finalizar').text(semielaborado);
        });

        $('#verDetallesModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var idProduccion = button.data('id');
            
            $.ajax({
                url: 'cargar_detalles_produccion.php',
                type: 'GET',
                data: { id_produccion: idProduccion },
                success: function(response) {
                    $('#detallesContenido').html(response);
                },
                error: function() {
                    $('#detallesContenido').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                }
            });
        });

        function cargarReceta() {
            var idSemielaborado = $('#nuevo_semielaborado').val();
            
            if (idSemielaborado) {
                $.ajax({
                    url: 'cargar_receta_semielaborado.php',
                    type: 'GET',
                    data: { 
                        id_semielaborado: idSemielaborado
                    },
                    success: function(response) {
                        $('#detalles_receta').html(response);
                        if (response.trim() !== '' && !response.includes('alert-warning')) {
                            $('#info_receta').removeClass('d-none');
                        } else {
                            $('#info_receta').addClass('d-none');
                        }
                    },
                    error: function() {
                        $('#detalles_receta').html('<div class="alert alert-warning">No se pudo cargar la receta</div>');
                        $('#info_receta').removeClass('d-none');
                    }
                });
            } else {
                $('#info_receta').addClass('d-none');
            }
        }

        $('#nuevo_semielaborado').on('change', cargarReceta);

        $('#usar_receta').on('change', function() {
            if ($(this).is(':checked')) {
                $('#btnCrearProduccion').html('🍮 Crear Producción con Receta');
                $('#texto_receta').addClass('text-success').removeClass('text-muted');
            } else {
                $('#btnCrearProduccion').html('Iniciar Producción');
                $('#texto_receta').removeClass('text-success').addClass('text-muted');
            }
        });

        $('#agregarIngredienteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var idProduccion = button.data('id');
            $(this).find('#id_produccion_ingrediente').val(idProduccion);
        });

        // Validación formulario agregar
        $('#formAgregarProduccion').on('submit', function(e) {
            let isValid = true;
            
            if ($('#nuevo_semielaborado').val() === '') {
                isValid = false;
                alert('Por favor selecciona un semielaborado');
            }
            
            if ($('#nueva_cantidad').val() < 1) {
                isValid = false;
                alert('La cantidad debe ser mayor a 0');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        $(document).ready(function() {
            $('input[name="buscar_nombre"]').focus();
        });

        document.getElementById('selectorPagina').addEventListener('change', function() {
            const pagina = this.value;
            const baseUrl = 'produccioni.php';
            const parametros = [];
            
            <?php if ($busqueda_activa): ?>
                parametros.push('buscar=' + encodeURIComponent('<?= $termino_busqueda ?>'));
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