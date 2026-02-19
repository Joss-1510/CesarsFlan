<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header('Location: login.php');
    exit();
}

$nombreUsuario = htmlspecialchars($_SESSION['SISTEMA']['nombre'] ?? 'Usuario');
$rolUsuario = $_SESSION['SISTEMA']['rol'] ?? 2;
$esAdministrador = ($rolUsuario == 1);
$rolTexto = $esAdministrador ? 'Administrador' : 'Empleado';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .navbar-custom {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1) !important;
        min-height: 80px !important;
        padding: 0.5rem 1rem !important;
        border-bottom: 3px solid #ffd700 !important;
    }

    .navbar-custom .navbar-container {
        width: 100%;
        padding: 0 15px;
        position: relative;
    }

    .navbar-custom .navbar-brand-container {
        display: flex;
        align-items: center;
        position: absolute;
        left: 15px;
    }

    .navbar-custom .navbar-brand {
        display: flex !important;
        align-items: center !important;
        font-family: 'Lora', serif !important;
        font-size: 1.5rem !important;
        color: #2c3e50 !important;
        margin: 0 !important;
        padding: 0 !important;
        white-space: nowrap !important;
        font-weight: bold !important;
    }

    .navbar-custom .navbar-logo {
        height: 50px;
        margin-right: 10px;
    }

    .navbar-custom .navbar-content {
        margin-left: 250px;
        padding-left: 20px;
    }

    .navbar-custom .nav-link {
        font-size: 1.1rem !important;
        padding: 0.8rem 1.2rem !important;
        color: #2c3e50 !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.3s ease !important;
        font-weight: 500 !important;
        border-radius: 8px !important;
        margin: 0 2px !important;
    }

    .navbar-custom .nav-link:hover {
        transform: translateY(-2px);
        color: #000000 !important;
        background-color: rgba(255, 215, 0, 0.2);
    }

    .navbar-custom .nav-link i {
        margin-right: 8px;
        width: 20px;
        text-align: center;
        color: #ffd700;
    }

    .navbar-custom .dropdown-menu {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
        border: 1px solid #ffd700 !important;
        border-radius: 10px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        padding: 0.5rem 0 !important;
    }

    .navbar-custom .dropdown-item {
        padding: 0.75rem 1.5rem !important;
        color: #2c3e50 !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.3s ease !important;
        font-weight: 500 !important;
        border: none !important;
    }

    .navbar-custom .dropdown-item i {
        margin-right: 10px;
        width: 18px;
        text-align: center;
        color: #ffd700;
    }

    .navbar-custom .dropdown-item:hover {
        background-color: rgba(255, 215, 0, 0.2) !important;
        color: #000000 !important;
        transform: translateX(5px);
    }

    .navbar-custom .dropdown-header {
        background-color: rgba(255, 215, 0, 0.1) !important;
        color: #2c3e50 !important;
        font-weight: 600 !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid #ffd700 !important;
    }

    .navbar-custom .dropdown-divider {
        border-color: #ffd700 !important;
        opacity: 0.3;
    }

    .navbar-custom .user-role {
        font-size: 0.9rem;
        background-color: #ffd700;
        border-radius: 12px;
        padding: 4px 12px;
        margin-left: 5px;
        font-weight: 600;
        color: #000000;
    }

    .navbar-custom .administrador-role {
        background-color: #ffd700;
        color: #000000;
    }

    .navbar-custom .empleado-role {
        background-color: #b8d4a0;
        color: #000000;
    }

    .navbar-custom .dropdown-toggle::after {
        color: #ffd700;
        margin-left: 5px;
    }

    .navbar-custom .dropdown-toggle:hover::after {
        color: #000000;
    }

    @media (max-width: 992px) {
        .navbar-custom .navbar-brand-container {
            position: relative;
            left: 0;
        }
        
        .navbar-custom .navbar-content {
            margin-left: 0;
            padding-left: 0;
        }
        
        .navbar-custom .dropdown-menu {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        
        .navbar-custom .nav-link {
            padding: 0.5rem 1rem !important;
        }
    }

    .navbar-custom {
        z-index: 1030 !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
    <div class="navbar-container">
        <div class="navbar-brand-container">
            <a class="navbar-brand" href="inicio.php">
                <img src="./Imagenes/Logo.jpg" class="navbar-logo" alt="Logo Cesar´s Flan">
                <span>Cesar´s Flan</span>
            </a>
        </div>
        
        <div class="navbar-content">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="inicio.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="PVentaI.php">
                            <i class="fas fa-cash-register"></i> Punto de Venta
                        </a>
                    </li>

                    <?php if ($esAdministrador): ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="catalogosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-database"></i> Registros
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="catalogosDropdown">
                                <li><a class="dropdown-item" href="productoi.php"><i class="fas fa-box-open me-2"></i>Productos</a></li>
                                <li><a class="dropdown-item" href="rutai.php"><i class="fas fa-map-signs me-2"></i>Rutas</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="personasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-users"></i> Personas
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="personasDropdown">
                                <li><a class="dropdown-item" href="usuarioi.php"><i class="fas fa-user-cog me-2"></i>Usuarios</a></li>
                                <li><a class="dropdown-item" href="clientei.php"><i class="fas fa-user-friends me-2"></i>Clientes</a></li>
                                <li><a class="dropdown-item" href="proveedori.php"><i class="fas fa-truck-loading me-2"></i>Proveedores</a></li>
                            </ul>
                        </li>
                        
                        <!-- Producción Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="produccionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-industry"></i> Producción
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="produccionDropdown">
                                <li><a class="dropdown-item" href="produccioni.php"><i class="fas fa-blender me-2"></i>Elaboración</a></li>
                                <li><a class="dropdown-item" href="empaquetadoi.php"><i class="fas fa-box me-2"></i>Empaquetado</a></li>
                                <li><a class="dropdown-item" href="temporizadori.php"><i class="fas fa-hourglass-half me-2"></i>Temporizador</a></li>
                                <li><a class="dropdown-item" href="materiali.php"><i class="fas fa-pallet me-2"></i>Materiales</a></li>
                                <li><a class="dropdown-item" href="ingredientei.php"><i class="fas fa-carrot me-2"></i>Ingredientes</a></li>
                            </ul>
                        </li>
                    <?php else: ?>

                    <?php endif; ?>
                </ul>
            
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <span class="user-role <?= strtolower($rolTexto) ?>-role">
                                <?= $rolTexto ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li class="dropdown-header text-center">
                                <small class="text-muted">Usuario</small>
                                <h6 class="mt-1 mb-0 text-dark"><?= $nombreUsuario ?></h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($esAdministrador): ?>
                                <li><a class="dropdown-item" href="logi.php"><i class="fas fa-clipboard-list me-2"></i>Registro de actividades</a></li>
                                <li><a class="dropdown-item" href="detventai.php"><i class="fas fa-file-invoice-dollar me-2"></i>Detalles de Ventas</a></li>
                                <li><a class="dropdown-item" href="cajai.php"><i class="fas fa-cash-register me-2"></i>Caja</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Navbar cargado correctamente');
        
        if (typeof bootstrap !== 'undefined') {
            console.log('Bootstrap 5 detectado');
        } else {
            console.warn('Bootstrap 5 no está cargado');
        }
    });
</script>