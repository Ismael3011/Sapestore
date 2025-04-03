<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Sapestore";

$conn = new mysqli($servername, $username, $password, $dbname);

$table = $_GET['table'];
$id = $_GET['id'];

$sql = "DELETE FROM $table WHERE ID = $id";
if ($conn->query($sql) === TRUE) {
    echo "Registro eliminado exitosamente";
    header('Location: manage.php?table=' . $table);
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
