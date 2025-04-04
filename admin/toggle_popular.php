<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

header('Content-Type: application/json');

$popularFile = 'popular_products.json';

if (!file_exists($popularFile)) {
    file_put_contents($popularFile, json_encode([]));
}

$popularProducts = json_decode(file_get_contents($popularFile), true) ?? [];
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

if ($id === null || $action === null) {
    echo json_encode(['success' => false, 'message' => 'ID o acción no proporcionados.']);
    exit;
}

if ($action === 'add' && !in_array($id, $popularProducts)) {
    $popularProducts[] = $id;
} elseif ($action === 'remove' && in_array($id, $popularProducts)) {
    $popularProducts = array_diff($popularProducts, [$id]);
}

if (file_put_contents($popularFile, json_encode(array_values($popularProducts)))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios.']);
}
exit;
?>
