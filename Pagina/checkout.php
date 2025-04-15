<?php
session_start();
include_once 'config.php';

if (empty($_SESSION['cart'])) {
    header("Location: carrito.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ciudad = $_POST['ciudad'];
    $codigoPostal = $_POST['codigo_postal'];
    $calle = $_POST['calle'];
    $userId = $_SESSION['user']['ID'];

    $direccion = trim("$calle, $codigoPostal, $ciudad");

    if (empty($ciudad) || empty($codigoPostal) || empty($calle)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['precio'] * $item['quantity'];
        }
        // Pendiente de actuaizacion cuando meta una psarela de pagos
        if (isset($_POST['payment_confirmed']) && $_POST['payment_confirmed'] === 'yes') {
            $conn->begin_transaction();
            try {
                $sqlOrder = "INSERT INTO Pedidos (usuario_id, fecha, total, direccion) VALUES (?, NOW(), ?, ?)";
                $stmtOrder = $conn->prepare($sqlOrder);
                if (!$stmtOrder) {
                    throw new Exception("Error en la preparación de la consulta: " . $conn->error);
                }
                $stmtOrder->bind_param("ids", $userId, $total, $direccion);
                $stmtOrder->execute();
                $orderId = $stmtOrder->insert_id;

                foreach ($_SESSION['cart'] as $item) {
                    $sqlOrderDetail = "INSERT INTO Detalles_pedido (pedido_id, producto_talla_id, cantidad) 
                                       VALUES (?, ?, ?)";
                    $stmtOrderDetail = $conn->prepare($sqlOrderDetail);
                    if (!$stmtOrderDetail) {
                        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
                    }
                    $stmtOrderDetail->bind_param(
                        "iii",
                        $orderId,
                        $item['producto_talla_id'], 
                        $item['quantity']
                    );
                    $stmtOrderDetail->execute();

                    $sqlUpdateStock = "UPDATE Talla t
                                       INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id
                                       SET t.stock = t.stock - ?
                                       WHERE pt.ID = ?";
                    $stmtUpdateStock = $conn->prepare($sqlUpdateStock);
                    if (!$stmtUpdateStock) {
                        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
                    }
                    $stmtUpdateStock->bind_param("ii", $item['quantity'], $item['producto_talla_id']);
                    $stmtUpdateStock->execute();
                }

                $conn->commit();
                unset($_SESSION['cart']);
                header("Location: confirmacion.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error al procesar el pedido: " . $e->getMessage();
            }
        } else {
            $error = "Ha habido un problema con el pago. Por favor, inténtelo de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .order-summary {
            max-width: 600px;
            margin: 0 auto;
        }
        .order-summary .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="container py-5">
        <h2 class="text-center mb-4">Resumen del pedido</h2>
        <div class="order-summary">
            <ul class="list-group mb-4">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <li class="list-group-item">
                        <span><?php echo htmlspecialchars($item['producto_nombre']); ?></span>
                        <span>$<?php echo number_format($item['precio'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <h2 class="text-center mb-4">Datos de Envío</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" id="checkoutForm" class="formulario-centrado">
            <input type="hidden" id="paymentConfirmed" name="payment_confirmed" value="no">
            <div class="form-group">
                <label for="ciudad">Ciudad:</label>
                <input type="text" id="ciudad" name="ciudad" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="codigo_postal">Código Postal:</label>
                <input type="text" id="codigo_postal" name="codigo_postal" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="calle">Calle y numero:</label>
                <input type="text" id="calle" name="calle" class="form-control" required>
            </div>
            <button type="button" class="btn btn-success btn-block" onclick="confirmPayment()">Confirmar Pedido</button>
        </form>
    </div>

    <?php include 'partes/footer.php'; ?>

    <script>
        function confirmPayment() {
            if (confirm('¿Se ha realizado el pago correctamente?')) {
                document.getElementById('paymentConfirmed').value = 'yes';
            } else {
                document.getElementById('paymentConfirmed').value = 'no';
            }
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>