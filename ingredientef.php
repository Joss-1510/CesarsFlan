<?php
class IngredienteF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public static $TIPOS_INGREDIENTE = [
        'Base' => 'Base',
        'Endulzante' => 'Endulzante', 
        'Saborizante' => 'Saborizante',
        'Fruta' => 'Fruta',
        'Decoración' => 'Decoración',
        'Estabilizante' => 'Estabilizante',
        'Conservador' => 'Conservador',
        'Colorante' => 'Colorante'
    ];
    
    public static $UNIDADES_INGREDIENTE = [
        'gramos' => 'gramos',
        'kilogramos' => 'kilogramos',
        'mililitros' => 'mililitros', 
        'litros' => 'litros',
        'piezas' => 'piezas',
        'sobres' => 'sobres',
        'cucharadas' => 'cucharadas',
        'cucharaditas' => 'cucharaditas',
        'tazas' => 'tazas'
    ];
    
    public function obtenerIngredientesConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tingrediente";
            
            if ($incluirInactivos) {
                $query .= " WHERE baja = true";
            } else {
                $query .= " WHERE baja = false";
            }
            
            $query .= " ORDER BY baja, nombre
                      LIMIT $limit OFFSET $offset";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ingredientes = [];
            while ($row = pg_fetch_assoc($result)) {
                $ingredientes[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tingrediente";
            if ($incluirInactivos) {
                $countQuery .= " WHERE baja = true";
            } else {
                $countQuery .= " WHERE baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'ingredientes' => $ingredientes,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerIngredientesConPaginacion: " . $e->getMessage());
            return ['ingredientes' => [], 'total' => 0];
        }
    }
    
    public function buscarIngredientesConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tingrediente
                      WHERE nombre ILIKE $1";
            
            if ($incluirInactivos) {
                $query .= " AND baja = true";
            } else {
                $query .= " AND baja = false";
            }
            
            $query .= " ORDER BY baja, nombre
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ingredientes = [];
            while ($row = pg_fetch_assoc($result)) {
                $ingredientes[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tingrediente WHERE nombre ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND baja = true";
            } else {
                $countQuery .= " AND baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'ingredientes' => $ingredientes,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar ingredientes con paginación: " . $e->getMessage());
            return ['ingredientes' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tingrediente') {
                return $this->createIngredienteEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_ingrediente";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_ingrediente'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createIngredienteEspecial($data) {
        try {
            $query = "INSERT INTO tingrediente 
                     (nombre, tipo, cantidad_stock, unidad_medida, costo_por_unidad, baja) 
                      VALUES ($1, $2, $3, $4, $5, $6) RETURNING id_ingrediente";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'],
                $data['tipo'] ?? 'Base',
                $data['cantidad_stock'] ?? 0,
                $data['unidad_medida'] ?? 'gramos',
                $data['costo_por_unidad'] ?? 0,
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear ingrediente: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_ingrediente'];
            
        } catch (Exception $e) {
            error_log("Error al crear ingrediente: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear ingrediente: " . $e->getMessage());
        }
    }
    
    public function update($table, $data, $conditions) {
        try {
            $set = [];
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $set[] = "\"$key\" = NULL";
                } else {
                    $set[] = "\"$key\" = '" . pg_escape_string($this->db, $value) . "'";
                }
            }
            
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "\"$key\" = '" . pg_escape_string($this->db, $value) . "'";
            }
            
            $query = "UPDATE $table SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $where);
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al actualizar registro: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al actualizar registro: " . $e->getMessage());
            throw new Exception("Error al actualizar registro: " . $e->getMessage());
        }
    }
    
    public function ingredienteExiste($nombreIngrediente, $idIngrediente = null) {
        try {
            $query = "SELECT id_ingrediente FROM tingrediente WHERE nombre = $1";
            $params = [$nombreIngrediente];
            
            if ($idIngrediente) {
                $query .= " AND id_ingrediente != $2";
                $params[] = $idIngrediente;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar ingrediente: " . $e->getMessage());
            return false;
        }
    }
}
?>