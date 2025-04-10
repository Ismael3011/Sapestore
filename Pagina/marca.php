<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Marca</title>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
    @media (max-width: 768px) {
  .product-card {
    margin-bottom: 20px;
  }

  .card-img-container {
    height: 150px;
    overflow: hidden;
  }

  .card-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

    </style>
  </head>

  <body>
    <?php include 'partes/navbar.php'; ?>

    <?php
    include_once 'config.php';

    $marcaId = $_GET['id'];
    $sqlMarca = "SELECT nombre, descripcion FROM Marca WHERE ID = ?";
    $stmt = $conn->prepare($sqlMarca);
    $stmt->bind_param("i", $marcaId);
    $stmt->execute();
    $resultMarca = $stmt->get_result();
    $marca = $resultMarca->fetch_assoc();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;

    $sqlProductos = "SELECT SQL_CALC_FOUND_ROWS p.ID, p.nombre AS producto_nombre, p.imagen_url, 
                                m.nombre AS marca_nombre, 
                                (SELECT MIN(t.precio) FROM Talla t 
                                 INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id 
                                 WHERE pt.producto_id = p.ID) AS precio_minimo
                         FROM Producto p
                         LEFT JOIN Marca m ON p.marca_id = m.ID
                         WHERE p.marca_id = ?
                         LIMIT ? OFFSET ?";
    $stmtProductos = $conn->prepare($sqlProductos);
    $stmtProductos->bind_param("iii", $marcaId, $limit, $offset);
    $stmtProductos->execute();
    $resultProductos = $stmtProductos->get_result();

    $resultTotal = $conn->query("SELECT FOUND_ROWS() AS total");
    $totalProductos = $resultTotal->fetch_assoc()['total'];
    $totalPages = ceil($totalProductos / $limit);

    $popularFile = 'admin/popular_products.json'; 
    $popularProducts = [];

    if (file_exists($popularFile)) {
        $popularProducts = json_decode(file_get_contents($popularFile), true) ?? [];
    }

    $fastDeliveryFile = 'admin/fast_delivery_products.json'; 
    $fastDeliveryProducts = [];

    if (file_exists($fastDeliveryFile)) {
        $fastDeliveryProducts = json_decode(file_get_contents($fastDeliveryFile), true) ?? [];
    }
    ?>

    <?php if ($marca): ?>
      <div class="container py-5 text-center">
        <h1 class="section-title"><span class="section-title-underline"><?php echo $marca['nombre']; ?></span></h1>
        <p><?php echo $marca['descripcion']; ?></p>
      </div>
    <?php endif; ?>

    <div class="container py-5">
      <div class="row">
        <?php if ($resultProductos->num_rows > 0): ?>
          <?php while ($producto = $resultProductos->fetch_assoc()): ?>
            <div class="col-6 col-md-3 mb-4">
              <a href="producto.php?id=<?php echo $producto['ID']; ?>" class="product-card-link">
                <div class="product-card">
                  <?php if (in_array($producto['ID'], $popularProducts)): ?>
                    <img src="fotos/fuego.gif" alt="Popular" class="popular-icon">
                  <?php endif; ?>
                  <?php if (in_array($producto['ID'], $fastDeliveryProducts)): ?>
                    <img src="fotos/cohete.webp" alt="Entrega Rápida" class="fast-delivery-icon">
                  <?php endif; ?>
                  <div class="card-img-container">
                    <img src="<?php echo $producto['imagen_url']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>">
                  </div>
                  <div class="card-body">
                    <p class="marca-nombre"><?php echo htmlspecialchars($producto['marca_nombre'] ?: 'Sin marca'); ?></p>
                    <h5 class="card-title"><?php echo htmlspecialchars($producto['producto_nombre']); ?></h5>
                    <p class="precio-minimo">Desde: <?php echo $producto['precio_minimo'] ? '$' . number_format($producto['precio_minimo'], 2) : 'No disponible'; ?></p>
                  </div>
                </div>
              </a>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-center">No hay productos disponibles para esta marca.</p>
        <?php endif; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="?id=<?php echo $marcaId; ?>&page=<?php echo $page - 1; ?>" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?id=<?php echo $marcaId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
              <a class="page-link" href="?id=<?php echo $marcaId; ?>&page=<?php echo $page + 1; ?>" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>

    <?php include 'partes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  </body>
</html>