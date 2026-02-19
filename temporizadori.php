<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'temporizadorf.php';
require_once 'conexion.php';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $vista_inactivos = (isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] == '1') ? true : false;
    $parametro_vista = $vista_inactivos ? '&ver_inactivos=1' : '';
    
    if ($vista_inactivos) {
        $temporizadores = TemporizadorOlla::obtenerTemporizadoresInactivos();
    } else {
        $temporizadores = TemporizadorOlla::obtenerTemporizadoresActivos();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_editar'])) {
        $temporizadorData = [
            'id_olla' => $_POST['id_olla_editar'],
            'nombre' => $_POST['nombre_editar'],
            'duracion' => $_POST['duracion_editar'],
            'tipo_producto' => $_POST['tipo_producto_editar'],
            'activo' => isset($_POST['activo_editar']) ? 'true' : 'false'
        ];
        
        $resultado = TemporizadorOlla::actualizarTemporizador($_POST['id_editar'], $temporizadorData);
        if ($resultado) {
            header("Location: temporizadori.php" . $parametro_vista);
            exit();
        } else {
            $error = "Error al editar el temporizador";
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_nombre'])) {
        $temporizadorData = [
            'id_olla' => $_POST['nuevo_id_olla'],
            'nombre' => $_POST['nuevo_nombre'],
            'duracion' => $_POST['nuevo_duracion'],
            'tipo_producto' => $_POST['nuevo_tipo_producto'],
            'activo' => isset($_POST['nuevo_activo']) ? 'true' : 'false'
        ];
        
        $resultado = TemporizadorOlla::crearTemporizador($temporizadorData);
        if ($resultado) {
            header("Location: temporizadori.php" . $parametro_vista);
            exit();
        } else {
            $error = "Error al agregar el temporizador";
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_eliminar'])) {
        $resultado = TemporizadorOlla::eliminarTemporizador($_POST['id_eliminar']);
        if ($resultado) {
            header("Location: temporizadori.php" . $parametro_vista);
            exit();
        } else {
            $error = "Error al eliminar el temporizador";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reactivar'])) {
        $resultado = TemporizadorOlla::reactivarTemporizador($_POST['id_reactivar']);
        if ($resultado) {
            header("Location: temporizadori.php" . $parametro_vista);
            exit();
        } else {
            $error = "Error al reactivar el temporizador";
        }
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Temporizadores - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .temporizadores-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .temporizadores-section h3 {
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
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        .btn-agregar:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        
        .btn-gestionar-ollas {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        .btn-gestionar-ollas:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: #ffffff;
        }
        
        .btn-config-empaques {
            background-color: #ff6b6b;
            border-color: #ff6b6b;
            color: #ffffff;
            font-weight: bold;
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        .btn-config-empaques:hover {
            background-color: #ff5252;
            border-color: #ff5252;
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
        
        .btn-iniciar {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-iniciar:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        
        .btn-detener {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-detener:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
        
        .btn-reiniciar {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-reiniciar:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: #ffffff;
        }
        
        .temporizador-card {
            background: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .temporizador-card.inactivo {
            background: #fff5f5;
            border-color: #dc3545;
            opacity: 0.8;
        }
        
        .temporizador-card.ejecutando {
            border-color: #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
        }
        
        .temporizador-card.completado {
            border-color: #dc3545;
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.3);
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.9; }
            100% { opacity: 1; }
        }
        
        .temporizador-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffd700;
        }
        
        .temporizador-titulo {
            color: #2d2d2d;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .temporizador-olla {
            background: #17a2b8;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .tiempo-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d2d2d;
            text-align: center;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            transition: all 0.3s ease;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        
        .tiempo-display.ejecutando {
            color: #28a745;
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .tiempo-display.advertencia {
            color: #ffc107;
            border-color: #ffc107;
            background: #fffbf0;
            animation: blink 0.5s infinite;
        }
        
        .tiempo-display.completado {
            color: #dc3545;
            border-color: #dc3545;
            background: #fff5f5;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .botones-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        .botones-completado {
            grid-template-columns: 1fr;
        }
        
        .progress {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 1s linear;
        }
        
        .estado-temporizador {
            text-align: center;
            font-size: 1rem;
            margin-bottom: 10px;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        .estado-inactivo {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .estado-ejecutando {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .estado-completado {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .hidden {
            display: none !important;
        }
        
        .modal-header {
            background-color: #2d2d2d;
            border-bottom: 2px solid #ffd700;
            color: #ffd700;
        }
        
        .modal-content {
            background-color: #2d2d2d;
            color: #ffffff;
            border-radius: 15px;
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
        
        .form-control, .form-select {
            background-color: #1a1a1a;
            border: 1px solid #444;
            color: #ffffff;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: #2d2d2d;
            border-color: #ffd700;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        
        .form-label {
            color: #ffd700;
            font-weight: bold;
        }
        
        .form-text {
            color: #ffd700 !important;
        }
        
        .badge-cantidad {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        
        .alert {
            margin: 10px 0;
            border-radius: 8px;
        }
        
        .info-producto {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ffd700;
        }
        
        .info-producto strong {
            color: #2d2d2d;
        }
        
        .vista-info {
            background-color: #2d2d2d;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ffd700;
            color: #ffffff;
        }
        
        .estado-activo {
            color: #28a745;
            font-weight: bold;
        }
        
        .estado-inactivo-badge {
            color: #dc3545;
            font-weight: bold;
        }
        
        .option-disponible {
            color: #28a745;
            font-weight: bold;
        }
        
        .option-ocupada {
            color: #dc3545;
            font-style: italic;
        }
        
        .option-info {
            font-size: 0.8em;
            opacity: 0.8;
            margin-left: 5px;
        }
        
        .notification-ciclo {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(90deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            font-weight: bold;
            animation: slideInRight 0.3s ease-out;
            border-left: 5px solid #ffd700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="temporizadores-section">
            <h3>⏰ Panel de Temporizadores</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarTemporizadorModal">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Temporizador
                    </button>
                    <a href="ollai.php" class="btn btn-gestionar-ollas ms-2">
                        <i class="fas fa-pot-food me-2"></i>🍲Gestionar Ollas
                    </a>
                    <a href="config_empaques.php" class="btn btn-config-empaques ms-2">
                        <i class="fas fa-cog me-2"></i>Configurar Empaques
                    </a>
                </div>
                
                <div>
                    <?php if ($vista_inactivos): ?>
                        <a href="temporizadori.php" class="btn btn-ver-activos">
                            👁️ Ver Temporizadores Activos
                        </a>
                    <?php else: ?>
                        <a href="temporizadori.php?ver_inactivos=1" class="btn btn-ver-inactivos">
                            👁️ Ver Temporizadores Inactivos
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($vista_inactivos): ?>
                <div class="vista-info">
                    <strong>⚠️ Vista de temporizadores inactivos</strong>
                    <p class="mb-0"><small>Estás viendo los temporizadores que han sido dados de baja.</small></p>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php if (!empty($temporizadores)): ?>
                    <?php foreach ($temporizadores as $temp): ?>
                        <?php 
                        $estaInactivo = ($temp['baja'] === 't' || $temp['baja'] === true || $temp['baja'] === 'true' || $temp['baja'] === 1);
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="temporizador-card <?= $estaInactivo ? 'inactivo' : '' ?>" id="card-<?= $temp['id_temporizador'] ?>">
                                
                                <div class="temporizador-header">
                                    <h3 class="temporizador-titulo">
                                        <?= htmlspecialchars($temp['nombre'] ?? 'Sin nombre') ?>
                                    </h3>
                                    <span class="temporizador-olla">
                                        Olla <?= htmlspecialchars($temp['numero_olla'] ?? '0') ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="estado-temporizador estado-inactivo" id="estado-<?= $temp['id_temporizador'] ?>">
                                        ⏰ Listo para iniciar
                                    </div>
                                    <span class="<?= $estaInactivo ? 'estado-inactivo-badge' : 'estado-activo' ?>">
                                        <?php if ($estaInactivo): ?>
                                            ⛔ Inactivo
                                        <?php else: ?>
                                            ✅ Activo
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="tiempo-display" id="tiempo-<?= $temp['id_temporizador'] ?>">
                                    <?= htmlspecialchars($temp['duracion'] ?? '00:00:00') ?>
                                </div>
                                
                                <div class="progress">
                                    <div class="progress-bar" id="progress-<?= $temp['id_temporizador'] ?>" style="width: 0%"></div>
                                </div>
                                
                                <div class="info-producto">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <strong>Producto:</strong><br>
                                            <span class="badge-cantidad">
                                                <?= htmlspecialchars($temp['tipo_producto'] ?? 'Flan') ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Capacidad:</strong><br>
                                            <span class="badge-cantidad">
                                                <?= htmlspecialchars($temp['capacidad'] ?? '0') ?> flanes
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($estaInactivo && $temp['fechabaja']): ?>
                                        <div class="mt-2 text-center">
                                            <small class="text-muted">
                                                <strong>Fecha de baja:</strong> <?= htmlspecialchars($temp['fechabaja']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($estaInactivo): ?>
                                    <div class="botones-container">
                                        <form method="post" action="temporizadori.php" class="d-grid">
                                            <input type="hidden" name="id_reactivar" value="<?= $temp['id_temporizador'] ?>">
                                            <?php if ($vista_inactivos): ?>
                                                <input type="hidden" name="ver_inactivos" value="1">
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-reactivar">🔄 Reactivar</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="botones-container" id="botones-normal-<?= $temp['id_temporizador'] ?>">
                                        <button class="btn btn-iniciar" id="iniciar-<?= $temp['id_temporizador'] ?>" 
                                                onclick="iniciarTemporizador(<?= $temp['id_temporizador'] ?>, '<?= $temp['duracion'] ?>')">
                                            <i class="fas fa-play me-1"></i> INICIAR
                                        </button>
                                        <button class="btn btn-detener" id="detener-<?= $temp['id_temporizador'] ?>" 
                                                onclick="detenerTemporizador(<?= $temp['id_temporizador'] ?>, '<?= $temp['duracion'] ?>')" disabled>
                                            <i class="fas fa-stop me-1"></i> DETENER
                                        </button>
                                        <button class="btn btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editarTemporizadorModal"
                                                data-id="<?= $temp['id_temporizador'] ?>"
                                                data-nombre="<?= htmlspecialchars($temp['nombre']) ?>"
                                                data-duracion="<?= htmlspecialchars($temp['duracion']) ?>"
                                                data-tipo-producto="<?= htmlspecialchars($temp['tipo_producto']) ?>"
                                                data-id-olla="<?= $temp['id_olla'] ?>"
                                                data-activo="<?= $temp['activo'] ? 'true' : 'false' ?>">
                                            <i class="fas fa-edit me-1"></i> EDITAR
                                        </button>
                                        <button class="btn btn-eliminar" data-bs-toggle="modal" data-bs-target="#eliminarTemporizadorModal"
                                                data-id="<?= $temp['id_temporizador'] ?>"
                                                data-nombre="<?= htmlspecialchars($temp['nombre']) ?>">
                                            <i class="fas fa-trash me-1"></i> ELIMINAR
                                        </button>
                                    </div>
                                    
                                    <div class="botones-container botones-completado hidden" id="botones-completado-<?= $temp['id_temporizador'] ?>">
                                        <button class="btn btn-reiniciar" 
                                                onclick="reiniciarTemporizador(<?= $temp['id_temporizador'] ?>, '<?= $temp['duracion'] ?>')">
                                            <i class="fas fa-redo me-1"></i> REINICIAR TEMPORIZADOR
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-warning">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <h4>
                                <?php if ($vista_inactivos): ?>
                                    No hay temporizadores inactivos
                                <?php else: ?>
                                    No hay temporizadores
                                <?php endif; ?>
                            </h4>
                            <p>
                                <?php if ($vista_inactivos): ?>
                                    Todos los temporizadores están activos.
                                <?php else: ?>
                                    Agrega tu primer temporizador para comenzar
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agregarTemporizadorModal" tabindex="-1" aria-labelledby="agregarTemporizadorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarTemporizadorModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Temporizador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="temporizadori.php<?= $parametro_vista ?>" id="formAgregarTemporizador">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="nuevo_id_olla" class="form-label">
                                <i class="fas fa-pot-food me-1"></i>Olla *
                            </label>
                            <select class="form-select" id="nuevo_id_olla" name="nuevo_id_olla" required>
                                <option value="">Seleccionar olla...</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Las ollas en <span class="option-disponible">verde</span> están disponibles. 
                                Las ollas en <span class="option-ocupada">rojo</span> ya tienen un temporizador activo.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_nombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre del Temporizador *
                            </label>
                            <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_duracion" class="form-label">
                                <i class="fas fa-clock me-1"></i>Duración *
                            </label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small">Horas</label>
                                    <input type="number" class="form-control" id="nuevo_duracion_horas" min="0" value="0" max="23" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Minutos</label>
                                    <input type="number" class="form-control" id="nuevo_duracion_minutos" min="0" value="30" max="59" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Segundos</label>
                                    <input type="number" class="form-control" id="nuevo_duracion_segundos" min="0" value="0" max="59" required>
                                </div>
                            </div>
                            <input type="hidden" id="nuevo_duracion" name="nuevo_duracion">
                            <div class="form-text text-warning">
                                <i class="fas fa-info-circle me-1"></i>Formato: HH:MM:SS
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_tipo_producto" class="form-label">
                                <i class="fas fa-box me-1"></i>Tipo de Producto
                            </label>
                            <input type="text" class="form-control" id="nuevo_tipo_producto" name="nuevo_tipo_producto" value="Flan">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="nuevo_activo" name="nuevo_activo" checked>
                            <label class="form-check-label" for="nuevo_activo">
                                <i class="fas fa-power-off me-1"></i>Temporizador activo
                            </label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal">
                                <i class="fas fa-save me-2"></i>Agregar Temporizador
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarTemporizadorModal" tabindex="-1" aria-labelledby="editarTemporizadorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarTemporizadorModalLabel">
                        <i class="fas fa-edit me-2"></i>Editar Temporizador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="temporizadori.php<?= $parametro_vista ?>" id="formEditarTemporizador">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <input type="hidden" name="id_olla_editar" id="id_olla_editar">
                        <?php if ($vista_inactivos): ?>
                            <input type="hidden" name="ver_inactivos" value="1">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nombre_editar" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre del Temporizador *
                            </label>
                            <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duracion_editar" class="form-label">
                                <i class="fas fa-clock me-1"></i>Duración *
                            </label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small">Horas</label>
                                    <input type="number" class="form-control" id="duracion_editar_horas" min="0" max="23" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Minutos</label>
                                    <input type="number" class="form-control" id="duracion_editar_minutos" min="0" max="59" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Segundos</label>
                                    <input type="number" class="form-control" id="duracion_editar_segundos" min="0" max="59" required>
                                </div>
                            </div>
                            <input type="hidden" id="duracion_editar" name="duracion_editar">
                            <div class="form-text text-warning">
                                <i class="fas fa-info-circle me-1"></i>Formato: HH:MM:SS
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_producto_editar" class="form-label">
                                <i class="fas fa-box me-1"></i>Tipo de Producto
                            </label>
                            <input type="text" class="form-control" id="tipo_producto_editar" name="tipo_producto_editar">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activo_editar" name="activo_editar">
                            <label class="form-check-label" for="activo_editar">
                                <i class="fas fa-power-off me-1"></i>Temporizador activo
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-modal">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eliminarTemporizadorModal" tabindex="-1" aria-labelledby="eliminarTemporizadorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarTemporizadorModalLabel">
                        <i class="fas fa-trash me-2"></i>Eliminar Temporizador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el temporizador <strong id="temporizador_eliminar" class="text-warning"></strong>?</p>
                    <p class="text-muted"><small>Esta acción marcará al temporizador como inactivo (eliminación lógica).</small></p>
                    <form method="post" action="temporizadori.php<?= $parametro_vista ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const temporizadoresActivos = new Map();

        function duracionASegundos(duracion) {
            const partes = duracion.split(':');
            const horas = parseInt(partes[0]) || 0;
            const minutos = parseInt(partes[1]) || 0;
            const segundos = parseInt(partes[2]) || 0;
            return horas * 3600 + minutos * 60 + segundos;
        }

        function segundosATiempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;
            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        function descomponerDuracion(duracion) {
            if (!duracion) return { horas: 0, minutos: 0, segundos: 0 };
            
            const partes = duracion.split(':');
            return {
                horas: parseInt(partes[0]) || 0,
                minutos: parseInt(partes[1]) || 0,
                segundos: parseInt(partes[2]) || 0
            };
        }

        function registrarCicloEnBD(id_temporizador) {
            console.log(`📊 Registrando ciclo para temporizador ${id_temporizador}...`);
            
            const formData = new FormData();
            formData.append('id_temporizador', id_temporizador);
            
            fetch('temporizadorf.php?action=registrar_ciclo', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log(`✅ Ciclo registrado: ${data.message}`);
                    console.log(`📈 Ciclos actuales: ${data.ciclos_actuales}`);
                    
                } else {
                    console.error(`❌ Error al registrar ciclo: ${data.message}`);
                    mostrarNotificacionError(data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error de red al registrar ciclo:', error);
                mostrarNotificacionError('Error de conexión con el servidor');
            });
        }

        function mostrarNotificacionCiclo(ciclos) {
            const notificacionesAnteriores = document.querySelectorAll('.notification-ciclo');
            notificacionesAnteriores.forEach(notif => {
                notif.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (notif.parentNode) {
                        notif.parentNode.removeChild(notif);
                    }
                }, 300);
            });
            
            const notification = document.createElement('div');
            notification.className = 'notification-ciclo';
            notification.innerHTML = `
                <i class="fas fa-check-circle fa-lg"></i>
                <div>
                    <strong>¡Ciclo registrado!</strong><br>
                    <small>Total: ${ciclos} ciclos completados</small>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        function mostrarNotificacionError(mensaje) {
            const notification = document.createElement('div');
            notification.className = 'notification-ciclo';
            notification.style.background = 'linear-gradient(90deg, #dc3545, #e4606d)';
            notification.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-lg"></i>
                <div>
                    <strong>Error al registrar ciclo</strong><br>
                    <small>${mensaje}</small>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        function reproducirAlarma() {
            console.log('🔊 Reproduciendo alarma...');
            
            try {
                const audio = new Audio();
                
                const audioSources = [
                    'https://assets.mixkit.co/sfx/download/mixkit-alarm-digital-clock-beep-989.mp3',
                    'https://assets.mixkit.co/sfx/download/mixkit-classic-alarm-995.mp3',
                    'https://assets.mixkit.co/sfx/download/mixkit-bell-notification-933.mp3'
                ];
                
                const randomSource = audioSources[Math.floor(Math.random() * audioSources.length)];
                audio.src = randomSource;
                
                audio.volume = 0.7;
                
                audio.play().then(() => {
                    console.log('✅ Alarma sonando...');
                    
                    setTimeout(() => {
                        audio.pause();
                        audio.currentTime = 0;
                        console.log('⏹️ Alarma detenida');
                    }, 3000);
                    
                }).catch(error => {
                    console.log('❌ Error al reproducir audio:', error.name);
                    
                    intentarWebAudioAPI();
                });
                
            } catch (error) {
                console.log('❌ Error con el sistema de audio:', error);
                mostrarNotificacion();
            }
        }

        function intentarWebAudioAPI() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) {
                    throw new Error('Web Audio API no disponible');
                }
                
                const audioContext = new AudioContext();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.type = 'sawtooth';
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.5);
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 1.0);
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 2);
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 2);
                
                console.log('🎵 Alarma Web Audio API activada');
                
            } catch (error) {
                console.log('Error con Web Audio API:', error);
                mostrarNotificacion();
            }
        }

        function mostrarNotificacion() {
            console.log('📢 Mostrando notificación...');
            
            if ("Notification" in window && Notification.permission === "granted") {
                try {
                    new Notification("⏰ ¡Temporizador Completado!", {
                        body: "El tiempo ha terminado",
                        icon: "https://cdn-icons-png.flaticon.com/512/3208/3208720.png"
                    });
                } catch (error) {
                    console.log('Error mostrando notificación:', error);
                }
            }
        }

        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        function iniciarTemporizador(id, duracionOriginal) {
            if (temporizadoresActivos.has(id)) {
                return;
            }

            const card = document.getElementById(`card-${id}`);
            const tiempoDisplay = document.getElementById(`tiempo-${id}`);
            const progressBar = document.getElementById(`progress-${id}`);
            const estado = document.getElementById(`estado-${id}`);
            const botonesNormal = document.getElementById(`botones-normal-${id}`);
            const botonesCompletado = document.getElementById(`botones-completado-${id}`);
            const btnIniciar = document.getElementById(`iniciar-${id}`);
            const btnDetener = document.getElementById(`detener-${id}`);

            const duracionTotal = duracionASegundos(duracionOriginal);
            let tiempoRestante = duracionTotal;

            card.classList.add('ejecutando');
            card.classList.remove('completado');
            tiempoDisplay.classList.add('ejecutando');
            tiempoDisplay.classList.remove('advertencia', 'completado');
            estado.textContent = '⏰ Ejecutándose...';
            estado.className = 'estado-temporizador estado-ejecutando';
            
            btnIniciar.disabled = true;
            btnDetener.disabled = false;
            
            botonesNormal.classList.remove('hidden');
            botonesCompletado.classList.add('hidden');

            const temporizador = setInterval(() => {
                tiempoRestante--;
                
                tiempoDisplay.textContent = segundosATiempo(tiempoRestante);
                
                const progreso = ((duracionTotal - tiempoRestante) / duracionTotal) * 100;
                progressBar.style.width = `${progreso}%`;
                
                if (tiempoRestante <= 60 && tiempoRestante > 10) {
                    tiempoDisplay.classList.remove('ejecutando');
                    tiempoDisplay.classList.add('advertencia');
                    estado.textContent = '⚠️ Poco tiempo restante';
                } else if (tiempoRestante <= 10 && tiempoRestante > 0) {
                    estado.textContent = '🚨 ¡Terminando!';
                }
                
                if (tiempoRestante <= 0) {
                    clearInterval(temporizador);
                    temporizadoresActivos.delete(id);
                    
                    card.classList.remove('ejecutando');
                    card.classList.add('completado');
                    tiempoDisplay.classList.remove('advertencia');
                    tiempoDisplay.classList.add('completado');
                    progressBar.style.width = '100%';
                    estado.textContent = '✅ ¡COMPLETADO!';
                    estado.className = 'estado-temporizador estado-completado';
                    
                    botonesNormal.classList.add('hidden');
                    botonesCompletado.classList.remove('hidden');
                    
                    console.log(`🚨 Temporizador ${id} completado, reproduciendo alarma...`);
                    reproducirAlarma();
                    
                    console.log(`📊 Registrando ciclo en BD para temporizador ${id}...`);
                    registrarCicloEnBD(id);
                    
                    if (navigator.vibrate) {
                        navigator.vibrate([300, 100, 300, 100, 300]);
                    }
                }
            }, 1000);

            temporizadoresActivos.set(id, temporizador);
        }

        function detenerTemporizador(id, duracionOriginal) {
            if (!temporizadoresActivos.has(id)) {
                return;
            }

            clearInterval(temporizadoresActivos.get(id));
            temporizadoresActivos.delete(id);
            
            reiniciarInterfaz(id, duracionOriginal);
        }

        function reiniciarTemporizador(id, duracionOriginal) {
            console.log(`🔄 Reiniciando temporizador ${id}...`);
            
            if (temporizadoresActivos.has(id)) {
                clearInterval(temporizadoresActivos.get(id));
                temporizadoresActivos.delete(id);
            }
            
            reiniciarInterfaz(id, duracionOriginal);
        }

        function reiniciarInterfaz(id, duracionOriginal) {
            const card = document.getElementById(`card-${id}`);
            const tiempoDisplay = document.getElementById(`tiempo-${id}`);
            const progressBar = document.getElementById(`progress-${id}`);
            const estado = document.getElementById(`estado-${id}`);
            const botonesNormal = document.getElementById(`botones-normal-${id}`);
            const botonesCompletado = document.getElementById(`botones-completado-${id}`);
            const btnIniciar = document.getElementById(`iniciar-${id}`);
            const btnDetener = document.getElementById(`detener-${id}`);

            card.classList.remove('ejecutando', 'completado');
            tiempoDisplay.classList.remove('ejecutando', 'advertencia', 'completado');
            tiempoDisplay.textContent = duracionOriginal;
            progressBar.style.width = '0%';
            estado.textContent = '⏰ Listo para iniciar';
            estado.className = 'estado-temporizador estado-inactivo';
            
            btnIniciar.disabled = false;
            btnDetener.disabled = true;
            
            botonesNormal.classList.remove('hidden');
            botonesCompletado.classList.add('hidden');
        }

        function actualizarDuracionNuevo() {
            const horas = document.getElementById('nuevo_duracion_horas').value.padStart(2, '0');
            const minutos = document.getElementById('nuevo_duracion_minutos').value.padStart(2, '0');
            const segundos = document.getElementById('nuevo_duracion_segundos').value.padStart(2, '0');
            
            document.getElementById('nuevo_duracion').value = `${horas}:${minutos}:${segundos}`;
        }

        function actualizarDuracionEditar() {
            const horas = document.getElementById('duracion_editar_horas').value.padStart(2, '0');
            const minutos = document.getElementById('duracion_editar_minutos').value.padStart(2, '0');
            const segundos = document.getElementById('duracion_editar_segundos').value.padStart(2, '0');
            
            document.getElementById('duracion_editar').value = `${horas}:${minutos}:${segundos}`;
        }

        function cargarOllasConEstado() {
            console.log('Cargando ollas con estado...');
            
            fetch('temporizadorf.php?action=get_ollas_con_estado')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(ollas => {
                    console.log('Ollas recibidas:', ollas);
                    
                    const selectNuevo = document.getElementById('nuevo_id_olla');
                    
                    selectNuevo.innerHTML = '<option value="">Seleccionar olla...</option>';
                    
                    ollas.forEach(olla => {
                        const option = document.createElement('option');
                        option.value = olla.id_olla;
                        
                        let textoOlla = `Olla ${olla.numero_olla}`;
                        
                        if (olla.capacidad) {
                            textoOlla += ` (Capacidad: ${olla.capacidad} flan${olla.capacidad > 1 ? 'es' : ''})`;
                        }
                        
                        if (olla.tiene_temporizador_activo) {
                            option.className = 'option-ocupada';
                            textoOlla += ` - ⚠️ Ocupada`;
                            
                            if (olla.nombre_temporizador) {
                                textoOlla += ` por: ${olla.nombre_temporizador}`;
                            } else {
                                textoOlla += ` (tiene temporizador activo)`;
                            }
                            
                            option.disabled = true;
                        } else {
                            option.className = 'option-disponible';
                            textoOlla += ' - ✅ Disponible';
                        }
                        
                        option.textContent = textoOlla;
                        selectNuevo.appendChild(option);
                    });
                    
                    if (ollas.length === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No hay ollas disponibles';
                        option.disabled = true;
                        selectNuevo.appendChild(option);
                    }
                    
                    console.log('Ollas cargadas correctamente');
                })
                .catch(error => {
                    console.error('Error cargando ollas:', error);
                    
                    const selectNuevo = document.getElementById('nuevo_id_olla');
                    selectNuevo.innerHTML = '<option value="">Error al cargar ollas</option>';
                });
        }

        $('#eliminarTemporizadorModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var temporizador = button.data('nombre');
            $(this).find('#id_eliminar').val(id);
            $(this).find('#temporizador_eliminar').text(temporizador);
        });

        $('#editarTemporizadorModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            
            modal.find('#id_editar').val(button.data('id'));
            modal.find('#id_olla_editar').val(button.data('id-olla'));
            modal.find('#nombre_editar').val(button.data('nombre'));
            modal.find('#tipo_producto_editar').val(button.data('tipo-producto'));
            modal.find('#activo_editar').prop('checked', button.data('activo') === 'true');
            
            const duracion = descomponerDuracion(button.data('duracion'));
            modal.find('#duracion_editar_horas').val(duracion.horas);
            modal.find('#duracion_editar_minutos').val(duracion.minutos);
            modal.find('#duracion_editar_segundos').val(duracion.segundos);
            actualizarDuracionEditar();
        });

        document.addEventListener('DOMContentLoaded', function() {
            ['nuevo_duracion_horas', 'nuevo_duracion_minutos', 'nuevo_duracion_segundos'].forEach(id => {
                document.getElementById(id).addEventListener('change', actualizarDuracionNuevo);
                document.getElementById(id).addEventListener('input', actualizarDuracionNuevo);
            });
            
            ['duracion_editar_horas', 'duracion_editar_minutos', 'duracion_editar_segundos'].forEach(id => {
                document.getElementById(id).addEventListener('change', actualizarDuracionEditar);
                document.getElementById(id).addEventListener('input', actualizarDuracionEditar);
            });
            
            actualizarDuracionNuevo();
            
            cargarOllasConEstado();
            
            console.log('✅ Sistema de temporizadores cargado');
            console.log('Vista:', <?= $vista_inactivos ? '"Inactivos"' : '"Activos"' ?>);
            console.log('Temporizadores encontrados:', <?= count($temporizadores) ?>);
            
            console.log('🔊 Sistema de audio listo con 3 métodos diferentes:');
            console.log('1. Audio HTML5 dinámico');
            console.log('2. Web Audio API');
            console.log('3. Notificaciones del navegador');
            
            console.log('📊 Sistema de registro de ciclos listo');
            console.log('👉 Los ciclos se registrarán automáticamente cuando los temporizadores terminen');
        });
    </script>
</body>
</html>