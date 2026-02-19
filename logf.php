<?php
include 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'obtenerEstadisticas':
            obtenerEstadisticas();
            break;
            
        case 'obtenerLogs':
            obtenerLogs();
            break;
            
        default:
            echo json_encode(['error' => 'Acción no válida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
}

function obtenerEstadisticas() {
    global $conn;
    
    try {
        if (!$conn) {
            echo json_encode(['error' => 'No hay conexión a la base de datos']);
            return;
        }
        
        $sqlTotal = "SELECT COUNT(*) as total FROM tlog";
        $resultTotal = pg_query($conn, $sqlTotal);
        if (!$resultTotal) {
            echo json_encode(['error' => 'Error en consulta total: ' . pg_last_error($conn)]);
            return;
        }
        $rowTotal = pg_fetch_assoc($resultTotal);
        $totalRegistros = $rowTotal['total'];
        
        $sqlInsert = "SELECT COUNT(*) as inserts FROM tlog WHERE accion = 'INSERT'";
        $resultInsert = pg_query($conn, $sqlInsert);
        if (!$resultInsert) {
            echo json_encode(['error' => 'Error en consulta INSERT: ' . pg_last_error($conn)]);
            return;
        }
        $rowInsert = pg_fetch_assoc($resultInsert);
        $inserts = $rowInsert['inserts'];
        
        $sqlUpdate = "SELECT COUNT(*) as updates FROM tlog WHERE accion = 'UPDATE'";
        $resultUpdate = pg_query($conn, $sqlUpdate);
        if (!$resultUpdate) {
            echo json_encode(['error' => 'Error en consulta UPDATE: ' . pg_last_error($conn)]);
            return;
        }
        $rowUpdate = pg_fetch_assoc($resultUpdate);
        $updates = $rowUpdate['updates'];
        
        $sqlDelete = "SELECT COUNT(*) as deletes FROM tlog WHERE accion = 'DELETE'";
        $resultDelete = pg_query($conn, $sqlDelete);
        if (!$resultDelete) {
            echo json_encode(['error' => 'Error en consulta DELETE: ' . pg_last_error($conn)]);
            return;
        }
        $rowDelete = pg_fetch_assoc($resultDelete);
        $deletes = $rowDelete['deletes'];
        
        echo json_encode([
            'success' => true,
            'totalRegistros' => $totalRegistros,
            'inserts' => $inserts,
            'updates' => $updates,
            'deletes' => $deletes
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error general en estadísticas: ' . $e->getMessage()]);
    }
}

function obtenerLogs() {
    global $conn;
    
    $pagina = $_GET['pagina'] ?? 1;
    $registrosPorPagina = $_GET['registrosPorPagina'] ?? 20;
    $fechaDesde = $_GET['fechaDesde'] ?? '';
    $fechaHasta = $_GET['fechaHasta'] ?? '';
    $accion = $_GET['accion'] ?? '';
    
    $offset = ($pagina - 1) * $registrosPorPagina;
    
    try {
        if (!$conn) {
            echo json_encode(['error' => 'No hay conexión a la base de datos']);
            return;
        }
        
        $sql = "SELECT l.*, 
                       u.nombre as nombre_usuario,
                       u.id_usuario as usuario_id_real
                FROM tlog l 
                LEFT JOIN tusuario u ON l.id_usuario = u.id_usuario 
                WHERE 1=1";
                
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM tlog l 
                     LEFT JOIN tusuario u ON l.id_usuario = u.id_usuario 
                     WHERE 1=1";
                     
        $params = [];
        $paramsCount = [];
        
        if (!empty($fechaDesde)) {
            $sql .= " AND l.fecha >= $1";
            $sqlCount .= " AND l.fecha >= $1";
            $params[] = $fechaDesde . ' 00:00:00';
            $paramsCount[] = $fechaDesde . ' 00:00:00';
        }
        
        if (!empty($fechaHasta)) {
            $sql .= " AND l.fecha <= $" . (count($params) + 1);
            $sqlCount .= " AND l.fecha <= $1";
            $params[] = $fechaHasta . ' 23:59:59';
            if (count($paramsCount) > 0) {
                $sqlCount .= " AND l.fecha <= $2";
                $paramsCount[] = $fechaHasta . ' 23:59:59';
            } else {
                $sqlCount .= " AND l.fecha <= $1";
                $paramsCount[] = $fechaHasta . ' 23:59:59';
            }
        }
        
        if (!empty($accion)) {
            $paramIndex = count($params) + 1;
            $sql .= " AND l.accion = $" . $paramIndex;
            $params[] = $accion;
            
            $paramCountIndex = count($paramsCount) + 1;
            $sqlCount .= " AND l.accion = $" . $paramCountIndex;
            $paramsCount[] = $accion;
        }
        
        $sql .= " ORDER BY l.fecha DESC LIMIT $" . (count($params) + 1) . " OFFSET $" . (count($params) + 2);
        $params[] = (int)$registrosPorPagina;
        $params[] = (int)$offset;
        
        if (!empty($paramsCount)) {
            $resultCount = pg_query_params($conn, $sqlCount, $paramsCount);
        } else {
            $resultCount = pg_query($conn, $sqlCount);
        }
        
        if (!$resultCount) {
            echo json_encode(['error' => 'Error en count: ' . pg_last_error($conn)]);
            return;
        }
        
        $rowCount = pg_fetch_assoc($resultCount);
        $totalRegistros = $rowCount['total'];
        
        if (!empty($params)) {
            $result = pg_query_params($conn, $sql, $params);
        } else {
            $result = pg_query($conn, $sql);
        }
        
        if (!$result) {
            echo json_encode(['error' => 'Error en consulta: ' . pg_last_error($conn)]);
            return;
        }
        
        $registros = [];
        while ($row = pg_fetch_assoc($result)) {
            $registros[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'registros' => $registros,
            'totalRegistros' => $totalRegistros,
            'pagina' => $pagina,
            'totalPaginas' => ceil($totalRegistros / $registrosPorPagina)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error general en obtenerLogs: ' . $e->getMessage()]);
    }
}
?>