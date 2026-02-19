<?php
class EmpaquetadoF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public static $ESTADOS_EMPAQUETADO = [
        'PENDIENTE' => 'Pendiente',
        'FINALIZADO' => 'Finalizado'
    ];
    
    public function obtenerEmpaquetadosConPaginacion($offset = 0, $limit = 10) {
        try {
            $query = "SELECT e.*, 
                             u.nombre as nombre_usuario
                      FROM tempaquetado e
                      LEFT JOIN tusuario u ON e.id_usuario = u.id_usuario
                      ORDER BY e.fecha_empaquetado DESC
                      LIMIT $1 OFFSET $2";
            
            $result = pg_query_params($this->db, $query, [$limit, $offset]);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $empaquetados = [];
            while ($row = pg_fetch_assoc($result)) {
                $empaquetados[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tempaquetado";
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'empaquetados' => $empaquetados,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerEmpaquetadosConPaginacion: " . $e->getMessage());
            return ['empaquetados' => [], 'total' => 0];
        }
    }
    
    public function buscarEmpaquetadosConPaginacion($busqueda, $offset = 0, $limit = 10) {
        try {
            $query = "SELECT e.*, 
                             u.nombre as nombre_usuario
                      FROM tempaquetado e
                      LEFT JOIN tusuario u ON e.id_usuario = u.id_usuario
                      WHERE (e.observaciones ILIKE $1 OR u.nombre ILIKE $1)
                      ORDER BY e.fecha_empaquetado DESC
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$busqueda%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $empaquetados = [];
            while ($row = pg_fetch_assoc($result)) {
                $empaquetados[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tempaquetado e
                          LEFT JOIN tusuario u ON e.id_usuario = u.id_usuario
                          WHERE (e.observaciones ILIKE $1 OR u.nombre ILIKE $1)";
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'empaquetados' => $empaquetados,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar empaquetados: " . $e->getMessage());
            return ['empaquetados' => [], 'total' => 0];
        }
    }
    
    public function crearEmpaquetado($data) {
        try {
            $query = "INSERT INTO tempaquetado 
                     (fecha_empaquetado, id_usuario, observaciones, estado) 
                      VALUES (NOW(), $1, $2, 'PENDIENTE') RETURNING id_empaquetado";
            
            $result = pg_query_params($this->db, $query, [
                $data['id_usuario'] ?? $_SESSION['SISTEMA']['id_usuario'] ?? null,
                $data['observaciones'] ?? ''
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear empaquetado: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_empaquetado'];
            
        } catch (Exception $e) {
            error_log("Error al crear empaquetado: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear empaquetado: " . $e->getMessage());
        }
    }
    
    public function crearEmpaquetadoConReceta($id_producto_final, $cantidad_lotes, $observaciones = '') {
        try {
            pg_query($this->db, "BEGIN");
            
            $empaquetadoData = [
                'observaciones' => $observaciones
            ];
            
            $id_empaquetado = $this->crearEmpaquetado($empaquetadoData);
            
            $this->aplicarRecetaEmpaquetado($id_empaquetado, $id_producto_final, $cantidad_lotes);
            
            pg_query($this->db, "COMMIT");
            
            return $id_empaquetado;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en empaquetado con receta: " . $e->getMessage());
            throw new Exception("Error al crear empaquetado automático: " . $e->getMessage());
        }
    }
    
    public function aplicarRecetaEmpaquetado($id_empaquetado, $id_producto_final, $cantidad_lotes) {
        try {
            $receta = $this->obtenerRecetaEmpaquetado($id_producto_final);

            if (empty($receta)) {
                throw new Exception("No hay receta de empaquetado configurada para este producto");
            }

            foreach ($receta as $item) {
                $cantidad_semielaborados = $item['cantidad_semielaborados_necesarios'] * $cantidad_lotes;
                $cantidad_materiales = $item['cantidad_material_necesario'] * $cantidad_lotes;
                $cantidad_productos = $item['cantidad_productos_resultantes'] * $cantidad_lotes;

                if (!$this->verificarStockSemielaborado($item['id_semielaborado'], $cantidad_semielaborados)) {
                    $stock_disponible = $item['stock_semielaborado'];
                    throw new Exception("Stock insuficiente de semielaborado: " . $item['nombre_semielaborado'] . 
                                      " (Necesario: $cantidad_semielaborados, Disponible: $stock_disponible)");
                }

                if (!$this->verificarStockMaterial($item['id_material'], $cantidad_materiales)) {
                    $stock_disponible = $item['stock_material'];
                    throw new Exception("Stock insuficiente de material: " . $item['nombre_material'] . 
                                      " (Necesario: $cantidad_materiales, Disponible: $stock_disponible)");
                }

                $this->agregarDetalleEmpaquetado([
                    'id_empaquetado' => $id_empaquetado,
                    'id_semielaborado' => $item['id_semielaborado'],
                    'id_producto_final' => $id_producto_final,
                    'id_material' => $item['id_material'],
                    'cantidad_semielaborados_usados' => $cantidad_semielaborados,
                    'cantidad_material_usado' => $cantidad_materiales,
                    'cantidad_productos_creados' => $cantidad_productos
                ]);

                $this->rebajarStockSemielaborado($item['id_semielaborado'], $cantidad_semielaborados);
                $this->rebajarStockMaterial($item['id_material'], $cantidad_materiales);
            }

            return true;

        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function agregarDetalleEmpaquetado($data) {
        try {
            $query = "INSERT INTO tdetalle_empaquetado 
                     (id_empaquetado, id_semielaborado, id_producto_final, id_material, 
                      cantidad_semielaborados_usados, cantidad_material_usado, cantidad_productos_creados) 
                      VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id_detalle_empaquetado";
            
            $result = pg_query_params($this->db, $query, [
                $data['id_empaquetado'],
                $data['id_semielaborado'],
                $data['id_producto_final'],
                $data['id_material'],
                $data['cantidad_semielaborados_usados'],
                $data['cantidad_material_usado'],
                $data['cantidad_productos_creados']
            ]);
            
            if (!$result) {
                throw new Exception("Error al agregar detalle de empaquetado: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_detalle_empaquetado'];
            
        } catch (Exception $e) {
            error_log("Error al agregar detalle de empaquetado: " . $e->getMessage());
            throw new Exception("Error al agregar detalle de empaquetado: " . $e->getMessage());
        }
    }
    
    public function obtenerRecetaEmpaquetado($id_producto_final) {
        try {
            $query = "SELECT 
                         re.id_receta_empaq,
                         re.id_semielaborado,
                         re.id_material,
                         re.cantidad_semielaborados_necesarios,
                         re.cantidad_material_necesario,
                         re.cantidad_productos_resultantes,
                         ps.nombre as nombre_semielaborado,
                         ps.cantidad as stock_semielaborado,
                         m.nombre as nombre_material,
                         m.cantidad_stock as stock_material
                  FROM treceta_empaquetado re
                  JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                  JOIN tmaterial m ON re.id_material = m.id_material
                  WHERE re.id_producto_final = $1 AND ps.estado = 'ACTIVO' AND m.baja = false
                  ORDER BY ps.nombre, m.nombre";
        
            $result = pg_query_params($this->db, $query, [$id_producto_final]);
            
            if (!$result) {
                throw new Exception("Error en la consulta de receta: " . pg_last_error($this->db));
            }
            
            $receta = [];
            while ($row = pg_fetch_assoc($result)) {
                $receta[] = $row;
            }
            
            return $receta;
        } catch (Exception $e) {
            error_log("Error al obtener receta: " . $e->getMessage());
            return [];
        }
    }
    
    public function verificarStockSemielaborado($id_semielaborado, $cantidad_necesaria) {
        try {
            $query = "SELECT cantidad FROM tproducto_semielaborado WHERE id_semielaborado = $1 AND estado = 'ACTIVO'";
            $result = pg_query_params($this->db, $query, [$id_semielaborado]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return ($row['cantidad'] >= $cantidad_necesaria);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar stock semielaborado: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarStockMaterial($id_material, $cantidad_necesaria) {
        try {
            $query = "SELECT cantidad_stock FROM tmaterial WHERE id_material = $1 AND baja = false";
            $result = pg_query_params($this->db, $query, [$id_material]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return ($row['cantidad_stock'] >= $cantidad_necesaria);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar stock material: " . $e->getMessage());
            return false;
        }
    }
    
    public function rebajarStockSemielaborado($id_semielaborado, $cantidad) {
        try {
            $query = "UPDATE tproducto_semielaborado 
                      SET cantidad = cantidad - $1 
                      WHERE id_semielaborado = $2 AND estado = 'ACTIVO'";
        
            $result = pg_query_params($this->db, $query, [$cantidad, $id_semielaborado]);
            
            if (!$result) {
                throw new Exception("Error al rebajar stock semielaborado: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al rebajar stock semielaborado: " . $e->getMessage());
            throw new Exception("Error al rebajar stock semielaborado: " . $e->getMessage());
        }
    }
    
    public function rebajarStockMaterial($id_material, $cantidad) {
        try {
            $query = "UPDATE tmaterial 
                      SET cantidad_stock = cantidad_stock - $1 
                      WHERE id_material = $2 AND baja = false";
        
            $result = pg_query_params($this->db, $query, [$cantidad, $id_material]);
            
            if (!$result) {
                throw new Exception("Error al rebajar stock material: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al rebajar stock material: " . $e->getMessage());
            throw new Exception("Error al rebajar stock material: " . $e->getMessage());
        }
    }
    
    public function finalizarEmpaquetado($id_empaquetado) {
        try {
            pg_query($this->db, "BEGIN");
            
            $query = "SELECT SUM(cantidad_productos_creados) as total_productos, 
                             id_producto_final
                      FROM tdetalle_empaquetado 
                      WHERE id_empaquetado = $1 
                      GROUP BY id_producto_final";
            
            $result = pg_query_params($this->db, $query, [$id_empaquetado]);
            
            if (!$result) {
                throw new Exception("Error al obtener detalles: " . pg_last_error($this->db));
            }
            
            $detalle = pg_fetch_assoc($result);
            if (!$detalle) {
                throw new Exception("No se encontraron detalles para este empaquetado");
            }
            
            $total_productos = $detalle['total_productos'];
            $id_producto_final = $detalle['id_producto_final'];
            
            $updateQuery = "UPDATE tproducto 
                           SET stock = stock + $1 
                           WHERE id_producto = $2 AND baja = false";
            
            $updateResult = pg_query_params($this->db, $updateQuery, [$total_productos, $id_producto_final]);
            
            if (!$updateResult) {
                throw new Exception("Error al incrementar stock del producto: " . pg_last_error($this->db));
            }
            
            $estadoQuery = "UPDATE tempaquetado 
                           SET estado = 'FINALIZADO' 
                           WHERE id_empaquetado = $1";
            
            $estadoResult = pg_query_params($this->db, $estadoQuery, [$id_empaquetado]);
            
            if (!$estadoResult) {
                throw new Exception("Error al actualizar estado del empaquetado: " . pg_last_error($this->db));
            }
            
            pg_query($this->db, "COMMIT");
            
            return [
                'total_productos' => $total_productos,
                'id_producto_final' => $id_producto_final
            ];
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error al finalizar empaquetado: " . $e->getMessage());
            throw new Exception("Error al finalizar empaquetado: " . $e->getMessage());
        }
    }
    
    public function obtenerDetallesEmpaquetado($id_empaquetado) {
        try {
            $query = "SELECT de.*, 
                         ps.nombre as nombre_semielaborado,
                         m.nombre as nombre_material,
                         pf.nombre as nombre_producto_final
                  FROM tdetalle_empaquetado de
                  JOIN tproducto_semielaborado ps ON de.id_semielaborado = ps.id_semielaborado
                  JOIN tmaterial m ON de.id_material = m.id_material
                  JOIN tproducto pf ON de.id_producto_final = pf.id_producto
                  WHERE de.id_empaquetado = $1
                  ORDER BY ps.nombre, m.nombre";
        
            $result = pg_query_params($this->db, $query, [$id_empaquetado]);
            
            if (!$result) {
                throw new Exception("Error en la consulta de detalles: " . pg_last_error($this->db));
            }
            
            $detalles = [];
            while ($row = pg_fetch_assoc($result)) {
                $detalles[] = $row;
            }
            
            return $detalles;
        } catch (Exception $e) {
            error_log("Error al obtener detalles: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerProductosFinales() {
        try {
            $query = "SELECT id_producto, nombre, stock, descripcion 
                      FROM tproducto 
                      WHERE baja = false
                      ORDER BY nombre";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $productos = [];
            while ($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            return $productos;
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerEmpaquetadoPorId($id_empaquetado) {
        try {
            $query = "SELECT e.*, 
                             u.nombre as nombre_usuario
                      FROM tempaquetado e
                      LEFT JOIN tusuario u ON e.id_usuario = u.id_usuario
                      WHERE e.id_empaquetado = $1";
            
            $result = pg_query_params($this->db, $query, [$id_empaquetado]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_fetch_assoc($result);
        } catch (Exception $e) {
            error_log("Error al obtener empaquetado: " . $e->getMessage());
            return null;
        }
    }
    
    public function estaFinalizado($id_empaquetado) {
        try {
            $query = "SELECT estado FROM tempaquetado WHERE id_empaquetado = $1";
            $result = pg_query_params($this->db, $query, [$id_empaquetado]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return $row['estado'] === 'FINALIZADO';
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar estado: " . $e->getMessage());
            return false;
        }
    }
}
?>