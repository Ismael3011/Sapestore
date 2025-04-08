<?php
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "Sapestore";

$conn = new mysqli($servidor, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>