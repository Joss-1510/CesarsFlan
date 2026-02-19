<?php
class ProductoF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerProductosCompletos($incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto";
            
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
            
            $productos = [];
            while ($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            return $productos;
        } catch (Exception $e) {
            error_log("Error en obtenerProductosCompletos: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerProductosCompletosConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto";
            
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
            
            $productos = [];
            while ($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproducto";
            if ($incluirInactivos) {
                $countQuery .= " WHERE baja = true";
            } else {
                $countQuery .= " WHERE baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'productos' => $productos,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerProductosCompletosConPaginacion: " . $e->getMessage());
            return ['productos' => [], 'total' => 0];
        }
    }
    
    public function buscarProductos($nombre, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto
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
            
            $productos = [];
            while ($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            return $productos;
        } catch (Exception $e) {
            error_log("Error al buscar productos: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarProductosConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT * FROM tproducto
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
            
            $productos = [];
            while ($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tproducto WHERE nombre ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND baja = true";
            } else {
                $countQuery .= " AND baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'productos' => $productos,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar productos con paginación: " . $e->getMessage());
            return ['productos' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tproducto') {
                return $this->createProductoEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_producto";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_producto'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createProductoEspecial($data) {
        try {
            $query = "INSERT INTO tproducto 
                     (nombre, descripcion, precio, stock, baja) 
                      VALUES ($1, $2, $3, $4, $5) RETURNING id_producto";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['precio'] ?? 0,
                $data['stock'] ?? 0,
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear producto: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_producto'];
            
        } catch (Exception $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear producto: " . $e->getMessage());
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

    public function productoExiste($nombreProducto, $idProducto = null) {
        try {
            $query = "SELECT id_producto FROM tproducto WHERE nombre = $1";
            $params = [$nombreProducto];
            
            if ($idProducto) {
                $query .= " AND id_producto != $2";
                $params[] = $idProducto;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar producto: " . $e->getMessage());
            return false;
        }
    }
}
?>