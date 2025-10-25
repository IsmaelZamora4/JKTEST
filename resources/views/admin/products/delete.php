<?php
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';

$db = (new Database())->getConnection();
$product = new Product($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $product->id = $id;
        $product->delete();
    }
}

header('Location: /admin/products');
exit;
