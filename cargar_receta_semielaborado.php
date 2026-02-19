<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    die("No autorizado");
}

require_once 'produccionf.php';
require_once 'conexion.php';

if (!isset($_GET['id_semielaborado']) || !is_numeric($_GET['id_semielaborado'])) {
    die("ID inválido");
}

$id_semielaborado = $_GET['id_semielaborado'];

try {
    $produccionF = new ProduccionF($conn);
    
    $receta = $produccionF->obtenerRecetaIngredientes($id_semielaborado);
    $titulo = "🧂 Ingredientes para Elaboración:";
    $tipo_vacio = "ingredientes";
    
    if (empty($receta)) {
        echo '<div class="alert alert-warning">Este semielaborado no tiene ' . $tipo_vacio . ' configurados en la receta</div>';
    } else {
        echo '<div class="receta-items">';
        echo '<h6>' . $titulo . '</h6>';
        foreach ($receta as $item) {
            echo '<div class="receta-item">';
            echo '<strong>' . htmlspecialchars($item['nombre']) . '</strong>: ';
            echo $item['cantidad_requerida'] . ' ' . $item['unidad_medida'] . ' por unidad';
            echo '<br><small class="text-muted">Stock disponible: ' . $item['cantidad_stock'] . ' ' . $item['unidad_medida'] . '</small>';
            echo '</div>';
        }
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar receta: ' . $e->getMessage() . '</div>';
}
?>