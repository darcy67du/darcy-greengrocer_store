<?php
$pageTitle = 'Shop';
require_once 'includes/header.php';

$db = getDB();

// Get categories
$cats = $db->query("SELECT DISTINCT category FROM products WHERE is_active=1 ORDER BY category");
$categories = [];
while ($r = $cats->fetch_assoc()) $categories[] = $r['category'];

// Get products
$result = $db->query("SELECT * FROM products WHERE is_active=1 ORDER BY category, name");
$products = [];
while ($r = $result->fetch_assoc()) $products[] = $r;

// Emoji map for categories
$emojis = [
    'Vegetables' => '🥦',
    'Fruits'     => '🍎',
    'Dairy & Eggs'=> '🥚',
    'Grains'     => '🌾',
    'Eco Products'=> '🌿',
];
?>

<section class="hero">
    <div class="container">
        <h1>Fresh from <span>Local Farms</span></h1>
        <p>Organic groceries & eco-friendly products delivered to your door — chosen with care for people and planet.</p>
        <a href="#shop" class="btn-primary" style="padding:12px 32px;font-size:1rem;">Start Shopping</a>
    </div>
</section>

<section class="shop-section container" id="shop">
    <h2 class="section-title">Our Products</h2>
    <p class="section-sub">Fractional weights for fresh produce, whole units for packaged goods.</p>

    <div class="filter-bar">
        <button class="filter-btn active" data-category="all">All</button>
        <?php foreach ($categories as $cat): ?>
            <button class="filter-btn" data-category="<?= htmlspecialchars($cat) ?>">
                <?= isset($emojis[$cat]) ? $emojis[$cat] . ' ' : '' ?><?= htmlspecialchars($cat) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="products-grid">
        <?php foreach ($products as $p):
            $emoji = $emojis[$p['category']] ?? '🛒';
            $isKg  = $p['unit_type'] === 'kg';
            $low   = ($p['stock'] > 0 && $p['stock'] <= ($isKg ? 3 : 5));
            $out   = $p['stock'] <= 0;
        ?>
        <div class="product-card" data-category="<?= htmlspecialchars($p['category']) ?>">
            <div class="product-img-wrap">
                <?php if (file_exists("assets/images/" . $p['image'])): ?>
                    <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                    <span><?= $emoji ?></span>
                <?php endif; ?>
            </div>
            <div class="product-body">
                <span class="product-category"><?= htmlspecialchars($p['category']) ?></span>
                <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>
                <?php if ($low): ?>
                    <span class="product-stock-low">⚠ Only <?= number_format($p['stock'], $isKg ? 1 : 0) ?> <?= $isKg ? 'kg' : 'units' ?> left</span>
                <?php endif; ?>
            </div>
            <div class="product-footer">
                <div>
                    <span class="product-price"><?= CURRENCY_SYMBOL . number_format($p['price'], CURRENCY_DECIMALS) ?></span>
                    <span class="product-unit">/ <?= $p['unit_type'] === 'kg' ? 'kg' : 'unit' ?></span>
                </div>
                <?php if ($out): ?>
                    <span class="sold-out-badge">Sold Out</span>
                <?php else: ?>
                    <form method="post" action="customer/add_to_cart.php" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <div class="qty-control">
                            <button type="button" data-action="minus">−</button>
                            <input type="number"
                                name="quantity"
                                value="<?= $isKg ? '0.5' : '1' ?>"
                                min="<?= $isKg ? '0.1' : '1' ?>"
                                max="<?= min($p['stock'], 99) ?>"
                                step="<?= $isKg ? '0.1' : '1' ?>"
                            >
                            <button type="button" data-action="plus">+</button>
                        </div>
                        <button type="submit" class="btn-green" style="padding:7px 14px;border-radius:6px;">
                            Add
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <p>No products available right now. Check back soon!</p>
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
