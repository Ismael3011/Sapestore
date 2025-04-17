<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config.php';

$tabla = $_GET['table'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$scrollPosition = isset($_GET['scroll']) ? (int)$_GET['scroll'] : 0;

$popularFile = 'popular_products.json';
$fastDeliveryFile = 'fast_delivery_products.json';

$popularProducts = file_exists($popularFile) ? json_decode(file_get_contents($popularFile), true) ?? [] : [];
$fastDeliveryProducts = file_exists($fastDeliveryFile) ? json_decode(file_get_contents($fastDeliveryFile), true) ?? [] : [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    if (is_numeric($search)) {
        $sql = "SELECT * FROM $tabla WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $search);
    } else {
        $sql = "SELECT * FROM $tabla WHERE nombre LIKE ?";
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$search%";
        $stmt->bind_param("s", $searchTerm);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $sql = "SELECT * FROM $tabla";
    $resultado = $conn->query($sql);
}

if (!$resultado) {
    die("Error en la consulta: " . $conn->error);
}
$campos = $resultado->fetch_fields();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar <?php echo $tabla; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        h1 {
            text-align: center; 
            color: #e3422b; 
            margin-top: 20px;
        }
        .contenedor {
            padding: 20px;
        }
        .boton-contenedor {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .boton-grupo {
            display: flex;
            gap: 10px;
        }
        .formulario-buscador {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .formulario-buscador input {
            padding: 6px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .boton-agregar, .boton-volver {
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
            background-color: #e3422b;
            color: #fff;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .boton-agregar:hover, .boton-volver:hover {
            background-color: #c12e1f;
        }
        .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            max-height: calc(100vh - 180px);
            background-color: #fff; 
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: max-content;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            white-space: nowrap;
        }
        th {
            background-color: #e3422b; 
            color: #fff; 
            text-align: left;
        }
        .action-link {
            text-decoration: none;
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .toggle-popular {
            background-color: #f0f0f0; 
            color: #333;
        }
        .toggle-popular.active {
            background-color: #e3422b; 
            color: #fff;
        }
        .toggle-fast-delivery {
            background-color: #f0f0f0; 
            color: #333;
        }
        .toggle-fast-delivery.active {
            background-color: #e3422b;
            color: #fff;
        }
        .action-separator {
            color: #999;
            margin: 0 4px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por ID o Nombre">
                <button type="submit" class="boton-agregar">Buscar</button>
            </form>
        </div>

        <div class="table-wrapper">
            <table id="tabla-principal">
                <tr>
                    <?php foreach ($campos as $campo): ?>
                        <th><?php echo $campo->name; ?></th>
                    <?php endforeach; ?>
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
                                <a href="#" class="action-link toggle-popular <?php echo in_array($fila['ID'], $popularProducts) ? 'active' : ''; ?>"
                                   data-id="<?php echo $fila['ID']; ?>"
                                   data-action="<?php echo in_array($fila['ID'], $popularProducts) ? 'remove' : 'add'; ?>"
                                   data-type="popular">
                                   <?php echo in_array($fila['ID'], $popularProducts) ? 'Quitar de Populares' : 'Agregar a Populares'; ?>
                                </a>
                                <span class="action-separator">|</span>
                                <a href="#" class="action-link toggle-fast-delivery <?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'active' : ''; ?>"
                                   data-id="<?php echo $fila['ID']; ?>"
                                   data-action="<?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'remove' : 'add'; ?>"
                                   data-type="fast_delivery">
                                   <?php echo in_array($fila['ID'], $fastDeliveryProducts) ? 'Quitar de Entrega Rápida' : 'Agregar a Entrega Rápida'; ?>
                                </a>
                                <span class="action-separator">|</span>
                                <a href="../producto.php?id=<?php echo $fila['ID']; ?>" class="action-link">Ver Producto</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- Scripts -->
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
                                button.text('Quitar de Populares').data('action', 'remove').addClass('active');
                            } else {
                                button.text('Agregar a Populares').data('action', 'add').removeClass('active');
                            }
                        } else {
                            if (action === 'add') {
                                button.text('Quitar de Entrega Rápida').data('action', 'remove').addClass('active');
                            } else {
                                button.text('Agregar a Entrega Rápida').data('action', 'add').removeClass('active');
                            }
                        }
                    } else {
                        alert('Error al actualizar el estado.');
                    }
                },
                error: function() {
                    alert('Error en la solicitud.');
                }
            });
        });

        function confirmDelete(url) {
            if (confirm("¿Estás seguro de que deseas eliminar este registro?")) {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
