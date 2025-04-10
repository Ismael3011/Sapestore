<?php
session_start();
include_once 'config.php';
// EN el caso de ser admin en el carrito apareceran los pedudos que hayan hecho los clientes
if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin') {
    $sqlOrders = "SELECT p.ID AS pedido_id, p.fecha, p.total, p.direccion, 
                         u.nombre AS cliente_nombre, u.mail AS cliente_email, 
                         dp.cantidad, t.numero AS talla, t.precio, 
                         pr.nombre AS producto_nombre
                  FROM Pedidos p
                  INNER JOIN Usuario u ON p.usuario_id = u.ID
                  INNER JOIN Detalles_pedido dp ON p.ID = dp.pedido_id
                  INNER JOIN Producto_Talla pt ON dp.producto_talla_id = pt.ID
                  INNER JOIN Producto pr ON pt.producto_id = pr.ID
                  INNER JOIN Talla t ON pt.talla_id = t.ID
                  ORDER BY p.fecha DESC";
    $resultOrders = $conn->query($sqlOrders);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pedidos de Clientes</title>
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    </head>
    <body>
        <?php include 'partes/navbar.php'; ?>

        <div class="container py-5">
            <h1 class="text-center mb-4">Pedidos de Clientes</h1>
            <?php if ($resultOrders->num_rows > 0): ?>
                <?php 
                $orders = [];
                while ($row = $resultOrders->fetch_assoc()) {
                    $orders[$row['pedido_id']]['fecha'] = $row['fecha'];
                    $orders[$row['pedido_id']]['total'] = $row['total'];
                    $orders[$row['pedido_id']]['direccion'] = $row['direccion'];
                    $orders[$row['pedido_id']]['cliente'] = [
                        'nombre' => $row['cliente_nombre'],
                        'email' => $row['cliente_email']
                    ];
                    $orders[$row['pedido_id']]['productos'][] = [
                        'nombre' => $row['producto_nombre'],
                        'talla' => $row['talla'],
                        'cantidad' => $row['cantidad'],
                        'precio' => $row['precio']
                    ];
                }
                ?>
                <?php foreach ($orders as $pedidoId => $pedido): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Pedido #<?php echo $pedidoId; ?></strong> - Fecha: <?php echo $pedido['fecha']; ?>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente']['nombre']); ?> (<?php echo htmlspecialchars($pedido['cliente']['email']); ?>)</p>
                            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?></p>
                            <ul class="list-group">
                                <?php foreach ($pedido['productos'] as $producto): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                        <br>Talla: <?php echo htmlspecialchars($producto['talla']); ?>
                                        <br>Cantidad: <?php echo $producto['cantidad']; ?>
                                        <br>Precio: $<?php echo number_format($producto['precio'], 2); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="text-right mt-3">
                                <strong>Total: $<?php echo number_format($pedido['total'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No hay pedidos realizados por los clientes.</p>
            <?php endif; ?>
        </div>

        <?php include 'partes/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
// Comprobar que el usuario esta logeado.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talla_id'])) {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para añadir productos al carrito.']);
        exit;
    }

    $tallaId = (int)$_POST['talla_id'];
    // Comprobar que la talla existe en la base de datos.
    if (isset($_SESSION['cart'][$tallaId])) {
        $_SESSION['cart'][$tallaId]['quantity']++;
    } else {
        $sql = "SELECT p.ID AS producto_id, p.nombre AS producto_nombre, p.imagen_url, t.numero AS talla, t.precio, pt.ID AS producto_talla_id
                FROM Producto p
                INNER JOIN Producto_Talla pt ON p.ID = pt.producto_id
                INNER JOIN Talla t ON pt.talla_id = t.ID
                WHERE t.ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tallaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            $_SESSION['cart'][$tallaId] = [
                'producto_id' => $product['producto_id'],
                'producto_nombre' => $product['producto_nombre'],
                'imagen_url' => $product['imagen_url'],
                'talla' => $product['talla'],
                'precio' => $product['precio'],
                'producto_talla_id' => $product['producto_talla_id'], 
                'quantity' => 1
            ];
        }
    }

    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
    echo json_encode(['success' => true, 'cartCount' => $cartCount]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['remove'])) {
    $tallaId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$tallaId]);
    header("Location: carrito.php");
    exit;
}

$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['precio'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .cart-img-container {
            width: 100%;
            height: 350px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background-color:  #f1efee ;
        }
        .cart-img-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="text-center mb-4">Carrito de Compras</h1>
        <?php if (!empty($_SESSION['cart'])): ?>
            <div class="row">
                <?php foreach ($_SESSION['cart'] as $tallaId => $item): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="cart-img-container">
                                <img src="<?php echo $item['imagen_url']; ?>" alt="<?php echo htmlspecialchars($item['producto_nombre']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['producto_nombre']); ?></h5>
                                <p class="card-text">Talla: <?php echo htmlspecialchars($item['talla']); ?></p>
                                <p class="card-text">Precio: $<?php echo number_format($item['precio'], 2); ?></p>
                                <p class="card-text">Cantidad: <?php echo $item['quantity']; ?></p>
                                <a href="carrito.php?remove=<?php echo $tallaId; ?>" class="btn btn-danger">Eliminar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <h3>Total: $<?php echo number_format($totalPrice, 2); ?></h3>
                <a href="checkout.php" class="btn btn-success">Proceder al Pago</a>
            </div>
        <?php else: ?>
            <p class="text-center">Tu carrito está vacío.</p>
        <?php endif; ?>
    </div>

    <?php include 'partes/footer.php'; ?>
</body>
</html>
