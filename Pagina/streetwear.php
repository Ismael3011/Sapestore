<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Streetwear</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
      #filterForm {
          display: none;
          opacity: 0;
          transition: opacity 0.3s ease-in-out;
      }

      #filterForm.show {
          display: flex;
          opacity: 1;
      }

      .filter-container {
          display: flex;
          align-items: center;
          gap: 10px;
      }
    </style>
  </head>
  <body>
    <?php include 'partes/navbar.php'; ?>
    <?php
    include_once 'config.php';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;

    $sqlProductos = "SELECT SQL_CALC_FOUND_ROWS p.ID, p.nombre AS producto_nombre, p.imagen_url, 
                                m.nombre AS marca_nombre, 
                                (SELECT MIN(t.precio) FROM Talla t 
                                 INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id 
                                 WHERE pt.producto_id = p.ID AND t.stock > 0) AS precio_minimo
                         FROM Producto p
                         LEFT JOIN Marca m ON p.marca_id = m.ID
                         LEFT JOIN Categoria c ON p.categoria_id = c.ID
                         WHERE c.nombre IS NOT NULL AND c.nombre != 'Zapatillas' AND EXISTS (
                             SELECT 1 FROM Producto_Talla pt
                             INNER JOIN Talla t ON pt.talla_id = t.ID
                             WHERE pt.producto_id = p.ID AND t.stock > 0
                         )";

    $params = [];
    $types = "";

    // Apply filters
    if (!empty($_GET['marca'])) {
        $sqlProductos .= " AND p.marca_id = ?";
        $params[] = $_GET['marca'];
        $types .= "i";
    }

    if (!empty($_GET['categoria'])) {
        $sqlProductos .= " AND p.categoria_id = ?";
        $params[] = $_GET['categoria'];
        $types .= "i";
    }

    if (!empty($_GET['precio'])) {
        $sqlProductos .= " ORDER BY precio_minimo " . ($_GET['precio'] === 'asc' ? "ASC" : "DESC");
    } else {
        $sqlProductos .= " ORDER BY p.ID ASC"; // Default order
    }

    $sqlProductos .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmtProductos = $conn->prepare($sqlProductos);
    $stmtProductos->bind_param($types, ...$params);
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
    <div class="container py-5 text-center">
      <h1 class="section-title"><span class="section-title-underline">Streetwear</span></h1>
      <p>Explora nuestra colección de productos de Streetwear para combinar tus Sneakers de la mejor forma.</p>
    </div>
    <div class="container py-5">
      <div class="filter-container mb-4">
        <button type="button" class="btn btn-light" id="toggleFilterButton">
          <img src="fotos/filtro.webp" alt="Filtrar" style="width: 40px; height: 40px;">
        </button>
        <form method="GET" class="form-inline" id="filterForm">
          <div class="form-group mx-2">
            <label for="precio" class="mr-2">Precio:</label>
            <select name="precio" id="precio" class="form-control">
              <option value="">Todos</option>
              <option value="asc" <?php echo (isset($_GET['precio']) && $_GET['precio'] === 'asc') ? 'selected' : ''; ?>>Menor a Mayor</option>
              <option value="desc" <?php echo (isset($_GET['precio']) && $_GET['precio'] === 'desc') ? 'selected' : ''; ?>>Mayor a Menor</option>
            </select>
          </div>
          <div class="form-group mx-2">
            <label for="marca" class="mr-2">Marca:</label>
            <select name="marca" id="marca" class="form-control">
              <option value="">Todas</option>
              <?php
              $sqlMarcas = "SELECT DISTINCT m.ID, m.nombre 
                            FROM Marca m
                            INNER JOIN Producto p ON m.ID = p.marca_id
                            INNER JOIN Categoria c ON p.categoria_id = c.ID
                            WHERE c.nombre != 'Zapatillas'";
              $resultMarcas = $conn->query($sqlMarcas);
              while ($marca = $resultMarcas->fetch_assoc()): ?>
                <option value="<?php echo $marca['ID']; ?>" <?php echo (isset($_GET['marca']) && $_GET['marca'] == $marca['ID']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($marca['nombre']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group mx-2">
            <label for="categoria" class="mr-2">Categoría:</label>
            <select name="categoria" id="categoria" class="form-control">
              <option value="">Todas</option>
              <?php
              $sqlCategorias = "SELECT DISTINCT c.ID, c.nombre FROM Categoria c 
                                INNER JOIN Producto p ON c.ID = p.categoria_id
                                WHERE c.nombre != 'Zapatillas'"; 
              $resultCategorias = $conn->query($sqlCategorias);
              while ($categoria = $resultCategorias->fetch_assoc()): ?>
                <option value="<?php echo $categoria['ID']; ?>" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['ID']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($categoria['nombre']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary mx-2">Filtrar</button>
        </form>
      </div>
      <div class="row">
        <?php if ($resultProductos->num_rows > 0): ?>
          <?php while ($producto = $resultProductos->fetch_assoc()): ?>
            <div class="col-md-3 mb-4">
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
          <p class="text-center">No hay productos disponibles en la categoría de Streetwear.</p>
        <?php endif; ?>
      </div>
      <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Siguiente">
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
    <script>
      document.getElementById('toggleFilterButton').addEventListener('click', function () {
        const filterForm = document.getElementById('filterForm');
        if (filterForm.classList.contains('show')) {
            filterForm.classList.remove('show');
            setTimeout(() => filterForm.style.display = 'none', 300); 
        } else {
            filterForm.style.display = 'flex';
            setTimeout(() => filterForm.classList.add('show'), 10); 
        }
      });
    </script>
  </body>
</html>