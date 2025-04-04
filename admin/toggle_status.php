<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

header('Content-Type: application/json');

$type = isset($_POST['type']) ? $_POST['type'] : null;
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

if ($type === null || $id === null || $action === null) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

$file = $type === 'popular' ? 'popular_products.json' : 'fast_delivery_products.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$products = json_decode(file_get_contents($file), true) ?? [];

if ($action === 'add' && !in_array($id, $products)) {
    $products[] = $id;
} elseif ($action === 'remove' && in_array($id, $products)) {
    $products = array_diff($products, [$id]);
}

if (file_put_contents($file, json_encode(array_values($products)))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios.']);
}
exit;
?>
