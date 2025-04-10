<?php
session_start(); // Inicia la sesión al principio del archivo

include_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['user']) && isset($_POST['logout'])) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    if (!isset($_SESSION['user'])) {
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

    if (isset($_SESSION['user'])) {
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
    }
}
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

            <h2 class="mt-5 text-center">Mis Pedidos</h2>
            <?php
            // Pedidos que haya realizado el usuario
            $userId = $_SESSION['user']['ID'];
            $sqlOrders = "SELECT p.ID AS pedido_id, p.fecha, p.total, p.direccion, 
                                 dp.cantidad, t.numero AS talla, t.precio, 
                                 pr.nombre AS producto_nombre
                          FROM Pedidos p
                          INNER JOIN Detalles_pedido dp ON p.ID = dp.pedido_id
                          INNER JOIN Producto_Talla pt ON dp.producto_talla_id = pt.ID
                          INNER JOIN Producto pr ON pt.producto_id = pr.ID
                          INNER JOIN Talla t ON pt.talla_id = t.ID
                          WHERE p.usuario_id = ?
                          ORDER BY p.fecha DESC";
            $stmtOrders = $conn->prepare($sqlOrders);
            $stmtOrders->bind_param("i", $userId);
            $stmtOrders->execute();
            $resultOrders = $stmtOrders->get_result();

            $orders = [];
            while ($row = $resultOrders->fetch_assoc()) {
                $orders[$row['pedido_id']]['fecha'] = $row['fecha'];
                $orders[$row['pedido_id']]['total'] = $row['total'];
                $orders[$row['pedido_id']]['direccion'] = $row['direccion'];
                $orders[$row['pedido_id']]['productos'][] = [
                    'nombre' => $row['producto_nombre'],
                    'talla' => $row['talla'],
                    'cantidad' => $row['cantidad'],
                    'precio' => $row['precio']
                ];
            }

            if (!empty($orders)): ?>
                <div class="container py-4">
                    <?php foreach ($orders as $pedidoId => $pedido): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <span>Pedido #<?php echo $pedidoId; ?></span>
                                <span>Fecha: <?php echo $pedido['fecha']; ?></span>
                            </div>
                            <div class="order-card-body">
                                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?></p>
                                <h5>Productos:</h5>
                                <ul class="list-group mb-3">
                                    <?php foreach ($pedido['productos'] as $producto): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                            <br>Talla: <?php echo htmlspecialchars($producto['talla']); ?>
                                            <br>Cantidad: <?php echo $producto['cantidad']; ?>
                                            <br>Precio: $<?php echo number_format($producto['precio'], 2); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="order-card-footer">
                                Total: $<?php echo number_format($pedido['total'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No tienes pedidos realizados.</p>
            <?php endif; ?>
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

    <?php $conn->close(); ?>
</body>
</html>
