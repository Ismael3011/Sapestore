<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "Sapestore";

// Crear conexión
$conn = new mysqli($servidor, $usuario, $contrasena, $base_datos);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$tabla = $_GET['table'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$scrollPosition = isset($_GET['scroll']) ? (int)$_GET['scroll'] : 0;

$popularFile = 'popular_products.json';
$fastDeliveryFile = 'fast_delivery_products.json';

$popularProducts = [];
if (file_exists($popularFile)) {
    $popularProducts = json_decode(file_get_contents($popularFile), true) ?? [];
}

$fastDeliveryProducts = [];
if (file_exists($fastDeliveryFile)) {
    $fastDeliveryProducts = json_decode(file_get_contents($fastDeliveryFile), true) ?? [];
}

$sql = "SELECT * FROM $tabla";
if ($search) {
    $sql .= " WHERE nombre LIKE ?";
}
$stmt = $conn->prepare($sql);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bind_param("s", $searchTerm);
}
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
    die("Error en la consulta: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar <?php echo $tabla; ?></title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function saveScrollPosition() {
            const scrollPosition = window.scrollY;
            const url = new URL(window.location.href);
            url.searchParams.set('scroll', scrollPosition);
            history.replaceState(null, '', url);
        }

        function restoreScrollPosition() {
            const scrollPosition = <?php echo $scrollPosition; ?>;
            window.scrollTo(0, scrollPosition);
        }

        function confirmDelete(url) {
            if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
                window.location.href = url;
            }
        }

        document.addEventListener('DOMContentLoaded', restoreScrollPosition);

        $(document).on('click', '.toggle-popular, .toggle-fast-delivery', function(e) {
            e.preventDefault();
            const button = $(this);
            const productId = button.data('id');
            const action = button.data('action');
            const type = button.data('type');

            $.ajax({
                url: 'toggle_status.php',
                method: 'POST',
                data: { id: productId, action: action, type: type },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (type === 'popular') {
                            if (action === 'add') {
                                button.text('Quitar de Populares');
                                button.data('action', 'remove');
                                button.addClass('active');
                            } else {
                                button.text('Agregar a Populares');
                                button.data('action', 'add');
                                button.removeClass('active');
                            }
                        } else if (type === 'fast_delivery') {
                            if (action === 'add') {
                                button.text('Quitar de Entrega Rápida');
                                button.data('action', 'remove');
                                button.addClass('active');
                            } else {
                                button.text('Agregar a Entrega Rápida');
                                button.data('action', 'add');
                                button.removeClass('active');
                            }
                        }
                    } else {
                        alert('Error al actualizar el estado.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la solicitud:', error);
                    alert('Error en la solicitud.');
                }
            });
        });
    </script>
</head>
<body onbeforeunload="saveScrollPosition()">

    <h1>Administrar <?php echo $tabla; ?></h1>

    <div class="contenedor">
        <div class="boton-contenedor">
            <div class="boton-grupo">
                <form action="insert.php" method="get">
                    <input type="hidden" name="table" value="<?php echo $tabla; ?>">
                    <button type="submit" class="boton-agregar">Agregar Nuevo Registro</button>
                </form>
                <form action="admin.php" method="get">
                    <button type="submit" class="boton-volver">Volver al Inicio</button>
                </form>
            </div>
            <form method="get" class="formulario-buscador">
                <input type="hidden" name="table" value="<?php echo $tabla; ?>">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por nombre">
                <button type="submit" class="boton-agregar">Buscar</button>
            </form>
        </div>
        <div class="table-container">
            <table>
                <tr>
                    <?php
                    // Mostrar los encabezados de las columnas
                    $campos = $resultado->fetch_fields();
                    foreach ($campos as $campo) {
                        echo "<th>{$campo->name}</th>";
                    }
                    ?>
                    <th>Acciones</th>
                </tr>
                <?php while($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?php echo $valor; ?></td>
                        <?php endforeach; ?>
                        <td>
                            <a href="update.php?table=<?php echo $tabla; ?>&id=<?php echo $fila['ID']; ?>" class="action-link edit-link">Editar</a>
                            <span class="action-separator">|</span>
                            <a href="#" class="action-link delete-link" onclick="confirmDelete('delete.php?table=<?php echo $tabla; ?>&id=<?php echo $fila['ID']; ?>')">Eliminar</a>
                            <?php if ($tabla === 'Producto'): ?>
                                <span class="action-separator">|</span>
                                <a href="#" class="action-link popular-link <?php echo in_array($fila['ID'], $popularProducts) ? 'active' : ''; ?> toggle-popular" 
                                   data-id="<?php echo $fila['ID']; ?>" 
                                   data-action="<?php echo in_array($fila['ID'], $popularProducts) ? 'remove' : 'add'; ?>" 
                                   data-type="popular">
                                   <?php echo in_array($fila['ID'], $popularProducts) ? 'Quitar de Populares' : 'Agregar a Populares'; ?>
                                </a>
                                <span class="action-separator">|</span>
                                <a href="#" class="action-link fast-delivery-link <?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'active' : ''; ?> toggle-fast-delivery" 
                                   data-id="<?php echo $fila['ID']; ?>" 
                                   data-action="<?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'remove' : 'add'; ?>" 
                                   data-type="fast_delivery">
                                   <?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'Quitar de Entrega Rápida' : 'Agregar a Entrega Rápida'; ?>
                                </a>
                                <span class="action-separator">|</span>
                                <a href="../public/views/producto.php?id=<?php echo $fila['ID']; ?>" class="action-link">Ver Producto</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>