<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: login.php");
    exit;
}

$nombreUsuario = htmlspecialchars($_SESSION['SISTEMA']['nombre'] ?? 'Usuario');
$id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? 1;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Cesar's Flan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .venta-section {
            margin-top: 20px;
            background-color: #2d2d2d;
            padding: 25px;
            border-radius: 15px;
        }
        .venta-section h3 {
            text-align: center;
            font-size: 2.2rem;
            color: #ffd700;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .btn-success {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000000;
            font-weight: bold;
        }
        .btn-success:hover {
            background-color: #e6c200;
            border-color: #e6c200;
            color: #000000;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
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
        .search-box {
            background-color: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #444;
        }
        .cliente-info {
            background-color: #1a3a1a;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            color: #ffffff;
        }

        .grid-simple {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
            min-height: 70vh;
        }
        .izquierda-section {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
        }
        .derecha-section {
            background-color: #2d2d2d;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #444;
            display: flex;
            flex-direction: column;
        }
        
        .productos-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .dropdown-compacto {
            margin-bottom: 0;
            position: relative;
        }
        .dropdown-compacto .dropdown-menu {
            background-color: #2d2d2d;
            border: 1px solid #ffd700;
            max-height: 400px;
            overflow-y: auto;
            width: auto;
            min-width: 100%;
            left: 0 !important;
            right: 0 !important;
        }
        .dropdown-compacto .dropdown-item {
            color: #ffffff;
            padding: 8px 12px;
            border-bottom: 1px solid #444;
            cursor: pointer;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .dropdown-compacto .dropdown-item:hover {
            background-color: #ffd700;
            color: #000000;
        }
        .producto-dropdown-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 300px;
        }
        .producto-info-compact {
            flex: 1;
            min-width: 0;
        }
        .producto-nombre-compact {
            font-weight: bold;
            color: #ffd700;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .producto-precio-compact {
            color: #28a745;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-left: 10px;
        }
        .producto-stock-compact {
            color: #ffd700;
            font-size: 0.7rem;
        }
        
        .busqueda-rapida {
            background-color: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #444;
        }
        .resultados-busqueda {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .producto-busqueda {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            border: 1px solid #444;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .producto-busqueda:hover {
            background: linear-gradient(135deg, #3d3d3d 0%, #4d4d4d 100%);
            border-color: #ffd700;
        }
        .producto-busqueda-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .producto-busqueda-nombre {
            font-weight: bold;
            color: #ffd700;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        .producto-busqueda-precio {
            color: #28a745;
            font-weight: bold;
            font-size: 0.9rem;
            margin-left: 10px;
            flex-shrink: 0;
        }
        .producto-busqueda-stock {
            color: #ffd700;
            font-size: 0.7rem;
        }
        
        .carrito-container {
            margin-top: 20px;
        }
        .tabla-carrito {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .tabla-carrito table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .tabla-carrito th {
            background-color: #ffd700;
            color: #000000;
            border: none;
            font-weight: bold;
            text-align: center;
            padding: 12px 8px;
            font-size: 0.9rem;
        }
        .tabla-carrito td {
            background-color: #ffffff;
            color: #333333;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
            padding: 10px 8px;
            vertical-align: middle;
        }
        .tabla-carrito .cantidad-input {
            width: 60px;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            color: #333333;
            text-align: center;
            border-radius: 4px;
            padding: 4px;
        }
        .carrito-vacio {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            font-style: italic;
            background: #1a1a1a;
            border-radius: 8px;
            border: 1px solid #444;
        }
        
        .total-display {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #000000;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 2rem;
            margin-bottom: 25px;
        }
        .pago-section {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        .cambio-section {
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .cambio-section .form-control {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border-radius: 10px;
            border: 1px solid #444;
        }
        .suggestions {
            position: absolute;
            background: #2d2d2d;
            border: 1px solid #444;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            border-radius: 5px;
        }
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            color: #ffffff;
            border-bottom: 1px solid #444;
        }
        .suggestion-item:hover {
            background-color: #ffd700;
            color: #000000;
        }

        .nav-tabs {
            border-bottom: 2px solid #ffd700;
            margin-bottom: 25px;
        }
        .nav-tabs .nav-link {
            color: #cccccc;
            background-color: #2d2d2d;
            border: 1px solid #444;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
            padding: 12px 25px;
        }
        .nav-tabs .nav-link.active {
            color: #000000;
            background-color: #ffd700;
            border-color: #ffd700;
        }
        .nav-tabs .nav-link:hover {
            color: #ffffff;
            background-color: #3d3d3d;
            border-color: #ffd700;
        }

        .ruta-clientes-container {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        
        .clientes-ruta-list {
            max-height: 400px; 
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .clientes-ruta-list::-webkit-scrollbar {
            width: 8px;
        }
        .clientes-ruta-list::-webkit-scrollbar-track {
            background: #1a1a1a;
            border-radius: 4px;
        }
        .clientes-ruta-list::-webkit-scrollbar-thumb {
            background: #4ecdc4;
            border-radius: 4px;
        }
        .clientes-ruta-list::-webkit-scrollbar-thumb:hover {
            background: #3bb4ac;
        }
        
        .cliente-ruta-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .cliente-ruta-card:hover {
            border-color: #4ecdc4;
            transform: translateY(-2px);
        }
        .cliente-ruta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .cliente-ruta-nombre {
            font-weight: bold;
            color: #4ecdc4;
            font-size: 1.1rem;
        }
        .cliente-ruta-info {
            color: #cccccc;
            font-size: 0.9rem;
        }
        .btn-ruta {
            background-color: #4ecdc4;
            border-color: #4ecdc4;
            color: #000000;
            font-weight: bold;
        }
        .btn-ruta:hover {
            background-color: #3bb4ac;
            border-color: #3bb4ac;
            color: #000000;
        }

        .carrito-ruta-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
        }
        .cliente-header-ruta {
            background: #4ecdc4;
            color: #000000;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .producto-ruta-locked {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .producto-ruta-locked:last-child {
            border-bottom: none;
        }

        .modal-ruta .modal-content {
            background-color: #2d2d2d;
            color: #ffffff;
            border: 1px solid #4ecdc4;
        }
        .modal-ruta .modal-header {
            border-bottom: 1px solid #444;
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
        }
        .modal-ruta .modal-footer {
            border-top: 1px solid #444;
        }

        .dropdown-modal-ruta {
            margin-bottom: 15px;
        }
        .dropdown-modal-ruta .dropdown-menu {
            background-color: #2d2d2d;
            border: 1px solid #4ecdc4;
            max-height: 300px;
            overflow-y: auto;
            width: 100%;
        }
        .dropdown-modal-ruta .dropdown-item {
            color: #ffffff;
            padding: 8px 12px;
            border-bottom: 1px solid #444;
            cursor: pointer;
        }
        .dropdown-modal-ruta .dropdown-item:hover {
            background-color: #4ecdc4;
            color: #000000;
        }
        .productos-seleccionados-container {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border: 1px solid #444;
        }
        .producto-seleccionado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            margin-bottom: 5px;
            background: rgba(78, 205, 196, 0.1);
            border-radius: 4px;
            border-left: 3px solid #4ecdc4;
        }
        .cantidad-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cantidad-control input {
            width: 70px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #ffffff;
            text-align: center;
            border-radius: 4px;
        }

        @media (max-width: 992px) {
            .grid-simple {
                grid-template-columns: 1fr;
            }
            .derecha-section {
                height: auto;
            }
            .productos-container {
                grid-template-columns: 1fr;
            }
            .dropdown-compacto .dropdown-menu {
                width: 100%;
            }
            .clientes-ruta-list {
                max-height: 300px; 
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-main">
        <div class="venta-section">
            <h3>🏪 Punto de Venta</h3>
            
            <div class="header-info">
                <div class="text-warning">
                    <strong>Vendedor:</strong> <?php echo $nombreUsuario; ?>
                </div>
                <div class="text-light">
                    <strong>Fecha:</strong> <span id="fecha-actual"></span>
                </div>
                <div class="text-light">
                    <strong>Hora:</strong> <span id="hora-actual"></span>
                </div>
            </div>

            <ul class="nav nav-tabs" id="ventaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="normal-tab" data-bs-toggle="tab" data-bs-target="#normal" type="button" role="tab">
                        🏪 Venta Normal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ruta-tab" data-bs-toggle="tab" data-bs-target="#ruta" type="button" role="tab">
                        🚚 Venta en Ruta
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="ventaTabsContent">
                
                <div class="tab-pane fade show active" id="normal" role="tabpanel">
                    <div class="grid-simple">
                        <div class="izquierda-section">
                            <h5 class="text-warning mb-4">🛒 Proceso de Venta Normal</h5>
                            
                            <div class="search-box">
                                <label class="form-label text-warning mb-3"><strong>👤 Cliente</strong></label>
                                <input type="text" class="form-control" id="searchCliente" 
                                       placeholder="Buscar cliente por nombre o teléfono..."
                                       onkeyup="buscarClientes()">
                                <div id="clienteSuggestions" class="suggestions" style="display: none;"></div>
                                
                                <div id="clienteInfo" class="cliente-info" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Cliente seleccionado:</strong>
                                            <span id="clienteNombre" class="text-warning"></span>
                                            <small class="text-light d-block" id="clienteTelefono"></small>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="quitarCliente()">
                                            ❌ Quitar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="search-box">
                                <label class="form-label text-warning mb-3"><strong>🛍️ Agregar Productos</strong></label>
                                
                                <div class="productos-container">

                                    <div class="dropdown-compacto">
                                        <button class="btn btn-warning w-100 text-dark dropdown-toggle" type="button" 
                                                id="dropdownProductosBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                            📦 Todos los Productos
                                        </button>
                                        
                                        <ul class="dropdown-menu" id="dropdownProductos" aria-labelledby="dropdownProductosBtn">
                                            <li class="text-center text-light p-3">
                                                <div class="spinner-border text-warning" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="mt-2 mb-0">Cargando productos...</p>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="busqueda-rapida">
                                        <label class="form-label text-warning mb-2"><small>🔍 Búsqueda Rápida</small></label>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control form-control-sm" id="busquedaProducto" 
                                                   placeholder="Buscar producto..."
                                                   onkeyup="filtrarProductos()">
                                        </div>
                                        <div class="resultados-busqueda" id="resultadosBusqueda">
                                            <div class="text-center text-light p-2">
                                                <small>Escribe para buscar productos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="carrito-container">
                                <h6 class="text-warning mb-3">📦 Productos en el Carrito</h6>
                                <div class="tabla-carrito">
                                    <div id="carritoContenido">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Precio Unit.</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cuerpoCarrito">
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">
                                                        <div class="carrito-vacio">
                                                            <p>No hay productos en el carrito</p>
                                                            <small>Selecciona productos del dropdown o búsqueda</small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="derecha-section">
                            <h5 class="text-warning mb-4">💰 Resumen de Pago</h5>
                            
                            <div class="total-display">
                                TOTAL A PAGAR<br>
                                <span id="totalVenta">$0.00</span>
                            </div>

                            <div class="pago-section">
                                <label class="form-label text-warning mb-3"><strong>💵 Efectivo Recibido</strong></label>
                                <input type="number" class="form-control form-control-lg" id="efectivo" 
                                       step="0.01" placeholder="0.00" onchange="calcularCambio()">
                            </div>

                            <div class="cambio-section">
                                <label class="form-label mb-3"><strong>🔄 Cambio</strong></label>
                                <input type="text" class="form-control form-control-lg" id="cambio" readonly value="0.00">
                            </div>

                            <button class="btn btn-success btn-lg w-100 py-3 mt-auto" onclick="procesarVenta()">
                                ✅ FINALIZAR VENTA
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="ruta" role="tabpanel">
                    <div class="grid-simple">
                        <div class="izquierda-section">
                            <h5 class="text-warning mb-4">🚚 Venta en Ruta</h5>
                            
                            <div class="ruta-clientes-container">
                                <label class="form-label text-warning mb-3"><strong>📋 Clientes de Ruta para Hoy</strong></label>
                                <div class="clientes-ruta-list" id="clientesRutaContainer">
                                    <div class="text-center text-light p-4">
                                        <div class="spinner-border text-warning" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <p class="mt-2 mb-0">Cargando clientes de ruta...</p>
                                    </div>
                                </div>
                            </div>

                            <div class="carrito-container">
                                <h6 class="text-warning mb-3">📦 Ventas de Ruta Registradas</h6>
                                <div class="tabla-carrito">
                                    <div id="carritoRutaContenido">
                                        <div class="carrito-vacio">
                                            <p>No hay ventas de ruta registradas</p>
                                            <small>Selecciona clientes y agrega productos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="derecha-section">
                            <h5 class="text-warning mb-4">💰 Resumen de Ruta</h5>
                            
                            <div class="total-display" style="background: linear-gradient(135deg, #4ecdc4 0%, #44b7b0 100%);">
                                TOTAL RUTA<br>
                                <span id="totalRuta">$0.00</span>
                            </div>

                            <div class="pago-section">
                                <label class="form-label text-warning mb-3"><strong>💵 Efectivo Total Recibido</strong></label>
                                <input type="number" class="form-control form-control-lg" id="efectivoRuta" 
                                       step="0.01" placeholder="0.00" onchange="calcularCambioRuta()">
                            </div>

                            <div class="cambio-section" style="background: linear-gradient(135deg, #4ecdc4 0%, #44b7b0 100%);">
                                <label class="form-label mb-3"><strong>🔄 Cambio Ruta</strong></label>
                                <input type="text" class="form-control form-control-lg" id="cambioRuta" readonly value="0.00">
                            </div>

                            <button class="btn btn-ruta btn-lg w-100 py-3 mt-auto" onclick="procesarVentaRuta()">
                                ✅ FINALIZAR VENTA DE RUTA
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-ruta" id="modalVentaRuta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning" id="modalVentaRutaTitle">Venta en Ruta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalVentaRutaContent">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-ruta" onclick="agregarVentaRuta()">Agregar a Ruta</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let carrito = [];
        let carritoRuta = [];
        let totalVenta = 0;
        let totalRuta = 0;
        let clienteSeleccionado = null;
        let todosProductos = [];
        let clienteRutaActual = null;
        let productosSeleccionadosRuta = [];

        function actualizarHora() {
            const ahora = new Date();
            document.getElementById('fecha-actual').textContent = ahora.toLocaleDateString('es-MX');
            document.getElementById('hora-actual').textContent = ahora.toLocaleTimeString('es-MX');
        }
        setInterval(actualizarHora, 1000);
        actualizarHora();

        document.addEventListener('DOMContentLoaded', function() {
            cargarProductos();
            cargarClientesRuta();
            
            const tab = new bootstrap.Tab(document.getElementById('normal-tab'));
            tab.show();
        });

        function cargarProductos() {
            fetch(`pventaf.php?action=buscarProductos`)
                .then(response => response.json())
                .then(productos => {
                    todosProductos = productos;
                    actualizarDropdownProductos(productos);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('dropdownProductos').innerHTML = '<li class="text-center text-light p-3">Error al cargar productos</li>';
                });
        }

        function actualizarDropdownProductos(productos) {
            const dropdown = document.getElementById('dropdownProductos');
            
            if (productos.error) {
                dropdown.innerHTML = '<li class="text-center text-light p-3">Error: ' + productos.error + '</li>';
                return;
            }
            
            if (productos.length === 0) {
                dropdown.innerHTML = '<li class="text-center text-light p-3">No hay productos disponibles</li>';
                return;
            }
            
            dropdown.innerHTML = '';
            
            productos.forEach(producto => {
                const item = document.createElement('li');
                item.innerHTML = `
                    <a class="dropdown-item" href="#" onclick="agregarAlCarrito(${producto.id_producto})">
                        <div class="producto-dropdown-compact">
                            <div class="producto-info-compact">
                                <div class="producto-nombre-compact">${producto.nombre}</div>
                                <div class="producto-stock-compact">Stock: ${producto.stock}</div>
                            </div>
                            <div class="producto-precio-compact">
                                $${parseFloat(producto.precio).toFixed(2)}
                            </div>
                        </div>
                    </a>
                `;
                dropdown.appendChild(item);
            });
        }

        function filtrarProductos() {
            const busqueda = document.getElementById('busquedaProducto').value.toLowerCase();
            const resultados = document.getElementById('resultadosBusqueda');
            
            if (busqueda.length < 2) {
                resultados.innerHTML = '<div class="text-center text-light p-2"><small>Escribe para buscar productos</small></div>';
                return;
            }
            
            const productosFiltrados = todosProductos.filter(producto => 
                producto.nombre.toLowerCase().includes(busqueda) ||
                producto.id_producto.toString().includes(busqueda)
            );
            
            if (productosFiltrados.length === 0) {
                resultados.innerHTML = '<div class="text-center text-light p-2"><small>No se encontraron productos</small></div>';
                return;
            }
            
            resultados.innerHTML = '';
            
            productosFiltrados.forEach(producto => {
                const productoElement = document.createElement('div');
                productoElement.className = 'producto-busqueda';
                productoElement.onclick = () => agregarAlCarrito(producto.id_producto);
                productoElement.innerHTML = `
                    <div class="producto-busqueda-info">
                        <div class="producto-busqueda-nombre">${producto.nombre}</div>
                        <div class="producto-busqueda-precio">$${parseFloat(producto.precio).toFixed(2)}</div>
                    </div>
                    <div class="producto-busqueda-stock">Stock: ${producto.stock} | Código: ${producto.id_producto}</div>
                `;
                resultados.appendChild(productoElement);
            });
        }

        function agregarAlCarrito(idProducto) {
            const productoSeleccionado = todosProductos.find(producto => producto.id_producto == idProducto);
            
            if (!productoSeleccionado) {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'El producto seleccionado no existe en el sistema',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const existente = carrito.find(item => item.id_producto === productoSeleccionado.id_producto);
            
            if (existente) {
                if (existente.cantidad < productoSeleccionado.stock) {
                    existente.cantidad++;
                    existente.subtotal = existente.cantidad * existente.precio;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stock insuficiente',
                        text: 'No hay suficiente stock disponible',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    });
                    return;
                }
            } else {
                if (productoSeleccionado.stock > 0) {
                    carrito.push({
                        id_producto: productoSeleccionado.id_producto,
                        nombre: productoSeleccionado.nombre,
                        precio: parseFloat(productoSeleccionado.precio),
                        cantidad: 1,
                        subtotal: parseFloat(productoSeleccionado.precio)
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Producto sin stock',
                        text: 'Este producto no tiene stock disponible',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    });
                    return;
                }
            }
            
            actualizarCarrito();
            document.getElementById('busquedaProducto').value = '';
            document.getElementById('resultadosBusqueda').innerHTML = '<div class="text-center text-light p-2"><small>Escribe para buscar productos</small></div>';
        }

        function actualizarCarrito() {
            const cuerpo = document.getElementById('cuerpoCarrito');
            
            if (carrito.length === 0) {
                cuerpo.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="carrito-vacio">
                                <p>No hay productos en el carrito</p>
                                <small>Selecciona productos del dropdown o búsqueda</small>
                            </div>
                        </td>
                    </tr>
                `;
                document.getElementById('totalVenta').textContent = '0.00';
                return;
            }
            
            cuerpo.innerHTML = '';
            totalVenta = 0;
            
            carrito.forEach((item, index) => {
                totalVenta += item.subtotal;
                
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td class="fw-bold">${item.nombre}</td>
                    <td class="text-success fw-bold">$${item.precio.toFixed(2)}</td>
                    <td>
                        <input type="number" class="cantidad-input" value="${item.cantidad}" min="1" 
                               onchange="actualizarCantidad(${index}, this.value)">
                    </td>
                    <td class="text-success fw-bold">$${item.subtotal.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="eliminarDelCarrito(${index})">
                            ❌
                        </button>
                    </td>
                `;
                cuerpo.appendChild(fila);
            });
            
            document.getElementById('totalVenta').textContent = totalVenta.toFixed(2);
            calcularCambio();
        }

        function actualizarCantidad(index, nuevaCantidad) {
            nuevaCantidad = parseInt(nuevaCantidad);
            if (nuevaCantidad > 0) {
                carrito[index].cantidad = nuevaCantidad;
                carrito[index].subtotal = carrito[index].cantidad * carrito[index].precio;
                actualizarCarrito();
            }
        }

        function eliminarDelCarrito(index) {
            carrito.splice(index, 1);
            actualizarCarrito();
        }

        function calcularCambio() {
            const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
            const cambio = efectivo - totalVenta;
            document.getElementById('cambio').value = cambio >= 0 ? cambio.toFixed(2) : '0.00';
        }

        function buscarClientes() {
            const search = document.getElementById('searchCliente').value;
            const suggestions = document.getElementById('clienteSuggestions');
            
            if (search.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            
            fetch(`pventaf.php?action=buscarClientes&search=${encodeURIComponent(search)}`)
                .then(response => response.json())
                .then(clientes => {
                    suggestions.innerHTML = '';
                    
                    if (clientes.error) return;
                    
                    if (clientes.length > 0) {
                        clientes.forEach(cliente => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.innerHTML = `${cliente.nombre} | ${cliente.telefono || 'Sin teléfono'}`;
                            item.onclick = () => seleccionarCliente(cliente);
                            suggestions.appendChild(item);
                        });
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function seleccionarCliente(cliente) {
            clienteSeleccionado = cliente;
            document.getElementById('clienteNombre').textContent = cliente.nombre;
            document.getElementById('clienteTelefono').textContent = cliente.telefono ? `Tel: ${cliente.telefono}` : '';
            document.getElementById('clienteInfo').style.display = 'block';
            document.getElementById('searchCliente').value = '';
            document.getElementById('clienteSuggestions').style.display = 'none';
        }

        function quitarCliente() {
            clienteSeleccionado = null;
            document.getElementById('clienteInfo').style.display = 'none';
            document.getElementById('searchCliente').value = '';
        }

        function procesarVenta() {
            if (carrito.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Carrito Vacío',
                    text: 'No hay productos en el carrito',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
            const cambio = parseFloat(document.getElementById('cambio').value) || 0;
            
            if (efectivo < totalVenta) {
                Swal.fire({
                    icon: 'error',
                    title: 'Efectivo Insuficiente',
                    html: `
                        <p>El efectivo recibido es menor al total de la venta.</p>
                        <p><strong>Total:</strong> $${totalVenta.toFixed(2)}</p>
                        <p><strong>Efectivo:</strong> $${efectivo.toFixed(2)}</p>
                        <p><strong>Faltante:</strong> $${(totalVenta - efectivo).toFixed(2)}</p>
                    `,
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const data = {
                action: 'procesarVenta',
                carrito: carrito,
                total: totalVenta,
                efectivo: efectivo,
                cambio: cambio,
                tipoVenta: 'CONTADO',
                idCliente: clienteSeleccionado ? clienteSeleccionado.id_cliente : null,
                idUsuario: <?php echo $id_usuario; ?>
            };
            
            fetch('pventaf.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Venta Exitosa',
                        html: `
                            <p><strong>Folio:</strong> ${result.folio}</p>
                            <p><strong>Total:</strong> $${totalVenta.toFixed(2)}</p>
                            <p><strong>Efectivo:</strong> $${efectivo.toFixed(2)}</p>
                            <p><strong>Cambio:</strong> $${cambio.toFixed(2)}</p>
                            ${clienteSeleccionado ? `<p><strong>Cliente:</strong> ${clienteSeleccionado.nombre}</p>` : ''}
                        `,
                        background: '#2d2d2d',
                        color: '#ffffff',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        carrito = [];
                        clienteSeleccionado = null;
                        actualizarCarrito();
                        document.getElementById('efectivo').value = '';
                        document.getElementById('clienteInfo').style.display = 'none';
                        document.getElementById('searchCliente').value = '';
                        cargarProductos();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al procesar la venta',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: error.message,
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
            });
        }

        // ========== FUNCIONES VENTA EN RUTA ==========
        function cargarClientesRuta() {
            fetch('pventa_rutaf.php?action=obtenerClientesRuta')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('clientesRutaContainer');
                    
                    if (data.error) {
                        container.innerHTML = `<div class="text-center text-light p-3">Error: ${data.error}</div>`;
                        return;
                    }
                    
                    if (data.info) {
                        container.innerHTML = `
                            <div class="text-center text-light p-4">
                                <i class="fas fa-route fa-3x mb-3" style="color: #4ecdc4;"></i>
                                <h5 style="color: #4ecdc4;">${data.info}</h5>
                                <p>No hay clientes de ruta programados para hoy.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    if (data.length === 0) {
                        container.innerHTML = `
                            <div class="text-center text-light p-4">
                                <i class="fas fa-route fa-3x mb-3" style="color: #4ecdc4;"></i>
                                <h5 style="color: #4ecdc4;">No hay rutas para hoy</h5>
                                <p>No hay clientes de ruta programados para hoy.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    container.innerHTML = '';
                    
                    const clientesOrdenados = data.sort((a, b) => {
                        if (a.orden && b.orden) {
                            return a.orden - b.orden;
                        }
                        return a.nombre_cliente.localeCompare(b.nombre_cliente);
                    });
                    
                    clientesOrdenados.forEach(cliente => {
                        const card = document.createElement('div');
                        card.className = 'cliente-ruta-card';
                        
                        const yaVendidoEnCarrito = carritoRuta.some(venta => venta.id_cliente === cliente.id_cliente);
                        const yaVendidoEnBD = cliente.estado_venta === 'completada';
                        const yaVendido = yaVendidoEnCarrito || yaVendidoEnBD;
                        
                        card.innerHTML = `
                            <div class="cliente-ruta-header">
                                <div class="cliente-ruta-nombre">
                                    ${yaVendido ? '✅' : '🏠'} ${cliente.nombre_cliente}
                                    ${cliente.orden ? ` | #${cliente.orden}` : ''}
                                    ${yaVendido ? '<span class="badge bg-success ms-2">Completado</span>' : ''}
                                </div>
                                ${!yaVendido ? 
                                    `<button class="btn btn-ruta btn-sm" onclick="abrirModalVentaRuta(${cliente.id_ruta}, ${cliente.id_cliente}, '${cliente.nombre_cliente.replace(/'/g, "\\'")}')">
                                        🛒 Vender
                                    </button>` :
                                    `<button class="btn btn-success btn-sm" disabled>
                                        ✅ Completado
                                    </button>`
                                }
                            </div>
                            <div class="cliente-ruta-info">
                                📍 ${cliente.direccion || 'Sin dirección'} | 
                                📞 ${cliente.telefono || 'Sin teléfono'}
                                ${cliente.cantidad_producto ? ` | 📦 Producto habitual: ${cliente.cantidad_producto} unidades` : ''}
                                ${cliente.vendedor ? ` | 👤 ${cliente.vendedor}` : ''}
                            </div>
                        `;
                        
                        if (yaVendido) {
                            card.style.opacity = '0.7';
                            card.style.borderLeft = '5px solid #28a745';
                            card.style.background = 'linear-gradient(135deg, #1a3a1a 0%, #2d2d2d 100%)';
                        }
                        
                        container.appendChild(card);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('clientesRutaContainer').innerHTML = '<div class="text-center text-light p-3">Error al cargar clientes de ruta</div>';
                });
        }

        function abrirModalVentaRuta(idRuta, idCliente, nombreCliente) {
            clienteRutaActual = { id_ruta: idRuta, id_cliente: idCliente, nombre: nombreCliente };
            productosSeleccionadosRuta = [];
            
            const modalTitle = document.getElementById('modalVentaRutaTitle');
            const modalContent = document.getElementById('modalVentaRutaContent');
            
            modalTitle.textContent = `Venta en Ruta - ${nombreCliente}`;
            
            modalContent.innerHTML = `
                <div class="mb-4">
                    <label class="form-label text-warning"><strong>🛍️ Seleccionar Productos</strong></label>
                    
                    <!-- Dropdown Compacto para Ruta -->
                    <div class="dropdown-modal-ruta">
                        <button class="btn btn-ruta w-100 text-dark dropdown-toggle" type="button" 
                                id="dropdownProductosRutaBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            📦 Seleccionar Productos
                        </button>
                        
                        <ul class="dropdown-menu" id="dropdownProductosRuta" aria-labelledby="dropdownProductosRutaBtn">
                            <li class="text-center text-light p-3">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0">Cargando productos...</p>
                            </li>
                        </ul>
                    </div>

                    <!-- Búsqueda Rápida para Ruta -->
                    <div class="busqueda-rapida mt-3">
                        <label class="form-label text-warning mb-2"><small>🔍 Búsqueda Rápida</small></label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control form-control-sm" id="busquedaProductoRuta" 
                                   placeholder="Buscar producto..."
                                   onkeyup="filtrarProductosRuta()">
                        </div>
                        <div class="resultados-busqueda" id="resultadosBusquedaRuta">
                            <div class="text-center text-light p-2">
                                <small>Escribe para buscar productos</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Productos Seleccionados -->
                <div class="mb-4">
                    <label class="form-label text-warning"><strong>📋 Productos a Entregar</strong></label>
                    <div class="productos-seleccionados-container" id="productosSeleccionadosRuta">
                        <div class="text-center text-light p-3">
                            <small>No hay productos seleccionados</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-warning"><strong>💵 Pago Recibido</strong></label>
                    <input type="number" class="form-control" id="pagoRutaModal" step="0.01" placeholder="0.00" value="0">
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-warning"><strong>📝 Detalles/Observaciones</strong></label>
                    <textarea class="form-control" id="detallesRutaModal" rows="3" placeholder="Observaciones sobre la entrega..."></textarea>
                </div>
                
                <div class="alert alert-info">
                    <small><strong>💡 Nota:</strong> Los productos seleccionados se agregarán al carrito de ruta y no podrán ser eliminados.</small>
                </div>
            `;
            
            cargarDropdownProductosRuta();
            
            const modal = new bootstrap.Modal(document.getElementById('modalVentaRuta'));
            modal.show();
        }

        function cargarDropdownProductosRuta() {
            const dropdown = document.getElementById('dropdownProductosRuta');
            
            if (todosProductos.length === 0) {
                cargarProductos().then(() => {
                    actualizarDropdownProductosRuta();
                });
            } else {
                actualizarDropdownProductosRuta();
            }
        }

        function actualizarDropdownProductosRuta() {
            const dropdown = document.getElementById('dropdownProductosRuta');
            
            if (todosProductos.error) {
                dropdown.innerHTML = '<li class="text-center text-light p-3">Error: ' + todosProductos.error + '</li>';
                return;
            }
            
            if (todosProductos.length === 0) {
                dropdown.innerHTML = '<li class="text-center text-light p-3">No hay productos disponibles</li>';
                return;
            }
            
            dropdown.innerHTML = '';
            
            todosProductos.forEach(producto => {
                if (producto.stock > 0) {
                    const item = document.createElement('li');
                    item.innerHTML = `
                        <a class="dropdown-item" href="#" onclick="agregarProductoRuta(${producto.id_producto})">
                            <div class="producto-dropdown-compact">
                                <div class="producto-info-compact">
                                    <div class="producto-nombre-compact">${producto.nombre}</div>
                                    <div class="producto-stock-compact">Stock: ${producto.stock}</div>
                                </div>
                                <div class="producto-precio-compact">
                                    $${parseFloat(producto.precio).toFixed(2)}
                                </div>
                            </div>
                        </a>
                    `;
                    dropdown.appendChild(item);
                }
            });
        }

        function filtrarProductosRuta() {
            const busqueda = document.getElementById('busquedaProductoRuta').value.toLowerCase();
            const resultados = document.getElementById('resultadosBusquedaRuta');
            
            if (busqueda.length < 2) {
                resultados.innerHTML = '<div class="text-center text-light p-2"><small>Escribe para buscar productos</small></div>';
                return;
            }
            
            const productosFiltrados = todosProductos.filter(producto => 
                producto.nombre.toLowerCase().includes(busqueda) ||
                producto.id_producto.toString().includes(busqueda)
            );
            
            if (productosFiltrados.length === 0) {
                resultados.innerHTML = '<div class="text-center text-light p-2"><small>No se encontraron productos</small></div>';
                return;
            }
            
            resultados.innerHTML = '';
            
            productosFiltrados.forEach(producto => {
                if (producto.stock > 0) {
                    const productoElement = document.createElement('div');
                    productoElement.className = 'producto-busqueda';
                    productoElement.onclick = () => agregarProductoRuta(producto.id_producto);
                    productoElement.innerHTML = `
                        <div class="producto-busqueda-info">
                            <div class="producto-busqueda-nombre">${producto.nombre}</div>
                            <div class="producto-busqueda-precio">$${parseFloat(producto.precio).toFixed(2)}</div>
                        </div>
                        <div class="producto-busqueda-stock">Stock: ${producto.stock} | Código: ${producto.id_producto}</div>
                    `;
                    resultados.appendChild(productoElement);
                }
            });
        }

        function agregarProductoRuta(idProducto) {
            const productoSeleccionado = todosProductos.find(producto => producto.id_producto == idProducto);
            
            if (!productoSeleccionado) {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'El producto seleccionado no existe en el sistema',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const existente = productosSeleccionadosRuta.find(item => item.id_producto === productoSeleccionado.id_producto);
            
            if (existente) {
                if (existente.cantidad < productoSeleccionado.stock) {
                    existente.cantidad++;
                    existente.subtotal = existente.cantidad * existente.precio;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stock insuficiente',
                        text: 'No hay suficiente stock disponible',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    });
                    return;
                }
            } else {
                productosSeleccionadosRuta.push({
                    id_producto: parseInt(productoSeleccionado.id_producto),
                    nombre: productoSeleccionado.nombre,
                    precio: parseFloat(productoSeleccionado.precio),
                    cantidad: 1,
                    subtotal: parseFloat(productoSeleccionado.precio)
                });
            }
            
            actualizarProductosSeleccionadosRuta();
            
            document.getElementById('busquedaProductoRuta').value = '';
            document.getElementById('resultadosBusquedaRuta').innerHTML = '<div class="text-center text-light p-2"><small>Escribe para buscar productos</small></div>';
        }

        function actualizarProductosSeleccionadosRuta() {
            const container = document.getElementById('productosSeleccionadosRuta');
            
            if (productosSeleccionadosRuta.length === 0) {
                container.innerHTML = '<div class="text-center text-light p-3"><small>No hay productos seleccionados</small></div>';
                return;
            }
            
            let html = '';
            let total = 0;
            
            productosSeleccionadosRuta.forEach((producto, index) => {
                total += producto.subtotal;
                
                html += `
                    <div class="producto-seleccionado">
                        <div>
                            <strong>${producto.nombre}</strong>
                            <br>
                            <small>$${producto.precio.toFixed(2)} c/u</small>
                        </div>
                        <div class="cantidad-control">
                            <button class="btn btn-sm btn-outline-light" onclick="cambiarCantidadRuta(${index}, -1)">-</button>
                            <input type="number" value="${producto.cantidad}" min="1" max="${todosProductos.find(p => p.id_producto == producto.id_producto)?.stock || 1}" 
                                   onchange="actualizarCantidadRuta(${index}, this.value)" 
                                   style="width: 60px; background: #1a1a1a; border: 1px solid #444; color: #ffffff; text-align: center;">
                            <button class="btn btn-sm btn-outline-light" onclick="cambiarCantidadRuta(${index}, 1)">+</button>
                            <span class="text-warning ms-2"><strong>$${producto.subtotal.toFixed(2)}</strong></span>
                            <button class="btn btn-sm btn-outline-danger ms-2" onclick="eliminarProductoRuta(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `
                <div class="mt-3 p-2 text-center" style="background: rgba(255, 215, 0, 0.1); border-radius: 4px;">
                    <strong class="text-warning">Total: $${total.toFixed(2)}</strong>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function cambiarCantidadRuta(index, cambio) {
            const producto = productosSeleccionadosRuta[index];
            const nuevoStock = producto.cantidad + cambio;
            const stockDisponible = todosProductos.find(p => p.id_producto == producto.id_producto)?.stock || 0;
            
            if (nuevoStock < 1) {
                eliminarProductoRuta(index);
                return;
            }
            
            if (nuevoStock > stockDisponible) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock insuficiente',
                    text: 'No hay suficiente stock disponible',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            producto.cantidad = nuevoStock;
            producto.subtotal = producto.cantidad * producto.precio;
            actualizarProductosSeleccionadosRuta();
        }

        function actualizarCantidadRuta(index, nuevaCantidad) {
            nuevaCantidad = parseInt(nuevaCantidad);
            const producto = productosSeleccionadosRuta[index];
            const stockDisponible = todosProductos.find(p => p.id_producto == producto.id_producto)?.stock || 0;
            
            if (nuevaCantidad < 1) {
                eliminarProductoRuta(index);
                return;
            }
            
            if (nuevaCantidad > stockDisponible) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock insuficiente',
                    text: 'No hay suficiente stock disponible',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                producto.cantidad = stockDisponible;
                producto.subtotal = producto.cantidad * producto.precio;
            } else {
                producto.cantidad = nuevaCantidad;
                producto.subtotal = producto.cantidad * producto.precio;
            }
            
            actualizarProductosSeleccionadosRuta();
        }

        function eliminarProductoRuta(index) {
            productosSeleccionadosRuta.splice(index, 1);
            actualizarProductosSeleccionadosRuta();
        }

        function agregarVentaRuta() {
            if (!clienteRutaActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cliente no seleccionado',
                    text: 'No hay cliente seleccionado para esta venta',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            if (productosSeleccionadosRuta.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Productos requeridos',
                    text: 'Debes seleccionar al menos un producto',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const pago = parseFloat(document.getElementById('pagoRutaModal').value) || 0;
            const detalles = document.getElementById('detallesRutaModal').value;
            
            const totalVentaCliente = productosSeleccionadosRuta.reduce((sum, producto) => sum + producto.subtotal, 0);
        
            carritoRuta.push({
                id_ruta: parseInt(clienteRutaActual.id_ruta),
                id_cliente: parseInt(clienteRutaActual.id_cliente),
                nombre_cliente: clienteRutaActual.nombre,
                pago_recibido: pago,
                detalles: detalles,
                productos: productosSeleccionadosRuta.map(producto => ({
                    id_producto: parseInt(producto.id_producto),
                    nombre: producto.nombre,
                    precio: parseFloat(producto.precio),
                    cantidad: parseInt(producto.cantidad),
                    subtotal: parseFloat(producto.subtotal)
                })),
                total: parseFloat(totalVentaCliente)
            });
            
            productosSeleccionadosRuta = [];
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalVentaRuta'));
            modal.hide();
            
            clienteRutaActual = null;
            
            actualizarCarritoRuta();
            cargarClientesRuta(); 
            
            Swal.fire({
                icon: 'success',
                title: 'Venta agregada',
                text: 'Venta agregada al carrito de ruta exitosamente',
                background: '#2d2d2d',
                color: '#ffffff',
                timer: 2000
            });
        }

        function actualizarCarritoRuta() {
            const container = document.getElementById('carritoRutaContenido');
            
            if (carritoRuta.length === 0) {
                container.innerHTML = `
                    <div class="carrito-vacio">
                        <p>No hay ventas de ruta registradas</p>
                        <small>Selecciona clientes y agrega productos</small>
                    </div>
                `;
                document.getElementById('totalRuta').textContent = '0.00';
                return;
            }
            
            let html = '';
            totalRuta = 0;
            
            carritoRuta.forEach((venta, index) => {
                totalRuta += venta.total;
                
                html += `
                    <div class="carrito-ruta-item">
                        <div class="cliente-header-ruta">
                            🏠 ${venta.nombre_cliente} - $${venta.total.toFixed(2)}
                        </div>
                        ${venta.productos.map(producto => `
                            <div class="producto-ruta-locked">
                                <span>${producto.nombre} x${producto.cantidad}</span>
                                <span class="text-success">$${producto.subtotal.toFixed(2)}</span>
                            </div>
                        `).join('')}
                        <div class="mt-2">
                            <small class="text-muted">
                                💵 Pago: $${venta.pago_recibido.toFixed(2)} | 
                                📝 ${venta.detalles || 'Sin observaciones'}
                            </small>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            document.getElementById('totalRuta').textContent = totalRuta.toFixed(2);
            calcularCambioRuta();
        }

        function calcularCambioRuta() {
            const efectivo = parseFloat(document.getElementById('efectivoRuta').value) || 0;
            const cambio = efectivo - totalRuta;
            document.getElementById('cambioRuta').value = cambio >= 0 ? cambio.toFixed(2) : '0.00';
        }

        function procesarVentaRuta() {
            if (carritoRuta.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin ventas de ruta',
                    text: 'No hay ventas de ruta registradas',
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const efectivo = parseFloat(document.getElementById('efectivoRuta').value) || 0;
            const cambio = parseFloat(document.getElementById('cambioRuta').value) || 0;
            
            if (efectivo < totalRuta) {
                Swal.fire({
                    icon: 'error',
                    title: 'Efectivo Insuficiente',
                    html: `
                        <p>El efectivo recibido es menor al total de la ruta.</p>
                        <p><strong>Total Ruta:</strong> $${totalRuta.toFixed(2)}</p>
                        <p><strong>Efectivo:</strong> $${efectivo.toFixed(2)}</p>
                        <p><strong>Faltante:</strong> $${(totalRuta - efectivo).toFixed(2)}</p>
                    `,
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
                return;
            }
            
            const ventasParaEnviar = carritoRuta.map(venta => {
                return {
                    id_ruta: venta.id_ruta,
                    id_cliente: venta.id_cliente,
                    nombre_cliente: venta.nombre_cliente,
                    pago_recibido: parseFloat(venta.pago_recibido) || 0,
                    detalles: venta.detalles || '',
                    productos: venta.productos.map(producto => {
                        return {
                            id_producto: parseInt(producto.id_producto),
                            nombre: producto.nombre,
                            precio: parseFloat(producto.precio),
                            cantidad: parseInt(producto.cantidad),
                            subtotal: parseFloat(producto.subtotal)
                        };
                    }),
                    total: parseFloat(venta.total)
                };
            });
            
            const data = {
                action: 'procesarVentaRuta',
                ventas: ventasParaEnviar,
                total: parseFloat(totalRuta),
                efectivo: parseFloat(efectivo),
                cambio: parseFloat(cambio),
                idUsuario: <?php echo $id_usuario; ?>
            };
            
            fetch('pventa_rutaf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Venta de Ruta Exitosa',
                        html: `
                            <p><strong>Folio:</strong> ${result.folio}</p>
                            <p><strong>Total Ruta:</strong> $${totalRuta.toFixed(2)}</p>
                            <p><strong>Clientes atendidos:</strong> ${result.total_ventas}</p>
                            <p><strong>Efectivo:</strong> $${efectivo.toFixed(2)}</p>
                            <p><strong>Cambio:</strong> $${cambio.toFixed(2)}</p>
                        `,
                        background: '#2d2d2d',
                        color: '#ffffff',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        carritoRuta = [];
                        actualizarCarritoRuta();
                        document.getElementById('efectivoRuta').value = '';
                        cargarClientesRuta(); // Recargar para mostrar estado final
                        cargarProductos();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al procesar la venta de ruta',
                        background: '#2d2d2d',
                        color: '#ffffff'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: error.message,
                    background: '#2d2d2d',
                    color: '#ffffff'
                });
            });
        }
    </script>
</body>
</html>