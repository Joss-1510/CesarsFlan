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
require_once 'organizador_rutaf.php';
require_once 'conexion.php';

if (!isset($conn) || !$conn) {
    die("❌ Error: No hay conexión a la base de datos");
}

try {
    $organizadorF = new OrganizadorRutaF($conn);
    $rutaF = new RutaF($conn);
    $organizadorF->reasignarOrdenesAutomaticamente();
    
    $rutasSemana = $organizadorF->obtenerRutasSemanalGlobal();
    $estadisticas = $organizadorF->obtenerEstadisticasRutasGlobal();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Error de seguridad. Por favor, recarga la página.";
        } else if (isset($_POST['mover_ruta'])) {
            $idRuta = filter_var($_POST['id_ruta'], FILTER_VALIDATE_INT);
            $nuevaPosicion = filter_var($_POST['nueva_posicion'], FILTER_VALIDATE_INT);
            
            if ($idRuta && $nuevaPosicion && $nuevaPosicion > 0) {
                if ($organizadorF->moverRuta($idRuta, $nuevaPosicion)) {
                    $mensaje = "✅ Ruta reordenada correctamente";
                    $rutasSemana = $organizadorF->obtenerRutasSemanalGlobal();
                } else {
                    $error = "❌ Error al reordenar la ruta";
                }
            } else {
                $error = "❌ Datos inválidos para el reordenamiento";
            }
        } else if (isset($_POST['reordenar_dia'])) {
            $idDia = filter_var($_POST['id_dia'], FILTER_VALIDATE_INT);
            $ordenes = $_POST['orden'] ?? [];
            
            if ($idDia && !empty($ordenes)) {
                if ($organizadorF->reordenarRutasDiaGlobal($idDia, $ordenes)) {
                    $mensaje = "✅ Día reordenado correctamente";
                    $rutasSemana = $organizadorF->obtenerRutasSemanalGlobal();
                } else {
                    $error = "❌ Error al reordenar el día";
                }
            } else {
                $error = "❌ Datos inválidos para el reordenamiento del día";
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

$tituloPagina = "Organizador de Rutas Semanales";
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
        .organizador-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
        }
        .organizador-section h3 {
            text-align: center;
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .dia-column {
            background-color: #3d3d3d;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #ffd700;
            height: 500px; 
            display: flex;
            flex-direction: column;
        }
        .dia-header {
            background-color: #ffd700;
            color: #000000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            flex-shrink: 0; 
        }
        .rutas-container {
            flex: 1; 
            overflow-y: auto; 
            max-height: 380px; /
            min-height: 150px; 
        }
        .rutas-container::-webkit-scrollbar {
            width: 8px;
        }
        .rutas-container::-webkit-scrollbar-track {
            background: #2d2d2d;
            border-radius: 4px;
        }
        .rutas-container::-webkit-scrollbar-thumb {
            background: #ffd700;
            border-radius: 4px;
        }
        .rutas-container::-webkit-scrollbar-thumb:hover {
            background: #e6c200;
        }
        .ruta-item {
            background-color: #1a1a1a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            color: #ffffff;
            transition: all 0.3s ease;
            cursor: move;
            flex-shrink: 0; 
        }
        .ruta-item:hover {
            background-color: #2a2a2a;
            border-color: #ffd700;
        }
        .ruta-orden {
            background-color: #ffd700;
            color: #000000;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0; 
        }
        .ruta-info {
            flex: 1;
            min-width: 0; 
        }
        .ruta-cliente {
            font-weight: bold;
            color: #ffd700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ruta-usuario {
            font-size: 0.8rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .estadisticas-dia {
            background-color: #1a3a1a;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-top: 10px;
            flex-shrink: 0; 
        }
        .btn-volver {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-volver:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: #ffffff;
        }
        .empty-state {
            background-color: #1a1a1a;
            border: 2px dashed #444;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #888;
            flex-shrink: 0;
        }
        .sortable-ghost {
            opacity: 0.4;
            background-color: #ffd700;
        }
        .sortable-chosen {
            background-color: #3a3a3a;
        }
        .global-badge {
            background-color: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        .contador-rutas {
            background-color: #000000;
            color: #ffd700;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        .scroll-indicator {
            text-align: center;
            color: #ffd700;
            font-size: 0.8rem;
            margin-top: 5px;
            opacity: 0.7;
        }
        @media (max-width: 768px) {
            .dia-column {
                margin-bottom: 15px;
                height: 400px; 
            }
            .rutas-container {
                max-height: 280px;
            }
        }
        @media (max-width: 576px) {
            .dia-column {
                height: 350px;
            }
            .rutas-container {
                max-height: 230px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="organizador-section">
            <h3>🗓️ Organizador de Rutas Semanales <span class="global-badge">GLOBAL</span></h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <a href="rutai.php" class="btn btn-volver">
                    ↩️ Volver a Gestión de Rutas
                </a>
                <div class="text-warning">
                    <small>💡 Arrastra las rutas para reordenarlas </small>
                </div>
            </div>

            <div class="row">
                <?php foreach ($rutasSemana as $idDia => $diaData): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="dia-column">
                            <div class="dia-header">
                                <?= htmlspecialchars($diaData['nombre_dia']) ?>
                                <span class="contador-rutas">
                                    <?= count($diaData['rutas']) ?>
                                </span>
                            </div>
                            
                            <div class="rutas-container">
                                <div class="rutas-list" data-dia="<?= $idDia ?>">
                                    <?php if (!empty($diaData['rutas'])): ?>
                                        <?php foreach ($diaData['rutas'] as $ruta): ?>
                                            <div class="ruta-item d-flex align-items-center" 
                                                 data-ruta-id="<?= $ruta['id_ruta'] ?>"
                                                 data-orden-actual="<?= $ruta['orden'] ?>">
                                                <div class="ruta-orden">
                                                    <?= $ruta['orden'] ?>
                                                </div>
                                                <div class="ruta-info">
                                                    <div class="ruta-cliente" title="<?= htmlspecialchars($ruta['nombre_cliente']) ?>">
                                                        <?= htmlspecialchars($ruta['nombre_cliente']) ?>
                                                    </div>
                                                    <div class="ruta-usuario" title="Repartidor: <?= htmlspecialchars($ruta['nombre_usuario']) ?>">
                                                        Repartidor: <?= htmlspecialchars($ruta['nombre_usuario']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <p>No hay rutas para este día</p>
                                            <a href="rutai.php" class="btn btn-sm btn-outline-warning">
                                                ➕ Agregar Rutas
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (count($diaData['rutas']) > 4): ?>
                                <div class="scroll-indicator">
                                    ↕️ Desplázate para ver más rutas
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($estadisticas[$idDia])): ?>
                                <div class="estadisticas-dia">
                                    <small>
                                        📊 Total: <strong><?= $estadisticas[$idDia]['total_rutas'] ?></strong> rutas | 
                                        Orden: <?= $estadisticas[$idDia]['minimo_orden'] ?>-<?= $estadisticas[$idDia]['maximo_orden'] ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        $(document).ready(function() {
            let isReordering = false;
            
            $('.rutas-list').each(function() {
                const diaId = $(this).data('dia');
                
                new Sortable(this, {
                    group: {
                        name: 'rutas',
                        pull: true,
                        put: true
                    },
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    filter: '.empty-state',
                    onStart: function(evt) {
                        isReordering = true;
                        const container = $(evt.from).closest('.rutas-container');
                        container.css('overflow-y', 'visible');
                    },
                    onEnd: function(evt) {
                        isReordering = false;
                        
                        const container = $(evt.from).closest('.rutas-container');
                        container.css('overflow-y', 'auto');
                        
                        if (evt.to === evt.from && evt.oldIndex === evt.newIndex) {
                            return;
                        }
                        
                        const itemEl = evt.item;
                        const rutaId = $(itemEl).data('ruta-id');
                        const nuevoOrden = evt.newIndex + 1;
                        const diaDestino = $(evt.to).data('dia');
                        
                        actualizarNumerosOrden($(evt.to));
                        
                        moverRuta(rutaId, nuevoOrden, diaDestino);
                    },
                    onAdd: function(evt) {
                        const container = $(evt.to).closest('.rutas-container');
                        container.css('overflow-y', 'auto');
                    }
                });
            });
            
            function actualizarNumerosOrden($lista) {
                $lista.find('.ruta-item').each(function(index) {
                    const nuevoOrden = index + 1;
                    $(this).find('.ruta-orden').text(nuevoOrden);
                    $(this).data('orden-actual', nuevoOrden);
                });
            }
            
            function moverRuta(rutaId, nuevoOrden, diaDestino) {
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                formData.append('mover_ruta', '1');
                formData.append('id_ruta', rutaId);
                formData.append('nueva_posicion', nuevoOrden);
                
                $('.organizador-section').prepend(
                    '<div class="alert alert-info alert-dismissible fade show" role="alert">' +
                    '🔄 Actualizando orden de rutas...' +
                    '</div>'
                );
                
                fetch('organizador_rutai.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.text();
                })
                .then(data => {
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('.organizador-section').prepend(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '❌ Error al guardar el orden. Recargando página...' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>'
                    );
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                });
            }
            
            // Mejorar la experiencia de scroll durante el drag
            document.addEventListener('dragover', function(e) {
                if (!isReordering) return;
                
                const scrollMargin = 50;
                const container = e.target.closest('.rutas-container');
                
                if (container) {
                    const rect = container.getBoundingClientRect();
                    
                    if (e.clientY > rect.bottom - scrollMargin) {
                        container.scrollTop += 10;
                    }
                    else if (e.clientY < rect.top + scrollMargin) {
                        container.scrollTop -= 10;
                    }
                }
            });
        });
    </script>
</body>
</html>