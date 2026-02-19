<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class GestionCaja {
    private $conn;
    private $id_usuario;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->id_usuario = $_SESSION['SISTEMA']['id_usuario'] ?? 1;
    }

    public function abrirCaja($monto_inicial, $descripcion = 'Apertura de caja') {
        try {
            $sql_check = "SELECT id_registro, tipo, estado FROM tcaja 
                         WHERE estado = 'ABIERTA' 
                         AND tipo = 'APERTURA'
                         ORDER BY fecha DESC LIMIT 1";
            $result_check = pg_query($this->conn, $sql_check);
            
            if (pg_num_rows($result_check) > 0) {
                $caja_existente = pg_fetch_assoc($result_check);
                
                $sql_check_cierre = "SELECT id_registro FROM tcaja 
                                    WHERE tipo = 'CIERRE' 
                                    AND fecha > (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                    AND estado = 'CERRADA'
                                    LIMIT 1";
                $result_cierre = pg_query_params($this->conn, $sql_check_cierre, [$caja_existente['id_registro']]);
                
                if (pg_num_rows($result_cierre) == 0) {
                    return ['success' => false, 'message' => 'Ya hay una caja abierta'];
                } else {
                    $sql_update = "UPDATE tcaja SET estado = 'CERRADA' WHERE id_registro = $1";
                    pg_query_params($this->conn, $sql_update, [$caja_existente['id_registro']]);
                }
            }

            $sql = "INSERT INTO tcaja (tipo, fecha, id_usuario, monto, saldo_inicial, saldo_final_calculado, estado, descripcion) 
                    VALUES ('APERTURA', NOW(), $1, $2, $2, $2, 'ABIERTA', $3) 
                    RETURNING id_registro";
            
            $params = [$this->id_usuario, $monto_inicial, $descripcion];
            $result = pg_query_params($this->conn, $sql, $params);
            
            if ($result) {
                $row = pg_fetch_assoc($result);
                return ['success' => true, 'id_registro' => $row['id_registro'], 'message' => 'Caja abierta correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al abrir caja: ' . pg_last_error($this->conn)];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function cerrarCaja($efectivo_real, $descripcion = 'Cierre de caja') {
        try {
            $sql_get = "SELECT id_registro, saldo_inicial, saldo_final_calculado 
                       FROM tcaja 
                       WHERE tipo = 'APERTURA' 
                       AND estado = 'ABIERTA' 
                       ORDER BY fecha DESC LIMIT 1";
            $result_get = pg_query($this->conn, $sql_get);
            
            if (pg_num_rows($result_get) == 0) {
                return ['success' => false, 'message' => 'No hay caja abierta'];
            }
            
            $caja = pg_fetch_assoc($result_get);
            $id_registro = $caja['id_registro'];
            $saldo_final = $caja['saldo_final_calculado'];
            $diferencia = $efectivo_real - $saldo_final;

            $sql_ventas = "SELECT COALESCE(SUM(monto), 0) as total_ventas 
                          FROM tcaja 
                          WHERE tipo = 'VENTA' 
                          AND fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)
                          AND estado IN ('ABIERTA', 'PENDIENTE')";
            
            $result_ventas = pg_query_params($this->conn, $sql_ventas, [$id_registro]);
            $ventas = pg_fetch_assoc($result_ventas);
            $total_ventas = $ventas['total_ventas'];

            $sql_cierre = "INSERT INTO tcaja (tipo, fecha, id_usuario, monto, efectivo_real, diferencia, estado, descripcion, saldo_inicial, saldo_final_calculado) 
                          VALUES ('CIERRE', NOW(), $1, $2, $3, $4, 'CERRADA', $5, $6, $6) 
                          RETURNING id_registro";
            
            $params_cierre = [$this->id_usuario, $total_ventas, $efectivo_real, $diferencia, $descripcion, $saldo_final];
            $result_cierre = pg_query_params($this->conn, $sql_cierre, $params_cierre);
            
            if ($result_cierre) {
                $sql_update_apertura = "UPDATE tcaja SET estado = 'CERRADA' WHERE id_registro = $1";
                pg_query_params($this->conn, $sql_update_apertura, [$id_registro]);
                
                return [
                    'success' => true, 
                    'message' => 'Caja cerrada correctamente',
                    'saldo_final' => $saldo_final,
                    'efectivo_real' => $efectivo_real,
                    'diferencia' => $diferencia,
                    'total_ventas' => $total_ventas
                ];
            } else {
                return ['success' => false, 'message' => 'Error al cerrar caja: ' . pg_last_error($this->conn)];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function registrarGasto($monto, $descripcion, $id_venta = null) {
        try {
            $sql_check = "SELECT id_registro, saldo_final_calculado 
                         FROM tcaja 
                         WHERE tipo = 'APERTURA' 
                         AND estado = 'ABIERTA' 
                         ORDER BY fecha DESC LIMIT 1";
            $result_check = pg_query($this->conn, $sql_check);
            
            if (pg_num_rows($result_check) == 0) {
                return ['success' => false, 'message' => 'No hay caja abierta para registrar gasto'];
            }
            
            $caja_actual = pg_fetch_assoc($result_check);
            $saldo_actual = $caja_actual['saldo_final_calculado'];
            $id_apertura = $caja_actual['id_registro'];
            
            $nuevo_saldo = $saldo_actual - $monto;

            $estado_movimiento = 'ABIERTA';
            
            $sql = "INSERT INTO tcaja (tipo, fecha, id_usuario, id_venta, monto, saldo_inicial, saldo_final_calculado, estado, descripcion) 
                    VALUES ('GASTO', NOW(), $1, $2, $3, $4, $5, $6, $7) 
                    RETURNING id_registro";
            
            $params = [
                $this->id_usuario,  
                $id_venta,          
                $monto,             
                $saldo_actual,      
                $nuevo_saldo,       
                $estado_movimiento, 
                $descripcion        
            ];
            
            $result = pg_query_params($this->conn, $sql, $params);
            
            if ($result) {
                $sql_update_apertura = "UPDATE tcaja 
                                       SET saldo_final_calculado = $1 
                                       WHERE id_registro = $2";
                pg_query_params($this->conn, $sql_update_apertura, [$nuevo_saldo, $id_apertura]);
                
                $row = pg_fetch_assoc($result);
                return [
                    'success' => true, 
                    'id_registro' => $row['id_registro'],
                    'saldo_anterior' => $saldo_actual,
                    'saldo_nuevo' => $nuevo_saldo,
                    'message' => 'Gasto registrado correctamente'
                ];
            } else {
                return ['success' => false, 'message' => 'Error al registrar gasto: ' . pg_last_error($this->conn)];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getEstadoCaja() {
        $sql_apertura = "SELECT id_registro, fecha, saldo_final_calculado, saldo_inicial, monto, descripcion, id_usuario
                        FROM tcaja 
                        WHERE tipo = 'APERTURA' 
                        ORDER BY fecha DESC LIMIT 1";
        $result_apertura = pg_query($this->conn, $sql_apertura);
        
        if (pg_num_rows($result_apertura) == 0) {
            return null;
        }
        
        $apertura = pg_fetch_assoc($result_apertura);
        $id_apertura = $apertura['id_registro'];
        $fecha_apertura = $apertura['fecha'];
        
        $sql_cierre = "SELECT id_registro 
                      FROM tcaja 
                      WHERE tipo = 'CIERRE' 
                      AND fecha > $1
                      AND estado = 'CERRADA'
                      LIMIT 1";
        $result_cierre = pg_query_params($this->conn, $sql_cierre, [$fecha_apertura]);
        
        if (pg_num_rows($result_cierre) > 0) {
            return null;
        }
        
        $sql_estado = "SELECT * FROM tcaja WHERE id_registro = $1 AND estado = 'ABIERTA'";
        $result_estado = pg_query_params($this->conn, $sql_estado, [$id_apertura]);
        
        if (pg_num_rows($result_estado) > 0) {
            return pg_fetch_assoc($result_estado);
        }
        
        return null;
    }

    public function getMovimientos($fecha_desde = null, $fecha_hasta = null, $tipo = null, $pagina = 1, $registros_por_pagina = 10) {
        $offset = ($pagina - 1) * $registros_por_pagina;
        
        $sql = "SELECT c.*, u.nombre as nombre_usuario 
                FROM tcaja c 
                LEFT JOIN tusuario u ON c.id_usuario = u.id_usuario 
                WHERE 1=1";
        
        $sql_count = "SELECT COUNT(*) as total FROM tcaja c WHERE 1=1";
        
        $params = [];
        $params_count = [];

        if ($fecha_desde) {
            $sql .= " AND c.fecha >= $" . (count($params) + 1);
            $sql_count .= " AND c.fecha >= $" . (count($params_count) + 1);
            $params[] = $fecha_desde . ' 00:00:00';
            $params_count[] = $fecha_desde . ' 00:00:00';
        }

        if ($fecha_hasta) {
            $sql .= " AND c.fecha <= $" . (count($params) + 1);
            $sql_count .= " AND c.fecha <= $" . (count($params_count) + 1);
            $params[] = $fecha_hasta . ' 23:59:59';
            $params_count[] = $fecha_hasta . ' 23:59:59';
        }

        if ($tipo) {
            $sql .= " AND c.tipo = $" . (count($params) + 1);
            $sql_count .= " AND c.tipo = $" . (count($params_count) + 1);
            $params[] = $tipo;
            $params_count[] = $tipo;
        }

        $sql .= " ORDER BY c.fecha DESC LIMIT $" . (count($params) + 1) . " OFFSET $" . (count($params) + 2);
        $params[] = $registros_por_pagina;
        $params[] = $offset;

        if (!empty($params_count)) {
            $result_count = pg_query_params($this->conn, $sql_count, $params_count);
        } else {
            $result_count = pg_query($this->conn, $sql_count);
        }
        
        $total_registros = 0;
        if ($result_count) {
            $row_count = pg_fetch_assoc($result_count);
            $total_registros = $row_count['total'];
        }

        if (!empty($params)) {
            $result = pg_query_params($this->conn, $sql, $params);
        } else {
            $result = pg_query($this->conn, $sql);
        }

        $movimientos = [];
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $movimientos[] = $row;
            }
        }

        return [
            'movimientos' => $movimientos,
            'total_registros' => $total_registros,
            'total_paginas' => ceil($total_registros / $registros_por_pagina)
        ];
    }

    public function getEstadisticasActuales() {
        $estado_caja = $this->getEstadoCaja();
        
        if (!$estado_caja) {
            return null;
        }
        
        $id_apertura = $estado_caja['id_registro'];
        $fecha_apertura = $estado_caja['fecha'];
        
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'VENTA' AND estado IN ('ABIERTA', 'PENDIENTE') THEN monto ELSE 0 END), 0) as monto_ventas,
                COALESCE(SUM(CASE WHEN tipo = 'GASTO' AND estado = 'ABIERTA' THEN monto ELSE 0 END), 0) as monto_gastos,
                COUNT(CASE WHEN tipo = 'VENTA' AND estado IN ('ABIERTA', 'PENDIENTE') THEN 1 END) as total_ventas,
                COUNT(CASE WHEN tipo = 'GASTO' AND estado = 'ABIERTA' THEN 1 END) as total_gastos
                FROM tcaja 
                WHERE fecha >= $1
                AND id_registro != $2";
        
        $params = [$fecha_apertura, $id_apertura];
        $result = pg_query_params($this->conn, $sql, $params);
        
        if ($result) {
            $estadisticas = pg_fetch_assoc($result);
            $estadisticas['saldo_actual'] = $estado_caja['saldo_final_calculado'];
            $estadisticas['saldo_inicial'] = $estado_caja['saldo_inicial'];
            return $estadisticas;
        }
        return null;
    }

    public function forzarCierreCaja($descripcion = 'Cierre forzado por sistema') {
        try {
            pg_query("BEGIN");
            
            $sql_abiertas = "SELECT id_registro, saldo_final_calculado 
                           FROM tcaja 
                           WHERE tipo = 'APERTURA' 
                           AND estado = 'ABIERTA' 
                           ORDER BY fecha DESC";
            
            $result_abiertas = pg_query($this->conn, $sql_abiertas);
            
            $cierres_realizados = 0;
            
            if (pg_num_rows($result_abiertas) > 0) {
                while ($caja = pg_fetch_assoc($result_abiertas)) {
                    $sql_check_cierre = "SELECT id_registro FROM tcaja 
                                        WHERE tipo = 'CIERRE' 
                                        AND fecha > (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                        AND estado = 'CERRADA'
                                        LIMIT 1";
                    $result_check = pg_query_params($this->conn, $sql_check_cierre, [$caja['id_registro']]);
                    
                    if (pg_num_rows($result_check) == 0) {
                        $sql_ventas_caja = "SELECT COALESCE(SUM(monto), 0) as total_ventas 
                                          FROM tcaja 
                                          WHERE tipo = 'VENTA' 
                                          AND fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                          AND estado IN ('ABIERTA', 'PENDIENTE')";
                        $result_ventas = pg_query_params($this->conn, $sql_ventas_caja, [$caja['id_registro']]);
                        $ventas = pg_fetch_assoc($result_ventas);
                        $total_ventas = $ventas['total_ventas'];
                        
                        $sql_cierre = "INSERT INTO tcaja (tipo, fecha, id_usuario, monto, estado, descripcion, saldo_inicial, saldo_final_calculado) 
                                      VALUES ('CIERRE', NOW(), $1, $2, 'CERRADA', $3, $4, $4)";
                        
                        $descripcion_completa = $descripcion . ' - ID Apertura: ' . $caja['id_registro'] . ' - Cierre forzado';
                        $params = [$this->id_usuario, $total_ventas, $descripcion_completa, $caja['saldo_final_calculado']];
                        pg_query_params($this->conn, $sql_cierre, $params);
                        
                        $sql_update = "UPDATE tcaja SET estado = 'CERRADA' WHERE id_registro = $1";
                        pg_query_params($this->conn, $sql_update, [$caja['id_registro']]);
                        
                        $cierres_realizados++;
                    }
                }
                
                pg_query("COMMIT");
                
                if ($cierres_realizados > 0) {
                    return ['success' => true, 'message' => "Se realizaron $cierres_realizados cierres forzados correctamente"];
                } else {
                    return ['success' => false, 'message' => 'No se encontraron cajas abiertas sin cierre'];
                }
            }
            
            pg_query("COMMIT");
            return ['success' => false, 'message' => 'No hay cajas abiertas'];
            
        } catch (Exception $e) {
            pg_query("ROLLBACK");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function actualizarVentasPendientes() {
        try {
            pg_query("BEGIN");
            
            $sql_cajas_abiertas = "SELECT id_registro, saldo_final_calculado 
                                  FROM tcaja 
                                  WHERE tipo = 'APERTURA' 
                                  AND estado = 'ABIERTA'
                                  ORDER BY fecha DESC";
            $result_cajas = pg_query($this->conn, $sql_cajas_abiertas);
            
            $cajas_actualizadas = 0;
            $ventas_actualizadas = 0;
            
            while ($caja = pg_fetch_assoc($result_cajas)) {
                $id_apertura = $caja['id_registro'];
                $saldo_actual = $caja['saldo_final_calculado'];
                
                $sql_ventas_pendientes = "SELECT COALESCE(SUM(monto), 0) as total_pendiente 
                                          FROM tcaja 
                                          WHERE tipo = 'VENTA' 
                                          AND estado = 'PENDIENTE' 
                                          AND fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)";
                $result_pendientes = pg_query_params($this->conn, $sql_ventas_pendientes, [$id_apertura]);
                $pendientes = pg_fetch_assoc($result_pendientes);
                $total_pendiente = $pendientes['total_pendiente'];
                
                $sql_update_pendientes = "UPDATE tcaja 
                                         SET estado = 'ABIERTA' 
                                         WHERE tipo = 'VENTA' 
                                         AND estado = 'PENDIENTE' 
                                         AND fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                         RETURNING id_registro";
                $result_update = pg_query_params($this->conn, $sql_update_pendientes, [$id_apertura]);
                
                if ($result_update) {
                    $rows_affected = pg_affected_rows($result_update);
                    $ventas_actualizadas += $rows_affected;
                    
                    if ($total_pendiente > 0) {
                        $nuevo_saldo = $saldo_actual + $total_pendiente;
                        $sql_update_saldo = "UPDATE tcaja 
                                            SET saldo_final_calculado = $1 
                                            WHERE id_registro = $2";
                        pg_query_params($this->conn, $sql_update_saldo, [$nuevo_saldo, $id_apertura]);
                        
                        $sql_actualizar_movimientos = "UPDATE tcaja t1
                                                      SET saldo_final_calculado = (
                                                          SELECT SUM(CASE 
                                                              WHEN t2.tipo = 'VENTA' THEN t2.monto
                                                              WHEN t2.tipo = 'GASTO' THEN -t2.monto
                                                              ELSE 0
                                                          END) + t3.saldo_inicial
                                                          FROM tcaja t2
                                                          CROSS JOIN (
                                                              SELECT saldo_inicial 
                                                              FROM tcaja 
                                                              WHERE id_registro = $1
                                                          ) t3
                                                          WHERE t2.fecha <= t1.fecha
                                                          AND t2.fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                                          AND t2.id_registro != $1
                                                          AND t2.estado IN ('ABIERTA', 'PENDIENTE')
                                                      )
                                                      WHERE t1.fecha >= (SELECT fecha FROM tcaja WHERE id_registro = $1)
                                                      AND t1.id_registro != $1
                                                      AND t1.tipo != 'APERTURA'";
                        pg_query_params($this->conn, $sql_actualizar_movimientos, [$id_apertura]);
                    }
                    
                    $cajas_actualizadas++;
                }
            }
            
            pg_query("COMMIT");
            
            return [
                'success' => true, 
                'message' => "Se actualizaron $ventas_actualizadas ventas pendientes en $cajas_actualizadas cajas abiertas. Los saldos han sido recalculados."
            ];
            
        } catch (Exception $e) {
            pg_query("ROLLBACK");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['SISTEMA'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $gestionCaja = new GestionCaja($conn);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'abrir_caja':
            $monto = $_POST['monto'] ?? 0;
            $descripcion = $_POST['descripcion'] ?? 'Apertura de caja';
            $resultado = $gestionCaja->abrirCaja($monto, $descripcion);
            echo json_encode($resultado);
            break;

        case 'cerrar_caja':
            $efectivo_real = $_POST['efectivo_real'] ?? 0;
            $descripcion = $_POST['descripcion'] ?? 'Cierre de caja';
            $resultado = $gestionCaja->cerrarCaja($efectivo_real, $descripcion);
            echo json_encode($resultado);
            break;

        case 'registrar_gasto':
            $monto = $_POST['monto'] ?? 0;
            $descripcion = $_POST['descripcion'] ?? '';
            $id_venta = $_POST['id_venta'] ?? null;
            $resultado = $gestionCaja->registrarGasto($monto, $descripcion, $id_venta);
            echo json_encode($resultado);
            break;

        case 'get_estado_caja':
            $estado = $gestionCaja->getEstadoCaja();
            echo json_encode(['success' => true, 'estado' => $estado]);
            break;

        case 'forzar_cierre':
            $descripcion = $_POST['descripcion'] ?? 'Cierre forzado por sistema';
            $resultado = $gestionCaja->forzarCierreCaja($descripcion);
            echo json_encode($resultado);
            break;

        case 'actualizar_ventas_pendientes':
            $resultado = $gestionCaja->actualizarVentasPendientes();
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    exit;
}
?>