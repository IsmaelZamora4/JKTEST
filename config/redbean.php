<?php
require_once __DIR__ . '/../vendor/autoload.php';
use RedBeanPHP\R;

// Detectar entorno (local o producción)
$isProd = (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'jkgrupotextil.com') !== false)
  || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');

// Configuración de base de datos según entorno
if ($isProd) {
  // Producción (Hestia)
  $host = 'localhost';
  $db   = 'jkgr_bd';
  $user = 'jkgr_usu';
  $pass = 'Tt|OIH(b0zC3@l5o';
} else {
  // Local (XAMPP)
  $host = 'localhost';
  $db   = 'tcnsite_jkj';
  $user = 'root';
  $pass = '';
}

// Conexión con RedBeanPHP
try {
  R::setup("mysql:host=$host;dbname=$db", $user, $pass);
  R::freeze(true); // evita que RedBean modifique la estructura de tablas
} catch (Exception $e) {
  die("Error al conectar con la base de datos: " . $e->getMessage());
}
