<?php
$pageTitle = 'Orders';
require_once 'admin_header.php';

$db = getDB();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = in_array($_POST['status'], ['pending','confirmed','delivered','cancelled'])
              ? $_POST['status'] : 'pending';
    $pay    = in_array($_POST['payment_status'], ['paid','unpaid'])
              ? $_POST['payment_status'] : 'unpaid';
    $stmt   = $db->prepare("UPDATE orders SET status=?, payment_status=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $pay, $oid);
    $stmt->execute();
    setFlash('success', 'Order #' . $oid . ' updated.');
    redirect(SITE_URL . '/admin/orders.php');
}

// View single order
$viewId = (int)($_GET['view'] ?? 0);
$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $stmt = $db->prepare("SELECT o.*, u.name AS customer, u.email, ds.slot_label FROM orders o JOIN users u ON o.user_id=u.id JOIN delivery_slots ds ON o.slot_id=ds.id WHERE o.id=?");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $viewOrder = $stmt->get_result()->fetch_assoc();

    if ($viewOrder) {
        $stmt2 = $db->prepare("SELECT oi.*, p.name, p.unit_type FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $stmt2->bind_param("i", $viewId);
        $stmt2->execute();
        $viewItems = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Filter
$filterStatus = sanitize($_GET['status'] ?? 'all');
$filterDate   = sanitize($_GET['date'] ?? '');
$where  = [];
$params = [];
$types  = '';
if ($filterStatus !== 'all') { $where[] = 'o.status=?'; $params[] = $filterStatus; $types .= 's'; }
if ($filterDate)             { $where[] = 'o.delivery_date=?'; $params[] = $filterDate; $types .= 's'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql  = "SELECT o.*, u.name AS customer, ds.slot_label FROM orders o JOIN users u ON o.user_id=u.id JOIN delivery_slots ds ON o.slot_id=ds.id $whereSql ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>📦 Orders</h1>

<?php if ($viewOrder): ?>
<!-- Single Order View -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2>Order #<?= $viewOrder['id'] ?> — <?= htmlspecialchars($viewOrder['customer']) ?></h2>
        <a href="orders.php" class="btn-outline">← Back to Orders</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        <div style="background:var(--cream);padding:16px;border-radius:8px;">
            <p><strong>Customer:</strong> <?= htmlspecialchars($viewOrder['customer']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($viewOrder['email']) ?></p>
            <p><strong>Placed:</strong> <?= date('d M Y H:i', strtotime($viewOrder['created_at'])) ?></p>
        </div>
        <div style="background:var(--cream);padding:16px;border-radius:8px;">
            <p><strong>Delivery Date:</strong> <?= date('D, d M Y', strtotime($viewOrder['delivery_date'])) ?></p>
            <p><strong>Time Slot:</strong> <?= htmlspecialchars($viewOrder['slot_label']) ?></p>
            <p><strong>Notes:</strong> <?= htmlspecialchars($viewOrder['notes'] ?: '—') ?></p>
        </div>
    </div>

    <table class="admin-table" style="margin-bottom:20px;">
        <thead><tr><th>Product</th><th>Qty</th><th>Price at Purchase</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($viewItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['unit_type']==='kg' ? number_format($item['quantity'],1).' kg' : (int)$item['quantity'].' unit(s)' ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($item['price_at_purchase'], CURRENCY_DECIMALS) ?></td>
                <td><strong><?= CURRENCY_SYMBOL . number_format($item['price_at_purchase']*$item['quantity'], CURRENCY_DECIMALS) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" style="text-align:right;font-weight:700;">Total:</td>
            <td style="font-weight:700;color:var(--green-mid);"><?= CURRENCY_SYMBOL . number_format($viewOrder['total_price'], CURRENCY_DECIMALS) ?></td></tr>
        </tfoot>
    </table>

    <!-- Update Status -->
    <form method="post" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
        <div class="form-group" style="margin:0;">
            <label>Order Status</label>
            <select name="status">
                <?php foreach (['pending','confirmed','delivered','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewOrder['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label>Payment</label>
            <select name="payment_status">
                <option value="unpaid" <?= $viewOrder['payment_status']==='unpaid'?'selected':'' ?>>Unpaid</option>
                <option value="paid"   <?= $viewOrder['payment_status']==='paid'  ?'selected':'' ?>>Paid</option>
            </select>
        </div>
        <button type="submit" name="update_status" class="btn-green">Update Order</button>
    </form>
</div>

<?php else: ?>
<!-- Orders List -->

<!-- Filters -->
<div class="admin-card" style="padding:16px 24px;">
    <form method="get" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0;">
            <label>Filter by Status</label>
            <select name="status">
                <option value="all"      <?= $filterStatus==='all'       ?'selected':'' ?>>All</option>
                <option value="pending"  <?= $filterStatus==='pending'   ?'selected':'' ?>>Pending</option>
                <option value="confirmed"<?= $filterStatus==='confirmed' ?'selected':'' ?>>Confirmed</option>
                <option value="delivered"<?= $filterStatus==='delivered' ?'selected':'' ?>>Delivered</option>
                <option value="cancelled"<?= $filterStatus==='cancelled' ?'selected':'' ?>>Cancelled</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label>Filter by Delivery Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <button type="submit" class="btn-green">Filter</button>
        <a href="orders.php" class="btn-outline">Reset</a>
    </form>
</div>

<div class="admin-card">
    <h2>Orders (<?= count($orders) ?>)</h2>
    <table class="admin-table">
        <thead>
            <tr><th>#</th><th>Customer</th><th>Total</th><th>Delivery</th><th>Slot</th><th>Status</th><th>Payment</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['customer']) ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($o['total_price'], CURRENCY_DECIMALS) ?></td>
                <td><?= date('d M Y', strtotime($o['delivery_date'])) ?></td>
                <td style="font-size:0.8rem;"><?= htmlspecialchars($o['slot_label']) ?></td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td>
                    <span class="badge" style="background:<?= $o['payment_status']==='paid'?'#d4edda':'#fff3cd' ?>;color:<?= $o['payment_status']==='paid'?'#155724':'#856404' ?>;">
                        <?= ucfirst($o['payment_status']) ?>
                    </span>
                </td>
                <td><a href="orders.php?view=<?= $o['id'] ?>" class="btn-green" style="padding:4px 10px;font-size:0.78rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($orders)): ?>
        <p style="text-align:center;color:var(--text-light);padding:24px;">No orders found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once 'admin_footer.php'; ?>
