<nav class="navbar">
  <!-- Icono de menú a la izquierda -->
  <div class="menu-icon-container">
    <img src="fotos/menu.png" alt="Menu" class="icon menu-icon" />
    <div class="dropdown-menu">
      <a href="index.php">Inicio</a>
      <div class="dropdown-submenu">
        <a href="#">Productos</a>
        <div class="submenu">
          <?php
          $conn = new mysqli("localhost", "root", "", "Sapestore");
          if ($conn->connect_error) {
              die("Conexión fallida: " . $conn->connect_error);
          }
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
        <a href="#">Marcas</a>
        <div class="submenu">
          <?php
          $sql = "SELECT ID, nombre FROM Marca";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
              while ($marca = $result->fetch_assoc()) {
                  echo '<div class="dropdown-submenu">';
                  echo '<a href="marca.php?id=' . $marca['ID'] . '">' . $marca['nombre'] . '</a>';
                  $sqlCategorias = "SELECT ID, nombre FROM Categoria_Marca WHERE marca_id = " . $marca['ID'];
                  $resultCategorias = $conn->query($sqlCategorias);
                  if ($resultCategorias->num_rows > 0) {
                      echo '<div class="submenu">';
                      while ($categoria = $resultCategorias->fetch_assoc()) {
                          echo '<a href="categoria_marca.php?id=' . $categoria['ID'] . '">' . $categoria['nombre'] . '</a>';
                      }
                      echo '</div>';
                  }
                  echo '</div>';
              }
          }
          $conn->close();
          ?>
        </div>
      </div>
      <a href="streetwear.php">Streetwear</a>
    </div>
  </div>
  <img src="fotos/lupa.png" alt="Buscar" class="icon search-icon" onclick="toggleSearchBar()" />

  <!-- Logo en el centro -->
  <div class="logo">
    <a href="index.php">
      <img src="fotos/logo.png" alt="Logo" />
    </a>
  </div>

  <!-- Iconos a la derecha -->
  <div class="navbar-icons">
    <img src="fotos/carrito.png" alt="Carrito" class="icon" />
    <img src="fotos/usuario.webp" alt="Usuario" class="icon" />
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
</script>
