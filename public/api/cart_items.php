<?php
// public/api/cart_items.php
header('Content-Type: application/json; charset=utf-8');

// Intentar cargar entorno mínimo
$root = __DIR__ . '/../../';
if (!defined('BASE_PATH')) {
    if (file_exists($root . 'config/paths.php')) require_once $root . 'config/paths.php';
}
if (!class_exists('Cart')) {
    if (file_exists((defined('BASE_PATH') ? BASE_PATH : $root) . 'classes/Cart.php')) require_once (defined('BASE_PATH') ? BASE_PATH : $root) . 'classes/Cart.php';
}
if (!class_exists('Database')) {
    if (file_exists((defined('BASE_PATH') ? BASE_PATH : $root) . 'config/database.php')) require_once (defined('BASE_PATH') ? BASE_PATH : $root) . 'config/database.php';
}

try {
    if (!class_exists('Database')) throw new Exception('Database class not available');
    $database = new Database();
    $db = $database->getConnection();
    if (!class_exists('Cart')) throw new Exception('Cart class not available');
    $cart = new Cart($db);
    $summary = $cart->getCheckoutSummary();
    $items = $summary['items'] ?? [];

    $out = [];
    foreach ($items as $it) {
        $out[] = [
            'name' => $it['name'] ?? '',
            'quantity' => isset($it['quantity']) ? (int)$it['quantity'] : 0,
            'size' => $it['size_name'] ?? '',
            'color' => $it['color_name'] ?? '',
            'unit_price' => isset($it['final_price']) ? (float)$it['final_price'] : null,
            'line_total' => isset($it['line_total']) ? (float)$it['line_total'] : null,
        ];
    }

    echo json_encode(['success' => true, 'items' => $out]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
