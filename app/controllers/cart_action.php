<?php
// cart_action.php (versión corregida)
// Manejo de carrito con protección contra "resync" accidental tras clear_cart

require_once BASE_PATH . "config/config.php";
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . "classes/Cart.php";


if (session_status() === PHP_SESSION_NONE) session_start();

// tiempo (segundos) durante el cual ignoramos un sync después de un clear
define('CART_IGNORE_SYNC_SECONDS', 120);

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

function json_ok($data = [])
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => 'ok'], $data));
    exit;
}
function json_error($msg = 'Error', $code = 400)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Helper: comprobar si acabamos de limpiar el carrito (flag en sesión)
function cartWasJustCleared()
{
    if (!isset($_SESSION['cart_just_cleared'])) return false;
    $ts = (int)$_SESSION['cart_just_cleared'];
    if ($ts <= 0) return false;
    return (time() - $ts) <= CART_IGNORE_SYNC_SECONDS;
}

switch ($action) {

    // AGREGAR (POST form-urlencoded)
    case 'add':
        // Si venía una bandera de "just_cleared" y ahora el usuario agrega, retirar la bandera:
        if (isset($_SESSION['cart_just_cleared'])) {
            unset($_SESSION['cart_just_cleared']);
        }

        $product_id = $_POST['product_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $size_id = $_POST['size_id'] ?? null;
        $color_id = $_POST['color_id'] ?? null;

        // DEBUG: Log de datos recibidos
        error_log("=== DEBUG cart_action.php ADD ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("Product ID: " . ($product_id ?? 'NULL'));
        error_log("Quantity: " . $quantity);
        error_log("Size ID: " . ($size_id ?? 'NULL'));
        error_log("Color ID: " . ($color_id ?? 'NULL'));

        if (!$product_id) {
            error_log("ERROR: Product ID is required");
            echo json_encode(['status' => 'error', 'message' => 'ID de producto requerido']);
            exit;
        }

        // Convertir a entero si no es null
        $size_id = $size_id ? (int)$size_id : null;
        $color_id = $color_id ? (int)$color_id : null;

        error_log("Processed Size ID: " . ($size_id ?? 'NULL'));
        error_log("Processed Color ID: " . ($color_id ?? 'NULL'));

        if ($cart->addItem($product_id, $quantity, $size_id, $color_id)) {
            $total_items = $cart->getTotalItems();
            $cart_summary = $cart->getSummary();
            error_log("SUCCESS: Product added to cart. Total items: " . $total_items);
            echo json_encode([
                'status' => 'ok',
                'message' => 'Producto agregado al carrito',
                'total_items' => $total_items,
                'cart_total' => $cart_summary['total']
            ]);
        } else {
            error_log("ERROR: Failed to add product to cart");
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar producto']);
        }
        break;

    // ACTUALIZAR CANTIDAD (POST)
    case 'update_quantity':
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        if ($product_id <= 0) json_error('Producto inválido', 422);

        $ok = $cart->updateQuantity($product_id, $quantity);
        if ($ok) {
            $summary = $cart->getCheckoutSummary();

            // Si tras la actualización el carrito quedó vacío, marcar que se vació
            if (isset($summary['total_items']) && (int)$summary['total_items'] === 0) {
                $_SESSION['cart_just_cleared'] = time();
            } else {
                if (isset($_SESSION['cart_just_cleared'])) unset($_SESSION['cart_just_cleared']);
            }

            json_ok(['summary' => $summary]);
        } else json_error('No se pudo actualizar', 500);
        break;

    // ELIMINAR (POST)
    case 'remove_item':
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($product_id <= 0) json_error('Producto inválido', 422);

        $ok = $cart->removeItem($product_id);
        if ($ok) {
            $summary = $cart->getCheckoutSummary();
            if (isset($summary['total_items']) && (int)$summary['total_items'] === 0) {
                $_SESSION['cart_just_cleared'] = time();
            } else {
                if (isset($_SESSION['cart_just_cleared'])) unset($_SESSION['cart_just_cleared']);
            }
            json_ok(['summary' => $summary]);
        } else json_error('No se pudo eliminar', 500);
        break;

    // VACIAR (POST)
    case 'clear_cart':
        $ok = $cart->clearCart();
        if ($ok) {
            // marcar en la sesión que acabo de vaciarse (ayuda a que el siguiente sync sea ignorado)
            $_SESSION['cart_just_cleared'] = time();
            json_ok(['message' => 'Carrito vaciado']);
        } else json_error('No se pudo vaciar', 500);
        break;

    // RESUMEN (GET)
    case 'get_summary':
        $summary = $cart->getCheckoutSummary();
        json_ok(['summary' => $summary]);
        break;

    // SYNC: overwrite/upsert local -> servidor (espera JSON body { cart: [...] })
    case 'sync':
        // Si acabamos de vaciar el carrito recientemente -> ignoramos el payload del cliente
        if (cartWasJustCleared()) {
            // consume la bandera (solo la usamos una vez)
            unset($_SESSION['cart_just_cleared']);
            $summary = $cart->getCheckoutSummary();
            // devolvemos el summary actual (probablemente vacío) y una señal
            json_ok(['summary' => $summary, 'ignored_sync_after_clear' => true]);
        }

        // Si no estamos en el caso de "just cleared", procesamos el payload normalmente
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!isset($payload['cart']) || !is_array($payload['cart'])) {
            json_error('Payload inválido', 422);
        }

        try {
            // Preparar statements
            $selectStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ? LIMIT 1");
            $updateStmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $insertStmt = $db->prepare("INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?)");

            $session_id = $_SESSION['cart_session_id'] ?? null;
            if (!$session_id) json_error('Sesión inválida', 500);

            foreach ($payload['cart'] as $it) {
                $pid = isset($it['id']) ? (int)$it['id'] : 0;
                $qty = isset($it['quantity']) ? max(1, (int)$it['quantity']) : 1;
                if ($pid <= 0) continue;

                $selectStmt->execute([$session_id, $pid]);
                $row = $selectStmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    // Overwrite quantity (idempotente para mismo payload)
                    $updateStmt->execute([$qty, $row['id']]);
                } else {
                    $insertStmt->execute([$session_id, $pid, $qty]);
                }
            }

            $summary = $cart->getCheckoutSummary();
            json_ok(['summary' => $summary]);
        } catch (Exception $e) {
            json_error('Error en sync: ' . $e->getMessage(), 500);
        }
        break;

    // DECREMENTAR 1 UNIDAD (POST) - usa cart_item_id
    case 'decrease_item':
        $cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
        if ($cart_item_id <= 0) json_error('cart_item_id inválido', 422);

        // Obtener fila actual del cart_items para sesión
        $session_id = $_SESSION['cart_session_id'] ?? null;
        if (!$session_id) json_error('Sesión inválida', 500);

        $select = $db->prepare("SELECT id, product_id, quantity FROM cart_items WHERE id = ? AND session_id = ? LIMIT 1");
        $select->execute([$cart_item_id, $session_id]);
        $row = $select->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_error('Ítem no encontrado', 404);

        $current_qty = (int)$row['quantity'];
        $product_id = (int)$row['product_id'];

        // Determinar si el producto es mayorista (misma heurística que en cart.php)
        $catStmt = $db->prepare("SELECT c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1");
        $catStmt->execute([$product_id]);
        $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
        $is_mayorista = $catRow && strpos(strtolower($catRow['category_name']), 'mayorista') !== false;
        $min_allowed = $is_mayorista ? 12 : 1;
        $step = $is_mayorista ? 6 : 1;

        // Decrementar: para mayorista usamos el step (p.ej. 6), para unidad decrementar por 1
        $decrement_by = $is_mayorista ? $step : 1;
        $new_qty = $current_qty - $decrement_by;

        // Si la nueva cantidad es menor o igual a 0 -> eliminar completamente
        if ($new_qty <= 0) {
            $ok = $cart->removeItemById($cart_item_id);
        } else {
            // Para mayorista, también asegurarse que la nueva cantidad sea múltiplo del step
            if ($is_mayorista) {
                // ajustar hacia abajo al múltiplo más cercano de step (no subir)
                $relative = $new_qty - $min_allowed;
                if ($relative < 0) {
                    // Si quedamos por debajo del mínimo, eliminar (ya cubierto arriba),
                    // pero por seguridad, forzamos al mínimo si el usuario no quiere eliminación.
                    $new_qty = $min_allowed;
                } else {
                    // Alinear a múltiplo de step
                    $mult = floor($relative / $step);
                    $new_qty = $min_allowed + $mult * $step;
                    if ($new_qty <= 0) {
                        // si por algún cálculo queda 0, eliminar
                        $ok = $cart->removeItemById($cart_item_id);
                        if ($ok) {
                            $summary = $cart->getCheckoutSummary();
                            if (isset($summary['total_items']) && (int)$summary['total_items'] === 0) {
                                $_SESSION['cart_just_cleared'] = time();
                            } else {
                                if (isset($_SESSION['cart_just_cleared'])) unset($_SESSION['cart_just_cleared']);
                            }
                            json_ok(['summary' => $summary, 'cart_item_id' => $cart_item_id, 'new_quantity' => 0]);
                        } else json_error('No se pudo eliminar', 500);
                    }
                }
            }

            // Si no se eliminó ya, actualizar a la nueva cantidad
            if (!isset($ok)) {
                $ok = $cart->updateQuantity($cart_item_id, $new_qty);
            }
        }

        if ($ok) {
            $summary = $cart->getCheckoutSummary();
            if (isset($summary['total_items']) && (int)$summary['total_items'] === 0) {
                $_SESSION['cart_just_cleared'] = time();
            } else {
                if (isset($_SESSION['cart_just_cleared'])) unset($_SESSION['cart_just_cleared']);
            }
            json_ok(['summary' => $summary, 'cart_item_id' => $cart_item_id, 'new_quantity' => $new_qty]);
        } else json_error('No se pudo decrementar', 500);
        break;

    default:
        json_error('Acción no especificada', 400);
}

