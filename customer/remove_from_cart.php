<?php
require_once '../includes/config.php';

if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$key = $_GET['key'] ?? '';
if ($key && isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
    setFlash('success', 'Item removed from cart.');
}

redirect(SITE_URL . '/customer/cart.php');
?>
