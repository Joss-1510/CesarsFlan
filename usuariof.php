<?php
class UsuarioF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerUsuariosCompletos($incluirInactivos = false) {
        try {
            $query = "SELECT 
                        u.id_usuario, 
                        u.nombre AS nombre_usuario, 
                        u.fechabaja,
                        u.baja,
                        r.nombre AS nombre_rol, 
                        r.id_rol
                      FROM tusuario u
                      LEFT JOIN trol r ON u.id_rol = r.id_rol";
        
            if ($incluirInactivos) {
                $query .= " WHERE u.baja = true";
            } else {
                $query .= " WHERE u.baja = false";
            }
        
            $query .= " ORDER BY u.baja, u.nombre";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error en obtenerUsuariosCompletos: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerUsuariosCompletosConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        u.id_usuario, 
                        u.nombre AS nombre_usuario, 
                        u.fechabaja,
                        u.baja,
                        r.nombre AS nombre_rol, 
                        r.id_rol
                      FROM tusuario u
                      LEFT JOIN trol r ON u.id_rol = r.id_rol";
        
            if ($incluirInactivos) {
                $query .= " WHERE u.baja = true";
            } else {
                $query .= " WHERE u.baja = false";
            }
        
            $query .= " ORDER BY u.baja, u.nombre
                      LIMIT $limit OFFSET $offset";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tusuario u";
            if ($incluirInactivos) {
                $countQuery .= " WHERE u.baja = true";
            } else {
                $countQuery .= " WHERE u.baja = false";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'usuarios' => $usuarios,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerUsuariosCompletosConPaginacion: " . $e->getMessage());
            return ['usuarios' => [], 'total' => 0];
        }
    }
    
    public function buscarUsuarios($nombre, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        u.id_usuario, 
                        u.nombre AS nombre_usuario, 
                        u.fechabaja,
                        u.baja,
                        r.nombre AS nombre_rol, 
                        r.id_rol
                      FROM tusuario u
                      LEFT JOIN trol r ON u.id_rol = r.id_rol
                      WHERE u.nombre ILIKE $1";
        
            if ($incluirInactivos) {
                $query .= " AND u.baja = true";
            } else {
                $query .= " AND u.baja = false";
            }
        
            $query .= " ORDER BY u.baja, u.nombre";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error al buscar usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarUsuariosConPaginacion($nombre, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        u.id_usuario, 
                        u.nombre AS nombre_usuario, 
                        u.fechabaja,
                        u.baja,
                        r.nombre AS nombre_rol, 
                        r.id_rol
                      FROM tusuario u
                      LEFT JOIN trol r ON u.id_rol = r.id_rol
                      WHERE u.nombre ILIKE $1";
        
            if ($incluirInactivos) {
                $query .= " AND u.baja = true";
            } else {
                $query .= " AND u.baja = false";
            }
        
            $query .= " ORDER BY u.baja, u.nombre
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$nombre%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM tusuario u WHERE u.nombre ILIKE $1";
            if ($incluirInactivos) {
                $countQuery .= " AND u.baja = true";
            } else {
                $countQuery .= " AND u.baja = false";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'usuarios' => $usuarios,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar usuarios con paginación: " . $e->getMessage());
            return ['usuarios' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'tusuario') {
                return $this->createUsuarioEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_usuario";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_usuario'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createUsuarioEspecial($data) {
        try {
            $query = "INSERT INTO tusuario 
                     (nombre, contra, id_rol, baja) 
                      VALUES ($1, $2, $3, $4) RETURNING id_usuario";
            
            $result = pg_query_params($this->db, $query, [
                $data['nombre'],
                $data['contra'], 
                $data['id_rol'],
                $data['baja'] ?? 'false'
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear usuario: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_usuario'];
            
        } catch (Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear usuario: " . $e->getMessage());
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

    public function usuarioExiste($nombreUsuario, $idUsuario = null) {
        try {
            $query = "SELECT id_usuario FROM tusuario WHERE nombre = $1";
            $params = [$nombreUsuario];
            
            if ($idUsuario) {
                $query .= " AND id_usuario != $2";
                $params[] = $idUsuario;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar usuario: " . $e->getMessage());
            return false;
        }
    }
}
?>