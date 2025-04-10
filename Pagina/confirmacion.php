<?php
session_start();

if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="container py-5 text-center">
        <h1 class="mb-4">¡Pedido realizado con éxito!</h1>
        <p>Gracias por tu compra. Tu pedido ha sido procesado correctamente.</p>
        <a href="index.php" class="btn btn-primary mt-3">Volver al Inicio</a>
    </div>

    <?php include 'partes/footer.php'; ?>
</body>
</html>
