<?php

require_once BASE_PATH . "config/config.php";
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . "classes/Cart.php";
require_once BASE_PATH . "classes/Order.php";
require_once BASE_PATH . "classes/Category.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$database = new Database();
$db = $database->getConnection();

$cart = new Cart($db);
$order = new Order($db);
$category = new Category($db);

// Detectar si es un checkout personalizado
$is_custom_checkout = isset($_GET['mode']) && $_GET['mode'] === 'custom' && isset($_SESSION['custom_cart_summary']);

if ($is_custom_checkout) {
    // Cargar resumen desde sesión
    $cart_summary = $_SESSION['custom_cart_summary'];
} else {
    // Obtener resumen desde el carrito normal
    $cart_summary = $cart->getCheckoutSummary();

    // Verificar si hay productos
    if (empty($cart_summary['items'])) {
        header('Location: cart.php?error=empty_cart');
        exit();
    }

    // Validar stock
    $stock_errors = $cart->validateStock();
    if (!empty($stock_errors)) {
        $_SESSION['stock_errors'] = $stock_errors;
        header('Location: cart.php?error=stock_unavailable');
        exit();
    }
}



// Cargar categorías (si las necesitás en la vista)
$categories = $category->getAllWithProductCount();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/journal/bootstrap.min.css"
        integrity="sha384-QDSPDoVOoSWz2ypaRUidLmLYl4RyoBWI44iA5agn6jHegBxZkNqgm2eHb6yZ5bYs" crossorigin="anonymous" />

    <link rel="stylesheet" href="assets/css/checkout_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/js/checkout_form.js" defer></script>
    <script src="assets/js/ubigeo-debug.js" defer></script>
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">

</head>

<body>
    <header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/logo.png" alt="<?php echo APP_NAME; ?>" height="40" class="me-2">
                <span class="fw-bold text-primary"><?php echo APP_NAME; ?></span>
            </a>
            <div class="d-flex align-items-center">
                <a href="<?php echo $is_custom_checkout ? 'personalizados-form.php' : 'cart.php'; ?>" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left"></i> <?php echo $is_custom_checkout ? 'Volver a Personalizados' : 'Volver al Carrito'; ?>
                </a>
            </div>
        </div>
    </header>
    <section class="py-3 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-home me-1 text-primary"></i>Inicio
                        </a>
                    </li>
                    <?php if ($is_custom_checkout): ?>
                        <li class="breadcrumb-item"><a href="personalizados-form.php" class="text-decoration-none text-secondary">Personalizados</a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none text-secondary">Carrito</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active text-primary fw-medium" aria-current="page">Checkout</li>
                </ol>
            </nav>
        </div>
    </section>


    <div class="container my-4">
        <div class="row">
            <!-- Formulario -->
            <div class="col-lg-8 mb-4">
                <div class="form-container">
                    <?php
                    // --- Preparar datos para el formulario de pago (server-side) ---
                    // $cart_summary ya está calculado más arriba en este script
                    $checkoutAmount = number_format($cart_summary['total'], 2, '.', ''); // e.g. "70.00"
                    // Generar order number temporal. Recomendado: crear el pedido en BD y usar su id.
                    $orderNumber = 'ORD' . time();

                    require_once __DIR__ . '/../components/checkout_form.php'; ?>
                </div>
            </div>

            <!-- Resumen del pedido -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <?php echo $is_custom_checkout ? 'Resumen de la Solicitud' : 'Resumen del Pedido'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <?php foreach ($cart_summary['items'] as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                class="me-2"
                                                style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <div class="me-2 d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px; border-radius: 5px; background: #f1f3f5;">
                                                <i class="fa-solid fa-box text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <small class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></small><br>
                                            <small class="text-muted">Cantidad: <?php echo (int)$item['quantity']; ?></small>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-end">
                                        <?php echo $is_custom_checkout ? '—' : 'S/ ' . number_format(($item['price'] ?? 0) * (int)$item['quantity'], 2); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo $is_custom_checkout ? '—' : 'S/ ' . number_format($cart_summary['subtotal'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <span>
                                <?php
                                echo $is_custom_checkout
                                    ? '—'
                                    : (($cart_summary['shipping'] == 0)
                                        ? '<span class="text-success">Gratis</span>'
                                        : 'S/ ' . number_format($cart_summary['shipping'], 2));
                                ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>IGV (18%):</span>
                            <span><?php echo $is_custom_checkout ? '—' : 'S/ ' . number_format($cart_summary['tax'], 2); ?></span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-primary">
                                <?php echo $is_custom_checkout ? 'A cotizar' : 'S/ ' . number_format($cart_summary['total'], 2); ?>
                            </strong>
                        </div>

                        <?php if ($is_custom_checkout && !empty($cart_summary['meta']['custom_details'])): ?>
                            <hr>
                            <details>
                                <summary class="text-muted">Ver detalles personalizados</summary>
                                <pre class="small mt-2" style="white-space: pre-wrap;">
<?php echo htmlspecialchars(json_encode($cart_summary['meta']['custom_details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                            </pre>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <div>
        <?php include COMPONENT_PATH . 'footer.php'; ?>

    </div>
</body>

</html>