<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    setFlash('info', 'Please log in to place an order.');
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/customer/cart.php');
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    setFlash('error', 'Your cart is empty.');
    redirect(SITE_URL . '/customer/cart.php');
}

$delivery_date = sanitize($_POST['delivery_date'] ?? '');
$slot_id       = (int)($_POST['slot_id'] ?? 0);
$notes         = sanitize($_POST['notes'] ?? '');
$user_id       = $_SESSION['user_id'];

// Validate date
if (empty($delivery_date) || strtotime($delivery_date) < strtotime('tomorrow')) {
    setFlash('error', 'Please choose a valid delivery date (tomorrow or later).');
    redirect(SITE_URL . '/customer/cart.php');
}

if ($slot_id <= 0) {
    setFlash('error', 'Please select a delivery time slot.');
    redirect(SITE_URL . '/customer/cart.php');
}

$db = getDB();

// Check slot overbooking
$slotCheck = $db->prepare("SELECT max_orders FROM delivery_slots WHERE id = ?");
$slotCheck->bind_param("i", $slot_id);
$slotCheck->execute();
$slot = $slotCheck->get_result()->fetch_assoc();
if (!$slot) {
    setFlash('error', 'Invalid delivery slot selected.');
    redirect(SITE_URL . '/customer/cart.php');
}

$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE delivery_date = ? AND slot_id = ?");
$countStmt->bind_param("si", $delivery_date, $slot_id);
$countStmt->execute();
$slotCount = $countStmt->get_result()->fetch_assoc()['cnt'];

if ($slotCount >= $slot['max_orders']) {
    setFlash('error', 'Sorry, this time slot is fully booked. Please choose another.');
    redirect(SITE_URL . '/customer/cart.php');
}

// ── Server-side price calculation (never trust client) ──
$orderItems = [];
$total      = 0;

foreach ($cart as $key => $item) {
    $stmt = $db->prepare("SELECT id, price, stock, unit_type, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        setFlash('error', 'One of your cart items is no longer available.');
        redirect(SITE_URL . '/customer/cart.php');
    }

    $qty = $product['unit_type'] === 'unit' ? (int)$item['quantity'] : round((float)$item['quantity'], 1);

    if ($qty > $product['stock']) {
        setFlash('error', '"' . $product['name'] . '" has insufficient stock. Only ' . $product['stock'] . ' ' . $product['unit_type'] . ' available.');
        redirect(SITE_URL . '/customer/cart.php');
    }

    // Subtotal = DB price × quantity (security enforced server-side)
    $subtotal = $product['price'] * $qty;
    $total   += $subtotal;

    $orderItems[] = [
        'product_id'        => $product['id'],
        'quantity'          => $qty,
        'price_at_purchase' => $product['price'],
        'stock'             => $product['stock'],
    ];
}

// ── Begin transaction ──
$db->begin_transaction();

try {
    // Insert order
    $ins = $db->prepare("INSERT INTO orders (user_id, total_price, delivery_date, slot_id, notes) VALUES (?,?,?,?,?)");
    $ins->bind_param("idssi", $user_id, $total, $delivery_date, $slot_id, $notes);
    $ins->execute();
    $order_id = $db->insert_id;

    // Insert order items & deduct stock
    foreach ($orderItems as $oi) {
        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?,?,?,?)");
        $itemStmt->bind_param("iidd", $order_id, $oi['product_id'], $oi['quantity'], $oi['price_at_purchase']);
        $itemStmt->execute();

        $newStock = $oi['stock'] - $oi['quantity'];
        $updStmt  = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $updStmt->bind_param("di", $newStock, $oi['product_id']);
        $updStmt->execute();
    }

    $db->commit();

    // Clear cart
    $_SESSION['cart'] = [];
    setFlash('success', '🎉 Order #' . $order_id . ' placed! Your delivery is scheduled for ' . date('D, d M Y', strtotime($delivery_date)) . '.');
    redirect(SITE_URL . '/customer/orders.php');

} catch (Exception $e) {
    $db->rollback();
    setFlash('error', 'Order failed. Please try again.');
    redirect(SITE_URL . '/customer/cart.php');
}
?>
