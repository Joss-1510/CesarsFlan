<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['SISTEMA'])) {
    die("No autorizado");
}

require_once 'empaquetadof.php';
require_once 'conexion.php';

if (!isset($_GET['id_producto_final']) || !is_numeric($_GET['id_producto_final'])) {
    die("ID inválido");
}

$id_producto_final = $_GET['id_producto_final'];

try {
    $empaquetadoF = new EmpaquetadoF($conn);
    
    $receta = $empaquetadoF->obtenerRecetaEmpaquetado($id_producto_final);
    
    if (empty($receta)) {
        echo '<div class="alert alert-warning">Este producto final no tiene receta de empaquetado configurada</div>';
    } else {
        echo '<div class="receta-items">';
        echo '<h6>📦 Receta de Empaquetado:</h6>';
        
        $total_productos = 0;
        foreach ($receta as $item) {
            echo '<div class="receta-item">';
            echo '<strong>Por lote:</strong><br>';
            echo '• 🍮 ' . htmlspecialchars($item['nombre_semielaborado']) . ': ' . 
                 $item['cantidad_semielaborados_necesarios'] . ' unidades<br>';
            echo '• 📦 ' . htmlspecialchars($item['nombre_material']) . ': ' . 
                 $item['cantidad_material_necesario'] . ' unidades<br>';
            echo '• 🎁 <strong>Produce: ' . $item['cantidad_productos_resultantes'] . ' productos</strong><br>';
            echo '<small class="text-muted">Stock disponible - ';
            echo 'Semielaborado: ' . $item['stock_semielaborado'] . ' | ';
            echo 'Material: ' . $item['stock_material'];
            echo '</small>';
            echo '</div>';
            
            $total_productos = $item['cantidad_productos_resultantes'];
        }
        
        echo '<div class="alert alert-info mt-2">';
        echo '<strong>📊 Resumen por lote:</strong><br>';
        echo '• Cada lote produce: ' . $total_productos . ' productos finales<br>';
        echo '• Se usarán semielaborados y materiales según la receta';
        echo '</div>';
        
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar receta: ' . $e->getMessage() . '</div>';
}
?>