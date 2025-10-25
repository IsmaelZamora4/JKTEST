<?php
require_once BASE_PATH . "config/keys.php";
require_once BASE_PATH . "config/redbean.php";
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . "classes/Cart.php";

// use RedBeanPHP\R;


$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);



// Asegurarte de que la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir el session_id para el carrito
$session_id = session_id();

if (empty($_POST)) {
    throw new Exception("No post data received!");
}

$is_custom_checkout = isset($_GET['mode']) && $_GET['mode'] === 'custom' && isset($_SESSION['custom_cart_summary']);

// Limpiar carrito si no es custom
if (!$is_custom_checkout) {
    $cart->clearCart();

    // R::exec("DELETE FROM cart_items WHERE session_id = ?", [$session_id]);
}

// Eliminar resumen custom si existe
if ($is_custom_checkout && isset($_SESSION['custom_cart_summary'])) {
    unset($_SESSION['custom_cart_summary']);
}

// Validación de firma
if (!checkHash(HMAC_SHA256)) {
    throw new Exception("Invalid signature");
}

$answer = json_decode($_POST["kr-answer"], true);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Resultado de pago</title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/journal/bootstrap.min.css"
        integrity="sha384-QDSPDoVOoSWz2ypaRUidLmLYl4RyoBWI44iA5agn6jHegBxZkNqgm2eHb6yZ5bYs" crossorigin="anonymous" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <script src="assets/js/cart-sync.js"></script>
</head>

<body>

    <?php include COMPONENT_PATH . 'header.php'; ?>

    <?php
    // Determinar si el pago fue exitoso
    $isSuccess = strtolower($answer['orderStatus']) === 'paid' || strtolower($answer['orderStatus']) === 'success';

    // Mapear estado en español
    $estados = [
        'paid' => 'Pagado',
        'success' => 'Pagado',
        'pending' => 'Pendiente',
        'failed' => 'Fallido',
        // agregá más si necesitás
    ];

    // Mapear moneda
    $monedas = [
        'PEN' => 'S/.',
        'USD' => 'US$',
        // agregá más si necesitás
    ];

    // Obtener valores originales (asegurarse que existan)
    $estadoOriginal = strtolower($answer['orderStatus']);
    $monedaOriginal = $answer['orderDetails']['orderCurrency'] ?? '';
    $monto = number_format($answer['orderDetails']['orderTotalAmount'] / 100, 2);
    $pedido = htmlspecialchars($answer['orderDetails']['orderId']);

    // Mostrar con mapeo
    $estadoMostrar = $estados[$estadoOriginal] ?? $answer['orderStatus'];
    $monedaMostrar = $monedas[$monedaOriginal] ?? $monedaOriginal;
    ?>

    <section class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <!-- Logo arriba -->
                <img src="assets/logo.png" alt="Logo de la tienda" style="max-width: 120px; margin-bottom: 20px;" />

                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <?php if ($isSuccess): ?>
                        <div style="color: #c79a3c; font-size: 40px; margin-bottom: 15px;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h2 style="color: #c79a3c;">¡Pago realizado con éxito!</h2>
                    <?php else: ?>
                        <div style="color: #d9534f; font-size: 40px; margin-bottom: 15px;">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <h2 style="color: #d9534f;">El pago no se pudo procesar</h2>
                    <?php endif; ?>

                    <hr style="margin: 20px 0;">

                    <p><strong>Estado:</strong> <?= htmlspecialchars($estadoMostrar) ?></p>
                    <p><strong>Monto:</strong> <?= $monedaMostrar ?> <?= $monto ?></p>
                    <p><strong>Número de pedido:</strong> <?= $pedido ?></p>

                    <hr style="margin: 20px 0;">

                    <?php if ($isSuccess): ?>
                        <p>¡Gracias por tu compra! Hemos recibido tu pedido y lo estamos preparando. Si necesitas algo, no dudes en escribirnos.</p>
                    <?php else: ?>
                        <p>Hubo un problema con tu pago. Por favor, intenta nuevamente o contacta a soporte.</p>
                    <?php endif; ?>

                    <a href="." class="btn btn-gold" style="display: inline-block; margin-top: 25px; background: var(--accent-color); color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; cursor: pointer;">
                        Volver a la tienda
                    </a>

                </div>
            </div>
        </div>
    </section>


    <?php include COMPONENT_PATH . 'footer.php'; ?>

</body>

</html>