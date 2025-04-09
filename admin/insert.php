<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include_once '../config.php';

$table = $_GET['table'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['imagen_url']) && $_FILES['imagen_url']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../fotos/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $targetFile = $targetDir . basename($_FILES['imagen_url']['name']);
        if (move_uploaded_file($_FILES['imagen_url']['tmp_name'], $targetFile)) {
            $_POST['imagen_url'] = "fotos/" . basename($_FILES['imagen_url']['name']);
        } else {
            echo "Error al guardar la imagen principal. Verifique los permisos de la carpeta.";
        }
    } elseif (isset($_FILES['imagen_url']['error']) && $_FILES['imagen_url']['error'] !== UPLOAD_ERR_NO_FILE) {
        echo "Error al subir la imagen principal: " . $_FILES['imagen_url']['error'];
    }

    if (isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../fotos/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); 
        }
        $targetFile = $targetDir . basename($_FILES['logo_url']['name']);
        if (move_uploaded_file($_FILES['logo_url']['tmp_name'], $targetFile)) {
            $_POST['logo_url'] = "fotos/" . basename($_FILES['logo_url']['name']); 
        } else {
            echo "Error al guardar el logo.";
        }
    }

    $additionalImages = [];
    if (isset($_FILES['additional_images']) && count($_FILES['additional_images']['name']) > 0) {
        $targetDir = "../fotos/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES['additional_images']['name'] as $key => $imageName) {
            if ($_FILES['additional_images']['error'][$key] == UPLOAD_ERR_OK) {
                $targetFile = $targetDir . basename($imageName);
                if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $targetFile)) {
                    $additionalImages[] = "fotos/" . basename($imageName);
                }
            }
        }
    }

    if ($table === 'Usuario' && isset($_POST['contrasena'])) {
        $_POST['contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
    }

    if (isset($_POST['categoria_marca_id']) && $_POST['categoria_marca_id'] === '') {
        $_POST['categoria_marca_id'] = null;
    }

    $tallas = isset($_POST['tallas']) ? $_POST['tallas'] : [];
    unset($_POST['tallas']);

    $columns = array_keys($_POST);
    $values = array_map(function($value) use ($conn) {
        return $value === null ? "NULL" : "'" . $conn->real_escape_string($value) . "'";
    }, array_values($_POST));

    $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";

    if ($conn->query($sql) === TRUE) {
        $productId = $conn->insert_id;

        foreach ($tallas as $talla) {
            $numero = $conn->real_escape_string($talla['numero']);
            $precio = $conn->real_escape_string($talla['precio']);
            $stock = $conn->real_escape_string($talla['stock']);

            $sqlTalla = "INSERT INTO Talla (numero, precio, stock) VALUES ('$numero', '$precio', '$stock')";
            if ($conn->query($sqlTalla) === TRUE) {
                $tallaId = $conn->insert_id;

                $sqlProductoTalla = "INSERT INTO Producto_Talla (producto_id, talla_id) VALUES ('$productId', '$tallaId')";
                $conn->query($sqlProductoTalla);
            }
        }

        if ($table === 'Producto' && !empty($additionalImages)) {
            foreach ($additionalImages as $imageUrl) {
                $sqlAdditionalImage = "INSERT INTO Producto_Imagen (producto_id, imagen_url) VALUES ('$productId', '$imageUrl')";
                $conn->query($sqlAdditionalImage);
            }
        }

        echo "Nuevo registro creado exitosamente";
        header('Location: ./admin.php');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar <?php echo $table; ?></title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <style>
        .images-contenedor {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-width: 100%; 
            overflow-x: auto; 
        }
        .images-contenedor input[type="file"] {
            flex: 1 1 auto;
            max-width: 200px; 
        }
    </style>
</head>
<body>

    <h1>Agregar Nuevo Registro en <?php echo $table; ?></h1>

    <div class="contenedor">
        <form method="POST" enctype="multipart/form-data" class="formulario-compacto">
            <?php
            $sql = "DESCRIBE $table";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                if ($row['Field'] != 'ID' && $row['Field'] != 'marca_id' && $row['Field'] != 'categoria_marca_id') {
                    echo "<div class='form-group'>";
                    echo "<label for='{$row['Field']}'>{$row['Field']}:</label>";
                    if ($row['Field'] == 'imagen_url' || $row['Field'] == 'logo_url') {
                        // Si es foto que haya que meter archivo
                        echo "<input type='file' id='{$row['Field']}' name='{$row['Field']}' accept='image/*'>";
                    } elseif ($row['Field'] == 'categoria_id') {
                        echo "<select id='{$row['Field']}' name='{$row['Field']}' required>";
                        $foreignSql = "SELECT ID, nombre FROM Categoria";
                        $foreignResult = $conn->query($foreignSql);
                        if ($foreignResult->num_rows > 0) {
                            while ($foreignRow = $foreignResult->fetch_assoc()) {
                                // Elegir la categoría de la marca
                                echo "<option value='{$foreignRow['ID']}'>{$foreignRow['nombre']}</option>";
                            }
                        } else {
                            echo "<option value=''>No hay categorías disponibles</option>";
                        }
                        echo "</select>";
                    } elseif ($table === 'Usuario' && $row['Field'] == 'rol') {
                        echo "<select id='{$row['Field']}' name='{$row['Field']}' required>";
                        echo "<option value='admin'>Admin</option>";
                        echo "<option value='cliente'>Cliente</option>";
                        echo "</select>";
                    } else {
                        echo "<input type='text' id='{$row['Field']}' name='{$row['Field']}' required>";
                    }
                    echo "</div>";
                }
            }
            ?>

            <?php if ($table === 'Producto'): ?>
                <div class="form-group">
                    <label for="marca_id">Marca</label>
                    <select id="marca_id" name="marca_id" required onchange="loadCategories(this.value)">
                        <option value="">Seleccione una marca</option>
                        <?php
                        // Cargar marcas
                        $sql = "SELECT ID, nombre FROM Marca";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['ID']}'>{$row['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="categoria_marca_id">Categoría de la Marca (opcional)</label>
                    <select id="categoria_marca_id" name="categoria_marca_id">
                        <option value="">Seleccione una categoría</option>
                    </select>
                </div>
                <script>
                    // Cargar categorías al seleccionar una marca si es que las tiene
                    function loadCategories(marcaId) {
                        const categoriaSelect = document.getElementById('categoria_marca_id');
                        categoriaSelect.innerHTML = '<option value="">Cargando categorías...</option>';
                        fetch(`./load_categories.php?marca_id=${marcaId}`)
                            .then(response => response.json())
                            .then(data => {
                                categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
                                data.forEach(categoria => {
                                    const option = document.createElement('option');
                                    option.value = categoria.ID;
                                    option.textContent = categoria.nombre;
                                    categoriaSelect.appendChild(option);
                                });
                            })
                            .catch(error => {
                                console.error('Error al cargar las categorías:', error);
                                categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
                            });
                    }
                </script>
                <h3>Tallas Disponibles</h3>
                <div id="tallas-container" class="tallas-contenedor">
                    <div class="talla">
                        <label for="tallas[0][numero]">Número:</label>
                        <input type="text" name="tallas[0][numero]">
                        <label for="tallas[0][precio]">Precio:</label>
                        <input type="number" step="0.01" name="tallas[0][precio]">
                        <label for="tallas[0][stock]">Stock:</label>
                        <input type="number" name="tallas[0][stock']">
                        <button type="button" class="boton-eliminar" onclick="removeTalla(this)">Eliminar</button>
                    </div>
                    <button type="button" class="boton-agregar" onclick="addTalla()">Agregar Talla</button>
                </div>
                <h3>Imágenes Adicionales</h3>
                <div id="images-container" class="images-contenedor" style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 150px; overflow-y: auto;">
                    <input type="file" name="additional_images[]" accept="image/*" onchange="addImageInput(this)" style="flex: 1 1 auto;">
                </div>
            <?php endif; ?>

            <?php if ($table === 'Categoria_Marca'): ?>
                <div class="form-group">
                    <label for="marca_id">Marca</label>
                    <select id="marca_id" name="marca_id" required>
                        <?php
                        $sql = "SELECT ID, nombre FROM Marca";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['ID']}'>{$row['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="boton-agregar" onclick="cleanEmptyTallas()">Guardar</button>
        </form>
    </div>

    <script>
        let tallaIndex = 1;
        // La funcion anade tallas a la lista si ya se ha metido una, sucesivamente
        function addTalla() {
            const container = document.getElementById('tallas-container');
            const div = document.createElement('div');
            div.classList.add('talla');
            div.innerHTML = `
                <label for="tallas[${tallaIndex}][numero]">Número:</label>
                <input type="text" name="tallas[${tallaIndex}][numero]">
                <label for="tallas[${tallaIndex}][precio]">Precio:</label>
                <input type="number" step="0.01" name="tallas[${tallaIndex}][precio]">
                <label for="tallas[${tallaIndex}][stock]">Stock:</label>
                <input type="number" name="tallas[${tallaIndex}][stock]">
                <button type="button" class="boton-eliminar" onclick="removeTalla(this)">Eliminar</button>
            `;
            container.insertBefore(div, container.querySelector('.boton-agregar'));
            tallaIndex++;
        }

        function removeTalla(button) {
            // Elimina la talla en la que se pulse eliminar
            const tallaDiv = button.parentElement;
            tallaDiv.remove();
        }

        function cleanEmptyTallas() {
            // No mete las tallas vacias
            const container = document.getElementById('tallas-container');
            if (!container) return;
            const tallas = container.querySelectorAll('.talla');
            tallas.forEach(talla => {
                const inputs = talla.querySelectorAll('input');
                const isEmpty = Array.from(inputs).every(input => input.value.trim() === '');
                if (isEmpty) {
                    talla.remove();
                }
            });
        }

        function addImageInput(input) {
            // Añade un nuevo input de imagen si se ha subido una imagen
            if (input.files && input.files.length > 0) {
                const container = document.getElementById('images-container');
                const newInput = document.createElement('input');
                newInput.type = 'file';
                newInput.name = 'additional_images[]';
                newInput.accept = 'image/*';
                newInput.style.flex = '1 1 auto';
                newInput.onchange = function () {
                    addImageInput(newInput);
                };
                container.appendChild(newInput);
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>