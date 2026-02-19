<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }

    try {
        $query = "SELECT * FROM TUsuario WHERE nombre = $1 AND contra = $2";
        
        $result = pg_prepare($conn, "login_query", $query);
        
        if (!$result) {
            header("Location: login.php?error=db_error");
            exit();
        }
        
        $result = pg_execute($conn, "login_query", array($username, $password));
        
        if (!$result) {
            header("Location: login.php?error=db_error");
            exit();
        }

        if (pg_num_rows($result) > 0) {
            $usuario = pg_fetch_assoc($result);

            // Guardar datos en sesión
            $_SESSION['SISTEMA'] = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['id_rol'],
            ];

            // Registrar en logs
            $descripcion = "Inicio de sesión exitoso - Usuario: " . $usuario['nombre'];
            $log_query = "INSERT INTO tlog (fecha, accion, descripcion, id_usuario) VALUES (NOW(), 'LOGIN', $1, $2)";
            pg_prepare($conn, "log_query", $log_query);
            pg_execute($conn, "log_query", array($descripcion, $usuario['id_usuario']));

            header("Location: inicio.php");
            exit();
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } catch (Exception $e) {
        header("Location: login.php?error=db_error");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>