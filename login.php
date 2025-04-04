<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Sapestore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    $sql = "UPDATE Usuario SET nombre = ?, mail = ?, direccion = ?, telefono = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssssi", $nombre, $email, $direccion, $telefono, $_SESSION['user']['ID']);

    if ($stmt->execute()) {
        $_SESSION['user']['nombre'] = $nombre;
        $_SESSION['user']['mail'] = $email;
        $_SESSION['user']['direccion'] = $direccion;
        $_SESSION['user']['telefono'] = $telefono;
        $success = "Datos actualizados correctamente.";
    } else {
        $error = "Error al actualizar los datos: " . $conn->error;
    }

    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Usuario WHERE mail = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['contrasena'])) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta</title>
    <link rel="stylesheet" href="styleindex.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="contenedor">
        <?php if (isset($_SESSION['user'])): ?>
            <h1>Mis Datos</h1>
            <?php if (isset($success)): ?>
                <div class="alerta exito"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alerta error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="formulario-centrado">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_SESSION['user']['nombre']); ?>" required>
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user']['mail']); ?>" required>
                <label for="direccion">Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($_SESSION['user']['direccion']); ?>" required>
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($_SESSION['user']['telefono']); ?>" required>
                <button type="submit" class="boton-agregar">Actualizar Datos</button>
            </form>
            <form method="POST" class="formulario-centrado">
                <button type="submit" name="logout" class="boton-agregar" style="background-color: #dc3545;">Cerrar Sesión</button>
            </form>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</body>
</html>
