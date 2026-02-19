<?php
require_once 'conexion.php';

class TemporizadorOlla {
    
    public static function obtenerTemporizadoresActivos() {
        global $conn;
        
        $sql = "SELECT tolla.id_temporizador, tolla.nombre, tolla.duracion, 
                       tolla.id_olla, tol.numero_olla, tol.capacidad,
                       tolla.tipo_producto, tolla.activo, tolla.baja, tolla.fechabaja
                FROM ttemporizador_olla tolla
                INNER JOIN tolla tol ON tolla.id_olla = tol.id_olla
                WHERE tolla.baja = false AND tol.baja = false
                ORDER BY tol.numero_olla ASC, tolla.nombre ASC";
        
        $result = pg_query($conn, $sql);
        if (!$result) {
            return [];
        }
        
        $temporizadores = [];
        while ($row = pg_fetch_assoc($result)) {
            $temporizadores[] = $row;
        }
        
        return $temporizadores;
    }
    
    public static function obtenerTemporizadoresInactivos() {
        global $conn;
        
        $sql = "SELECT tolla.id_temporizador, tolla.nombre, tolla.duracion, 
                       tolla.id_olla, tol.numero_olla, tol.capacidad,
                       tolla.tipo_producto, tolla.activo, tolla.baja, tolla.fechabaja
                FROM ttemporizador_olla tolla
                INNER JOIN tolla tol ON tolla.id_olla = tol.id_olla
                WHERE tolla.baja = true
                ORDER BY tolla.fechabaja DESC, tol.numero_olla ASC";
        
        $result = pg_query($conn, $sql);
        if (!$result) {
            return [];
        }
        
        $temporizadores = [];
        while ($row = pg_fetch_assoc($result)) {
            $temporizadores[] = $row;
        }
        
        return $temporizadores;
    }
    
    public static function obtenerTemporizador($id_temporizador) {
        global $conn;
        
        $sql = "SELECT tolla.*, tol.numero_olla, tol.capacidad 
                FROM ttemporizador_olla tolla
                INNER JOIN tolla tol ON tolla.id_olla = tol.id_olla
                WHERE tolla.id_temporizador = $id_temporizador";
        
        $result = pg_query($conn, $sql);
        if (!$result) {
            return false;
        }
        
        return pg_fetch_assoc($result);
    }
    
    public static function crearTemporizador($datos) {
        global $conn;
        
        $id_olla = $datos['id_olla'];
        $nombre = pg_escape_string($datos['nombre']);
        $duracion = pg_escape_string($datos['duracion']);
        $tipo_producto = pg_escape_string($datos['tipo_producto']);
        $activo = $datos['activo'] ?? 'true';
        
        $sql = "INSERT INTO ttemporizador_olla 
                (id_olla, nombre, duracion, tipo_producto, activo, baja, fecha_creacion) 
                VALUES ($id_olla, '$nombre', '$duracion', '$tipo_producto', $activo, false, NOW())";
        
        $result = pg_query($conn, $sql);
        return $result !== false;
    }
    
    public static function actualizarTemporizador($id_temporizador, $datos) {
        global $conn;
        
        $id_olla = $datos['id_olla'];
        $nombre = pg_escape_string($datos['nombre']);
        $duracion = pg_escape_string($datos['duracion']);
        $tipo_producto = pg_escape_string($datos['tipo_producto']);
        $activo = $datos['activo'];
        
        $sql = "UPDATE ttemporizador_olla 
                SET id_olla = $id_olla, 
                    nombre = '$nombre', 
                    duracion = '$duracion', 
                    tipo_producto = '$tipo_producto', 
                    activo = $activo
                WHERE id_temporizador = $id_temporizador";
        
        $result = pg_query($conn, $sql);
        return $result !== false;
    }
    
    public static function eliminarTemporizador($id_temporizador) {
        global $conn;
        
        $fecha_actual = date('Y-m-d H:i:s');
        $sql = "UPDATE ttemporizador_olla 
                SET baja = true, fechabaja = '$fecha_actual' 
                WHERE id_temporizador = $id_temporizador";
        
        $result = pg_query($conn, $sql);
        return $result !== false;
    }
    
    public static function reactivarTemporizador($id_temporizador) {
        global $conn;
        
        $sql = "UPDATE ttemporizador_olla 
                SET baja = false, fechabaja = NULL 
                WHERE id_temporizador = $id_temporizador";
        
        $result = pg_query($conn, $sql);
        return $result !== false;
    }
    
    public static function obtenerOllasConEstado() {
        global $conn;
        
        $sql = "SELECT 
                    o.id_olla, 
                    o.numero_olla, 
                    o.capacidad,
                    o.baja as olla_baja,
                    CASE 
                        WHEN t.id_temporizador IS NOT NULL AND t.baja = false THEN true
                        ELSE false
                    END as tiene_temporizador_activo,
                    t.nombre as nombre_temporizador,
                    t.baja as temporizador_baja
                FROM tolla o
                LEFT JOIN ttemporizador_olla t ON o.id_olla = t.id_olla
                WHERE o.baja = false
                ORDER BY o.numero_olla ASC";
        
        $result = pg_query($conn, $sql);
        if (!$result) {
            error_log("Error en obtenerOllasConEstado: " . pg_last_error($conn));
            return [];
        }
        
        $ollas = [];
        while ($row = pg_fetch_assoc($result)) {
            $tiene_temporizador = $row['tiene_temporizador_activo'] === true || 
                                  $row['tiene_temporizador_activo'] === 't' ||
                                  $row['tiene_temporizador_activo'] === 1;
            
            $row['tiene_temporizador_activo'] = $tiene_temporizador;
            $ollas[] = $row;
        }
        
        return $ollas;
    }
   
    public static function registrarCicloEmpaque($id_temporizador, $id_olla) {
        global $conn;
        
        try {
            self::crearTablasSiNoExisten();
            
            $sql_check = "SELECT id_conteo, ciclos_completados 
                          FROM conteo_empaque_olla 
                          WHERE id_olla = $1 AND id_temporizador = $2";
            
            $result_check = pg_query_params($conn, $sql_check, array($id_olla, $id_temporizador));
            
            $fecha_actual = date('Y-m-d H:i:s');
            
            if ($result_check && pg_num_rows($result_check) > 0) {
                $row = pg_fetch_assoc($result_check);
                $nuevos_ciclos = $row['ciclos_completados'] + 1;
                
                $sql_update = "UPDATE conteo_empaque_olla 
                              SET ciclos_completados = $1,
                                  fecha_ultimo_ciclo = $2
                              WHERE id_conteo = $3";
                
                $result = pg_query_params($conn, $sql_update, 
                    array($nuevos_ciclos, $fecha_actual, $row['id_conteo']));
                
                if ($result) {
                    return [
                        'success' => true, 
                        'message' => 'Ciclo incrementado',
                        'ciclos_actuales' => $nuevos_ciclos
                    ];
                }
                
            } else {
                $sql_insert = "INSERT INTO conteo_empaque_olla 
                              (id_olla, id_temporizador, ciclos_completados, fecha_ultimo_ciclo)
                              VALUES ($1, $2, 1, $3)";
                
                $result = pg_query_params($conn, $sql_insert, 
                    array($id_olla, $id_temporizador, $fecha_actual));
                
                if ($result) {
                    return [
                        'success' => true, 
                        'message' => 'Nuevo ciclo registrado',
                        'ciclos_actuales' => 1
                    ];
                }
            }
            
            return [
                'success' => false, 
                'message' => 'Error al registrar ciclo: ' . pg_last_error($conn)
            ];
            
        } catch (Exception $e) {
            error_log("Error en registrarCicloEmpaque: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }
    
    public static function obtenerInfoConteoEmpaque($id_olla) {
        global $conn;
        
        $sql = "SELECT 
                    ceo.id_olla,
                    ceo.id_temporizador,
                    ceo.ciclos_completados,
                    ceo.fecha_ultimo_ciclo,
                    COALESCE(cl.limite_ciclos, 100) as limite_ciclos,
                    o.numero_olla
                FROM conteo_empaque_olla ceo
                INNER JOIN tolla o ON o.id_olla = ceo.id_olla
                LEFT JOIN config_limite_empaque cl ON cl.id_olla = ceo.id_olla
                WHERE ceo.id_olla = $1 
                ORDER BY ceo.fecha_ultimo_ciclo DESC
                LIMIT 1";
        
        $result = pg_query_params($conn, $sql, array($id_olla));
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            
            $row['porcentaje'] = round(($row['ciclos_completados'] * 100.0) / 
                max($row['limite_ciclos'], 1), 2);
            $row['ciclos_restantes'] = max($row['limite_ciclos'] - $row['ciclos_completados'], 0);
            
            return $row;
        }
        
        $sql_olla = "SELECT numero_olla FROM tolla WHERE id_olla = $1";
        $result_olla = pg_query_params($conn, $sql_olla, array($id_olla));
        $numero_olla = 'N/A';
        if ($result_olla && pg_num_rows($result_olla) > 0) {
            $row_olla = pg_fetch_assoc($result_olla);
            $numero_olla = $row_olla['numero_olla'];
        }
        
        return [
            'id_olla' => $id_olla,
            'numero_olla' => $numero_olla,
            'ciclos_completados' => 0,
            'limite_ciclos' => 100,
            'porcentaje' => 0,
            'ciclos_restantes' => 100,
            'fecha_ultimo_ciclo' => null
        ];
    }
    
    public static function crearTablasSiNoExisten() {
        global $conn;
        
        $sqlCheck = "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'conteo_empaque_olla')";
        $result = pg_query($conn, $sqlCheck);
        $row = pg_fetch_assoc($result);
        
        if (!$row['exists']) {
            $sqlCreate = "CREATE TABLE conteo_empaque_olla (
                id_conteo SERIAL PRIMARY KEY,
                id_olla INT NOT NULL,
                id_temporizador INT NOT NULL,
                ciclos_completados INT DEFAULT 0,
                fecha_ultimo_ciclo TIMESTAMP DEFAULT NOW(),
                FOREIGN KEY (id_olla) REFERENCES tolla(id_olla) ON DELETE CASCADE,
                FOREIGN KEY (id_temporizador) REFERENCES ttemporizador_olla(id_temporizador) ON DELETE CASCADE
            )";
            pg_query($conn, $sqlCreate);
            
            $sqlIndex = "CREATE INDEX idx_conteo_olla_temp ON conteo_empaque_olla(id_olla, id_temporizador)";
            pg_query($conn, $sqlIndex);
        }
        
        $sqlCheck2 = "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'config_limite_empaque')";
        $result2 = pg_query($conn, $sqlCheck2);
        $row2 = pg_fetch_assoc($result2);
        
        if (!$row2['exists']) {
            $sqlCreate2 = "CREATE TABLE config_limite_empaque (
                id_config SERIAL PRIMARY KEY,
                id_olla INT NOT NULL UNIQUE,
                limite_ciclos INT DEFAULT 100,
                fecha_config TIMESTAMP DEFAULT NOW(),
                FOREIGN KEY (id_olla) REFERENCES tolla(id_olla) ON DELETE CASCADE
            )";
            pg_query($conn, $sqlCreate2);
        }
    }
}

// ============================================
// MANEJAR REQUESTS AJAX
// ============================================

if (isset($_GET['action'])) {
    
    switch ($_GET['action']) {
        case 'get_ollas_con_estado':
            $ollas = TemporizadorOlla::obtenerOllasConEstado();
            echo json_encode($ollas);
            break;
            
        case 'registrar_ciclo':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id_temporizador = $_POST['id_temporizador'] ?? null;
                $id_olla = $_POST['id_olla'] ?? null;
                
                if ($id_temporizador && $id_olla) {
                    $resultado = TemporizadorOlla::registrarCicloEmpaque($id_temporizador, $id_olla);
                    echo json_encode($resultado);
                } else {
                    if ($id_temporizador) {
                        $sql = "SELECT id_olla FROM ttemporizador_olla WHERE id_temporizador = $1";
                        $result = pg_query_params($GLOBALS['conn'], $sql, array($id_temporizador));
                        
                        if ($result && pg_num_rows($result) > 0) {
                            $row = pg_fetch_assoc($result);
                            $id_olla = $row['id_olla'];
                            
                            $resultado = TemporizadorOlla::registrarCicloEmpaque($id_temporizador, $id_olla);
                            echo json_encode($resultado);
                        } else {
                            echo json_encode([
                                'success' => false, 
                                'message' => 'Temporizador no encontrado'
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'ID de temporizador no proporcionado'
                        ]);
                    }
                }
            }
            break;
            
        case 'obtener_info_conteo':
            $id_olla = $_GET['id_olla'] ?? null;
            
            if ($id_olla) {
                $info = TemporizadorOlla::obtenerInfoConteoEmpaque($id_olla);
                echo json_encode($info);
            } else {
                echo json_encode(['error' => 'ID de olla no proporcionado']);
            }
            break;
            
        case 'test_conexion':
            echo json_encode([
                'status' => 'ok',
                'message' => 'Conexión establecida',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
    }
    exit;
}