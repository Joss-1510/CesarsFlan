<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

require_once 'conexion.php';

if (!isset($conn) || !$conn) {
    header("HTTP/1.1 500 Internal Server Error");
    exit();
}

try {
    $query = "SELECT id_producto, nombre 
              FROM tproducto 
              ORDER BY nombre";
    
    $result = pg_query($conn, $query);
    
    $productos = [];
    while ($row = pg_fetch_assoc($result)) {
        $productos[] = [
            'id_producto' => $row['id_producto'],
            'nombre' => $row['nombre']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($productos);
    
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([]);
}
?>