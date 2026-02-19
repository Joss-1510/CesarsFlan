<?php
class RecetaEF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerSemielaboradosDisponibles() {
        try {
            $query = "SELECT * FROM tproducto_semielaborado 
                      WHERE estado = 'ACTIVO'
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
    
    public function obtenerMateriales() {
        try {
            $query = "SELECT id_material, nombre, cantidad_stock, tipo, descripcion
                      FROM tmaterial 
                      WHERE baja = false 
                      ORDER BY nombre";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $materiales = [];
            while ($row = pg_fetch_assoc($result)) {
                $materiales[] = $row;
            }
            
            return $materiales;
        } catch (Exception $e) {
            error_log("Error al obtener materiales: " . $e->getMessage());
            return [];
        }
    }
    
    public function agregarRecetaEmpaquetado($data) {
        try {
            $query = "INSERT INTO treceta_empaquetado 
                     (id_producto_final, id_semielaborado, id_material, 
                      cantidad_semielaborados_necesarios, cantidad_material_necesario, cantidad_productos_resultantes) 
                      VALUES ($1, $2, $3, $4, $5, $6) RETURNING id_receta_empaq";
            
            $result = pg_query_params($this->db, $query, [
                $data['id_producto_final'],
                $data['id_semielaborado'],
                $data['id_material'],
                $data['cantidad_semielaborados'],
                $data['cantidad_material'],
                $data['cantidad_productos']
            ]);
            
            if (!$result) {
                throw new Exception("Error al agregar receta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_receta_empaq'];
            
        } catch (Exception $e) {
            error_log("Error al agregar receta: " . $e->getMessage());
            throw new Exception("Error al agregar receta: " . $e->getMessage());
        }
    }
    
    public function editarRecetaEmpaquetado($id_receta, $data) {
        try {
            $query = "UPDATE treceta_empaquetado SET 
                      id_producto_final = $1,
                      id_semielaborado = $2,
                      id_material = $3,
                      cantidad_semielaborados_necesarios = $4,
                      cantidad_material_necesario = $5,
                      cantidad_productos_resultantes = $6
                      WHERE id_receta_empaq = $7";
            
            $result = pg_query_params($this->db, $query, [
                $data['id_producto_final'],
                $data['id_semielaborado'],
                $data['id_material'],
                $data['cantidad_semielaborados'],
                $data['cantidad_material'],
                $data['cantidad_productos'],
                $id_receta
            ]);
            
            if (!$result) {
                throw new Exception("Error al editar receta: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al editar receta: " . $e->getMessage());
            throw new Exception("Error al editar receta: " . $e->getMessage());
        }
    }
    
    public function eliminarRecetaEmpaquetado($id_receta) {
        try {
            $query = "DELETE FROM treceta_empaquetado WHERE id_receta_empaq = $1";
            $result = pg_query_params($this->db, $query, [$id_receta]);
            
            if (!$result) {
                throw new Exception("Error al eliminar receta: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al eliminar receta: " . $e->getMessage());
            throw new Exception("Error al eliminar receta: " . $e->getMessage());
        }
    }
    
    public function obtenerRecetasEmpaquetado() {
        try {
            $query = "SELECT re.*, 
                             pf.nombre as nombre_producto_final,
                             ps.nombre as nombre_semielaborado,
                             m.nombre as nombre_material
                      FROM treceta_empaquetado re
                      JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                      JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                      JOIN tmaterial m ON re.id_material = m.id_material
                      ORDER BY pf.nombre, ps.nombre";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            return $recetas;
        } catch (Exception $e) {
            error_log("Error al obtener recetas: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerRecetasEmpaquetadoPaginado($offset = 0, $limit = 10) {
        try {
            $query = "SELECT re.*, 
                             pf.nombre as nombre_producto_final,
                             ps.nombre as nombre_semielaborado,
                             m.nombre as nombre_material
                      FROM treceta_empaquetado re
                      JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                      JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                      JOIN tmaterial m ON re.id_material = m.id_material
                      ORDER BY pf.nombre, ps.nombre
                      LIMIT $1 OFFSET $2";
            
            $result = pg_query_params($this->db, $query, array($limit, $offset));
            
            if (!$result) {
                throw new Exception("Error en la consulta paginada: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            $sql_count = "SELECT COUNT(*) as total FROM treceta_empaquetado";
            $result_count = pg_query($this->db, $sql_count);
            $total_registros = 0;
            if ($result_count) {
                $row_count = pg_fetch_assoc($result_count);
                $total_registros = $row_count['total'];
            }
            
            return array(
                'recetas' => $recetas,
                'total_registros' => $total_registros
            );
            
        } catch (Exception $e) {
            error_log("Error al obtener recetas paginadas: " . $e->getMessage());
            return array(
                'recetas' => [],
                'total_registros' => 0
            );
        }
    }
    
    public function buscarRecetasEmpaquetadoPaginado($termino, $offset = 0, $limit = 10) {
        try {
            $searchTerm = "%" . $termino . "%";
            
            $query = "SELECT re.*, 
                             pf.nombre as nombre_producto_final,
                             ps.nombre as nombre_semielaborado,
                             m.nombre as nombre_material
                      FROM treceta_empaquetado re
                      JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                      JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                      JOIN tmaterial m ON re.id_material = m.id_material
                      WHERE pf.nombre ILIKE $1 OR ps.nombre ILIKE $1 OR m.nombre ILIKE $1
                      ORDER BY pf.nombre, ps.nombre
                      LIMIT $2 OFFSET $3";
            
            $result = pg_query_params($this->db, $query, array($searchTerm, $limit, $offset));
            
            if (!$result) {
                throw new Exception("Error en la consulta de búsqueda: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            $sql_count = "SELECT COUNT(*) as total 
                          FROM treceta_empaquetado re
                          JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                          JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                          JOIN tmaterial m ON re.id_material = m.id_material
                          WHERE pf.nombre ILIKE $1 OR ps.nombre ILIKE $1 OR m.nombre ILIKE $1";
            
            $result_count = pg_query_params($this->db, $sql_count, array($searchTerm));
            $total_registros = 0;
            if ($result_count) {
                $row_count = pg_fetch_assoc($result_count);
                $total_registros = $row_count['total'];
            }
            
            return array(
                'recetas' => $recetas,
                'total_registros' => $total_registros
            );
            
        } catch (Exception $e) {
            error_log("Error al buscar recetas paginadas: " . $e->getMessage());
            return array(
                'recetas' => [],
                'total_registros' => 0
            );
        }
    }
    
    public function buscarRecetasEmpaquetado($termino) {
        try {
            $searchTerm = "%" . $termino . "%";
            
            $query = "SELECT re.*, 
                             pf.nombre as nombre_producto_final,
                             ps.nombre as nombre_semielaborado,
                             m.nombre as nombre_material
                      FROM treceta_empaquetado re
                      JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                      JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                      JOIN tmaterial m ON re.id_material = m.id_material
                      WHERE pf.nombre ILIKE $1 OR ps.nombre ILIKE $1 OR m.nombre ILIKE $1
                      ORDER BY pf.nombre, ps.nombre";
            
            $result = pg_query_params($this->db, $query, array($searchTerm));
            
            if (!$result) {
                throw new Exception("Error en la consulta de búsqueda: " . pg_last_error($this->db));
            }
            
            $recetas = [];
            while ($row = pg_fetch_assoc($result)) {
                $recetas[] = $row;
            }
            
            return $recetas;
            
        } catch (Exception $e) {
            error_log("Error al buscar recetas: " . $e->getMessage());
            return [];
        }
    }
    
    public function verificarRecetaExistente($id_producto_final, $id_semielaborado, $id_material) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM treceta_empaquetado 
                      WHERE id_producto_final = $1 
                      AND id_semielaborado = $2 
                      AND id_material = $3";
            
            $result = pg_query_params($this->db, $query, [
                $id_producto_final,
                $id_semielaborado,
                $id_material
            ]);
            
            if ($result && $row = pg_fetch_assoc($result)) {
                return $row['total'] > 0;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar receta: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerRecetaPorId($id_receta) {
        try {
            $query = "SELECT re.*, 
                             pf.nombre as nombre_producto_final,
                             ps.nombre as nombre_semielaborado,
                             m.nombre as nombre_material
                      FROM treceta_empaquetado re
                      JOIN tproducto pf ON re.id_producto_final = pf.id_producto
                      JOIN tproducto_semielaborado ps ON re.id_semielaborado = ps.id_semielaborado
                      JOIN tmaterial m ON re.id_material = m.id_material
                      WHERE re.id_receta_empaq = $1";
            
            $result = pg_query_params($this->db, $query, [$id_receta]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            if ($row = pg_fetch_assoc($result)) {
                return $row;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener receta por ID: " . $e->getMessage());
            return null;
        }
    }
}
?>