<?php
require_once BASE_PATH . "config/keys.php";

if (empty($_POST)) {
    throw new Exception("No post data received!");
    // Limpiar carrito si no es custom
    if (!$is_custom_checkout) {
        $cart->clearCart();
    }

    // Eliminar resumen custom si existe
    if ($is_custom_checkout && isset($_SESSION['custom_cart_summary'])) {
        unset($_SESSION['custom_cart_summary']);
    }
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
</head>

<body>
    <nav class="navbar bg-primary" style="background-color: #FF2D46!important;">
        <div class="container-fluid">
            <a href="/" class="navbar-brand mb-1"><img src="https://iziweb001b.s3.amazonaws.com/webresources/img/logo.png" width="80"></a>
        </div>
    </nav>
    <section class="container">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="center-column col-md-6">
                <section class="result-form">
                    <h2>Resultado de pago:</h2>
                    <hr>
                    <p><strong>Estado:</strong> <?= $answer['orderStatus'] ?></p>
                    <p><strong>Monto:</strong> <?= $answer['orderDetails']["orderCurrency"] ?>. <?= number_format($answer['orderDetails']["orderTotalAmount"] / 100, 2)  ?></p>
                    <p><strong>Order-id:</strong> <?= $answer['orderDetails']["orderId"]  ?></p>
                    <hr>
                    <details open>
                        <summary>
                            <h2>Respuesta recibida del servidor:</h2>
                        </summary>
                        <pre><?php echo json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                    </details>
                    <hr>
                    <details>
                        <summary>
                            <h2>kr-answer:</h2>
                        </summary>
                        <pre><?php echo json_encode($answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                    </details>
                    <hr>
                    <form action="/" method="get">
                        <button class="btn btn-primary">Volver a probar</button>
                    </form>
                </section>
            </div>
            <div class="col-md-3"></div>
        </div>
    </section>
</body>

</html>