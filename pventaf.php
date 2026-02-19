<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] == 'buscarProductos') {
    $search = $_GET['search'] ?? '';
    
    if (empty($search)) {
        $query = "SELECT id_producto, nombre, precio, stock 
                  FROM tproducto 
                  WHERE (baja IS NULL OR baja = false)
                  AND stock > 0
                  ORDER BY nombre";
    } else {
        $query = "SELECT id_producto, nombre, precio, stock 
                  FROM tproducto 
                  WHERE (nombre ILIKE $1 OR CAST(id_producto AS TEXT) = $2)
                  AND (baja IS NULL OR baja = false)
                  AND stock > 0
                  ORDER BY nombre";
    }
    
    if (empty($search)) {
        $result = pg_query($conn, $query);
    } else {
        $result = pg_query_params($conn, $query, array("%$search%", $search));
    }
    
    if (!$result) {
        echo json_encode(array('error' => 'Error en la consulta: ' . pg_last_error($conn)));
        exit;
    }
    
    $productos = array();
    while ($row = pg_fetch_assoc($result)) {
        $productos[] = $row;
    }
    
    echo json_encode($productos);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'buscarClientes') {
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT id_cliente, nombre, telefono, email 
              FROM tcliente 
              WHERE (nombre ILIKE $1 OR telefono ILIKE $1)
              AND (baja IS NULL OR baja = false)
              ORDER BY nombre";
    
    $result = pg_query_params($conn, $query, array("%$search%"));
    
    if (!$result) {
        echo json_encode(array('error' => 'Error en la consulta: ' . pg_last_error($conn)));
        exit;
    }
    
    $clientes = array();
    while ($row = pg_fetch_assoc($result)) {
        $clientes[] = $row;
    }
    
    echo json_encode($clientes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['action'])) {
        
        if ($input['action'] == 'procesarVenta') {
            $data = $input;
            
            if (!isset($_SESSION['SISTEMA'])) {
                echo json_encode(array('success' => false, 'message' => 'Sesión no iniciada'));
                exit;
            }
            
            $id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? null;
            
            if (!$id_usuario) {
                echo json_encode(array('success' => false, 'message' => 'ID de usuario no encontrado en sesión'));
                exit;
            }
            
            try {
                pg_query("BEGIN");
                
                $folio = "VTA-" . date('Ymd-His');
                $total = $data['total'];
                $efectivo = $data['efectivo'];
                $cambio = $data['cambio'];
                $tipoVenta = $data['tipoVenta'];
                $idCliente = $data['idCliente'] ?? null;
                
                $queryVenta = "INSERT INTO tventa (fecha, total, id_cliente, id_usuario, folio, efectivo, cambio, tipo_venta) 
                               VALUES (NOW(), $1, $2, $3, $4, $5, $6, $7) RETURNING id_venta";
                
                $result = pg_query_params($conn, $queryVenta, array(
                    $total, $idCliente, $id_usuario, $folio, $efectivo, $cambio, $tipoVenta
                ));
                
                if (!$result) {
                    throw new Exception('Error al insertar venta: ' . pg_last_error($conn));
                }
                
                $idVenta = pg_fetch_result($result, 0, 0);
                
                foreach ($data['carrito'] as $item) {
                    $queryDetalle = "INSERT INTO tdetalle_venta (id_venta, id_producto, cantidad, precio_unitario) 
                                     VALUES ($1, $2, $3, $4)";
                    $resultDetalle = pg_query_params($conn, $queryDetalle, array(
                        $idVenta, $item['id_producto'], $item['cantidad'], $item['precio']
                    ));
                    
                    if (!$resultDetalle) {
                        throw new Exception('Error al insertar detalle: ' . pg_last_error($conn));
                    }
                    
                    $queryUpdateStock = "UPDATE tproducto SET stock = stock - $1 WHERE id_producto = $2";
                    $resultUpdate = pg_query_params($conn, $queryUpdateStock, array($item['cantidad'], $item['id_producto']));
                    
                    if (!$resultUpdate) {
                        throw new Exception('Error al actualizar stock: ' . pg_last_error($conn));
                    }
                }
                
                $descripcionCaja = "Venta normal - Folio: $folio" . ($idCliente ? " - Cliente ID: $idCliente" : "");
                
                $querySaldoActual = "SELECT c.saldo_final_calculado as saldo_actual,
                                            c.id_registro as id_apertura
                                     FROM tcaja c
                                     WHERE c.tipo = 'APERTURA' 
                                     AND c.estado = 'ABIERTA'
                                     ORDER BY c.fecha DESC, c.id_registro DESC 
                                     LIMIT 1";
                $resultSaldo = pg_query($conn, $querySaldoActual);
                
                if ($resultSaldo && pg_num_rows($resultSaldo) > 0) {
                    $row = pg_fetch_assoc($resultSaldo);
                    $saldo_inicial = $row['saldo_actual'];
                    $id_apertura = $row['id_apertura'];
                    
                    $saldo_final_calculado = $saldo_inicial + $total;
                    $efectivo_real = null;
                    $diferencia = null;
                    
                    $queryCaja = "INSERT INTO tcaja (
                                    tipo, fecha, id_usuario, id_venta, monto, 
                                    saldo_inicial, saldo_final_calculado, 
                                    efectivo_real, diferencia, descripcion, estado
                                  ) VALUES ('VENTA', NOW(), $1, $2, $3, $4, $5, $6, $7, $8, 'ABIERTA')";
                    
                    $resultCaja = pg_query_params($conn, $queryCaja, array(
                        $id_usuario, $idVenta, $total, 
                        $saldo_inicial, $saldo_final_calculado,
                        $efectivo_real, $diferencia, $descripcionCaja
                    ));
                    
                    if ($resultCaja) {
                        $queryActualizarApertura = "UPDATE tcaja 
                                                   SET saldo_final_calculado = $1 
                                                   WHERE id_registro = $2";
                        pg_query_params($conn, $queryActualizarApertura, array($saldo_final_calculado, $id_apertura));
                    } else {
                        error_log("Error al registrar en caja: " . pg_last_error($conn));
                    }
                } else {
                    $descripcionCaja = "Venta normal - Folio: $folio" . ($idCliente ? " - Cliente ID: $idCliente" : "") . " - SIN CAJA ABIERTA";
                    
                    $saldo_inicial = 0;
                    $saldo_final_calculado = $total;
                    
                    $queryCajaPendiente = "INSERT INTO tcaja (
                                            tipo, fecha, id_usuario, id_venta, monto, 
                                            saldo_inicial, saldo_final_calculado, 
                                            efectivo_real, diferencia, descripcion, estado
                                          ) VALUES ('VENTA', NOW(), $1, $2, $3, $4, $5, null, null, $6, 'PENDIENTE')";
                    
                    $resultCaja = pg_query_params($conn, $queryCajaPendiente, array(
                        $id_usuario, $idVenta, $total, 
                        $saldo_inicial, $saldo_final_calculado,
                        $descripcionCaja
                    ));
                    
                    if (!$resultCaja) {
                        error_log("Error al registrar venta pendiente: " . pg_last_error($conn));
                    }
                }
                
                pg_query("COMMIT");
                
                echo json_encode(array(
                    'success' => true,
                    'folio' => $folio,
                    'id_venta' => $idVenta,
                    'message' => 'Venta procesada correctamente'
                ));
                
            } catch (Exception $e) {
                pg_query("ROLLBACK");
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error al procesar venta: ' . $e->getMessage()
                ));
            }
            exit;
        }
    }
}

echo json_encode(array('error' => 'Acción no válida'));
?>