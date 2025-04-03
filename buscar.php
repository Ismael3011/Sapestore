<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultados de Búsqueda</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
  <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
  <?php include 'partes/navbar.php'; ?>

  <div class="container py-5">
    <h2 class="text-center mb-4">Resultados de Búsqueda</h2>
    <div class="row">
      <?php
      $query = isset($_GET['query']) ? trim($_GET['query']) : '';
      $popularFile = 'admin/popular_products.json'; // Updated path
      $popularProducts = [];

      if (file_exists($popularFile)) {
          $popularProducts = json_decode(file_get_contents($popularFile), true) ?? [];
      }

      $fastDeliveryFile = 'admin/fast_delivery_products.json'; // Added path for fast delivery products
      $fastDeliveryProducts = [];

      if (file_exists($fastDeliveryFile)) {
          $fastDeliveryProducts = json_decode(file_get_contents($fastDeliveryFile), true) ?? [];
      }

      if ($query) {
          $conn = new mysqli("localhost", "root", "", "Sapestore");
          if ($conn->connect_error) {
              die("Conexión fallida: " . $conn->connect_error);
          }

          $sql = "SELECT p.ID, p.nombre AS producto_nombre, p.imagen_url, m.nombre AS marca_nombre, 
                         (SELECT MIN(t.precio) FROM Talla t 
                          INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id 
                          WHERE pt.producto_id = p.ID) AS precio_minimo
                  FROM Producto p
                  LEFT JOIN Marca m ON p.marca_id = m.ID
                  WHERE p.nombre LIKE ? OR m.nombre LIKE ?";
          $stmt = $conn->prepare($sql);
          $searchTerm = "%$query%";
          $stmt->bind_param("ss", $searchTerm, $searchTerm);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()): ?>
              <div class="col-md-3 mb-4">
                <a href="producto.php?id=<?php echo $row['ID']; ?>" class="product-card-link">
                  <div class="product-card">
                    <?php if (in_array($row['ID'], $popularProducts)): ?>
                      <img src="fotos/fuego.gif" alt="Popular" class="popular-icon">
                    <?php endif; ?>
                    <?php if (in_array($row['ID'], $fastDeliveryProducts)): ?>
                      <img src="fotos/cohete.webp" alt="Entrega Rápida" class="fast-delivery-icon">
                    <?php endif; ?>
                    <div class="card-img-container">
                      <img src="<?php echo $row['imagen_url']; ?>" class="card-img-top" alt="<?php echo $row['producto_nombre']; ?>">
                    </div>
                    <div class="card-body">
                      <p class="marca-nombre"><?php echo $row['marca_nombre'] ?: 'Sin marca'; ?></p>
                      <h5 class="card-title"><?php echo $row['producto_nombre']; ?></h5>
                      <p class="precio-minimo">Desde: <?php echo $row['precio_minimo'] ? '$' . number_format($row['precio_minimo'], 2) : 'No disponible'; ?></p>
                    </div>
                  </div>
                </a>
              </div>
            <?php endwhile;
          else: ?>
            <p class="text-center">No se encontraron resultados para "<?php echo htmlspecialchars($query); ?>".</p>
          <?php endif;

          $stmt->close();
          $conn->close();
      } else {
          echo '<p class="text-center">Por favor, ingrese un término de búsqueda.</p>';
      }
      ?>
    </div>
  </div>

  <?php include 'partes/footer.php'; ?>
</body>
</html>