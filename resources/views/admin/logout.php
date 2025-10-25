<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Auth.php';
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->logout();

header('Location: login.php?message=logout_success');
exit();
?>
