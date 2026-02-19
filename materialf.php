<?php
class MaterialF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public static $TIPOS_MATERIAL = [
        'Empaque' => 'Empaque',
        'Etiqueta' => 'Etiqueta',
        'Tapa' => 'Tapa',
        'Accesorio' => 'Accesorio', 
        'Decoración' => 'Decoración',
        'Protector' => 'Protector',
        'Cinta' => 'Cinta',
        'Bolsa' => 'Bolsa'
    ];
    
    public function obtenerMaterialesConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tmaterial";
            
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
            
            $materiales = [];
            while ($row = pg_fetch_assoc($result)) {
                $materiales[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tmaterial";
            if ($incluirInactivos) {
                $countQuery .= " WHERE baja = true";
            } else {
                $countQuery .= " WHERE baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'materiales' => $materiales,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerMaterialesConPaginacion: " . $e->getMessage());
            return ['materiales' => [], 'total' => 0];
        }
    }
    
    public function buscarMaterialesConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tmaterial
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
            
            $materiales = [];
            while ($row = pg_fetch_assoc($result)) {
                $materiales[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tmaterial WHERE nombre ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND baja = true";
            } else {
                $countQuery .= " AND baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'materiales' => $materiales,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar materiales con paginación: " . $e->getMessage());
            return ['materiales' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tmaterial') {
                return $this->createMaterialEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_material";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_material'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createMaterialEspecial($data) {
        try {
            $query = "INSERT INTO tmaterial 
                     (nombre, tipo, cantidad_stock, costo_por_unidad, descripcion, baja) 
                      VALUES ($1, $2, $3, $4, $5, $6) RETURNING id_material";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'],
                $data['tipo'] ?? 'Empaque',
                $data['cantidad_stock'] ?? 0,
                $data['costo_por_unidad'] ?? 0,
                $data['descripcion'] ?? null,
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear material: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_material'];
            
        } catch (Exception $e) {
            error_log("Error al crear material: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear material: " . $e->getMessage());
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
    
    public function materialExiste($nombreMaterial, $idMaterial = null) {
        try {
            $query = "SELECT id_material FROM tmaterial WHERE nombre = $1";
            $params = [$nombreMaterial];
            
            if ($idMaterial) {
                $query .= " AND id_material != $2";
                $params[] = $idMaterial;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar material: " . $e->getMessage());
            return false;
        }
    }
}
?>