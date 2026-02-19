<?php
class ProduccionF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public static $ESTADOS_PRODUCCION = [
        'En proceso' => 'En proceso',
        'Terminada' => 'Terminada'
    ];
    
    public function obtenerProduccionesConPaginacion($offset = 0, $limit = 10) {
        try {
            $query = "SELECT p.*, 
                             ps.nombre as nombre_semielaborado
                      FROM tproduccion p
                      LEFT JOIN tproducto_semielaborado ps ON p.id_semielaborado = ps.id_semielaborado
                      ORDER BY p.fecha_inicio DESC
                      LIMIT $1 OFFSET $2";
            
            $result = pg_query_params($this->db, $query, [$limit, $offset]);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $producciones = [];
            while ($row = pg_fetch_assoc($result)) {
                $producciones[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproduccion";
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'producciones' => $producciones,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerProduccionesConPaginacion: " . $e->getMessage());
            return ['producciones' => [], 'total' => 0];
        }
    }
    
    public function buscarProduccionesConPaginacion($busqueda, $offset = 0, $limit = 10) {
        try {
            $query = "SELECT p.*, 
                             ps.nombre as nombre_semielaborado
                      FROM tproduccion p
                      LEFT JOIN tproducto_semielaborado ps ON p.id_semielaborado = ps.id_semielaborado
                      WHERE (ps.nombre ILIKE $1 OR p.observaciones ILIKE $1)
                      ORDER BY p.fecha_inicio DESC
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$busqueda%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $producciones = [];
            while ($row = pg_fetch_assoc($result)) {
                $producciones[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproduccion p
                          LEFT JOIN tproducto_semielaborado ps ON p.id_semielaborado = ps.id_semielaborado
                          WHERE (ps.nombre ILIKE $1 OR p.observaciones ILIKE $1)";
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'producciones' => $producciones,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar producciones: " . $e->getMessage());
            return ['producciones' => [], 'total' => 0];
        }
    }
    
    public function crearProduccion($data) {
        try {
            $query = "INSERT INTO tproduccion 
                     (fecha_inicio, cantidad, estado, id_semielaborado, observaciones, uso_receta) 
                      VALUES (NOW(), $1, $2, $3, $4, $5) RETURNING id_produccion";
            
            $uso_receta = isset($data['uso_receta']) ? ($data['uso_receta'] ? 't' : 'f') : 'f';
            
            $result = pg_query_params($this->db, $query, [
                $data['cantidad'] ?? 0,
                $data['estado'] ?? 'En proceso',
                $data['id_semielaborado'] ?? null,
                $data['observaciones'] ?? '',
                $uso_receta
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear producción: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_produccion'];
            
        } catch (Exception $e) {
            error_log("Error al crear producción: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear producción: " . $e->getMessage());
        }
    }
    
    public function crearProduccionConReceta($id_semielaborado, $cantidad, $observaciones = '') {
        try {
            pg_query($this->db, "BEGIN");
            
            $produccionData = [
                'cantidad' => $cantidad,
                'id_semielaborado' => $id_semielaborado,
                'observaciones' => $observaciones,
                'uso_receta' => true
            ];
            
            $id_produccion = $this->crearProduccion($produccionData);
            
            $this->aplicarRecetaProduccion($id_produccion, $id_semielaborado, $cantidad);
            
            pg_query($this->db, "COMMIT");
            
            return $id_produccion;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en producción con receta: " . $e->getMessage());
            throw new Exception("Error al crear producción automática: " . $e->getMessage());
        }
    }
    
    public function aplicarRecetaProduccion($id_produccion, $id_semielaborado, $cantidad) {
        try {
            $receta = $this->obtenerRecetaIngredientes($id_semielaborado);

            if (empty($receta)) {
                throw new Exception("No hay receta de ingredientes configurada para este semielaborado");
            }

            foreach ($receta as $item) {
                $cantidad_total = $item['cantidad_requerida'] * $cantidad;
                $costo_actual = $this->obtenerCostoActualIngrediente($item['id_ingrediente']);

                if (!$this->verificarStockIngrediente($item['id_ingrediente'], $cantidad_total)) {
                    $unidad = $item['unidad_medida'];
                    $stock_disponible = $item['cantidad_stock'];
                    throw new Exception("Stock insuficiente de: " . $item['nombre'] . 
                                      " (Necesario: $cantidad_total $unidad, Disponible: $stock_disponible $unidad)");
                }

                $this->agregarDetalleIngrediente([
                    'id_produccion' => $id_produccion,
                    'id_ingrediente' => $item['id_ingrediente'],
                    'cantidad_usada' => $cantidad_total,
                    'costo_unitario' => $costo_actual,
                    'es_automatico' => true
                ]);

                $this->rebajarStockIngrediente($item['id_ingrediente'], $cantidad_total);
            }

            return true;

        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function agregarDetalleIngrediente($data) {
        try {
            $query = "INSERT INTO tdetalle_produccion 
                     (id_produccion, tipo_item, id_ingrediente, cantidad_usada, costo_ingrediente, es_automatico) 
                      VALUES ($1, 'ingrediente', $2, $3, $4, $5) RETURNING id_detalle";
            
            $es_automatico = isset($data['es_automatico']) ? ($data['es_automatico'] ? 't' : 'f') : 'f';
            
            $result = pg_query_params($this->db, $query, [
                $data['id_produccion'],
                $data['id_ingrediente'],
                $data['cantidad_usada'],
                $data['costo_unitario'],
                $es_automatico
            ]);
            
            if (!$result) {
                throw new Exception("Error al agregar detalle de ingrediente: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_detalle'];
            
        } catch (Exception $e) {
            error_log("Error al agregar detalle de ingrediente: " . $e->getMessage());
            throw new Exception("Error al agregar detalle de ingrediente: " . $e->getMessage());
        }
    }
    
    public function agregarDetalleProduccion($data) {
        try {
            if ($data['tipo_item'] === 'ingrediente') {
                return $this->agregarDetalleIngrediente($data);
            } else {
                throw new Exception("Tipo de item no válido: " . $data['tipo_item']);
            }
        } catch (Exception $e) {
            error_log("Error al agregar detalle: " . $e->getMessage());
            throw new Exception("Error al agregar detalle: " . $e->getMessage());
        }
    }
    
    public function verificarSemielaboradoActivo($id_semielaborado) {
        try {
            $query = "SELECT estado FROM tproducto_semielaborado WHERE id_semielaborado = $1";
            $result = pg_query_params($this->db, $query, [$id_semielaborado]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return ($row['estado'] === 'ACTIVO');
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar semielaborado: " . $e->getMessage());
            return false;
        }
    }
    
    public function cambiarEstadoProduccion($id_produccion, $nuevo_estado) {
        try {
            $produccion = $this->obtenerProduccionPorId($id_produccion);
            
            if (!$produccion) {
                throw new Exception("Producción no encontrada");
            }
            
            $query = "UPDATE tproduccion SET estado = $1";
            
            if ($nuevo_estado === 'Terminada') {
                $query .= ", fecha_fin = NOW()";
                
                if ($produccion['estado'] !== 'Terminada') {
                    if ($produccion['id_semielaborado'] && $produccion['cantidad'] > 0) {
                        if (!$this->verificarSemielaboradoActivo($produccion['id_semielaborado'])) {
                            throw new Exception("No se puede finalizar la producción: el semielaborado está inactivo");
                        }
                        
                        $this->incrementarStockSemielaborado($produccion['id_semielaborado'], $produccion['cantidad']);
                        
                        error_log("Producción {$id_produccion} finalizada: +{$produccion['cantidad']} unidades agregadas al semielaborado {$produccion['id_semielaborado']}");
                    }
                }
            }
            
            $query .= " WHERE id_produccion = $2";
            
            $result = pg_query_params($this->db, $query, [$nuevo_estado, $id_produccion]);
            
            if (!$result) {
                throw new Exception("Error al cambiar estado: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
            
        } catch (Exception $e) {
            error_log("Error al cambiar estado de producción: " . $e->getMessage());
            throw new Exception("Error al cambiar estado: " . $e->getMessage());
        }
    }
    
    public function incrementarStockSemielaborado($id_semielaborado, $cantidad) {
        try {
            $query = "UPDATE tproducto_semielaborado 
                      SET cantidad = cantidad + $1 
                      WHERE id_semielaborado = $2";
            
            $result = pg_query_params($this->db, $query, [$cantidad, $id_semielaborado]);
            
            if (!$result) {
                throw new Exception("Error al incrementar stock de semielaborado: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
            
        } catch (Exception $e) {
            error_log("Error al incrementar stock: " . $e->getMessage());
            throw new Exception("Error al incrementar stock: " . $e->getMessage());
        }
    }
    
    public function obtenerSemielaboradosDisponibles() {
        try {
            $query = "SELECT * FROM tproducto_semielaborado 
                      WHERE estado = 'ACTIVO'
                      ORDER BY fecha_creacion";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $semielaborados = [];
            while ($row = pg_fetch_assoc($result)) {
                $semielaborados[] = $row;
            }
            
            return $semielaborados;
            
        } catch (Exception $e) {
            error_log("Error al obtener semielaborados: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerSemielaboradosParaProduccion() {
        try {
            $query = "SELECT 
                         ps.id_semielaborado,
                         ps.nombre,
                         ps.cantidad as stock_actual,
                         ps.estado
                      FROM tproducto_semielaborado ps
                      WHERE ps.estado = 'ACTIVO'
                      ORDER BY ps.nombre";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $semielaborados = [];
            while ($row = pg_fetch_assoc($result)) {
                $semielaborados[] = $row;
            }
            
            return $semielaborados;
        } catch (Exception $e) {
            error_log("Error al obtener semielaborados: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerRecetaIngredientes($id_semielaborado) {
        try {
            $query = "SELECT 
                         r.id_receta,
                         r.id_ingrediente,
                         r.cantidad_requerida,
                         i.nombre as nombre, 
                         i.unidad_medida,
                         i.cantidad_stock,
                         'ingrediente' as tipo
                  FROM treceta r
                  JOIN tingrediente i ON r.id_ingrediente = i.id_ingrediente
                  WHERE r.id_semielaborado = $1 AND r.id_ingrediente IS NOT NULL
                  ORDER BY i.nombre";
        
            $result = pg_query_params($this->db, $query, [$id_semielaborado]);
            
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
    
    public function obtenerRecetaCompletaProducto($id_semielaborado) {
        return $this->obtenerRecetaIngredientes($id_semielaborado);
    }
    
    public function obtenerDetallesProduccion($id_produccion) {
        try {
            $query = "SELECT dp.*, 
                         i.nombre as nombre_item,
                         i.unidad_medida,
                         'Ingrediente' as tipo_nombre,
                         CASE 
                             WHEN dp.es_automatico = true THEN '🔄 Automático'
                             ELSE '✍️ Manual'
                         END as origen
                  FROM tdetalle_produccion dp
                  JOIN tingrediente i ON dp.id_ingrediente = i.id_ingrediente
                  WHERE dp.id_produccion = $1 AND dp.tipo_item = 'ingrediente'
                  ORDER BY i.nombre";
        
            $result = pg_query_params($this->db, $query, [$id_produccion]);
            
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
    
    public function verificarStockIngrediente($id_ingrediente, $cantidad_necesaria) {
        try {
            $query = "SELECT cantidad_stock FROM tingrediente WHERE id_ingrediente = $1";
            $result = pg_query_params($this->db, $query, [$id_ingrediente]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return ($row['cantidad_stock'] >= $cantidad_necesaria);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar stock: " . $e->getMessage());
            return false;
        }
    }
    
    public function rebajarStockIngrediente($id_ingrediente, $cantidad) {
        try {
            $query = "UPDATE tingrediente 
                  SET cantidad_stock = cantidad_stock - $1 
                  WHERE id_ingrediente = $2";
        
            $result = pg_query_params($this->db, $query, [$cantidad, $id_ingrediente]);
            
            if (!$result) {
                throw new Exception("Error al rebajar stock: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al rebajar stock: " . $e->getMessage());
            throw new Exception("Error al rebajar stock: " . $e->getMessage());
        }
    }
    
    public function obtenerCostoActualIngrediente($id_ingrediente) {
        try {
            $query = "SELECT costo_por_unidad FROM tingrediente WHERE id_ingrediente = $1";
            $result = pg_query_params($this->db, $query, [$id_ingrediente]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return $row['costo_por_unidad'] ?? 0;
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error al obtener costo: " . $e->getMessage());
            return 0;
        }
    }
    
    public function actualizarProduccion($id_produccion, $data) {
        try {
            $set = [];
            $params = [];
            $paramCount = 1;
            
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $set[] = "\"$key\" = NULL";
                } else {
                    if ($key === 'uso_receta') {
                        $value = $value ? 't' : 'f';
                    }
                    $set[] = "\"$key\" = $" . $paramCount;
                    $params[] = $value;
                    $paramCount++;
                }
            }
            
            $params[] = $id_produccion;
            
            $query = "UPDATE tproduccion SET " . implode(", ", $set) . " WHERE id_produccion = $" . $paramCount;
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error al actualizar producción: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al actualizar producción: " . $e->getMessage());
            throw new Exception("Error al actualizar producción: " . $e->getMessage());
        }
    }
    
    public function eliminarDetalleProduccion($id_detalle) {
        try {
            $query = "DELETE FROM tdetalle_produccion WHERE id_detalle = $1";
            $result = pg_query_params($this->db, $query, [$id_detalle]);
            
            if (!$result) {
                throw new Exception("Error al eliminar detalle: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al eliminar detalle: " . $e->getMessage());
            throw new Exception("Error al eliminar detalle: " . $e->getMessage());
        }
    }
    
    public function obtenerProduccionPorId($id_produccion) {
        try {
            $query = "SELECT p.*, 
                             ps.nombre as nombre_semielaborado
                      FROM tproduccion p
                      LEFT JOIN tproducto_semielaborado ps ON p.id_semielaborado = ps.id_semielaborado
                      WHERE p.id_produccion = $1";
            
            $result = pg_query_params($this->db, $query, [$id_produccion]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_fetch_assoc($result);
        } catch (Exception $e) {
            error_log("Error al obtener producción: " . $e->getMessage());
            return null;
        }
    }
    
    public function calcularCostosProduccion($id_produccion) {
        try {
            $query = "SELECT 
                         SUM(cantidad_usada * costo_ingrediente) as costo_total
                      FROM tdetalle_produccion
                      WHERE id_produccion = $1";
            
            $result = pg_query_params($this->db, $query, [$id_produccion]);
            
            if (!$result) {
                throw new Exception("Error en cálculo de costos: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return [
                'costo_total' => $row['costo_total'] ?? 0,
                'costo_ingredientes' => $row['costo_total'] ?? 0,
                'costo_materiales' => 0
            ];
        } catch (Exception $e) {
            error_log("Error al calcular costos: " . $e->getMessage());
            return [
                'costo_total' => 0,
                'costo_ingredientes' => 0,
                'costo_materiales' => 0
            ];
        }
    }
    
    public function obtenerProductoFinalPorId($id_producto_final) {
        try {
            $query = "SELECT id_producto, nombre FROM tproducto WHERE id_producto = $1 AND baja = false";
            $result = pg_query_params($this->db, $query, [$id_producto_final]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return $row;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener producto final: " . $e->getMessage());
            return null;
        }
    }
    
    public function obtenerIngredientesDisponibles() {
        try {
            $query = "SELECT id_ingrediente, nombre, unidad_medida, cantidad_stock, costo_por_unidad 
                      FROM tingrediente 
                      WHERE cantidad_stock > 0 
                      ORDER BY nombre";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ingredientes = [];
            while ($row = pg_fetch_assoc($result)) {
                $ingredientes[] = $row;
            }
            
            return $ingredientes;
        } catch (Exception $e) {
            error_log("Error al obtener ingredientes: " . $e->getMessage());
            return [];
        }
    }
    
    public function estaFinalizada($id_produccion) {
        try {
            $query = "SELECT estado FROM tproduccion WHERE id_produccion = $1";
            $result = pg_query_params($this->db, $query, [$id_produccion]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return $row['estado'] === 'Terminada';
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar estado: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerEstadisticasProduccion() {
        try {
            $query = "SELECT 
                         COUNT(*) as total_producciones,
                         COUNT(CASE WHEN estado = 'Terminada' THEN 1 END) as producciones_terminadas,
                         COUNT(CASE WHEN estado = 'En proceso' THEN 1 END) as producciones_en_proceso,
                         COALESCE(SUM(cantidad), 0) as total_unidades_producidas
                      FROM tproduccion";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta de estadísticas: " . pg_last_error($this->db));
            }
            
            return pg_fetch_assoc($result);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [
                'total_producciones' => 0,
                'producciones_terminadas' => 0,
                'producciones_en_proceso' => 0,
                'total_unidades_producidas' => 0
            ];
        }
    }
}
?>