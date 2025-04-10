<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config.php';

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
    <style>
        .boton-volver {
            display: block;
            margin: 30px auto 0 auto;
            text-align: center;
            width: 200px;
        }
        .contenedor {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
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
    <div class="text-center mt-4">
        <a href="../index.php" class="boton-agregar boton-volver">Volver al Inicio</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>