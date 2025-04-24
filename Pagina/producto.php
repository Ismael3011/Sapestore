<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producto</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="styleindex.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
    .main-image {
        width: 100%;
        height: 500px;
        object-fit: contain;
        transition: opacity 0.3s ease;
    }

    .additional-images {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        justify-content: center;
    }

    .additional-images img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid #ddd;
        border-radius: 5px;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .additional-images img:hover {
        transform: scale(1.1);
        border-color: #cb432d;
    }

    .product-details {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .row {
            flex-direction: column; 
        }

        .col-md-2 {
            order: 1; 
        }

        .col-md-5 {
            order: 2;
        }

        .additional-images {
            flex-direction: row; 
            gap: 15px;
            align-items: flex-start;
        }

        .additional-images img {
            width: 50px; 
            height: 50px;
        }

        .product-details {
            margin-top: 20px; 
        }

        .main-image {
            margin-bottom: 20px; 
        }

        .related-products {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
    }

    @media (min-width: 769px) {
        .row {
            align-items: center; 
        }

        .additional-images {
            justify-content: center; 
        }

        .related-products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
    }
    .product-card {
  opacity: 0;
  transform: translateX(-50px);
  transition: all 0.5s ease-in-out;
}

.product-card.visible {
  opacity: 1;
  transform: translateX(0);
}
</style>

    <script>
        let currentMainImage = null;

        function setMainImage(mainImage, newImageSrc) {
            mainImage.src = newImageSrc;
            currentMainImage = newImageSrc;
        }

        function initializeMainImage(mainImage, originalSrc) {
            if (!currentMainImage) {
                mainImage.src = originalSrc;
            }
        }
    </script>
</head>
<body>
    <?php include 'partes/navbar.php'; ?>

    <?php
    include_once 'config.php';

    $productoId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Obtener detalles del producto
    $sqlProducto = "SELECT p.ID, p.nombre AS producto_nombre, p.descripcion, p.imagen_url, 
                           m.nombre AS marca_nombre, m.ID AS marca_id
                    FROM Producto p
                    LEFT JOIN Marca m ON p.marca_id = m.ID
                    WHERE p.ID = ?";
    $stmtProducto = $conn->prepare($sqlProducto);
    $stmtProducto->bind_param("i", $productoId);
    $stmtProducto->execute();
    $resultProducto = $stmtProducto->get_result();
    $producto = $resultProducto->fetch_assoc();

    // Obtener imágenes adicionales del producto
    $sqlImagenes = "SELECT imagen_url FROM Producto_Imagen WHERE producto_id = ?";
    $stmtImagenes = $conn->prepare($sqlImagenes);
    $stmtImagenes->bind_param("i", $productoId);
    $stmtImagenes->execute();
    $resultImagenes = $stmtImagenes->get_result();

    // Obtener tallas del producto
    $sqlTallas = "SELECT t.ID, t.numero, t.precio
                  FROM Talla t
                  INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id
                  WHERE pt.producto_id = ?";
    $stmtTallas = $conn->prepare($sqlTallas);
    $stmtTallas->bind_param("i", $productoId);
    $stmtTallas->execute();
    $resultTallas = $stmtTallas->get_result();

    // Obtener productos relacionados (aleatorios de la misma marca)
    $sqlRelacionados = "SELECT p.ID, p.nombre AS producto_nombre, p.imagen_url, 
                               (SELECT MIN(t.precio) FROM Talla t 
                                INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id 
                                WHERE pt.producto_id = p.ID) AS precio_minimo
                        FROM Producto p
                        WHERE p.marca_id = ? AND p.ID != ?
                        ORDER BY RAND() 
                        LIMIT 4"; // Select 4 random related products
    $stmtRelacionados = $conn->prepare($sqlRelacionados);
    $stmtRelacionados->bind_param("ii", $producto['marca_id'], $productoId);
    $stmtRelacionados->execute();
    $resultRelacionados = $stmtRelacionados->get_result();
    ?>

    <div class="container py-5">
        <?php if ($producto): ?>
            <div class="row">
                <div class="col-md-2">
                    <div class="additional-images">
                        <img src="<?php echo $producto['imagen_url']; ?>" alt="Imagen principal"
                             onmouseover="setMainImage(document.getElementById('mainImage'), '<?php echo $producto['imagen_url']; ?>')">
                        <?php while ($imagen = $resultImagenes->fetch_assoc()): ?>
                            <img src="<?php echo $imagen['imagen_url']; ?>" alt="Imagen adicional"
                                 onmouseover="setMainImage(document.getElementById('mainImage'), '<?php echo $imagen['imagen_url']; ?>')">
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <img id="mainImage" src="<?php echo $producto['imagen_url']; ?>" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" class="img-fluid main-image mb-3">
                </div>
                <div class="col-md-5 product-details">
                    <h1><?php echo htmlspecialchars($producto['producto_nombre']); ?></h1>
                    <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                    <h4>Tallas disponibles:</h4>
                    <form id="addToCartForm">
                        <input type="hidden" name="producto_id" value="<?php echo $producto['ID']; ?>">
                        <div class="form-group">
                            <select name="talla_id" id="talla_id" class="form-control" required>
                                <option value="">Seleccione una talla</option>
                                <?php while ($talla = $resultTallas->fetch_assoc()): ?>
                                    <option value="<?php echo $talla['ID']; ?>">
                                        Talla <?php echo $talla['numero']; ?> - $<?php echo number_format($talla['precio'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="button" id="addToCartButton" class="btn btn-danger">Añadir al Carrito</button>
                    </form>
                    <div id="cartMessage" class="mt-3" style="display: none;"></div>
                </div>
                <script>
                    document.getElementById('addToCartButton').addEventListener('click', function () {
                        const tallaId = document.getElementById('talla_id').value;
                        if (!tallaId) {
                            alert('Por favor, seleccione una talla.');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('talla_id', tallaId);

                        fetch('carrito.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const cartMessage = document.getElementById('cartMessage');
                                cartMessage.textContent = 'Producto añadido al carrito.';
                                cartMessage.style.display = 'block';
                                cartMessage.style.color = 'green';

                                // Update cart count in the navbar dynamically
                                const cartBadge = document.getElementById('cartCountBadge');
                                if (cartBadge) {
                                    cartBadge.textContent = data.cartCount;
                                    cartBadge.style.display = 'inline-block';
                                }
                            } else {
                                if (data.message === 'Debes iniciar sesión para añadir productos al carrito.') {
                                    alert(data.message);
                                    window.location.href = 'login.php'; // Redirect to login page
                                } else {
                                    alert('Error al añadir el producto al carrito.');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al procesar la solicitud.');
                        });
                    });
                </script>
            </div>
        <?php else: ?>
            <p class="text-center">Producto no encontrado.</p>
        <?php endif; ?>
    </div>

    <div class="container py-5">
        <h2 class="section-title text-center mb-4 text-uppercase font-weight-bold">
            <span class="section-title-underline">Productos Relacionados</span>
        </h2>
        <div class="related-products">
            <?php if ($resultRelacionados->num_rows > 0): ?>
                <?php while ($relacionado = $resultRelacionados->fetch_assoc()): ?>
                    <div class="product-card">
                        <a href="producto.php?id=<?php echo $relacionado['ID']; ?>" class="product-card-link">
                            <div class="card-img-container">
                                <img src="<?php echo $relacionado['imagen_url']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($relacionado['producto_nombre']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($relacionado['producto_nombre']); ?></h5>
                                <p class="precio-minimo">Desde: $<?php echo number_format($relacionado['precio_minimo'], 2); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center">No hay productos relacionados disponibles.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'partes/footer.php'; ?>
    <script>
  document.addEventListener('DOMContentLoaded', () => {
    // Selecciona todos los elementos con la clase .product-card dentro de .related-products
    const productCards = document.querySelectorAll('.related-products .product-card');

    // Aplica la clase 'visible' con un retraso escalonado
    productCards.forEach((card, index) => {
      setTimeout(() => {
        card.classList.add('visible');
      }, index * 100); // Retraso de 100ms por cada producto
    });
  });
</script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>