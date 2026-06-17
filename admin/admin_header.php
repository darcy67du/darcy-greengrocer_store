<?php require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || !isAdmin()) {
    setFlash('error', 'Admin access required.');
    redirect(SITE_URL . '/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – Admin' : 'Admin – ' . SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <span style="font-size:1.4rem;">🌿</span>
        <span style="font-family:'Playfair Display',serif;color:#d8f3dc;font-size:1rem;display:block;margin-top:4px;">GreenGrocer<br><small style="font-weight:400;opacity:.7;font-family:Inter,sans-serif;font-size:.75rem;">Admin Panel</small></span>
    </div>
    <?php $cur = basename($_SERVER['PHP_SELF']); ?>
    <a href="<?= SITE_URL ?>/admin/index.php"    class="<?= $cur==='index.php'    ?'active':'' ?>">📊 Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/products.php" class="<?= $cur==='products.php' ?'active':'' ?>">🛒 Products</a>
    <a href="<?= SITE_URL ?>/admin/orders.php"   class="<?= $cur==='orders.php'   ?'active':'' ?>">📦 Orders</a>
    <a href="<?= SITE_URL ?>/admin/users.php"    class="<?= $cur==='users.php'    ?'active':'' ?>">👤 Customers</a>
    <a href="<?= SITE_URL ?>/index.php" style="margin-top:auto;">← Back to Shop</a>
    <a href="<?= SITE_URL ?>/logout.php">🚪 Logout</a>
</aside>
<div class="admin-content">
<?php
$flash = getFlash();
if ($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>" style="margin-bottom:24px;border-radius:8px;">
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>
