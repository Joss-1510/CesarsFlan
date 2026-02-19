<?php
class SemielaboradoF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerSemielaboradosCompletos($incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto_semielaborado";
            
            if (!$incluirInactivos) {
                $query .= " WHERE estado != 'INACTIVO' OR estado IS NULL";
            } else {
                $query .= " WHERE estado = 'INACTIVO'";
            }
            
            $query .= " ORDER BY estado, nombre";
            
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
            error_log("Error en obtenerSemielaboradosCompletos: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerSemielaboradosCompletosConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto_semielaborado";
            
            if (!$incluirInactivos) {
                $query .= " WHERE estado != 'INACTIVO' OR estado IS NULL";
            } else {
                $query .= " WHERE estado = 'INACTIVO'";
            }
            
            $query .= " ORDER BY estado, nombre
                      LIMIT $limit OFFSET $offset";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $semielaborados = [];
            while ($row = pg_fetch_assoc($result)) {
                $semielaborados[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproducto_semielaborado";
            if (!$incluirInactivos) {
                $countQuery .= " WHERE estado != 'INACTIVO' OR estado IS NULL";
            } else {
                $countQuery .= " WHERE estado = 'INACTIVO'";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'semielaborados' => $semielaborados,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerSemielaboradosCompletosConPaginacion: " . $e->getMessage());
            return ['semielaborados' => [], 'total' => 0];
        }
    }
    
    public function buscarSemielaborados($nombre, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto_semielaborado
                      WHERE nombre ILIKE $1";
            
            if (!$incluirInactivos) {
                $query .= " AND (estado != 'INACTIVO' OR estado IS NULL)";
            } else {
                $query .= " AND estado = 'INACTIVO'";
            }
            
            $query .= " ORDER BY estado, nombre";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $semielaborados = [];
            while ($row = pg_fetch_assoc($result)) {
                $semielaborados[] = $row;
            }
            
            return $semielaborados;
        } catch (Exception $e) {
            error_log("Error al buscar semielaborados: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarSemielaboradosConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto_semielaborado
                      WHERE nombre ILIKE $1";
            
            if (!$incluirInactivos) {
                $query .= " AND (estado != 'INACTIVO' OR estado IS NULL)";
            } else {
                $query .= " AND estado = 'INACTIVO'";
            }
            
            $query .= " ORDER BY estado, nombre
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $semielaborados = [];
            while ($row = pg_fetch_assoc($result)) {
                $semielaborados[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproducto_semielaborado WHERE nombre ILIKE $1";
            if (!$incluirInactivos) {
                $countQuery .= " AND (estado != 'INACTIVO' OR estado IS NULL)";
            } else {
                $countQuery .= " AND estado = 'INACTIVO'";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'semielaborados' => $semielaborados,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar semielaborados con paginación: " . $e->getMessage());
            return ['semielaborados' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tproducto_semielaborado') {
                return $this->createSemielaboradoEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_semielaborado";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_semielaborado'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createSemielaboradoEspecial($data) {
        try {
            $query = "INSERT INTO tproducto_semielaborado 
                     (nombre, cantidad, estado, fecha_creacion) 
                      VALUES ($1, $2, $3, $4) RETURNING id_semielaborado";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'] ?? '',
                $data['cantidad'] ?? 0,
                $data['estado'] ?? 'ACTIVO',
                date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear semielaborado: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_semielaborado'];
            
        } catch (Exception $e) {
            error_log("Error al crear semielaborado: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear semielaborado: " . $e->getMessage());
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
    
    public function semielaboradoExiste($nombre, $idSemielaborado = null) {
        try {
            $query = "SELECT id_semielaborado FROM tproducto_semielaborado WHERE nombre = $1";
            $params = [$nombre];
            
            if ($idSemielaborado) {
                $query .= " AND id_semielaborado != $2";
                $params[] = $idSemielaborado;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar semielaborado: " . $e->getMessage());
            return false;
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
}
?>