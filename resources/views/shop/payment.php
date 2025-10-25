<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once BASE_PATH . "config/keys.php";
require_once BASE_PATH . "config/config.php";
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . "classes/Cart.php";
require_once BASE_PATH . "classes/Order.php";
require_once BASE_PATH . "classes/Category.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (session_status() === PHP_SESSION_NONE) session_start();

    $database = new Database();
    $db = $database->getConnection();

    require_once BASE_PATH . "config/keys.php";
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

    // Inicializar variables para mensajes y token
    $error_message = '';
    $validation_errors = [];
    $formToken = null;
    $cart_summary = null;

    if ($is_custom_checkout) {
        $cart_summary = $_SESSION['custom_cart_summary'];
    } else {
        $cart_summary = $cart->getCheckoutSummary();
        // Verificar si hay productos
        if (empty($cart_summary['items'])) {
            $error_message = 'Tu carrito está vacío. Por favor, agrega productos antes de continuar.';
        } else {
            // Validar stock
            $stock_errors = $cart->validateStock();
            if (!empty($stock_errors)) {
                $error_message = 'Algunos productos no tienen stock suficiente. Por favor, revisa tu carrito.';
            }
        }
    }

    // Procesar POST (si se está enviando el formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
        $customer_data = [
            'first_name'   => sanitize_input($_POST['firstName'] ?? ''),
            'last_name'    => sanitize_input($_POST['lastName'] ?? ''),
            'email'        => sanitize_input($_POST['email'] ?? ''),
            'phone'        => sanitize_input($_POST['phoneNumber'] ?? ''),
            'address'      => sanitize_input($_POST['address'] ?? ''),
            'city'         => sanitize_input($_POST['city'] ?? ''),
            'postal_code'  => sanitize_input($_POST['zipCode'] ?? ''),
            'country'      => sanitize_input($_POST['country'] ?? 'Perú'),
            'notes'        => sanitize_input($_POST['notes'] ?? '')
        ];

        if (empty($customer_data['first_name'])) $validation_errors[] = 'El nombre es requerido';
        if (empty($customer_data['last_name']))  $validation_errors[] = 'El apellido es requerido';
        if (empty($customer_data['phone']))      $validation_errors[] = 'El teléfono es requerido';
        if (empty($customer_data['address']))    $validation_errors[] = 'La dirección es requerida';
        if (empty($customer_data['city']))       $validation_errors[] = 'La ciudad es requerida';
        if (!empty($customer_data['email']) && !filter_var($customer_data['email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = 'Email debe ser válido';
        }

        if (empty($validation_errors)) {
            // Crear orden
            $order_id = $order->create($customer_data, $cart_summary['items'], $cart_summary);
            if ($order_id) {
                $izipay_data = [
                    'orderId' => $order_id,
                    'amount' => $cart_summary['total'] ?? 0,
                    'currency' => 'PEN'
                ];
                $formToken = formToken($izipay_data);
            } else {
                $error_message = 'No se pudo crear la orden. Por favor, intenta nuevamente.';
            }
        }
    }
}
?>







































<!DOCTYPE html>
<html>

<head>
    <title>Form Token</title>
    <link rel='stylesheet' href='css/style.css' />
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/journal/bootstrap.min.css"
        integrity="sha384-QDSPDoVOoSWz2ypaRUidLmLYl4RyoBWI44iA5agn6jHegBxZkNqgm2eHb6yZ5bYs" crossorigin="anonymous" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Scripts y estilos de la pasarela -->
    <script type="text/javascript"
        src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js"
        kr-public-key="<?= PUBLIC_KEY ?>"
        kr-post-url-success="order-confirmation" kr-language="es-Es">
    </script>
    <link rel="stylesheet" href="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.css">
    <script type="text/javascript" src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js">
    </script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">

</head>

<body>


    <?php include COMPONENT_PATH . 'header.php'; ?>

    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0" style="background: var(--light-gray);">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-credit-card fa-3x text-gold mb-2"></i>
                            <h2 class="text-gold">Pago con tarjeta</h2>
                        </div>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-warning text-dark text-center mb-4" style="background: var(--warning-color); border: none;">
                                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error_message) ?>
                                <div class="mt-3">
                                    <a href="/" class="btn btn-lg" style="background: var(--accent-color); color: white;">Volver a la tienda</a>
                                </div>
                            </div>
                        <?php elseif (!empty($validation_errors)): ?>
                            <div class="alert alert-danger text-dark text-center mb-4" style="background: var(--danger-color); border: none;">
                                <i class="fa-solid fa-circle-xmark"></i> Corrige los siguientes errores:<br>
                                <ul class="mt-2" style="list-style:none; padding-left:0;">
                                    <?php foreach ($validation_errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="/cart.php" class="btn btn-lg" style="background: var(--accent-color); color: white;">Volver al carrito</a>
                                </div>
                            </div>
                        <?php elseif (!isset($formToken) || !$formToken): ?>
                            <div class="alert alert-danger text-dark text-center mb-4" style="border: none;">
                                <i class="fa-solid fa-circle-xmark"></i> No se pudo generar el formulario de pago.<br>
                                <span>Por favor, intenta nuevamente o contacta a soporte.</span>
                                <div class="mt-3">
                                    <a href="/cart.php" class="btn btn-lg" style="background: var(--accent-color); color: white;">Volver al carrito</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-dark text-center">Completa los datos para finalizar tu compra de forma segura.</p>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <img src="https://github.com/izipay-pe/Imagenes/blob/main/logo_tarjetas_aceptadas/logo-tarjetas-aceptadas-351x42.png?raw=true" alt="Tarjetas aceptadas" style="width: 220px;">
                            </div>
                            <hr>
                            <div id="micuentawebstd_rest_wrapper">
                                <!-- HTML para incrustar pasarela de pagos -->
                                <div class="kr-embedded" kr-form-token="<?= $formToken; ?>" style="margin: 0 auto;"></div>
                            </div>
                            <hr>
                            <div class="text-center mt-3">
                                <a href="/cart.php" class="btn btn-lg" style="background: var(--accent-color); color: white;">Volver al carrito</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include COMPONENT_PATH . 'footer.php'; ?>

</body>

</html>