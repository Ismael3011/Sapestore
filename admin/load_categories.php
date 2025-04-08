<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config.php';

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$marcaId = isset($_GET['marca_id']) ? (int)$_GET['marca_id'] : 0;

$sql = "SELECT ID, nombre FROM Categoria_Marca WHERE marca_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $marcaId);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

header('Content-Type: application/json');
echo json_encode($categorias);

$stmt->close();
$conn->close();
?>
