<?php
$pageTitle = 'My Orders';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    setFlash('info', 'Please log in to view your orders.');
    redirect(SITE_URL . '/login.php');
}

$db      = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT o.*, ds.slot_label
    FROM orders o
    JOIN delivery_slots ds ON o.slot_id = ds.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<section class="orders-section container">
    <h2 class="section-title">📦 My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <p>No orders yet.</p>
            <a href="<?= SITE_URL ?>/index.php" class="btn-green" style="display:inline-block;margin-top:16px;">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $stmt2 = $db->prepare("
                SELECT oi.*, p.name, p.unit_type
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt2->bind_param("i", $order['id']);
            $stmt2->execute();
            $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="order-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
                <div>
                    <h3>Order #<?= $order['id'] ?></h3>
                    <p style="color:var(--text-light);font-size:0.85rem;">
                        Placed: <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                    </p>
                </div>
                <div style="text-align:right;">
                    <span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    <p style="margin-top:6px;font-weight:700;color:var(--green-mid);"><?= CURRENCY_SYMBOL . number_format($order['total_price'], CURRENCY_DECIMALS) ?></p>
                </div>
            </div>

            <div style="background:var(--cream);border-radius:8px;padding:14px;margin-bottom:14px;">
                <p style="font-size:0.85rem;color:var(--text-mid);">
                    📅 <strong>Delivery:</strong> <?= date('D, d M Y', strtotime($order['delivery_date'])) ?>
                    &nbsp;|&nbsp; 🕐 <strong><?= htmlspecialchars($order['slot_label']) ?></strong>
                </p>
                <?php if ($order['notes']): ?>
                    <p style="font-size:0.82rem;color:var(--text-light);margin-top:4px;">📝 <?= htmlspecialchars($order['notes']) ?></p>
                <?php endif; ?>
            </div>

            <table style="width:100%;font-size:0.85rem;border-collapse:collapse;">
                <thead>
                    <tr style="color:var(--text-light);text-align:left;">
                        <th style="padding:6px 0;border-bottom:1px solid var(--border);">Item</th>
                        <th style="padding:6px 0;border-bottom:1px solid var(--border);">Qty</th>
                        <th style="padding:6px 0;border-bottom:1px solid var(--border);">Price Paid</th>
                        <th style="padding:6px 0;border-bottom:1px solid var(--border);">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="padding:7px 0;"><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['unit_type'] === 'kg' ? number_format($item['quantity'],1).' kg' : (int)$item['quantity'].' unit(s)' ?></td>
                        <td><?= CURRENCY_SYMBOL . number_format($item['price_at_purchase'], CURRENCY_DECIMALS) ?></td>
                        <td><strong><?= CURRENCY_SYMBOL . number_format($item['price_at_purchase'] * $item['quantity'], CURRENCY_DECIMALS) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once '../includes/footer.php'; ?>
