<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    setFlash('info', 'Please log in to add items to your cart.');
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/index.php');
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (float)($_POST['quantity'] ?? 0);

if ($product_id <= 0 || $quantity <= 0) {
    setFlash('error', 'Invalid product or quantity.');
    redirect(SITE_URL . '/index.php');
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/index.php');
}

// Validate quantity
if ($product['unit_type'] === 'unit') {
    $quantity = (int)$quantity;
    if ($quantity < 1) $quantity = 1;
} else {
    $quantity = round($quantity, 1);
    if ($quantity < 0.1) $quantity = 0.1;
}

// Check stock
if ($quantity > $product['stock']) {
    setFlash('error', 'Not enough stock available. Only ' . $product['stock'] . ' ' . $product['unit_type'] . ' left.');
    redirect(SITE_URL . '/index.php');
}

// Add to session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$key = 'p_' . $product_id;
if (isset($_SESSION['cart'][$key])) {
    $newQty = $_SESSION['cart'][$key]['quantity'] + $quantity;
    if ($newQty > $product['stock']) {
        setFlash('error', 'Cannot add more — stock limit reached.');
        redirect(SITE_URL . '/index.php');
    }
    $_SESSION['cart'][$key]['quantity'] = $newQty;
} else {
    $_SESSION['cart'][$key] = [
        'product_id' => $product_id,
        'name'       => $product['name'],
        'unit_type'  => $product['unit_type'],
        'quantity'   => $quantity,
    ];
}

setFlash('success', htmlspecialchars($product['name']) . ' added to your cart!');
redirect(SITE_URL . '/index.php');
?>
