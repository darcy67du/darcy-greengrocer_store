<?php
$pageTitle = 'My Cart';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    setFlash('info', 'Please log in to view your cart.');
    redirect(SITE_URL . '/login.php');
}

$db = getDB();
$cart = $_SESSION['cart'] ?? [];

// Fetch live prices from DB (security: never trust session prices)
$cartItems = [];
$total = 0;

foreach ($cart as $key => $item) {
    $stmt = $db->prepare("SELECT id, name, price, stock, unit_type FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $subtotal = $product['price'] * $item['quantity'];
        $total += $subtotal;
        $cartItems[$key] = [
            'key'       => $key,
            'product'   => $product,
            'quantity'  => $item['quantity'],
            'subtotal'  => $subtotal,
        ];
    }
}

// Fetch delivery slots
$slots = $db->query("SELECT * FROM delivery_slots")->fetch_all(MYSQLI_ASSOC);
?>

<section class="cart-section container">
    <h2 class="section-title">🛒 Your Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <p>Your cart is empty.</p>
            <a href="<?= SITE_URL ?>/index.php" class="btn-green mt-2" style="display:inline-block;margin-top:16px;">Browse Products</a>
        </div>
    <?php else: ?>

    <table class="cart-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Remove</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $ci): $p = $ci['product']; ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><?= CURRENCY_SYMBOL . number_format($p['price'], CURRENCY_DECIMALS) ?> / <?= $p['unit_type'] ?></td>
                <td>
                    <?= $p['unit_type'] === 'kg'
                        ? number_format($ci['quantity'], 1) . ' kg'
                        : (int)$ci['quantity'] . ' unit(s)' ?>
                </td>
                <td><strong><?= CURRENCY_SYMBOL . number_format($ci['subtotal'], CURRENCY_DECIMALS) ?></strong></td>
                <td>
                    <a href="remove_from_cart.php?key=<?= urlencode($ci['key']) ?>"
                       class="btn-danger" style="padding:5px 12px;font-size:0.8rem;"
                       onclick="return confirm('Remove this item?')">Remove</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;font-weight:700;">Total:</td>
                <td><strong style="color:var(--green-mid);font-size:1.1rem;"><?= CURRENCY_SYMBOL . number_format($total, CURRENCY_DECIMALS) ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Checkout / Delivery Selection -->
    <div class="cart-summary">
        <h3>📅 Select Delivery Window</h3>
        <form method="post" action="checkout.php">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div class="form-group">
                    <label for="delivery_date">Delivery Date</label>
                    <input type="date" id="delivery_date" name="delivery_date" required>
                </div>
                <div class="form-group">
                    <label for="slot_id">Time Slot</label>
                    <select id="slot_id" name="slot_id" required>
                        <option value="">-- Choose a slot --</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?= $slot['id'] ?>"><?= htmlspecialchars($slot['slot_label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="notes">Delivery Notes (optional)</label>
                <textarea id="notes" name="notes" placeholder="e.g. Leave at the door, ring bell..."></textarea>
            </div>
            <div style="display:flex;gap:12px;align-items:center;">
                <button type="submit" class="btn-primary" style="padding:12px 32px;font-size:1rem;">
                    ✅ Place Order — <?= CURRENCY_SYMBOL . number_format($total, CURRENCY_DECIMALS) ?>
                </button>
                <a href="<?= SITE_URL ?>/index.php" class="btn-outline">Continue Shopping</a>
            </div>
        </form>
    </div>

    <?php endif; ?>
</section>

<?php require_once '../includes/footer.php'; ?>
