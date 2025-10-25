<?php
// === Sesiones (activa secure solo si usas HTTPS) ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
session_start();

// === Entorno ===
$IS_PROD = (
  (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'tecnovedadesweb.site') !== false)
  || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production')
);


// === Errores ===
error_reporting(E_ALL);
ini_set('display_errors', $IS_PROD ? '0' : '1');

// === Zona horaria ===
date_default_timezone_set('America/Lima');

// === App ===
define('APP_NAME', 'JK Jackets');
define('APP_VERSION', '1.0.0');
define('BASE_URL', $IS_PROD ? 'https://tecnovedadesweb.site' : 'http://localhost');

// === Archivos ===
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// === Paginación ===
define('PRODUCTS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 10);

// === Helpers ===
function sanitize_input($data)
{
  return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}
function generate_csrf_token()
{
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function verify_csrf_token($token)
{
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function json_response($data, $status_code = 200)
{
  http_response_code($status_code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit();
}
