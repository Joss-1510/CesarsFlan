<?php
class RutaF {
    private $db;
    
    public function __construct($conn) {
        $this->db = $conn;
    }
    
    public function obtenerRutasCompletasConPaginacion($offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE 1=1";
        
            if (!$incluirInactivos) {
                $query .= " AND r.baja = false";
            } else {
                $query .= " AND r.baja = true";
            }
        
            $query .= " ORDER BY r.orden, r.baja, r.id_ruta
                      LIMIT $1 OFFSET $2";
            
            $result = pg_query_params($this->db, $query, [$limit, $offset]);
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $rutas = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
                $rutas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM truta r WHERE 1=1";
            if (!$incluirInactivos) {
                $countQuery .= " AND r.baja = false";
            } else {
                $countQuery .= " AND r.baja = true";
            }
            
            $countResult = pg_query($this->db, $countQuery);
            if (!$countResult) {
                throw new Exception("Error en consulta de conteo: " . pg_last_error($this->db));
            }
            
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'rutas' => $rutas,
                'total' => (int)$total
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerRutasCompletasConPaginacion: " . $e->getMessage());
            return ['rutas' => [], 'total' => 0];
        }
    }
    
    public function buscarRutas($termino, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE (u.nombre ILIKE $1 OR c.nombre ILIKE $1 OR dr.dia ILIKE $1)";
        
            if (!$incluirInactivos) {
                $query .= " AND r.baja = false";
            } else {
                $query .= " AND r.baja = true";
            }
        
            $query .= " ORDER BY r.orden, r.baja, r.id_ruta";
            
            $searchTerm = "%$termino%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $rutas = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
                $rutas[] = $row;
            }
            
            return $rutas;
        } catch (Exception $e) {
            error_log("Error al buscar rutas: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarRutasConPaginacion($termino, $offset = 0, $limit = 10, $incluirInactivos = false) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE (u.nombre ILIKE $1 OR c.nombre ILIKE $1 OR dr.dia ILIKE $1)";
        
            if (!$incluirInactivos) {
                $query .= " AND r.baja = false";
            } else {
                $query .= " AND r.baja = true";
            }
        
            $query .= " ORDER BY r.orden, r.baja, r.id_ruta
                      LIMIT $2 OFFSET $3";
            
            $searchTerm = "%$termino%";
            $result = pg_query_params($this->db, $query, [$searchTerm, $limit, $offset]);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            $rutas = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
                $rutas[] = $row;
            }
            
            $countQuery = "SELECT COUNT(*) as total 
                          FROM truta r
                          LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                          LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                          LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                          WHERE (u.nombre ILIKE $1 OR c.nombre ILIKE $1 OR dr.dia ILIKE $1)";
            
            if (!$incluirInactivos) {
                $countQuery .= " AND r.baja = false";
            } else {
                $countQuery .= " AND r.baja = true";
            }
            
            $countResult = pg_query_params($this->db, $countQuery, [$searchTerm]);
            if (!$countResult) {
                throw new Exception("Error en consulta de conteo: " . pg_last_error($this->db));
            }
            
            $totalRow = pg_fetch_assoc($countResult);
            $total = $totalRow['total'];
            
            return [
                'rutas' => $rutas,
                'total' => (int)$total
            ];
        } catch (Exception $e) {
            error_log("Error al buscar rutas con paginación: " . $e->getMessage());
            return ['rutas' => [], 'total' => 0];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }

            if ($table === 'truta') {
                return $this->createRutaEspecial($data);
            }

            $columns = implode(", ", array_map(function($col) {
                return "\"$col\"";
            }, array_keys($data)));
            
            $values = implode(", ", array_map(function($key) {
                return "'" . pg_escape_string($this->db, $data[$key]) . "'";
            }, array_keys($data)));
            
            $query = "INSERT INTO $table ($columns) VALUES ($values) RETURNING id_ruta";
            
            $result = pg_query($this->db, $query);
            if (!$result) {
                throw new Exception("Error al ejecutar la consulta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_ruta'];
            
        } catch (Exception $e) {
            error_log("Error en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createRutaEspecial($data) {
        try {
            $query = "INSERT INTO truta 
                     (id_usuario, id_dia, id_cliente, baja, orden) 
                      VALUES ($1, $2, $3, $4, $5) RETURNING id_ruta";
            
            $baja = isset($data['baja']) && ($data['baja'] === 'true' || $data['baja'] === true || $data['baja'] === 't') ? 'true' : 'false';
            
            $orden = isset($data['orden']) ? $data['orden'] : $this->obtenerSiguienteOrden($data['id_usuario'], $data['id_dia']);
            
            $result = pg_query_params($this->db, $query, [
                $data['id_usuario'],
                $data['id_dia'],
                $data['id_cliente'],
                $baja,
                $orden
            ]);
            
            if (!$result) {
                throw new Exception("Error al crear ruta: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return $row['id_ruta'];
            
        } catch (Exception $e) {
            error_log("Error al crear ruta: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear ruta: " . $e->getMessage());
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
            $params = [];
            $paramCount = 1;
            
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $set[] = "\"$key\" = NULL";
                } else {
                    if ($key === 'baja') {
                        $value = ($value === 'true' || $value === true || $value === 't') ? 'true' : 'false';
                    }
                    $set[] = "\"$key\" = \$$paramCount";
                    $params[] = $value;
                    $paramCount++;
                }
            }
            
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "\"$key\" = \$$paramCount";
                $params[] = $value;
                $paramCount++;
            }
            
            $query = "UPDATE $table SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $where);
            $result = pg_query_params($this->db, $query, $params);
            
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
    
    public function obtenerUsuariosActivos() {
        try {
            $query = "SELECT id_usuario, nombre FROM tusuario WHERE baja = false AND id_rol = 2 ORDER BY nombre";
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al obtener usuarios: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerUsuariosEmpleados() {
        try {
            $query = "SELECT id_usuario, nombre FROM tusuario WHERE baja = false AND id_rol = 2 ORDER BY nombre";
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al obtener usuarios empleados: " . pg_last_error($this->db));
            }
            
            $usuarios = [];
            while ($row = pg_fetch_assoc($result)) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error al obtener usuarios empleados: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarUsuarios($termino) {
        try {
            $query = "SELECT id_usuario, nombre FROM tusuario 
                      WHERE baja = false AND id_rol = 2 AND nombre ILIKE $1 
                      ORDER BY nombre LIMIT 10";
            $searchTerm = "%$termino%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error al buscar usuarios: " . pg_last_error($this->db));
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
    
    public function obtenerClientesActivos() {
        try {
            $query = "SELECT id_cliente, nombre FROM tcliente WHERE baja = false ORDER BY nombre";
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al obtener clientes: " . pg_last_error($this->db));
            }
            
            $clientes = [];
            while ($row = pg_fetch_assoc($result)) {
                $clientes[] = $row;
            }
            
            return $clientes;
        } catch (Exception $e) {
            error_log("Error al obtener clientes: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarClientes($termino) {
        try {
            $query = "SELECT id_cliente, nombre FROM tcliente 
                      WHERE baja = false AND nombre ILIKE $1 
                      ORDER BY nombre LIMIT 10";
            $searchTerm = "%$termino%";
            $result = pg_query_params($this->db, $query, [$searchTerm]);
            
            if (!$result) {
                throw new Exception("Error al buscar clientes: " . pg_last_error($this->db));
            }
            
            $clientes = [];
            while ($row = pg_fetch_assoc($result)) {
                $clientes[] = $row;
            }
            
            return $clientes;
        } catch (Exception $e) {
            error_log("Error al buscar clientes: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerDias() {
        try {
            $query = "SELECT id_dia, dia FROM tdia_ruta ORDER BY id_dia";
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al obtener días: " . pg_last_error($this->db));
            }
            
            $dias = [];
            while ($row = pg_fetch_assoc($result)) {
                $dias[] = $row;
            }
            
            return $dias;
        } catch (Exception $e) {
            error_log("Error al obtener días: " . $e->getMessage());
            return [];
        }
    }
    
    public function rutaExiste($idUsuario, $idDia, $idCliente, $idRuta = null) {
        try {
            $query = "SELECT id_ruta FROM truta WHERE id_usuario = $1 AND id_dia = $2 AND id_cliente = $3";
            $params = [$idUsuario, $idDia, $idCliente];
            
            if ($idRuta) {
                $query .= " AND id_ruta != $4";
                $params[] = $idRuta;
            }
            
            $result = pg_query_params($this->db, $query, $params);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . pg_last_error($this->db));
            }
            
            return pg_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log("Error al verificar ruta: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerSiguienteOrden($idUsuario, $idDia) {
        try {
            $query = "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente 
                      FROM truta 
                      WHERE id_usuario = $1 AND id_dia = $2 AND baja = false";
            
            $result = pg_query_params($this->db, $query, [$idUsuario, $idDia]);
            
            if (!$result) {
                throw new Exception("Error al obtener siguiente orden: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            return (int)$row['siguiente'];
            
        } catch (Exception $e) {
            error_log("Error en obtenerSiguienteOrden: " . $e->getMessage());
            return 1; 
        }
    }
    
    public function obtenerRutasPorUsuarioYDia($idUsuario, $idDia, $soloActivas = true) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE r.id_usuario = $1 AND r.id_dia = $2";
        
            if ($soloActivas) {
                $query .= " AND r.baja = false";
            }
        
            $query .= " ORDER BY r.orden, r.id_ruta";
            
            $result = pg_query_params($this->db, $query, [$idUsuario, $idDia]);
            
            if (!$result) {
                throw new Exception("Error al obtener rutas por usuario y día: " . pg_last_error($this->db));
            }
            
            $rutas = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
                $rutas[] = $row;
            }
            
            return $rutas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerRutasPorUsuarioYDia: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerRutaPorId($idRuta) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE r.id_ruta = $1";
            
            $result = pg_query_params($this->db, $query, [$idRuta]);
            
            if (!$result) {
                throw new Exception("Error al obtener ruta por ID: " . pg_last_error($this->db));
            }
            
            $row = pg_fetch_assoc($result);
            if ($row) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
            }
            
            return $row;
            
        } catch (Exception $e) {
            error_log("Error en obtenerRutaPorId: " . $e->getMessage());
            return null;
        }
    }
    
    private function normalizarBooleano($valor) {
        if ($valor === 't' || $valor === true || $valor === 'true' || $valor === 1) {
            return true;
        }
        return false;
    }
}
?>