<?php


require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/redbean.php';



$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

$url = preg_replace('/\.php$/', '', $url);

if ($url === '' || $url === 'index') {
    require VIEW_PATH . 'shop/home.php';
    exit;
}

// Puedes mapear rutas a vistas/controladores aquí
$routes = [
    'checkout' => VIEW_PATH . 'shop/checkout.php',
    'cart' => VIEW_PATH . 'shop/cart.php',
    'order-confirmation' => VIEW_PATH . 'shop/order_confirmation.php',
    'pasarela' => VIEW_PATH . 'shop/pasarela.php',
    'payment' => VIEW_PATH . 'shop/payment.php',
    'result' => VIEW_PATH . 'shop/result.php',
    'products' => VIEW_PATH . 'shop/products.php',
    'product' => VIEW_PATH . 'shop/product.php',
    'returns' => VIEW_PATH . 'shop/returns.php',
    'shipping-policies' => VIEW_PATH . 'shop/shipping_policies.php',
    'services' => VIEW_PATH . 'shop/services.php',
    'rate-store' => VIEW_PATH . 'shop/rate_store.php',
    'order-tracking' => VIEW_PATH . 'shop/order_tracking.php',
    'faq' => VIEW_PATH . 'shop/faq.php',
    'contact' => VIEW_PATH . 'shop/contact.php',
    'about' => VIEW_PATH . 'shop/about.php',
    'catalogs' => VIEW_PATH . 'shop/catalogs.php',
    'arma-tu-pack' => VIEW_PATH . 'shop/arma_tu_pack.php',
    'personalizados' => VIEW_PATH . 'shop/personalizados.php',
    'cart_action' => BASE_PATH . 'app/controllers/cart_action.php',
    'admin' => VIEW_PATH . 'admin/login.php',
    'admin/login' => VIEW_PATH . 'admin/login.php',
    'admin/dashboard' => VIEW_PATH . 'admin/dashboard.php',
    'admin/products' => VIEW_PATH . 'admin/products/index.php',
    'admin/products/create' => VIEW_PATH . 'admin/products/create.php',
    'admin/products/edit' => VIEW_PATH . 'admin/products/edit.php',
    'admin/products/delete' => VIEW_PATH . 'admin/products/delete.php',
    'admin/categories' => VIEW_PATH . 'admin/categories.php',
    'admin/orders' => VIEW_PATH . 'admin/orders.php',
    'admin/sizes' => VIEW_PATH . 'admin/sizes.php',
    'admin/colors' => VIEW_PATH . 'admin/colors.php',
    'admin/variants' => VIEW_PATH . 'admin/variants.php',
    'admin/product-sizes' => VIEW_PATH . 'admin/product-sizes.php',
    'admin/logout' => VIEW_PATH . 'admin/logout.php',


    // Agrega más rutas según tu estructura
];

if (array_key_exists($url, $routes)) {
    require $routes[$url];
    exit;
}

// Si no existe la ruta se muestra 404
http_response_code(404);
echo '<h1>404 - Página no encontrada</h1>';


