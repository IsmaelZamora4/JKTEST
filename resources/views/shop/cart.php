<?php


require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . 'classes/Cart.php';
require_once BASE_PATH . 'classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);
$category = new Category($db);

// // Helper function for cache-busting
// function asset_url_with_v($path)
// {
//     if (!$path) return '';
//     $path = str_replace('\\', '/', $path);
//     if (preg_match('#^https?://#i', $path)) {
//         return $path;
//     }
//     $fs = __DIR__ . '/' . ltrim($path, '/');
//     if (file_exists($fs)) {
//         return $path . '?v=' . filemtime($fs);
//     }
//     return $path;
// }

// Manejar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                if (isset($_POST['cart_item_id'])) {
                    $cart_item_id = (int)$_POST['cart_item_id'];
                    $quantity = (int)$_POST['quantity'];
                    $cart->updateQuantity($cart_item_id, $quantity);
                } else {
                    // Fallback para compatibilidad con product_id
                    $product_id = (int)$_POST['product_id'];
                    $quantity = (int)$_POST['quantity'];
                    $cart->updateQuantityByProduct($product_id, $quantity);
                }

                $summary = $cart->getCheckoutSummary();
                if ($summary['total_items'] === 0) {
                    $_SESSION['cart_just_cleared'] = time();
                }
                break;

            case 'remove_item':
                if (isset($_POST['cart_item_id'])) {
                    $cart_item_id = (int)$_POST['cart_item_id'];
                    $cart->removeItemById($cart_item_id);
                } else {
                    // Fallback para compatibilidad con product_id
                    $product_id = (int)$_POST['product_id'];
                    $cart->removeItem($product_id);
                }

                $summary = $cart->getCheckoutSummary();
                if ($summary['total_items'] === 0) {
                    $_SESSION['cart_just_cleared'] = time();
                }
                break;

            case 'clear_cart':
                $cart->clearCart();
                // Marca en la sesión para que el siguiente sync sea ignorado y no reinsertar items desde localStorage
                $_SESSION['cart_just_cleared'] = time();
                break;

            case 'decrease_item':
                if (isset($_POST['cart_item_id'])) {
                    $cart_item_id = (int)$_POST['cart_item_id'];
                    // Obtener fila actual (asegurarnos que pertenece a la sesión)
                    $session_id = $_SESSION['cart_session_id'] ?? null;
                    if ($session_id) {
                        $stmt = $db->prepare("SELECT id, product_id, quantity FROM cart_items WHERE id = ? AND session_id = ? LIMIT 1");
                        $stmt->execute([$cart_item_id, $session_id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $current_qty = (int)$row['quantity'];
                            $product_id = (int)$row['product_id'];
                            // Detectar mayorista (misma heurística que la vista)
                            $catStmt = $db->prepare("SELECT c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1");
                            $catStmt->execute([$product_id]);
                            $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
                            $is_mayorista_cart = $catRow && strpos(strtolower($catRow['category_name']), 'mayorista') !== false;
                            $min_cart_qty = $is_mayorista_cart ? 12 : 1;

                            // Para mayorista: decrementar por step (p.ej. 6). Para unidad decrementar 1.
                            $decrement = $is_mayorista_cart ? $step_cart_qty : 1;
                            $new_qty = $current_qty - $decrement;

                            if ($new_qty <= 0) {
                                // eliminar completamente
                                $cart->removeItemById($cart_item_id);
                            } else {
                                if ($is_mayorista_cart) {
                                    // Alinear hacia abajo a múltiplos de step respetando min
                                    $relative = $new_qty - $min_cart_qty;
                                    if ($relative < 0) {
                                        // Si quedó por debajo del mínimo, eliminar (o ajustar al mínimo si prefieres)
                                        $cart->removeItemById($cart_item_id);
                                    } else {
                                        $mult = floor($relative / $step_cart_qty);
                                        $new_qty = $min_cart_qty + $mult * $step_cart_qty;
                                        if ($new_qty <= 0) {
                                            $cart->removeItemById($cart_item_id);
                                        } else {
                                            $cart->updateQuantity($cart_item_id, $new_qty);
                                        }
                                    }
                                } else {
                                    $cart->updateQuantity($cart_item_id, $new_qty);
                                }
                            }
                        }
                    }
                }
                break;
        }

        // Redireccionar para evitar reenvío del formulario
        header('Location: cart.php');
        exit();
    }
}

// Obtener datos del carrito
$cart_summary = $cart->getCheckoutSummary();
$categories = $category->getAllWithProductCount();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Revisa y gestiona los productos en tu carrito de compras.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS modular cargado directamente para mejor rendimiento -->
    <link href="assets/css/variables.css" rel="stylesheet">
    <script>
        // Actualizar precio del navbar con el total real del carrito al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const cartTotal = <?php echo $cart_summary['total']; ?>;
            const cartTotalElement = document.getElementById('cart-total');
            if (cartTotalElement) {
                cartTotalElement.textContent = cartTotal.toFixed(2);
            }

            // También actualizar el contador
            const cartCount = <?php echo $cart_summary['total_items']; ?>;
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = cartCount;
            }
        });
    </script>
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">

</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <!-- Breadcrumb -->
    <section class="py-4 page-header-minimal">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="breadcrumb-link">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active breadcrumb-current" aria-current="page">
                        Carrito de Compras
                    </li>
                </ol>
            </nav>
            <h1 class="page-title-minimal mb-2">
                <i class="fas fa-shopping-cart me-2" style="color: #f0bd36;"></i>
                Carrito de Compras
            </h1>
            <p class="page-subtitle-minimal mb-0">
                Revisa y gestiona los productos seleccionados
            </p>
        </div>
    </section>

    <!-- Contenido del Carrito -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php if ($cart_summary['total_items'] > 0): ?>
                        <div class="mb-3">
                            <span class="badge bg-primary"><?php echo $cart_summary['total_items']; ?> productos</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($cart_summary['items'])): ?>
                <!-- Carrito Vacío -->
                <div id="empty-cart" class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Tu carrito está vacío</h3>
                    <p>¡Agrega algunos productos increíbles a tu carrito!</p>
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag"></i> Explorar Productos
                    </a>
                </div>
            <?php else: ?>
                <!-- Contenido del Carrito -->
                <div id="cart-content">
                    <div class="row">
                        <!-- Lista de Productos -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Productos en tu carrito</h5>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="clear_cart">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de que deseas vaciar el carrito?')">
                                            <i class="fas fa-trash"></i> Vaciar Carrito
                                        </button>
                                    </form>
                                </div>
                                <div class="card-body">
                                    <div id="cart-items">
                                        <table class="table table-striped cart-table">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Precio</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                    <th>Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cart_summary['items'] as $item): ?>
                                                    <tr data-cart-item-id="<?php echo $item['cart_item_id']; ?>">
                                                        <td data-label="Producto">
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo htmlspecialchars($item['display_image']); ?>"
                                                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                    class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                    <small class="text-muted">
                                                                        <?php
                                                                        $variant_info = [];
                                                                        if (!empty($item['size_name'])) {
                                                                            $variant_info[] = 'Talla: ' . $item['size_name'];
                                                                        }
                                                                        if (!empty($item['color_name'])) {
                                                                            $variant_info[] = 'Color: ' . $item['color_name'];
                                                                        }
                                                                        echo !empty($variant_info) ? htmlspecialchars(implode(' • ', $variant_info)) : 'Sin variante';
                                                                        ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center" data-label="Precio">
                                                            <span class="fw-bold item-price">S/ <?php echo number_format($item['final_price'] ?? $item['price'] ?? $item['base_price'], 2); ?></span>
                                                        </td>
                                                        <td class="text-center" data-label="Cantidad">
                                                            <div class="input-group" style="width: 120px; margin: 0 auto;">
                                                                <?php
                                                                // Determinar si es producto mayorista
                                                                $stmt = $db->prepare("SELECT c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
                                                                $stmt->execute([$item['product_id']]);
                                                                $product_info = $stmt->fetch(PDO::FETCH_ASSOC);
                                                                $is_mayorista_cart = $product_info && strpos(strtolower($product_info['category_name']), 'mayorista') !== false;
                                                                $min_cart_qty = $is_mayorista_cart ? 12 : 1;
                                                                $step_cart_qty = $is_mayorista_cart ? 6 : 1;
                                                                ?>
                                                                <input type="number"
                                                                    class="form-control text-center cart-quantity"
                                                                    value="<?php echo $item['quantity']; ?>"
                                                                    min="<?php echo $min_cart_qty; ?>"
                                                                    max="<?php echo $item['size_stock'] ?? $item['stock_quantity'] ?? 999; ?>"
                                                                    step="<?php echo $step_cart_qty; ?>"
                                                                    data-product-id="<?php echo $item['product_id']; ?>"
                                                                    data-is-mayorista="<?php echo $is_mayorista_cart ? '1' : '0'; ?>"
                                                                    data-min-quantity="<?php echo $min_cart_qty; ?>"
                                                                    <?php if ($is_mayorista_cart): ?>title="Mayorista: Mínimo 12 unidades, incrementos de 6" <?php endif; ?>>
                                                            </div>
                                                        </td>
                                                        <td class="text-center" data-label="Subtotal">
                                                            <span class="fw-bold item-subtotal">S/ <?php echo number_format($item['line_total'] ?? ($item['final_price'] ?? $item['price']) * $item['quantity'], 2); ?></span>
                                                        </td>
                                                        <td class="text-center" data-label="Eliminar">
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="decrease_item">
                                                                <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                    onclick="return confirm('¿Eliminar una unidad de este producto del carrito?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen del Pedido -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Resumen del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="cart-subtotal" class="cart-total">S/ <?php echo number_format($cart_summary['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Envío:</span>
                                        <span id="cart-shipping">
                                            <?php if ($cart_summary['shipping'] == 0): ?>
                                                <span class="text-success">Gratis</span>
                                            <?php else: ?>
                                                S/ <?php echo number_format($cart_summary['shipping'], 2); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>IGV (18%):</span>
                                        <span id="cart-tax">S/ <?php echo number_format($cart_summary['tax'], 2); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Total:</strong>
                                        <strong id="cart-total" class="text-primary">S/ <?php echo number_format($cart_summary['total'], 2); ?></strong>
                                    </div>

                                    <?php if ($cart_summary['subtotal'] < 150): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            Agrega S/ <?php echo number_format(150 - $cart_summary['subtotal'], 2); ?> más para envío gratis
                                        </div>
                                    <?php endif; ?>

                                    <!-- Información importante -->
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Importante:</strong> Tienes 4 horas para confirmar el pago vía WhatsApp, de lo contrario la orden se cancelará automáticamente.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="checkout.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-arrow-right me-2"></i> Continuar al Checkout
                                        </a>
                                        <a href="products.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left"></i> Seguir Comprando
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Información Adicional -->
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-shield-alt text-primary"></i>
                                        Compra Segura
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Coordinación directa por WhatsApp
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Delivery en Huancayo - Tambo - Chilca
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Garantía de calidad JK Grupo Textil
                                        </li>
                                        <li>
                                            <i class="fas fa-check text-success me-2"></i>
                                            Atención personalizada
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <script src="assets/js/cart.js"></script>
    <script>
        function updateQuantity(cartItemId, quantity) {
            if (quantity < 1) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_quantity">
                <input type="hidden" name="cart_item_id" value="${cartItemId}">
                <input type="hidden" name="quantity" value="${quantity}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <!-- cart-sync.js se carga desde el footer para evitar inclusiones duplicadas -->

    <script>
        // Actualizar precio del navbar con el total real del carrito al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const cartTotal = <?php echo $cart_summary['total']; ?>;
            const cartTotalElement = document.getElementById('cart-total');
            if (cartTotalElement) {
                cartTotalElement.textContent = cartTotal.toFixed(2);
            }

            // También actualizar el contador
            const cartCount = <?php echo $cart_summary['total_items']; ?>;
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = cartCount;
            }
        });
    </script>
</body>

</html>