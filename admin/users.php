<?php
$pageTitle = 'Customers';
require_once 'admin_header.php';

$db = getDB();
$users = $db->query("
    SELECT u.*, COUNT(o.id) AS order_count, IFNULL(SUM(o.total_price),0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<h1>👤 Customers</h1>

<div class="admin-card">
    <h2>All Users (<?= count($users) ?>)</h2>
    <table class="admin-table">
        <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="badge" style="background:<?= $u['role']==='admin'?'var(--earth-light)':'var(--green-pale)' ?>;color:<?= $u['role']==='admin'?'var(--earth)':'var(--green-dark)' ?>;">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </td>
                <td><?= $u['order_count'] ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($u['total_spent'], CURRENCY_DECIMALS) ?></td>
                <td style="font-size:0.82rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>
