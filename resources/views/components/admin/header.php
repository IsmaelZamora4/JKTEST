<?php
// header.php - carga navbar y sidebar y verifica sesión de admin
if (session_status() === PHP_SESSION_NONE) session_start();
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';

// verificar sesión simple (ajustar según tu Auth existente)
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin'); exit;
}

$current_user = ['username' => $_SESSION['admin_username'] ?? 'admin'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/public/assets/css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Admin</title>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
