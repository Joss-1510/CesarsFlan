<?php
class RecetaF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerRecetasConPaginacion($offset = 0, $limit = 10) {
        try {
            $query = "SELECT r.*, 
                             ps.nombre as nombre_semielaborado,
                             i.nombre as nombre_ingrediente,
                             i.unidad_medida
                      FROM treceta r
                      JOIN tproducto_semielaborado ps ON r.id_semielaborado = ps.id_semielaborado
                      JOIN tingrediente i ON r.id_ingrediente = i.id_ingrediente
                      ORDER BY ps.nombre, i.nombre
                      LIMIT $1 OFFSET $2";
            
            $result = pg_query_params($this->db, $query, [$limit, $offset]);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM treceta";
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'recetas' => $recetas,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerRecetasConPaginacion: " . $e->getMessage());
            return ['recetas' => [], 'total' => 0];
        }
    }
    
    public function agregarIngredienteReceta($id_semielaborado, $id_ingrediente, $cantidad_requerida, $observaciones = '') {
        try {
            $queryCheck = "SELECT 1 FROM treceta 
                          WHERE id_semielaborado = $1 AND id_ingrediente = $2";
            $resultCheck = pg_query_params($this->db, $queryCheck, [$id_semielaborado, $id_ingrediente]);
            
            if (pg_num_rows($resultCheck) > 0) {
                throw new Exception("Este ingrediente ya está en la receta de este semielaborado");
            }
            
            $query = "INSERT INTO treceta 
                     (id_semielaborado, id_ingrediente, cantidad_requerida, observaciones, fecha_creacion) 
                      VALUES ($1, $2, $3, $4, $5) RETURNING id_receta";
            
            $result = pg_query_params($this->db, $query, [
                $id_semielaborado, $id_ingrediente, $cantidad_requerida, $observaciones, date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                throw new Exception("Error al agregar ingrediente a receta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_receta'];
            
        } catch (Exception $e) {
            error_log("Error al agregar ingrediente a receta: " . $e->getMessage());
            throw new Exception("Error al agregar ingrediente: " . $e->getMessage());
        }
    }
    
    public function actualizarCantidadReceta($id_receta, $cantidad_requerida) {
        try {
            $query = "UPDATE treceta SET cantidad_requerida = $1 WHERE id_receta = $2";
            $result = pg_query_params($this->db, $query, [$cantidad_requerida, $id_receta]);
            
            if (!$result) {
                throw new Exception("Error al actualizar receta: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al actualizar receta: " . $e->getMessage());
            throw new Exception("Error al actualizar receta: " . $e->getMessage());
        }
    }
    
    public function eliminarIngredienteReceta($id_receta) {
        try {
            $query = "DELETE FROM treceta WHERE id_receta = $1";
            $result = pg_query_params($this->db, $query, [$id_receta]);
            
            if (!$result) {
                throw new Exception("Error al eliminar ingrediente de receta: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al eliminar ingrediente de receta: " . $e->getMessage());
            throw new Exception("Error al eliminar ingrediente: " . $e->getMessage());
        }
    }
   
    public function obtenerSemielaborados() {
        try {
            $query = "SELECT id_semielaborado, nombre 
                      FROM tproducto_semielaborado 
                      WHERE estado != 'INACTIVO' OR estado IS NULL
                      ORDER BY nombre";
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
    
    public function obtenerIngredientes() {
        try {
            $query = "SELECT id_ingrediente, nombre, unidad_medida 
                      FROM tingrediente 
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
    
    public function buscarRecetasPorSemielaborado($busqueda, $offset = 0, $limit = 10) {
        try {
            $query = "SELECT r.*, 
                             ps.nombre as nombre_semielaborado,
                             i.nombre as nombre_ingrediente,
                             i.unidad_medida
                      FROM treceta r
                      JOIN tproducto_semielaborado ps ON r.id_semielaborado = ps.id_semielaborado
                      JOIN tingrediente i ON r.id_ingrediente = i.id_ingrediente
                      WHERE ps.nombre ILIKE $1
                      ORDER BY ps.nombre, i.nombre
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$busqueda%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM treceta r
                          JOIN tproducto_semielaborado ps ON r.id_semielaborado = ps.id_semielaborado
                          WHERE ps.nombre ILIKE $1";
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'recetas' => $recetas,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar recetas: " . $e->getMessage());
            return ['recetas' => [], 'total' => 0];
        }
    }
}
?>