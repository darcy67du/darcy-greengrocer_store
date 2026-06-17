<?php
$pageTitle = 'Products';
require_once 'admin_header.php';

$db = getDB();

// ── Image upload helper ────────────────────────────────────
function uploadProductImage($fileInput) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file      = $_FILES[$fileInput];
    $uploadDir = dirname(__DIR__) . '/assets/images/';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: code ' . $file['error']);
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Image must be under 2MB.');
    }
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    if (!in_array($mimeType, $allowed)) {
        throw new Exception('Only JPG, PNG, GIF or WEBP images are allowed.');
    }
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save the uploaded image.');
    }
    return $filename;
}

// ── DELETE ─────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$pid    = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $pid) {
    $stmt = $db->prepare("SELECT image FROM products WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && !empty($row['image']) && $row['image'] !== 'default.jpg') {
        $imgPath = dirname(__DIR__) . '/assets/images/' . $row['image'];
        if (file_exists($imgPath)) unlink($imgPath);
    }
    $stmt = $db->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    setFlash('success', 'Product deleted.');
    redirect(SITE_URL . '/admin/products.php');
}

// ── TOGGLE ─────────────────────────────────────────────────
if ($action === 'toggle' && $pid) {
    $stmt = $db->prepare("UPDATE products SET is_active = 1 - is_active WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    setFlash('success', 'Product status updated.');
    redirect(SITE_URL . '/admin/products.php');
}

// ── SAVE (add / edit) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (float)($_POST['stock'] ?? 0);
    $unit_type   = in_array($_POST['unit_type'], ['kg','unit']) ? $_POST['unit_type'] : 'unit';
    $category    = sanitize($_POST['category'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($category) || $price <= 0) {
        setFlash('error', 'Please fill in all required fields (name, category, price).');
        redirect(SITE_URL . '/admin/products.php' . ($id ? '?action=edit&id='.$id : ''));
    }

    try {
        $uploadedImage = uploadProductImage('product_image');
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
        redirect(SITE_URL . '/admin/products.php' . ($id ? '?action=edit&id='.$id : ''));
    }

    if ($id > 0) {
        // UPDATE existing product
        if ($uploadedImage) {
            // Remove old image file
            $s2 = $db->prepare("SELECT image FROM products WHERE id=?");
            $s2->bind_param("i", $id);
            $s2->execute();
            $old = $s2->get_result()->fetch_assoc();
            if ($old && !empty($old['image']) && $old['image'] !== 'default.jpg') {
                $oldPath = dirname(__DIR__) . '/assets/images/' . $old['image'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $stmt = $db->prepare("UPDATE products SET name=?,description=?,price=?,stock=?,unit_type=?,category=?,is_active=?,image=? WHERE id=?");
            // correct types: s s d d s s i s i
            $stmt = $db->prepare("UPDATE products SET name=?,description=?,price=?,stock=?,unit_type=?,category=?,is_active=?,image=? WHERE id=?");
            $stmt->bind_param("ssddssisi", $name, $description, $price, $stock, $unit_type, $category, $is_active, $uploadedImage, $id);
        } else {
            $stmt = $db->prepare("UPDATE products SET name=?,description=?,price=?,stock=?,unit_type=?,category=?,is_active=? WHERE id=?");
            $stmt->bind_param("ssddssii", $name, $description, $price, $stock, $unit_type, $category, $is_active, $id);
        }
        $stmt->execute();
        setFlash('success', 'Product updated successfully.');
    } else {
        // INSERT new product
        $imageFile = $uploadedImage ?? 'default.jpg';
        $stmt = $db->prepare("INSERT INTO products (name,description,price,stock,unit_type,category,is_active,image) VALUES (?,?,?,?,?,?,?,?)");
        // types: s s d d s s i s
        $stmt->bind_param("ssddssis", $name, $description, $price, $stock, $unit_type, $category, $is_active, $imageFile);
        $stmt->execute();
        setFlash('success', 'Product added successfully.');
    }
    redirect(SITE_URL . '/admin/products.php');
}

// ── EDIT mode ──────────────────────────────────────────────
$editProduct = null;
if ($action === 'edit' && $pid) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
}

// ── List all products ──────────────────────────────────────
$products = $db->query("SELECT * FROM products ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
?>

<h1>🛒 Products</h1>

<!-- ── Add / Edit Form ── -->
<div class="admin-card">
    <h2><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></h2>

    <form method="post" enctype="multipart/form-data">
        <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" required placeholder="e.g. Organic Tomatoes"
                    value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" required placeholder="e.g. Vegetables"
                    value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>"
                    list="category-suggestions">
                <datalist id="category-suggestions">
                    <?php
                    $cats = $db->query("SELECT DISTINCT category FROM products ORDER BY category");
                    while ($c = $cats->fetch_row()): ?>
                        <option value="<?= htmlspecialchars($c[0]) ?>">
                    <?php endwhile; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label>Price (RWF) *</label>
                <input type="number" name="price" step="1" min="1" required
                    placeholder="e.g. 2500"
                    value="<?= $editProduct['price'] ?? '' ?>">
            </div>

            <div class="form-group">
                <label>Stock *</label>
                <input type="number" name="stock" step="0.001" min="0" required
                    placeholder="e.g. 50"
                    value="<?= $editProduct['stock'] ?? '' ?>">
            </div>

            <div class="form-group">
                <label>Unit Type *</label>
                <select name="unit_type">
                    <option value="unit" <?= ($editProduct['unit_type'] ?? 'unit') === 'unit' ? 'selected' : '' ?>>📦 Per Unit</option>
                    <option value="kg"   <?= ($editProduct['unit_type'] ?? '')      === 'kg'   ? 'selected' : '' ?>>⚖️ Per kg (weight)</option>
                </select>
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:28px;">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    <?= ($editProduct['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label for="is_active" style="margin:0;cursor:pointer;">Active (visible in shop)</label>
            </div>

        </div>

        <!-- ── Photo Upload ── -->
        <div class="form-group" style="margin-top:8px;">
            <label>Product Photo <?= $editProduct ? '<span style="font-weight:400;color:var(--text-light);">(leave empty to keep current)</span>' : '' ?></label>

            <div class="upload-area" id="uploadArea">
                <?php if ($editProduct && !empty($editProduct['image']) && $editProduct['image'] !== 'default.jpg'): ?>
                    <div id="previewWrap" style="margin-bottom:12px;">
                        <img id="imgPreview"
                             src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($editProduct['image']) ?>"
                             alt="Current product image"
                             style="max-height:150px;border-radius:8px;object-fit:cover;border:2px solid var(--green-light);">
                        <p style="font-size:0.78rem;color:var(--text-light);margin-top:6px;">Current image — upload a new one to replace it</p>
                    </div>
                <?php else: ?>
                    <div id="previewWrap" style="display:none;margin-bottom:12px;">
                        <img id="imgPreview" src="" alt="Preview"
                             style="max-height:150px;border-radius:8px;object-fit:cover;border:2px solid var(--green-light);">
                    </div>
                <?php endif; ?>

                <label for="product_image" class="upload-label" onclick="event.stopPropagation();">
                    <span class="upload-icon">📷</span>
                    <span id="uploadText">Click to choose photo or drag &amp; drop here</span>
                    <span style="font-size:0.78rem;color:var(--text-light);display:block;margin-top:4px;">JPG, PNG, WEBP, GIF — max 2MB</span>
                </label>
                <input type="file" id="product_image" name="product_image"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       style="display:none;">
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Describe the product — origin, benefits, usage..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
            <button type="submit" class="btn-green" style="padding:11px 28px;">
                <?= $editProduct ? '💾 Update Product' : '✅ Add Product' ?>
            </button>
            <?php if ($editProduct): ?>
                <a href="products.php" class="btn-outline">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── Products Table ── -->
<div class="admin-card">
    <h2>All Products (<?= count($products) ?>)</h2>

    <?php if (empty($products)): ?>
        <p style="color:var(--text-light);text-align:center;padding:24px;">No products yet. Add one above!</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>#</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p):
                $imgFile = $p['image'] ?? 'default.jpg';
                $hasImg  = $imgFile !== 'default.jpg'
                           && file_exists(dirname(__DIR__) . '/assets/images/' . $imgFile);
            ?>
            <tr>
                <td>
                    <?php if ($hasImg): ?>
                        <img src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($imgFile) ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                    <?php else: ?>
                        <div style="width:50px;height:50px;background:var(--earth-light);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🛒</div>
                    <?php endif; ?>
                </td>
                <td><?= $p['id'] ?></td>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><?= CURRENCY_SYMBOL . number_format($p['price'], CURRENCY_DECIMALS) ?></td>
                <td data-stock="<?= $p['stock'] ?>">
                    <?= number_format($p['stock'], $p['unit_type']==='kg' ? 1 : 0) ?> <?= $p['unit_type'] ?>
                </td>
                <td><?= $p['unit_type'] === 'kg' ? '⚖️ kg' : '📦 unit' ?></td>
                <td>
                    <span class="badge" style="background:<?= $p['is_active'] ? 'var(--green-pale)' : '#eee' ?>;color:<?= $p['is_active'] ? 'var(--green-dark)' : 'var(--text-light)' ?>;">
                        <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <a href="products.php?action=edit&id=<?= $p['id'] ?>"
                       class="btn-green" style="padding:4px 10px;font-size:0.78rem;">Edit</a>
                    <a href="products.php?action=toggle&id=<?= $p['id'] ?>"
                       class="btn-outline" style="padding:4px 10px;font-size:0.78rem;color:var(--text-mid);border-color:var(--border);">
                        <?= $p['is_active'] ? 'Hide' : 'Show' ?>
                    </a>
                    <a href="products.php?action=delete&id=<?= $p['id'] ?>"
                       class="btn-danger" style="padding:4px 10px;font-size:0.78rem;"
                       data-confirm="Delete '<?= htmlspecialchars($p['name']) ?>'? This cannot be undone.">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Upload styles & JS ── -->
<style>
.upload-area {
    border: 2px dashed var(--green-light);
    border-radius: var(--radius);
    padding: 28px 20px;
    text-align: center;
    background: var(--green-pale);
    transition: background 0.2s, border-color 0.2s;
    cursor: pointer;
}
.upload-area:hover,
.upload-area.dragover {
    background: #b7e4c7;
    border-color: var(--green-mid);
}
.upload-label { cursor: pointer; display: block; }
.upload-icon  { font-size: 2.2rem; display: block; margin-bottom: 8px; }
</style>

<script>
(function() {
    const input     = document.getElementById('product_image');
    const area      = document.getElementById('uploadArea');
    const preview   = document.getElementById('imgPreview');
    const previewW  = document.getElementById('previewWrap');
    const uploadTxt = document.getElementById('uploadText');

    // Click area → open picker
    area.addEventListener('click', function(e) {
        if (e.target.tagName !== 'INPUT') input.click();
    });

    // Drag & drop
    area.addEventListener('dragover',  function(e) { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', function()  { area.classList.remove('dragover'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        var file = e.dataTransfer.files[0];
        if (file) { input.files = e.dataTransfer.files; showPreview(file); }
    });

    input.addEventListener('change', function() {
        if (input.files[0]) showPreview(input.files[0]);
    });

    function showPreview(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src       = e.target.result;
            previewW.style.display = 'block';
            uploadTxt.textContent  = '✅ ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB) — click to change';
        };
        reader.readAsDataURL(file);
    }
})();
</script>

<?php require_once 'admin_footer.php'; ?>
