<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit();
}

$nombreUsuario = $_SESSION['SISTEMA']['nombre'] ?? 'Usuario';
$rolUsuario = $_SESSION['SISTEMA']['rol'] ?? 2;
$idUsuario = $_SESSION['SISTEMA']['id_usuario'] ?? null;

$esAdministrador = ($rolUsuario == 1);
$rolTexto = $esAdministrador ? 'Administrador' : 'Empleado';

require_once 'conexion.php';
require_once 'rutaf.php';
require_once 'organizador_rutaf.php';

$stock_minimo_productos = 10;
$stock_minimo_materiales = 50;
$stock_minimo_ingredientes = 50;

$productos_bajo_stock = [];
$materiales_bajo_stock = [];
$ingredientes_bajo_stock = [];
$rutas_hoy = [];

try {
    if (!$conn || !is_resource($conn)) {
        throw new Exception("No hay conexión a la base de datos");
    }
    
    $query = "SELECT nombre, stock FROM tproducto WHERE stock <= $1 AND baja = false ORDER BY stock ASC LIMIT 5";
    $result = pg_prepare($conn, "stock_productos_query", $query);
    
    if ($result) {
        $result = pg_execute($conn, "stock_productos_query", array($stock_minimo_productos));
        if ($result) {
            $productos_bajo_stock = pg_fetch_all($result);
            if ($productos_bajo_stock === false) {
                $productos_bajo_stock = [];
            }
        }
    }
    
    $query = "SELECT nombre, cantidad_stock, tipo FROM tmaterial WHERE cantidad_stock <= $1 AND baja = false ORDER BY cantidad_stock ASC LIMIT 5";
    $result = pg_prepare($conn, "stock_materiales_query", $query);
    
    if ($result) {
        $result = pg_execute($conn, "stock_materiales_query", array($stock_minimo_materiales));
        if ($result) {
            $materiales_bajo_stock = pg_fetch_all($result);
            if ($materiales_bajo_stock === false) {
                $materiales_bajo_stock = [];
            }
        }
    }
    
    $query = "SELECT nombre, cantidad_stock, unidad_medida, tipo FROM tingrediente WHERE cantidad_stock <= $1 AND baja = false ORDER BY cantidad_stock ASC LIMIT 5";
    $result = pg_prepare($conn, "stock_ingredientes_query", $query);
    
    if ($result) {
        $result = pg_execute($conn, "stock_ingredientes_query", array($stock_minimo_ingredientes));
        if ($result) {
            $ingredientes_bajo_stock = pg_fetch_all($result);
            if ($ingredientes_bajo_stock === false) {
                $ingredientes_bajo_stock = [];
            }
        }
    }
    
    date_default_timezone_set('America/Mexico_City');
    $dia_actual = date('N'); 

    $rutaF = new RutaF($conn);
    $organizadorF = new OrganizadorRutaF($conn);
    
    if ($esAdministrador) {
        $rutas_hoy = $organizadorF->obtenerRutasPorDiaGlobal($dia_actual);
    } else {
        $rutas_hoy = $rutaF->obtenerRutasPorUsuarioYDia($idUsuario, $dia_actual);
    }
    
} catch (Exception $e) {
    error_log("Error al consultar datos: " . $e->getMessage());
    $productos_bajo_stock = [];
    $materiales_bajo_stock = [];
    $ingredientes_bajo_stock = [];
    $rutas_hoy = [];
}

$nombres_dias = [
    1 => 'Lunes',
    2 => 'Martes', 
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];
$nombre_dia_actual = $nombres_dias[$dia_actual] ?? 'Hoy';

$tituloPagina = "Inicio";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            padding-top: 80px; 
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            font-family: 'Arial', sans-serif;
            color: #ffffff;
            margin: 0;
            min-height: 100vh;
        }
        
        .welcome-header {
            text-align: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 215, 0, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            color: #ffd700;
            margin-bottom: 1rem;
            font-weight: 300;
        }
        
        .day-info-badge {
            display: inline-block;
            background: rgba(78, 205, 196, 0.2);
            color: #4ecdc4;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(78, 205, 196, 0.3);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .main-container {
            padding: 0 2rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .alerts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border: 1px solid #444;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .rutas-card {
            border-left: 5px solid #4ecdc4 !important;
        }
        
        .productos-card {
            border-left: 5px solid #ffd700 !important;
        }
        
        .materiales-card {
            border-left: 5px solid #FF9800 !important;
        }
        
        .ingredientes-card {
            border-left: 5px solid #4CAF50 !important;
        }
        
        .alert-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            flex-shrink: 0;
        }
        
        .rutas-header {
            color: #4ecdc4 !important;
        }
        
        .productos-header {
            color: #ffd700 !important;
        }
        
        .materiales-header {
            color: #FF9800 !important;
        }
        
        .ingredientes-header {
            color: #4CAF50 !important;
        }
        
        .alert-header i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .alert-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .rutas-title {
            color: #4ecdc4 !important;
        }
        
        .productos-title {
            color: #ffd700 !important;
        }
        
        .materiales-title {
            color: #FF9800 !important;
        }
        
        .ingredientes-title {
            color: #4CAF50 !important;
        }
        
        .alert-list {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex-grow: 1;
            max-height: 250px;
        }
        
        .alert-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .alert-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .alert-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .alert-item {
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .ruta-item {
            border-left: 3px solid #4ecdc4 !important;
        }
        
        .producto-item {
            border-left: 3px solid #ffd700 !important;
        }
        
        .material-item {
            border-left: 3px solid #FF9800 !important;
        }
        
        .ingrediente-item {
            border-left: 3px solid #4CAF50 !important;
        }
        
        .alert-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .alert-name, .ruta-info {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
        }
        
        .alert-details, .cliente-info {
            font-size: 0.9rem;
            color: #cccccc;
        }
        
        .stock-critico {
            color: #ff6b6b !important;
            font-weight: bold;
        }
        
        .stock-bajo {
            color: #ffa726 !important;
        }
        
        .stock-adecuado {
            color: #66bb6a !important;
        }
        
        .no-alerts {
            text-align: center;
            padding: 2rem 1rem;
            color: #cccccc;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .no-alerts i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .no-rutas i {
            color: #4ecdc4;
        }
        
        .no-productos i {
            color: #ffd700;
        }
        
        .no-materiales i {
            color: #FF9800;
        }
        
        .no-ingredientes i {
            color: #4CAF50;
        }
        
        .ruta-usuario {
            font-size: 0.8rem;
            color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }
        
        .ruta-orden-mini {
            background-color: #4ecdc4;
            color: #000000;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.7rem;
            margin-right: 8px;
        }
        
        .tipo-info {
            font-size: 0.8rem;
            color: #aaaaaa;
            font-style: italic;
            margin-top: 0.2rem;
        }
        
        .stock-limit {
            font-size: 0.8rem;
            color: #888;
            text-align: center;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 992px) {
            .alerts-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-subtitle {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 0 1rem 1rem;
            }
            
            .welcome-header {
                padding: 1.5rem 1rem;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .alert-card {
                padding: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .alert-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="welcome-header">
        <h1 class="welcome-title">
            Bienvenido, <?= htmlspecialchars($nombreUsuario) ?>
        </h1>
        <p class="welcome-subtitle">
            Sistema de Gestión Cesar's Flan
        </p>
        <div class="day-info-badge">
            <i class="fas fa-calendar-day me-1"></i>
            Hoy es <?= $nombre_dia_actual ?> 
            <?php if (!empty($rutas_hoy)): ?>
                • <?= count($rutas_hoy) ?> ruta(s) programada(s)
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <div class="alerts-row">
            <div class="alert-card rutas-card">
                <div class="alert-header rutas-header">
                    <i class="fas fa-route"></i>
                    <h3 class="alert-title rutas-title">Rutas de <?= $nombre_dia_actual ?></h3>
                </div>
                <?php if (!empty($rutas_hoy)): ?>
                    <ul class="alert-list">
                        <?php foreach ($rutas_hoy as $ruta): ?>
                            <li class="alert-item ruta-item">
                                <div class="alert-name">
                                    <span class="ruta-orden-mini"><?= $ruta['orden'] ?></span>
                                    🏠 <?= htmlspecialchars($ruta['nombre_cliente']) ?>
                                    <?php if ($esAdministrador): ?>
                                        <span class="ruta-usuario">👤 <?= htmlspecialchars($ruta['nombre_usuario']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="alert-details">
                                    Orden de visita: <?= $ruta['orden'] ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-alerts no-rutas">
                        <i class="fas fa-check-circle"></i>
                        <h4 style="color: #4ecdc4; margin-bottom: 0.5rem;">Sin rutas programadas</h4>
                        <p style="color: #cccccc; font-size: 0.9rem;">No hay rutas programadas para hoy.</p>
                        <?php if ($esAdministrador): ?>
                            <a href="rutai.php" class="btn btn-sm mt-2" style="background-color: #4ecdc4; border-color: #4ecdc4; color: #000;">
                                <i class="fas fa-plus me-1"></i> Programar Rutas
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="stock-limit">
                    <i class="fas fa-info-circle me-1"></i> Rutas del día actual
                </div>
            </div>

            <div class="alert-card productos-card">
                <div class="alert-header productos-header">
                    <i class="fas fa-boxes"></i>
                    <h3 class="alert-title productos-title">Productos por agotarse</h3>
                </div>
                
                <?php if (!empty($productos_bajo_stock)): ?>
                    <ul class="alert-list">
                        <?php foreach ($productos_bajo_stock as $producto): ?>
                            <li class="alert-item producto-item">
                                <div class="alert-name">📦 <?= htmlspecialchars($producto['nombre']) ?></div>
                                <div class="alert-details <?= 
                                    $producto['stock'] <= 3 ? 'stock-critico' : 
                                    ($producto['stock'] <= 5 ? 'stock-bajo' : 'stock-adecuado')
                                ?>">
                                    Stock: <?= $producto['stock'] ?> unidades
                                    <?php if ($producto['stock'] <= 3): ?>
                                        <span style="color: #ff6b6b;"> ⚠️ Crítico</span>
                                    <?php elseif ($producto['stock'] <= 5): ?>
                                        <span style="color: #ffa726;"> ⚠️ Bajo</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-alerts no-productos">
                        <i class="fas fa-check-circle"></i>
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">Stock en orden</h4>
                        <p style="color: #cccccc; font-size: 0.9rem;">No hay productos con stock bajo.</p>
                    </div>
                <?php endif; ?>
                <div class="stock-limit">
                    <i class="fas fa-exclamation-triangle me-1"></i> Alerta: stock ≤ <?= $stock_minimo_productos ?> unidades
                </div>
            </div>
        </div>

        <div class="alerts-row">
            <div class="alert-card materiales-card">
                <div class="alert-header materiales-header">
                    <i class="fas fa-tools"></i>
                    <h3 class="alert-title materiales-title">Materiales por agotarse</h3>
                </div>
                
                <?php if (!empty($materiales_bajo_stock)): ?>
                    <ul class="alert-list">
                        <?php foreach ($materiales_bajo_stock as $material): ?>
                            <li class="alert-item material-item">
                                <div class="alert-name">🏗️ <?= htmlspecialchars($material['nombre']) ?></div>
                                <div class="alert-details <?= 
                                    $material['cantidad_stock'] <= 15 ? 'stock-critico' : 
                                    ($material['cantidad_stock'] <= 30 ? 'stock-bajo' : 'stock-adecuado')
                                ?>">
                                    Stock: <?= $material['cantidad_stock'] ?> unidades
                                    <?php if ($material['cantidad_stock'] <= 15): ?>
                                        <span style="color: #ff6b6b;"> ⚠️ Crítico</span>
                                    <?php elseif ($material['cantidad_stock'] <= 30): ?>
                                        <span style="color: #ffa726;"> ⚠️ Bajo</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($material['tipo'])): ?>
                                    <div class="tipo-info">
                                        Tipo: <?= htmlspecialchars($material['tipo']) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-alerts no-materiales">
                        <i class="fas fa-check-circle"></i>
                        <h4 style="color: #FF9800; margin-bottom: 0.5rem;">Materiales en orden</h4>
                        <p style="color: #cccccc; font-size: 0.9rem;">No hay materiales con stock bajo.</p>
                    </div>
                <?php endif; ?>
                <div class="stock-limit">
                    <i class="fas fa-exclamation-triangle me-1"></i> Alerta: stock ≤ <?= $stock_minimo_materiales ?> unidades
                </div>
            </div>

            <div class="alert-card ingredientes-card">
                <div class="alert-header ingredientes-header">
                    <i class="fas fa-egg"></i>
                    <h3 class="alert-title ingredientes-title">Ingredientes por agotarse</h3>
                </div>
                
                <?php if (!empty($ingredientes_bajo_stock)): ?>
                    <ul class="alert-list">
                        <?php foreach ($ingredientes_bajo_stock as $ingrediente): ?>
                            <li class="alert-item ingrediente-item">
                                <div class="alert-name">🥚 <?= htmlspecialchars($ingrediente['nombre']) ?></div>
                                <div class="alert-details <?= 
                                    $ingrediente['cantidad_stock'] <= 15 ? 'stock-critico' : 
                                    ($ingrediente['cantidad_stock'] <= 30 ? 'stock-bajo' : 'stock-adecuado')
                                ?>">
                                    Stock: <?= $ingrediente['cantidad_stock'] ?> <?= htmlspecialchars($ingrediente['unidad_medida']) ?>
                                    <?php if ($ingrediente['cantidad_stock'] <= 15): ?>
                                        <span style="color: #ff6b6b;"> ⚠️ Crítico</span>
                                    <?php elseif ($ingrediente['cantidad_stock'] <= 30): ?>
                                        <span style="color: #ffa726;"> ⚠️ Bajo</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($ingrediente['tipo'])): ?>
                                    <div class="tipo-info">
                                        Tipo: <?= htmlspecialchars($ingrediente['tipo']) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-alerts no-ingredientes">
                        <i class="fas fa-check-circle"></i>
                        <h4 style="color: #4CAF50; margin-bottom: 0.5rem;">Ingredientes en orden</h4>
                        <p style="color: #cccccc; font-size: 0.9rem;">No hay ingredientes con stock bajo.</p>
                    </div>
                <?php endif; ?>
                <div class="stock-limit">
                    <i class="fas fa-exclamation-triangle me-1"></i> Alerta: stock ≤ <?= $stock_minimo_ingredientes ?> unidades
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>