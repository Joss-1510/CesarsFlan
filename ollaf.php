<?php
class OllaF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerOllasCompletas($incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tolla";
            
            if ($incluirInactivos) {
                $query .= " WHERE baja = true";
            } else {
                $query .= " WHERE baja = false";
            }
            
            $query .= " ORDER BY numero_olla";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ollas = [];
            while ($row = pg_fetch_assoc($result)) {
                $ollas[] = $row;
            }
            
            return $ollas;
        } catch (Exception $e) {
            error_log("Error en obtenerOllasCompletas: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerOllasCompletasConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tolla";
             
            if ($incluirInactivos) {
                $query .= " WHERE baja = true";
            } else {
                $query .= " WHERE baja = false";
            }
            
            $query .= " ORDER BY numero_olla
                      LIMIT $limit OFFSET $offset";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ollas = [];
            while ($row = pg_fetch_assoc($result)) {
                $ollas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tolla";
            if ($incluirInactivos) {
                $countQuery .= " WHERE baja = true";
            } else {
                $countQuery .= " WHERE baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'ollas' => $ollas,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerOllasCompletasConPaginacion: " . $e->getMessage());
            return ['ollas' => [], 'total' => 0];
        }
    }
    
    public function buscarOllas($numeroOlla, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tolla
                      WHERE CAST(numero_olla AS TEXT) ILIKE $1";
            
            if ($incluirInactivos) {
                $query .= " AND baja = true";
            } else {
                $query .= " AND baja = false";
            }
            
            $query .= " ORDER BY numero_olla";
            
            $searchTerm = "%$numeroOlla%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ollas = [];
            while ($row = pg_fetch_assoc($result)) {
                $ollas[] = $row;
            }
            
            return $ollas;
        } catch (Exception $e) {
            error_log("Error al buscar ollas: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarOllasConPaginacion($numeroOlla, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tolla
                      WHERE CAST(numero_olla AS TEXT) ILIKE $1";
            
            if ($incluirInactivos) {
                $query .= " AND baja = true";
            } else {
                $query .= " AND baja = false";
            }
            
            $query .= " ORDER BY numero_olla
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$numeroOlla%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $ollas = [];
            while ($row = pg_fetch_assoc($result)) {
                $ollas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tolla WHERE CAST(numero_olla AS TEXT) ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND baja = true";
            } else {
                $countQuery .= " AND baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'ollas' => $ollas,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar ollas con paginación: " . $e->getMessage());
            return ['ollas' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tolla') {
                return $this->createOllaEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_olla";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_olla'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createOllaEspecial($data) {
        try {
            $query = "INSERT INTO tolla 
                     (numero_olla, capacidad, estado, baja, fecha_creacion) 
                      VALUES ($1, $2, $3, $4, NOW()) RETURNING id_olla";
            
            $result = pg_query_params($this->db, $query, [
                $data['numero_olla'],
                $data['capacidad'] ?? 0,
                $data['estado'] ?? 'Disponible',
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear olla: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_olla'];
            
        } catch (Exception $e) {
            error_log("Error al crear olla: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear olla: " . $e->getMessage());
        }
    }
    
    public function read($table, $conditions = [], $limit = null) {
        try {
            $query = "SELECT * FROM $table";
            
            if (!empty($conditions)) {
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "\"$key\" = '" . pg_escape_string($this->db, $value) . "'";
                }
                $query .= " WHERE " . implode(" AND ", $where);
            }
            
            if ($limit) {
                $query .= " LIMIT $limit";
            }
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $rows = [];
            while ($row = pg_fetch_assoc($result)) {
                $rows[] = $row;
            }
            
            return $rows;
        } catch (Exception $e) {
            error_log("Error al leer registros: " . $e->getMessage());
            return false;
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
    
    public function delete($table, $conditions) {
        try {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "\"$key\" = '" . pg_escape_string($this->db, $value) . "'";
            }
            
            $query = "DELETE FROM $table WHERE " . implode(" AND ", $where);
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al eliminar registro: " . pg_last_error($this->db));
            }
            
            return pg_affected_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al eliminar registro: " . $e->getMessage());
            throw new Exception("Error al eliminar registro: " . $e->getMessage());
        }
    }
    
    public function lastInsertId() {
        $result = pg_query($this->db, "SELECT lastval()");
        if ($result) {
            $row = pg_fetch_row($result);
            return $row[0];
        }
        return null;
    }

    public function ollaExiste($numeroOlla, $idOlla = null) {
        try {
            $query = "SELECT id_olla FROM tolla WHERE numero_olla = $1";
            $params = [$numeroOlla];
            
            if ($idOlla) {
                $query .= " AND id_olla != $2";
                $params[] = $idOlla;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar olla: " . $e->getMessage());
            return false;
        }
    }

    public function ollaTieneTemporizadoresActivos($idOlla) {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM ttemporizador_olla 
                      WHERE id_olla = $1 AND baja = false";
            
            $result = pg_query_params($this->db, $query, [$idOlla]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar temporizadores: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerOllasConEstado() {
        try {
            $sql = "SELECT 
                        o.id_olla, 
                        o.numero_olla, 
                        o.capacidad,
                        o.estado,
                        CASE 
                            WHEN tolla.id_temporizador IS NOT NULL AND tolla.baja = false THEN true
                            ELSE false
                        END as tiene_temporizador_activo,
                        tolla.nombre as nombre_temporizador
                    FROM tolla o
                    LEFT JOIN ttemporizador_olla tolla ON o.id_olla = tolla.id_olla AND tolla.baja = false
                    WHERE o.baja = false
                    ORDER BY o.numero_olla ASC";
            
            $result = pg_query($this->db, $sql);
            if (!$result) {
                return [];
            }
            
            $ollas = [];
            while ($row = pg_fetch_assoc($result)) {
                $ollas[] = $row;
            }
            
            return $ollas;
        } catch (Exception $e) {
            error_log("Error al obtener ollas con estado: " . $e->getMessage());
            return [];
        }
    }
}
?>