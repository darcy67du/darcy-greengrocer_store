<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= SITE_URL ?>/index.php" class="logo">
            <span class="logo-leaf">🌿</span>
            <span class="logo-text">Darcy <strong>GreenGrocer</strong></span>
        </a>
        <nav class="main-nav">
            <a href="<?= SITE_URL ?>/index.php">Shop</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/customer/orders.php">My Orders</a>
                <a href="<?= SITE_URL ?>/customer/cart.php" class="cart-link">
                    🛒 Cart
                    <?php
                    $cartCount = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) $cartCount += 1;
                    }
                    if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="<?= SITE_URL ?>/admin/index.php" class="btn-admin">Admin Panel</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php">Login</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn-primary">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main">
<?php
$flash = getFlash();
if ($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>">
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>
