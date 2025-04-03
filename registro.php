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
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contrasena = $_POST['contrasena'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    if (strlen($contrasena) < 10) {
        $error = "La contraseña debe tener al menos 10 caracteres.";
    } elseif (!preg_match('/^\d{9}$/', $telefono)) {
        $error = "El teléfono debe tener exactamente 9 números.";
    } else {
        $contrasena = password_hash($contrasena, PASSWORD_BCRYPT);

        $sql = "INSERT INTO Usuario (nombre, mail, contrasena, direccion, telefono, rol) VALUES (?, ?, ?, ?, ?, 'cliente')";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Error en la preparación de la consulta: " . $conn->error);
        }

        $stmt->bind_param("sssss", $nombre, $email, $contrasena, $direccion, $telefono);

        if ($stmt->execute()) {
            echo "<script>
                alert('Registro exitoso. Ahora serás redirigido al inicio de sesión.');
                window.location.href = 'login.php';
            </script>";
            exit;
        } else {
            $error = "Error al registrar el usuario: " . $conn->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="styleindex.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="contenedor">
        <h1>Registro</h1>
        <?php if (isset($error)): ?>
            <div class="alerta error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="formulario-centrado" id="registroForm">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>
            <label for="direccion">Dirección:</label>
            <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($direccion ?? ''); ?>" required>
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono ?? ''); ?>" required>
            <button type="submit" class="boton-agregar">Registrarse</button>
            <p class="text-center mt-3">
            ¿Ya tienes cuenta? <a href="login.php" class="link-registrate">Inicia Sesión</a>
        </p>
        </form>
    </div>
</body>
</html>
