<?php
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "Sapestore";

$conn = new mysqli($servidor, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (!is_dir('../fotos')) {
    mkdir('../fotos', 0777, true);
}

// Obtener las tablas disponibles
$tablas = ["Categoria", "Marca", "Categoria_Marca", "Usuario", "Producto", "Talla", "Producto_Talla", "Pedidos", "Detalles_pedido","Producto_Imagen"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Sapestore</title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
</head>
<body>

    <h1>Administrar Sapestore</h1>

    <div class="contenedor">
        <h2>Selecciona una tabla para administrar</h2>
        <ul>
            <?php foreach ($tablas as $tabla): ?>
                <li><a href="manage.php?table=<?php echo $tabla; ?>"><?php echo $tabla; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>

<?php
$conn->close();
?>