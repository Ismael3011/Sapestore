<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Navbar móvil</title>
  <style>
    body {
      margin: 0;
    }

    @media (max-width: 768px) {
      .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        margin: 0;
      }

      .menu-icon-container {
        display: flex;
        align-items: center;
        gap: 10px;
        height: 100%;
      }

      .icon {
        height: 24px;
        width: auto;
        display: block;
        object-fit: contain;
        cursor: pointer;
      }

      .logo {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .logo img {
        max-width: 200px;
        height: auto;
      }

      .navbar-icons {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .dropdown-menu {
        display: none;
        flex-direction: column;
        background-color: #fff;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        padding: 10px;
        z-index: 10;
      }

      .dropdown-menu.active {
        display: flex;
      }

      .dropdown-submenu {
        display: flex;
        flex-direction: column;
      }

      .submenu-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
      }

      .submenu {
        display: none;
        flex-direction: column;
        padding-left: 10px;
      }

      .submenu.active {
        display: flex;
      }

      .submenu-toggle::after {
        content: "▼";
        font-size: 12px;
        margin-left: 5px;
      }

      #searchBar {
        display: none;
        padding: 10px;
        background-color: #f5f5f5;
      }

      #searchBar.active {
        display: block;
      }

      #searchBar input {
        width: 70%;
        padding: 5px;
      }

      #searchBar button {
        padding: 5px 10px;
      }

      .badge-circle {
        width: 10px;
        height: 10px;
        background-color: red;
        border-radius: 50%;
        display: inline-block;
      }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="menu-icon-container">
    <img src="fotos/menu.png" alt="Menu" class="icon menu-icon" />
    <img src="fotos/lupa.png" alt="Buscar" class="icon search-icon" onclick="toggleSearchBar()" />

    <div class="dropdown-menu">
      <a href="index.php">Inicio</a>

      <div class="dropdown-submenu">
        <div class="submenu-container">
          <a href="#">Productos</a>
          <span class="submenu-toggle"></span>
        </div>
        <div class="submenu">
          <?php
          $sql = "SELECT nombre FROM Categoria";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  echo '<a href="categoria.php?nombre=' . urlencode($row['nombre']) . '">' . $row['nombre'] . '</a>';
              }
          }
          ?>
        </div>
      </div>

      <div class="dropdown-submenu">
        <div class="submenu-container">
          <a href="#">Marcas</a>
          <span class="submenu-toggle"></span>
        </div>
        <div class="submenu">
          <?php
          $sql = "SELECT ID, nombre FROM Marca";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
              while ($marca = $result->fetch_assoc()) {
                  $sqlCategorias = "SELECT ID, nombre FROM Categoria_Marca WHERE marca_id = " . $marca['ID'];
                  $resultCategorias = $conn->query($sqlCategorias);
                  $hasSubmenu = $resultCategorias->num_rows > 0;

                  echo '<div class="dropdown-submenu">';
                  echo '<div class="submenu-container">';
                  echo '<a href="marca.php?id=' . $marca['ID'] . '">' . $marca['nombre'] . '</a>';
                  if ($hasSubmenu) {
                      echo '<span class="submenu-toggle"></span>';
                  }
                  echo '</div>';

                  if ($hasSubmenu) {
                      echo '<div class="submenu">';
                      while ($categoria = $resultCategorias->fetch_assoc()) {
                          echo '<a href="categoria_marca.php?id=' . $categoria['ID'] . '">' . $categoria['nombre'] . '</a>';
                      }
                      echo '</div>';
                  }

                  echo '</div>';
              }
          }
          ?>
        </div>
      </div>

      <a href="streetwear.php">Streetwear</a>
    </div>
  </div>

  <div class="logo">
    <a href="index.php">
      <img src="fotos/logo.png" alt="Logo" />
    </a>
  </div>

  <div class="navbar-icons">
    <?php
    $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
    ?>
    <a href="carrito.php">
      <img src="fotos/carrito.png" alt="Carrito" class="icon">
      <?php if ($cartCount > 0): ?>
        <span class="badge-circle"></span>
      <?php endif; ?>
    </a>
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
      <a href="admin/admin.php">
        <img src="fotos/configuracion.png" alt="Configuración" class="icon">
      </a>
    <?php endif; ?>
    <?php
    $userIcon = isset($_SESSION['user']) ? 'usuariolog.png' : 'usuario.webp';
    ?>
    <a href="login.php">
      <img src="fotos/<?php echo $userIcon; ?>" alt="Usuario" class="icon">
    </a>
  </div>
</nav>

<div id="searchBar" class="search-bar">
  <form action="buscar.php" method="GET">
    <input type="text" name="query" placeholder="Buscar productos o marcas..." required />
    <button type="submit">Buscar</button>
  </form>
</div>

<script>
  function toggleSearchBar() {
    const searchBar = document.getElementById('searchBar');
    searchBar.classList.toggle('active');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const cartCount = <?php echo $cartCount; ?>;
    const cartBadge = document.getElementById('cartCountBadge');
    if (cartBadge && cartCount === 0) {
      cartBadge.style.display = 'none';
    }

    const menuIcon = document.querySelector(".menu-icon");
    const dropdownMenu = document.querySelector(".dropdown-menu");

    menuIcon.addEventListener("click", function () {
      dropdownMenu.classList.toggle("active");
    });

    if (window.innerWidth <= 768) {
      const allSubmenus = document.querySelectorAll(".submenu");
      const allToggles = document.querySelectorAll(".submenu-toggle");

      allToggles.forEach(toggle => {
        toggle.addEventListener("click", function (e) {
          e.stopPropagation();
          const parent = this.closest(".dropdown-submenu");
          const submenu = parent.querySelector(".submenu");

          allSubmenus.forEach(sub => {
            if (sub !== submenu) sub.classList.remove("active");
          });

          submenu.classList.toggle("active");
        });
      });
    }
  });
</script>

</body>
</html>