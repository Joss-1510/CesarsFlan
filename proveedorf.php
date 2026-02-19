<?php
class ProveedorF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerProveedoresCompletos($incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproveedor";
            
            if ($incluirInactivos) {
                $query .= " WHERE baja = true";
            } else {
                $query .= " WHERE baja = false";
            }
            
            $query .= " ORDER BY baja, nombre";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $proveedores = [];
            while ($row = pg_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            
            return $proveedores;
        } catch (Exception $e) {
            error_log("Error en obtenerProveedoresCompletos: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerProveedoresCompletosConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproveedor";
            
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
            
            $proveedores = [];
            while ($row = pg_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproveedor";
            if ($incluirInactivos) {
                $countQuery .= " WHERE baja = true";
            } else {
                $countQuery .= " WHERE baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'proveedores' => $proveedores,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerProveedoresCompletosConPaginacion: " . $e->getMessage());
            return ['proveedores' => [], 'total' => 0];
        }
    }
    
    public function buscarProveedores($nombre, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproveedor
                      WHERE nombre ILIKE $1";
            
            if ($incluirInactivos) {
                $query .= " AND baja = true";
            } else {
                $query .= " AND baja = false";
            }
            
            $query .= " ORDER BY baja, nombre";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $proveedores = [];
            while ($row = pg_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            
            return $proveedores;
        } catch (Exception $e) {
            error_log("Error al buscar proveedores: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarProveedoresConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproveedor
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
            
            $proveedores = [];
            while ($row = pg_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproveedor WHERE nombre ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND baja = true";
            } else {
                $countQuery .= " AND baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'proveedores' => $proveedores,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar proveedores con paginación: " . $e->getMessage());
            return ['proveedores' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tproveedor') {
                return $this->createProveedorEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_proveedor";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_proveedor'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createProveedorEspecial($data) {
        try {
            $query = "INSERT INTO tproveedor 
                     (nombre, telefono, direccion, baja) 
                      VALUES ($1, $2, $3, $4) RETURNING id_proveedor";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'],
                $data['telefono'] ?? null,
                $data['direccion'] ?? null,
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear proveedor: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_proveedor'];
            
        } catch (Exception $e) {
            error_log("Error al crear proveedor: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear proveedor: " . $e->getMessage());
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

    public function proveedorExiste($nombreProveedor, $idProveedor = null) {
        try {
            $query = "SELECT id_proveedor FROM tproveedor WHERE nombre = $1";
            $params = [$nombreProveedor];
            
            if ($idProveedor) {
                $query .= " AND id_proveedor != $2";
                $params[] = $idProveedor;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar proveedor: " . $e->getMessage());
            return false;
        }
    }
}
?>