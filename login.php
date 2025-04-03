<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Sapestore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['contrasena'])) {
        session_start();
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styleindex.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="contenedor">
        <h1>Iniciar Sesión</h1>
        <?php if (isset($error)): ?>
            <div class="alerta error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="formulario-centrado">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit" class="boton-agregar">Iniciar Sesión</button>
            <p class="text-center mt-3">
                ¿No tienes cuenta? <a href="registro.php" class="link-registrate">Regístrate</a>
            </p>
        </form>
    </div>
</body>
</html>
