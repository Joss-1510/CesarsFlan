<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['configurar'])) {
    $actualizados = 0;
    $errores = [];
    
    if (!isset($_POST['limites']) || empty($_POST['limites'])) {
        $_SESSION['mensaje_exito'] = "⚠️ No se recibieron datos para actualizar";
        $_SESSION['tipo_mensaje'] = 'warning';
        header("Location: config_empaques.php");
        exit();
    }
    
    foreach ($_POST['limites'] as $id_olla => $limite) {
        if (trim($limite) === '') {
            $errores[] = "Límite vacío para olla $id_olla";
            continue;
        }
        
        $limite = intval($limite);
        $id_olla = intval($id_olla);
        
        if ($limite < 1 || $limite > 1000) {
            $errores[] = "Límite inválido para olla $id_olla: $limite (debe ser 1-1000)";
            continue;
        }
        
        $sql_check = "SELECT id_config FROM config_limite_empaque WHERE id_olla = $id_olla";
        $result_check = pg_query($conn, $sql_check);
        
        if (!$result_check) {
            $error_msg = pg_last_error($conn);
            $errores[] = "Error al verificar olla $id_olla: $error_msg";
            continue;
        }
        
        if (pg_num_rows($result_check) > 0) {
            $sql = "UPDATE config_limite_empaque 
                   SET limite_ciclos = $limite, fecha_config = NOW() 
                   WHERE id_olla = $id_olla";
            $result = pg_query($conn, $sql);
            
            if ($result) {
                $actualizados++;
            } else {
                $error_msg = pg_last_error($conn);
                $errores[] = "Error al actualizar olla $id_olla";
            }
        } else {
            $sql = "INSERT INTO config_limite_empaque (id_olla, limite_ciclos, fecha_config) 
                   VALUES ($id_olla, $limite, NOW())";
            $result = pg_query($conn, $sql);
            
            if ($result) {
                $actualizados++;
            } else {
                $error_msg = pg_last_error($conn);
                $errores[] = "Error al insertar olla $id_olla";
            }
        }
    }
    
    if ($actualizados > 0) {
        $_SESSION['mensaje_exito'] = "✅ Límites actualizados.";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje_exito'] = "⚠️ No se guardaron cambios";
        if (!empty($errores)) {
            $_SESSION['mensaje_exito'] .= ": " . implode(", ", array_slice($errores, 0, 3));
            if (count($errores) > 3) {
                $_SESSION['mensaje_exito'] .= " y " . (count($errores) - 3) . " error(es) más";
            }
        }
        $_SESSION['tipo_mensaje'] = 'warning';
    }
    
    header("Location: config_empaques.php");
    exit();
}

$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'success';

unset($_SESSION['mensaje_exito'], $_SESSION['tipo_mensaje']);

$sql = "SELECT 
            o.id_olla,
            o.numero_olla,
            o.capacidad,
            o.estado,
            COALESCE(cl.limite_ciclos, 100) as limite_ciclos,
            COALESCE(cl.fecha_config, NOW()) as fecha_config,
            COALESCE((
                SELECT ciclos_completados 
                FROM conteo_empaque_olla ceo2 
                WHERE ceo2.id_olla = o.id_olla 
                ORDER BY fecha_ultimo_ciclo DESC 
                LIMIT 1
            ), 0) as ciclos_actuales,
            (
                SELECT fecha_ultimo_ciclo 
                FROM conteo_empaque_olla ceo2 
                WHERE ceo2.id_olla = o.id_olla 
                ORDER BY fecha_ultimo_ciclo DESC 
                LIMIT 1
            ) as fecha_ultimo_ciclo
        FROM tolla o
        LEFT JOIN config_limite_empaque cl ON cl.id_olla = o.id_olla
        WHERE o.baja = false
        ORDER BY o.numero_olla";

$result = pg_query($conn, $sql);
$ollas = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $row['porcentaje'] = $row['limite_ciclos'] > 0 ? 
            round(($row['ciclos_actuales'] * 100) / $row['limite_ciclos'], 1) : 0;
        
        if ($row['porcentaje'] >= 100) {
            $row['estado_empaque'] = 'URGENTE';
            $row['clase_css'] = 'urgente';
            $row['icono'] = 'fas fa-fire';
        } elseif ($row['porcentaje'] >= 80) {
            $row['estado_empaque'] = 'ALERTA';
            $row['clase_css'] = 'alerta';
            $row['icono'] = 'fas fa-exclamation-triangle';
        } else {
            $row['estado_empaque'] = 'NORMAL';
            $row['clase_css'] = 'normal';
            $row['icono'] = 'fas fa-check-circle';
        }
        
        $ollas[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Empaques - Cesar's Flan</title>
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
        .empaques-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .empaques-section h3 {
            text-align: center;
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .btn-guardar {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
            font-weight: bold;
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        .btn-guardar:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #ffffff;
        }
        
        .btn-volver {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        .btn-volver:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
        
        .fixed-save-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .fixed-save-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        
        .empaque-card {
            background: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }
        
        .empaque-card.urgente {
            border-color: #dc3545;
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.3);
            animation: pulse-urgente 1s infinite;
        }
        
        .empaque-card.alerta {
            border-color: #ffc107;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }
        
        .empaque-card.normal {
            border-color: #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
        }
        
        @keyframes pulse-urgente {
            0% { opacity: 1; }
            50% { opacity: 0.9; }
            100% { opacity: 1; }
        }
        
        .empaque-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffd700;
        }
        
        .empaque-titulo {
            color: #2d2d2d;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .empaque-numero {
            background: #17a2b8;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .estado-empaque {
            text-align: center;
            font-size: 1rem;
            margin-bottom: 10px;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .estado-normal {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .estado-alerta {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .estado-urgente {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #2d2d2d;
        }
        
        .progress-label .ciclos-info {
            font-weight: bold;
        }
        
        .progress-label .porcentaje-info {
            font-weight: bold;
            color: #2d2d2d;
        }
        
        .progress {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .progress-bar-custom {
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.5s ease;
            height: 100%;
        }
        
        .progress-bar-warning {
            background: linear-gradient(90deg, #ffc107, #ffca2c);
        }
        
        .progress-bar-danger {
            background: linear-gradient(90deg, #dc3545, #e4606d);
            animation: progress-pulse 1s infinite;
        }
        
        @keyframes progress-pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .info-empaque {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ffd700;
            flex: 1;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .badge-cantidad {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        
        .badge-ciclos {
            background-color: #6f42c1;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .badge-estado {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .limite-form {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #ffd700;
            margin-top: auto;
        }
        
        .form-group-limite {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
        }
        
        .form-control-limite {
            width: 100px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid #ced4da;
            font-weight: bold;
            padding: 8px;
            font-size: 1rem;
        }
        
        .form-control-limite:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
            outline: none;
        }
        
        .label-limite {
            font-weight: bold;
            color: #2d2d2d;
            min-width: 100px;
        }
        
        .alert {
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            position: relative;
            padding: 15px 20px;
            font-size: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .alert-light {
            background-color: #f8f9fa;
            color: #212529;
            border-left: 4px solid #6c757d;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .btn-close-custom {
            background: transparent;
            border: none;
            font-size: 1.2rem;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-close-custom:hover {
            color: #000;
        }
        
        .instrucciones-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #17a2b8;
        }
        
        .estados-container {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .estado-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            width: 30%;
        }
        
        .modal-content {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
            border: 2px solid #ffd700 !important;
            border-radius: 15px !important;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%) !important;
            border-bottom: 2px solid #ffd700 !important;
            padding: 20px 30px !important;
            border-top-left-radius: 13px !important;
            border-top-right-radius: 13px !important;
        }
        
        .modal-header .modal-title {
            color: #ffd700 !important;
            font-weight: bold !important;
            font-size: 1.5rem !important;
        }
        
        .modal-body {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
            padding: 30px !important;
        }
        
        .modal-footer {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
            border-top: 2px solid #ffd700 !important;
            padding: 20px 30px !important;
            border-bottom-left-radius: 13px !important;
            border-bottom-right-radius: 13px !important;
            display: flex !important;
            justify-content: center !important;
            gap: 15px !important;
        }
        
        .btn-modal-confirm,
        .btn-modal-cancel {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            min-width: 140px !important;
            padding: 12px 25px !important;
            border-radius: 8px !important;
            font-weight: bold !important;
            font-size: 1rem !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-modal-confirm {
            background: linear-gradient(90deg, #28a745, #20c997) !important;
            color: white !important;
        }
        
        .btn-modal-confirm:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4) !important;
        }
        
        .btn-modal-cancel {
            background: linear-gradient(90deg, #6c757d, #8a939b) !important;
            color: white !important;
        }
        
        .btn-modal-cancel:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4) !important;
        }
        
        .modal-custom .modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-custom .modal-title i {
            color: #ffd700;
        }
        
        .modal-body .form-label {
            color: #ffd700 !important;
            font-weight: bold !important;
        }
        
        .modal-body .form-control,
        .modal-body .form-select {
            background-color: #1a1a1a !important;
            border: 1px solid #444 !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
        }
        
        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            background-color: #2d2d2d !important;
            border-color: #ffd700 !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25) !important;
        }
        
        .modal-loading .modal-content {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .modal-loading .modal-body {
            text-align: center;
            padding: 50px;
            background: transparent !important;
        }
        
        .modal-loading .spinner-text {
            margin-top: 20px;
            color: #ffd700 !important;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .spinner-custom {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255, 215, 0, 0.3);
            border-top: 8px solid #ffd700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        
        .error-list li {
            background-color: #1a1a1a;
            border-left: 4px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 5px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-list li i {
            color: #dc3545;
        }
        
        .modal-error .modal-header {
            background: linear-gradient(90deg, #dc3545, #e4606d) !important;
            color: white !important;
        }
        
        .modal-error .modal-body i {
            color: #dc3545;
            font-size: 3rem;
            text-align: center;
            display: block;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .empaque-card {
                min-height: 480px;
            }
            
            .estados-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .estado-item {
                width: 100%;
            }
            
            .fixed-save-button {
                bottom: 20px;
                right: 20px;
                padding: 12px 24px;
                font-size: 1rem;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px !important;
            }
            
            .modal-header {
                padding: 15px 20px !important;
            }
            
            .modal-footer {
                padding: 15px 20px !important;
                flex-direction: column !important;
                gap: 10px !important;
            }
            
            .btn-modal-confirm,
            .btn-modal-cancel {
                width: 100% !important;
                min-width: auto !important;
            }
        }
        
        .bottom-save-button {
            margin-top: 40px;
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 2px dashed rgba(255, 215, 0, 0.3);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-slide {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="empaques-section">
            <h3>⚙️ Configuración de Empaques</h3>
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-slide" role="alert" id="mensaje-alerta">
                    <div class="d-flex align-items-center">
                        <i class="fas <?= $tipo_mensaje == 'success' ? 'fa-check-circle' : 
                                       ($tipo_mensaje == 'warning' ? 'fa-exclamation-triangle' : 
                                       ($tipo_mensaje == 'danger' ? 'fa-times-circle' : 'fa-info-circle')) ?> me-2"></i>
                        <span class="flex-grow-1"><?= htmlspecialchars($mensaje_exito) ?></span>
                        <button type="button" class="btn-close-custom" onclick="cerrarAlerta()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="instrucciones-container">
                <h5><i class="fas fa-info-circle me-2"></i>Instrucciones</h5>
                <p class="mb-2">Configura el número máximo de ciclos que soporta cada empaque antes de requerir cambio.</p>
                
                <div class="estados-container">
                    <div class="estado-item" style="background-color: #d4edda; border: 1px solid #c3e6cb;">
                        <span class="badge-estado bg-success mb-2">NORMAL</span>
                        <small class="d-block text-muted">Menos del 80%</small>
                    </div>
                    <div class="estado-item" style="background-color: #fff3cd; border: 1px solid #ffeaa7;">
                        <span class="badge-estado bg-warning text-dark mb-2">ALERTA</span>
                        <small class="d-block text-muted">80% - 99%</small>
                    </div>
                    <div class="estado-item" style="background-color: #f8d7da; border: 1px solid #f5c6cb;">
                        <span class="badge-estado bg-danger mb-2">URGENTE</span>
                        <small class="d-block text-muted">100% o más</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="temporizadori.php" class="btn btn-volver">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Temporizadores
                    </a>
                </div>
            </div>
            
            <form method="post" action="config_empaques.php" id="formConfigEmpaques">
                <input type="hidden" name="configurar" value="1">
                
                <div class="row">
                    <?php if (!empty($ollas)): ?>
                        <?php foreach ($ollas as $olla): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="empaque-card <?= $olla['clase_css'] ?>">
                                    
                                    <div class="empaque-header">
                                        <h3 class="empaque-titulo">
                                            Olla <?= htmlspecialchars($olla['numero_olla']) ?>
                                        </h3>
                                        <span class="empaque-numero">
                                            <?= htmlspecialchars($olla['capacidad']) ?> flanes
                                        </span>
                                    </div>
                                    
                                    <div class="estado-empaque estado-<?= $olla['clase_css'] ?>" id="estado-<?= $olla['id_olla'] ?>">
                                        <i class="<?= $olla['icono'] ?>" id="icono-<?= $olla['id_olla'] ?>"></i>
                                        <span id="texto-estado-<?= $olla['id_olla'] ?>"><?= $olla['estado_empaque'] ?></span>
                                    </div>
                                    
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span class="ciclos-info">Ciclos: <strong><?= $olla['ciclos_actuales'] ?></strong></span>
                                            <span class="porcentaje-info" id="porcentaje-<?= $olla['id_olla'] ?>">
                                                <?= $olla['porcentaje'] ?>%
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar-custom 
                                                <?= $olla['porcentaje'] >= 100 ? 'progress-bar-danger' : 
                                                   ($olla['porcentaje'] >= 80 ? 'progress-bar-warning' : '') ?>"
                                                id="barra-<?= $olla['id_olla'] ?>"
                                                style="width: <?= min($olla['porcentaje'], 100) ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-empaque">
                                        <div class="info-row">
                                            <span>Capacidad:</span>
                                            <span class="badge-cantidad">
                                                <?= htmlspecialchars($olla['capacidad']) ?> flanes
                                            </span>
                                        </div>
                                        
                                        <div class="info-row">
                                            <span>Ciclos completados:</span>
                                            <span class="badge-ciclos">
                                                <?= $olla['ciclos_actuales'] ?>
                                            </span>
                                        </div>
                                        
                                        <div class="info-row">
                                            <span>Estado olla:</span>
                                            <span class="badge-cantidad" style="background-color: 
                                                <?= $olla['estado'] == 'ACTIVA' ? '#28a745' : 
                                                   ($olla['estado'] == 'MANTENIMIENTO' ? '#ffc107' : '#6c757d') ?>">
                                                <?= htmlspecialchars($olla['estado'] ?? 'ACTIVA') ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($olla['fecha_ultimo_ciclo']): ?>
                                        <div class="info-row">
                                            <span>Último ciclo:</span>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($olla['fecha_ultimo_ciclo'])) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="info-row">
                                            <span>Última configuración:</span>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($olla['fecha_config'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="limite-form">
                                        <div class="form-group-limite">
                                            <label class="label-limite">Límite de ciclos:</label>
                                            <input type="number" 
                                                   name="limites[<?= $olla['id_olla'] ?>]" 
                                                   value="<?= $olla['limite_ciclos'] ?>" 
                                                   min="1" 
                                                   max="1000" 
                                                   class="form-control form-control-limite" 
                                                   required
                                                   data-id="<?= $olla['id_olla'] ?>"
                                                   data-ciclos="<?= $olla['ciclos_actuales'] ?>"
                                                   oninput="actualizarProgreso(this)">
                                            <span class="badge-cantidad">ciclos</span>
                                        </div>
                                        <div class="mt-2 text-center">
                                            <small class="text-muted">
                                                <i class="fas fa-calculator me-1"></i>
                                                Ciclos restantes: 
                                                <strong id="restantes-<?= $olla['id_olla'] ?>"
                                                        style="color: <?= ($olla['limite_ciclos'] - $olla['ciclos_actuales']) <= 0 ? '#dc3545' : 
                                                                      (($olla['limite_ciclos'] - $olla['ciclos_actuales']) <= 10 ? '#ffc107' : '#28a745') ?>">
                                                    <?= max($olla['limite_ciclos'] - $olla['ciclos_actuales'], 0) ?>
                                                </strong>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-warning">
                                <i class="fas fa-pot-food fa-3x mb-3"></i>
                                <h4>No hay ollas registradas</h4>
                                <p>Primero debes agregar ollas en el sistema</p>
                                <a href="ollai.php" class="btn btn-warning">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Ollas
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-guardar fixed-save-button" id="btnGuardar">
                    <i class="fas fa-save me-2"></i>Guardar Configuración
                </button>
                
                <div class="bottom-save-button d-block d-md-none">
                    <button type="submit" class="btn btn-guardar btn-lg" id="btnGuardarMobile">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="fas fa-question-circle me-2"></i>Confirmar Cambios
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-circle fa-4x" style="color: #ffd700;"></i>
                    </div>
                    <h4 class="text-center mb-3" style="color: #ffd700;">¿Estás seguro de que deseas guardar los cambios?</h4>
                    <p class="text-center text-light">
                        Los límites de ciclos para los empaques serán actualizados.
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-modal-confirm" id="confirmSave">
                        <i class="fas fa-check me-2"></i>Sí, Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-custom"></div>
                    <div class="spinner-text mt-4">Guardando configuración...</div>
                    <p class="text-light mt-2">Por favor, espera un momento</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #dc3545, #e4606d);">
                    <h5 class="modal-title" id="errorModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Errores de Validación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle fa-4x" style="color: #dc3545;"></i>
                    </div>
                    <h5 class="text-center mb-4" style="color: #ffd700;">Se encontraron los siguientes errores:</h5>
                    <div id="errorList" class="error-list">
                    </div>
                    <div class="mt-4 p-3" style="background-color: #1a1a1a; border-radius: 8px; border-left: 4px solid #ffd700;">
                        <p class="mb-2" style="color: #ffd700;">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong>
                        </p>
                        <p class="mb-0 text-light">
                            Los límites deben estar entre <strong>1 y 1000</strong> ciclos.
                            Corrige los errores antes de continuar.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-confirm" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let formToSubmit = null;
        let formValid = false;

        function actualizarProgreso(input) {
            const nuevoLimite = parseInt(input.value);
            const ciclos = parseInt(input.dataset.ciclos);
            const idOlla = input.dataset.id;
            
            if (nuevoLimite > 0 && !isNaN(nuevoLimite)) {
                const nuevoPorcentaje = Math.min((ciclos * 100) / nuevoLimite, 100);
                const porcentajeRedondeado = Math.round(nuevoPorcentaje * 10) / 10;
                const ciclosRestantes = Math.max(nuevoLimite - ciclos, 0);
                
                const porcentajeSpan = document.getElementById('porcentaje-' + idOlla);
                const barraProgreso = document.getElementById('barra-' + idOlla);
                const ciclosRestantesSpan = document.getElementById('restantes-' + idOlla);
                const estadoDiv = document.getElementById('estado-' + idOlla);
                const estadoIcono = document.getElementById('icono-' + idOlla);
                const estadoTexto = document.getElementById('texto-estado-' + idOlla);
                const card = input.closest('.empaque-card');
                
                if (barraProgreso) {
                    barraProgreso.style.width = nuevoPorcentaje + '%';
                    barraProgreso.classList.remove('progress-bar-danger', 'progress-bar-warning');
                    
                    if (nuevoPorcentaje >= 100) {
                        barraProgreso.classList.add('progress-bar-danger');
                        if (estadoDiv) estadoDiv.className = 'estado-empaque estado-urgente';
                        if (estadoIcono) estadoIcono.className = 'fas fa-fire';
                        if (estadoTexto) estadoTexto.textContent = 'URGENTE';
                        if (card) {
                            card.classList.remove('normal', 'alerta');
                            card.classList.add('urgente');
                        }
                    } else if (nuevoPorcentaje >= 80) {
                        barraProgreso.classList.add('progress-bar-warning');
                        if (estadoDiv) estadoDiv.className = 'estado-empaque estado-alerta';
                        if (estadoIcono) estadoIcono.className = 'fas fa-exclamation-triangle';
                        if (estadoTexto) estadoTexto.textContent = 'ALERTA';
                        if (card) {
                            card.classList.remove('normal', 'urgente');
                            card.classList.add('alerta');
                        }
                    } else {
                        if (estadoDiv) estadoDiv.className = 'estado-empaque estado-normal';
                        if (estadoIcono) estadoIcono.className = 'fas fa-check-circle';
                        if (estadoTexto) estadoTexto.textContent = 'NORMAL';
                        if (card) {
                            card.classList.remove('alerta', 'urgente');
                            card.classList.add('normal');
                        }
                    }
                }
                
                if (porcentajeSpan) {
                    porcentajeSpan.textContent = porcentajeRedondeado + '%';
                }
                
                if (ciclosRestantesSpan) {
                    ciclosRestantesSpan.textContent = ciclosRestantes;
                    if (ciclosRestantes <= 0) {
                        ciclosRestantesSpan.style.color = '#dc3545';
                    } else if (ciclosRestantes <= 10) {
                        ciclosRestantesSpan.style.color = '#ffc107';
                    } else {
                        ciclosRestantesSpan.style.color = '#28a745';
                    }
                }
            }
        }
        
        function cerrarAlerta() {
            const alerta = document.getElementById('mensaje-alerta');
            if (alerta) {
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-20px)';
                alerta.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    alerta.style.display = 'none';
                }, 300);
            }
        }
        
        function validarFormulario() {
            let valid = true;
            const inputs = document.querySelectorAll('.form-control-limite');
            const errores = [];
            
            inputs.forEach(input => {
                const valor = parseInt(input.value);
                const card = input.closest('.empaque-card');
                const ollaNum = card.querySelector('.empaque-titulo').textContent;
                
                if (isNaN(valor) || valor < 1 || valor > 1000) {
                    valid = false;
                    input.classList.add('is-invalid');
                    input.style.borderColor = '#dc3545';
                    input.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.3)';
                    errores.push(`${ollaNum}: límite inválido (${input.value})`);
                } else {
                    input.classList.remove('is-invalid');
                    input.style.borderColor = '#ced4da';
                    input.style.boxShadow = 'none';
                }
            });
            
            formValid = valid;
            return { valid, errores };
        }
        
        function mostrarErroresModal(errores) {
            const errorList = document.getElementById('errorList');
            let errorHTML = '';
            
            errores.forEach(err => errorHTML += 
                `<li><i class="fas fa-exclamation-circle"></i> ${err}</li>`
            );
            
            errorList.innerHTML = errorHTML;
            
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            
            const primerError = document.querySelector('.is-invalid');
            if (primerError) {
                setTimeout(() => {
                    primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    primerError.focus();
                }, 500);
            }
        }
        
        function mostrarConfirmacionModal() {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }
        
        function mostrarModalCarga() {
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
            
            const saveButtons = document.querySelectorAll('.btn-guardar');
            saveButtons.forEach(button => {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            });
        }
        
        function ocultarModalCarga() {
            const loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
            if (loadingModal) {
                loadingModal.hide();
            }
            
            setTimeout(() => {
                const saveButtons = document.querySelectorAll('.btn-guardar');
                saveButtons.forEach(button => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-save me-2"></i>' + 
                        (button.id === 'btnGuardarMobile' ? 'Guardar Cambios' : 'Guardar Configuración');
                });
            }, 2000);
        }
        
        document.getElementById('formConfigEmpaques').addEventListener('submit', function(e) {
            e.preventDefault();
            formToSubmit = this;
            
            const { valid, errores } = validarFormulario();
            
            if (!valid) {
                mostrarErroresModal(errores);
            } else {
                mostrarConfirmacionModal();
            }
        });
        
        document.getElementById('confirmSave').addEventListener('click', function() {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            if (confirmModal) {
                confirmModal.hide();
            }
            mostrarModalCarga();
            setTimeout(() => {
                if (formToSubmit && formValid) {
                    formToSubmit.submit();
                } else {
                    ocultarModalCarga();
                }
            }, 1000);
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Configuración de empaques cargada');
            
            const alerta = document.getElementById('mensaje-alerta');
            if (alerta) {
                setTimeout(() => {
                    cerrarAlerta();
                }, 5000);
            }
            
            const inputs = document.querySelectorAll('.form-control-limite');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.select();
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                        setTimeout(() => {
                            actualizarProgreso(this);
                        }, 10);
                    }
                });
                
                input.addEventListener('change', function() {
                    const value = parseInt(this.value);
                    if (value < 1) this.value = 1;
                    if (value > 1000) this.value = 1000;
                    actualizarProgreso(this);
                });
            });
            
            document.querySelectorAll('.btn-modal-confirm, .btn-modal-cancel').forEach(btn => {
                btn.style.display = 'inline-block';
                btn.style.visibility = 'visible';
                btn.style.opacity = '1';
            });
        });
    </script>
</body>
</html>