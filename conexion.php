<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$port = "5433";
$dbname = "CesarsFlan";
$user = "postgres";
$password = "1234";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Error al conectar con PostgreSQL: " . pg_last_error());
}
?>