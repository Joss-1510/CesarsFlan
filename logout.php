<?php
session_start();

if (isset($_SESSION['SISTEMA'])) {
    include 'conexion.php';
    
    $id_usuario = $_SESSION['SISTEMA']['id_usuario'];
    $nombre_usuario = $_SESSION['SISTEMA']['nombre'];
    
    $descripcion = "Cierre de sesión - Usuario: " . $nombre_usuario;
    $log_query = "INSERT INTO tlog (fecha, accion, descripcion, id_usuario) VALUES (NOW(), 'LOGOUT', $1, $2)";
    
    if ($conn) {
        pg_prepare($conn, "logout_log_query", $log_query);
        pg_execute($conn, "logout_log_query", array($descripcion, $id_usuario));
    }
}

session_destroy();

header("Location: login.php?error=logout");
exit();
?>