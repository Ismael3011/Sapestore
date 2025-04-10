<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config.php';

$table = $_GET['table'];
$id = $_GET['id'];

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
            echo "Error al guardar la imagen principal.";
        }
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

    // Para que no haya problemas al actualizar las imagenes adiccionales
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

    if ($table === 'Producto' && !empty($additionalImages)) {
        foreach ($additionalImages as $imageUrl) {
            $sqlAdditionalImage = "INSERT INTO Producto_Imagen (producto_id, imagen_url) VALUES ('$id', '$imageUrl')";
            $conn->query($sqlAdditionalImage);
        }
    }

    if ($table === 'Producto' && isset($_POST['tallas'])) {
        $tallas = $_POST['tallas'];

        foreach ($tallas as $tallaId => $tallaData) {
            $numero = $conn->real_escape_string($tallaData['numero']);
            $precio = $conn->real_escape_string($tallaData['precio']);
            $stock = $conn->real_escape_string($tallaData['stock']);

            if ($tallaId === 'new') {
                $sqlInsertTalla = "INSERT INTO Talla (numero, precio, stock) VALUES ('$numero', '$precio', '$stock')";
                if ($conn->query($sqlInsertTalla) === TRUE) {
                    $newTallaId = $conn->insert_id;
                    $sqlLinkTalla = "INSERT INTO Producto_Talla (producto_id, talla_id) VALUES ('$id', '$newTallaId')";
                    $conn->query($sqlLinkTalla);
                }
            } else {
                $sqlUpdateTalla = "UPDATE Talla SET numero = '$numero', precio = '$precio', stock = '$stock' WHERE ID = '$tallaId'";
                $conn->query($sqlUpdateTalla);
            }
        }
    }

    if (isset($_POST['categoria_marca_id']) && $_POST['categoria_marca_id'] === '') {
        $_POST['categoria_marca_id'] = null;
    }

    $set = [];
    foreach ($_POST as $key => $value) {

        if ($key === 'tallas') {
            continue;
        }
        $set[] = $value === null ? "$key = NULL" : "$key = '" . $conn->real_escape_string($value) . "'";
    }

    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE ID = $id";

    if ($conn->query($sql) === TRUE) {
        echo "Registro actualizado exitosamente";
        header('Location: manage.php?table=' . $table); 
        exit;
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$sql = "SELECT * FROM $table WHERE ID = $id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?php echo $table; ?></title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <style>
        .images-contenedor {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <h1>Editar Registro en <?php echo $table; ?></h1>

    <div class="contenedor">
        <form method="POST" enctype="multipart/form-data">
            <?php
            // Obtener los nombres de las columnas de la tabla
            $sql = "DESCRIBE $table";
            $result = $conn->query($sql);
            while ($column = $result->fetch_assoc()) {
                if ($column['Field'] != 'ID' && $column['Field'] != 'marca_id' && $column['Field'] != 'categoria_marca_id') {
                    echo "<label for='{$column['Field']}'>{$column['Field']}:</label><br>";
                    if ($column['Field'] == 'logo_url' || $column['Field'] == 'imagen_url') { 
                        echo "<input type='file' id='{$column['Field']}' name='{$column['Field']}' accept='image/*'><br><br>";
                    } elseif ($column['Field'] == 'categoria_id') {
                        echo "<select id='{$column['Field']}' name='{$column['Field']}' required>";
                        $foreignSql = "SELECT ID, nombre FROM Categoria";
                        $foreignResult = $conn->query($foreignSql);
                        if ($foreignResult->num_rows > 0) {
                            while ($foreignRow = $foreignResult->fetch_assoc()) {
                                $selected = $row[$column['Field']] == $foreignRow['ID'] ? "selected" : "";
                                echo "<option value='{$foreignRow['ID']}' $selected>{$foreignRow['nombre']}</option>";
                            }
                        } else {
                            echo "<option value=''>No hay categorías disponibles</option>";
                        }
                        echo "</select><br><br>";
                    } else {
                        echo "<input type='text' id='{$column['Field']}' name='{$column['Field']}' value='" . $row[$column['Field']] . "' required><br><br>";
                    }
                }
            }
            ?>
            <?php if ($table === 'Categoria_Marca'): ?>
                <label for="nombre">Nombre de la Categoría:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo $row['nombre']; ?>" required><br><br>
                <label for="marca_id">Marca:</label>
                <select id="marca_id" name="marca_id" required>
                    <?php
                    $sql = "SELECT ID, nombre FROM Marca";
                    $result = $conn->query($sql);
                    while ($marca = $result->fetch_assoc()) {
                        $selected = $row['marca_id'] == $marca['ID'] ? "selected" : "";
                        echo "<option value='{$marca['ID']}' $selected>{$marca['nombre']}</option>";
                    }
                    ?>
                </select><br><br>
            <?php endif; ?>
            <?php if ($table === 'Producto'): ?>
               
                <label for="marca_id">Marca:</label>
                <select id="marca_id" name="marca_id" required onchange="loadCategories(this.value)">
                    <option value="">Seleccione una marca</option>
                    <?php
                    $sql = "SELECT ID, nombre FROM Marca";
                    $result = $conn->query($sql);
                    while ($marca = $result->fetch_assoc()) {
                        $selected = $row['marca_id'] == $marca['ID'] ? "selected" : "";
                        echo "<option value='{$marca['ID']}' $selected>{$marca['nombre']}</option>";
                    }
                    ?>
                </select><br><br>

                <label for="categoria_marca_id">Categoría de la Marca (opcional):</label>
                <select id="categoria_marca_id" name="categoria_marca_id">
                    <option value="">Seleccione una categoría</option>
                    <?php
                    if (!empty($row['marca_id'])) {
                        $sqlCategorias = "SELECT ID, nombre FROM Categoria_Marca WHERE marca_id = ?";
                        $stmtCategorias = $conn->prepare($sqlCategorias);
                        $stmtCategorias->bind_param("i", $row['marca_id']);
                        $stmtCategorias->execute();
                        $resultCategorias = $stmtCategorias->get_result();
                        while ($categoria = $resultCategorias->fetch_assoc()) {
                            $selected = $row['categoria_marca_id'] == $categoria['ID'] ? "selected" : "";
                            echo "<option value='{$categoria['ID']}' $selected>{$categoria['nombre']}</option>";
                        }
                        $stmtCategorias->close();
                    }
                    ?>
                </select><br><br>

                <div class="form-group">
                    <label for="additional_images">Imágenes Adicionales:</label>
                    <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                </div>

                <h3>Imágenes Adicionales</h3>
                <div id="images-container" class="images-contenedor" style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 150px; overflow-y: auto;">
                    <input type="file" name="additional_images[]" accept="image/*" onchange="addImageInput(this)" style="flex: 1 1 auto;">
                </div>

                <h3>Tallas Disponibles</h3>
                <div id="tallas-container" class="tallas-contenedor">
                    <?php
                    $sqlTallas = "SELECT t.ID, t.numero, t.precio, t.stock FROM Talla t INNER JOIN Producto_Talla pt ON t.ID = pt.talla_id WHERE pt.producto_id = ?";
                    $stmtTallas = $conn->prepare($sqlTallas);
                    $stmtTallas->bind_param("i", $id);
                    $stmtTallas->execute();
                    $resultTallas = $stmtTallas->get_result();

                    while ($talla = $resultTallas->fetch_assoc()): ?>
                        <div class="talla">
                            <input type="hidden" name="tallas[<?php echo $talla['ID']; ?>][id]" value="<?php echo $talla['ID']; ?>">
                            <label for="tallas[<?php echo $talla['ID']; ?>][numero]">Número:</label>
                            <input type="text" name="tallas[<?php echo $talla['ID']; ?>][numero]" value="<?php echo $talla['numero']; ?>">
                            <label for="tallas[<?php echo $talla['ID']; ?>][precio]">Precio:</label>
                            <input type="number" step="0.01" name="tallas[<?php echo $talla['ID']; ?>][precio]" value="<?php echo $talla['precio']; ?>">
                            <label for="tallas[<?php echo $talla['ID']; ?>][stock]">Stock:</label>
                            <input type="number" name="tallas[<?php echo $talla['ID']; ?>][stock]" value="<?php echo $talla['stock']; ?>">
                            <button type="button" class="boton-eliminar" onclick="removeTalla(this, <?php echo $talla['ID']; ?>)">Eliminar</button>
                        </div>
                    <?php endwhile; ?>
                    <button type="button" class="boton-agregar" onclick="addTalla()">Agregar Nueva Talla</button>
                </div>
                <script>
                    let tallaIndex = 'new';

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
                        tallaIndex = 'new';
                    }

                    function removeTalla(button, tallaId = null) {
                        const tallaDiv = button.parentElement;
                        tallaDiv.remove();
                        if (tallaId) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = `tallas[${tallaId}][delete]`;
                            hiddenInput.value = '1';
                            document.querySelector('form').appendChild(hiddenInput);
                        }
                    }

                    let imageIndex = 0;

                    function addImageInput(input) {
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
            <?php endif; ?>
            <button type="submit" class="boton-agregar">Actualizar</button>
        </form>
        <div class="boton-volver-contenedor">
            <form action="manage.php" method="get">
                <input type="hidden" name="table" value="<?php echo $table; ?>">
                <button type="submit" class="boton-volver">Volver</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>