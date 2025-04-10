<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../config.php';

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