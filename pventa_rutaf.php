<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] == 'obtenerClientesRuta') {
    $id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? null;
    
    if (!$id_usuario) {
        echo json_encode(array('error' => 'Usuario no autenticado'));
        exit;
    }
    
    try {
        date_default_timezone_set('America/Mexico_City');
        $fecha_actual = date('Y-m-d');
        $dia_semana_actual = date('N');
        
        $es_admin = ($_SESSION['SISTEMA']['rol'] == 1);
        
        if ($es_admin) {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_cliente,
                        r.orden,
                        c.nombre as nombre_cliente,
                        c.direccion,
                        c.telefono,
                        c.cantidad_producto,
                        u.nombre as vendedor
                      FROM truta r
                      INNER JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      WHERE r.id_dia = $1 
                      AND (r.baja IS NULL OR r.baja = false)
                      ORDER BY r.orden, c.nombre";
                      
            $result = pg_query_params($conn, $query, array($dia_semana_actual));
        } else {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_cliente,
                        r.orden,
                        c.nombre as nombre_cliente,
                        c.direccion,
                        c.telefono,
                        c.cantidad_producto
                      FROM truta r
                      INNER JOIN tcliente c ON r.id_cliente = c.id_cliente
                      WHERE r.id_dia = $1 
                      AND r.id_usuario = $2
                      AND (r.baja IS NULL OR r.baja = false)
                      ORDER BY r.orden, c.nombre";
                      
            $result = pg_query_params($conn, $query, array($dia_semana_actual, $id_usuario));
        }
        
        if (!$result) {
            throw new Exception('Error en consulta: ' . pg_last_error($conn));
        }
        
        $clientes_ruta = array();
        while ($row = pg_fetch_assoc($result)) {
            $clientes_ruta[] = $row;
        }
        
        if (count($clientes_ruta) === 0) {
            echo json_encode(array(
                'info' => 'No hay rutas programadas para hoy',
                'dia_actual' => $dia_semana_actual
            ));
            exit;
        }
        
        echo json_encode($clientes_ruta);
        
    } catch (Exception $e) {
        echo json_encode(array('error' => 'Error al cargar clientes de ruta: ' . $e->getMessage()));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['action']) && $input['action'] == 'procesarVentaRuta') {
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
            
            $folio = "RUTA-" . date('Ymd-His');
            $total = $data['total'];
            $efectivo = $data['efectivo'];
            $cambio = $data['cambio'];
            
            $queryVenta = "INSERT INTO tventa (fecha, total, id_usuario, folio, efectivo, cambio, tipo_venta) 
                           VALUES (NOW(), $1, $2, $3, $4, $5, 'RUTA') RETURNING id_venta";
            
            $result = pg_query_params($conn, $queryVenta, array(
                $total, $id_usuario, $folio, $efectivo, $cambio
            ));
            
            if (!$result) {
                throw new Exception('Error al insertar venta principal: ' . pg_last_error($conn));
            }
            
            $id_venta = pg_fetch_result($result, 0, 0);
            
            $clientes_procesados = 0;
            foreach ($data['ventas'] as $venta) {
                foreach ($venta['productos'] as $producto) {
                    $queryDetalle = "INSERT INTO tdetalle_venta (id_venta, id_producto, cantidad, precio_unitario) 
                                     VALUES ($1, $2, $3, $4)";
                    $resultDetalle = pg_query_params($conn, $queryDetalle, array(
                        $id_venta, $producto['id_producto'], $producto['cantidad'], $producto['precio']
                    ));
                    
                    if (!$resultDetalle) {
                        throw new Exception('Error al insertar detalle de venta: ' . pg_last_error($conn));
                    }
                    
                    $queryUpdateStock = "UPDATE tproducto SET stock = stock - $1 WHERE id_producto = $2";
                    $resultUpdate = pg_query_params($conn, $queryUpdateStock, array(
                        $producto['cantidad'], $producto['id_producto']
                    ));
                    
                    if (!$resultUpdate) {
                        throw new Exception('Error al actualizar stock: ' . pg_last_error($conn));
                    }
                }
                
                $clientes_procesados++;
            }
            
            $descripcionCaja = "Venta en ruta - Folio: $folio - $clientes_procesados clientes";
            
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
                    $id_usuario, $id_venta, $total, 
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
                $descripcionCaja = "Venta en ruta - Folio: $folio - $clientes_procesados clientes - SIN CAJA ABIERTA";
                
                $saldo_inicial = 0;
                $saldo_final_calculado = $total;
                
                $queryCajaPendiente = "INSERT INTO tcaja (
                                        tipo, fecha, id_usuario, id_venta, monto, 
                                        saldo_inicial, saldo_final_calculado, 
                                        efectivo_real, diferencia, descripcion, estado
                                      ) VALUES ('VENTA', NOW(), $1, $2, $3, $4, $5, null, null, $6, 'PENDIENTE')";
                
                $resultCaja = pg_query_params($conn, $queryCajaPendiente, array(
                    $id_usuario, $id_venta, $total, 
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
                'id_venta' => $id_venta,
                'message' => 'Venta de ruta procesada correctamente',
                'total_ventas' => count($data['ventas'])
            ));
            
        } catch (Exception $e) {
            pg_query("ROLLBACK");
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al procesar venta de ruta: ' . $e->getMessage()
            ));
        }
        exit;
    }
}

echo json_encode(array('error' => 'Acción no válida'));
?>