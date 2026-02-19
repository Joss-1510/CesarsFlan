<?php
// Generador para tabla TPRODUCTO - Flanes
$tiposFlan = [
    'Napolitano' => 'Delicioso flan napolitano con caramelo',
    'Vainilla' => 'Clásico flan de vainilla con suave textura',
    'Chocolate' => 'Flan de chocolate intenso y cremoso',
    'Coco' => 'Flan de coco tropical con ralladura natural',
    'Café' => 'Flan de café aromático con toques de canela',
    'Queso' => 'Flan de queso cremoso tipo cheesecake',
    'Zanahoria' => 'Flan de zanahoria con nueces y especias',
    'Naranja' => 'Flan cítrico de naranja con ralladura',
    'Frambuesa' => 'Flan de frambuesa con coulis de frutos rojos',
    'Durazno' => 'Flan de durazno con trozos de fruta natural',
    'Limón' => 'Flan refrescante de limón con merengue',
    'Almendra' => 'Flan de almendra tostada con textura suave',
    'Avellana' => 'Flan de avellana con chocolate blanco',
    'Moka' => 'Flan de moka con café y chocolate',
    'Tres Leches' => 'Flan estilo tres leches esponjoso',
    'Calabaza' => 'Flan de calabaza con especias de otoño',
    'Plátano' => 'Flan de plátano maduro con caramelo',
    'Mango' => 'Flan tropical de mango con coco',
    'Maracuyá' => 'Flan de maracuyá ácido y dulce',
    'Romero' => 'Flan de romero con miel y limón'
];

echo "-- Generando productos - Flanes\n\n";

$totalProductos = 1700; // Puedes cambiar este número

for ($i = 1; $i <= $totalProductos; $i++) {
    $tipoFlan = array_rand($tiposFlan);
    $descripcion = $tiposFlan[$tipoFlan];
    
    $nombre = "Flan " . $i;
    
    // Precio entre 25 y 150 pesos
    $precio = number_format(rand(2500, 15000) / 100, 2, '.', '');
    
    // Stock entre 10 y 100 unidades
    $stock = rand(10, 100);
    
    // Aproximadamente 5% de productos dados de baja
    $baja = (rand(1, 100) <= 5);
    $fechabaja = $baja ? "'" . date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days')) . "'" : 'NULL';
    
    echo "INSERT INTO tproducto (nombre, descripcion, precio, stock, baja, fechabaja) VALUES (" .
         "'" . $nombre . "', " .
         "'" . $descripcion . "', " .
         $precio . ", " .
         $stock . ", " .
         ($baja ? 'true' : 'false') . ", " .
         $fechabaja .
         ");\n";
}

echo "\n✅ " . $totalProductos . " productos (Flanes) generados\n";
echo "🍮 Nombres: Flan 1, Flan 2, Flan 3...\n";
echo "📝 Descripciones de diferentes tipos de flanes\n";
echo "💰 Precios entre $25.00 y $150.00 MXN\n";
echo "📦 Stock entre 10 y 100 unidades\n";
echo "📊 Aproximadamente 5% dados de baja\n";
?>