<?php
$pageTitle = 'Dashboard';
require_once 'admin_header.php';

$db    = getDB();
$today = date('Y-m-d');

// Stats
$totalOrders    = $db->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$todayOrders    = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)='$today'")->fetch_row()[0];
$pendingOrders  = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$totalRevenue   = $db->query("SELECT IFNULL(SUM(total_price),0) FROM orders WHERE payment_status='paid'")->fetch_row()[0];
$todayRevenue   = $db->query("SELECT IFNULL(SUM(total_price),0) FROM orders WHERE DATE(created_at)='$today'")->fetch_row()[0];
$lowStock       = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0")->fetch_row()[0];
$outOfStock     = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 0")->fetch_row()[0];
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];

// Today's delivery schedule grouped by slot
$schedule = $db->query("
    SELECT o.id, o.total_price, o.status, u.name AS customer, u.email,
           ds.slot_label, o.delivery_date, o.notes
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN delivery_slots ds ON o.slot_id = ds.id
    WHERE o.delivery_date = '$today'
    ORDER BY ds.id, o.id
")->fetch_all(MYSQLI_ASSOC);

// Group by slot
$bySlot = [];
foreach ($schedule as $row) {
    $bySlot[$row['slot_label']][] = $row;
}

// Recent orders
$recent = $db->query("
    SELECT o.id, o.total_price, o.status, o.created_at, u.name AS customer
    FROM orders o JOIN users u ON o.user_id=u.id
    ORDER BY o.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);
?>

<h1>📊 Dashboard</h1>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $totalOrders ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $todayOrders ?></div>
        <div class="stat-label">Orders Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pendingOrders ?></div>
        <div class="stat-label">Pending Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--earth);"><?= CURRENCY_SYMBOL . number_format($todayRevenue, CURRENCY_DECIMALS) ?></div>
        <div class="stat-label">Today's Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= CURRENCY_SYMBOL . number_format($totalRevenue, CURRENCY_DECIMALS) ?></div>
        <div class="stat-label">Total Paid Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $totalCustomers ?></div>
        <div class="stat-label">Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--warning);"><?= $lowStock ?></div>
        <div class="stat-label">Low Stock Items</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--danger);"><?= $outOfStock ?></div>
        <div class="stat-label">Out of Stock</div>
    </div>
</div>

<!-- Today's Delivery Schedule -->
<div class="admin-card">
    <h2>🚚 Today's Delivery Schedule — <?= date('D, d M Y') ?></h2>
    <?php if (empty($bySlot)): ?>
        <p style="color:var(--text-light);">No deliveries scheduled for today.</p>
    <?php else: ?>
        <?php foreach ($bySlot as $slotLabel => $slotOrders): ?>
        <div style="margin-bottom:24px;">
            <h3 style="font-size:1rem;color:var(--green-mid);margin-bottom:10px;">
                🕐 <?= htmlspecialchars($slotLabel) ?>
                <span style="font-size:0.8rem;color:var(--text-light);font-weight:400;">
                    (<?= count($slotOrders) ?> order<?= count($slotOrders)>1?'s':'' ?>)
                </span>
            </h3>
            <table class="admin-table">
                <thead>
                    <tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($slotOrders as $o): ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer']) ?><br><small style="color:var(--text-light);"><?= htmlspecialchars($o['email']) ?></small></td>
                        <td><?= CURRENCY_SYMBOL . number_format($o['total_price'], CURRENCY_DECIMALS) ?></td>
                        <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td style="font-size:0.8rem;color:var(--text-mid);"><?= htmlspecialchars($o['notes'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Recent Orders -->
<div class="admin-card">
    <h2>🕘 Recent Orders</h2>
    <table class="admin-table">
        <thead>
            <tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $o): ?>
            <tr>
                <td>#<?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['customer']) ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($o['total_price'], CURRENCY_DECIMALS) ?></td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td style="font-size:0.82rem;"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                <td><a href="orders.php?view=<?= $o['id'] ?>" class="btn-green" style="padding:5px 12px;font-size:0.8rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>
