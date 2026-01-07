<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = getCurrentUser();
if (!in_array($user['role'], ['manager', 'supplier', 'admin'])) {
    header('Location: /');
    exit;
}

$db = getDB();
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $productId > 0;
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$error = '';

// Determine return URL based on referrer or user role
$ref = $_GET['ref'] ?? $_POST['ref'] ?? '';
if ($ref === 'admin' && $user['role'] === 'admin') {
    $returnUrl = '/admin.php?tab=products';
} else {
    $returnUrl = '/manager.php?tab=products';
}

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get product data if editing
$product = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock_quantity' => 0,
    'unit' => 'pc',
    'category_id' => '',
    'thumbnail' => '',
    'is_active' => 1
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: ' . $returnUrl);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stockQuantity = intval($_POST['stock_quantity'] ?? $_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pc');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Product name is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } else {
        try {
            if ($isEdit) {
                $stmt = $db->prepare("
                    UPDATE products SET
                        name = ?, description = ?, price = ?, stock_quantity = ?,
                        unit = ?, category_id = ?, thumbnail = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $stockQuantity, $unit, $categoryId ?: null, $imageUrl ?: null, $isActive, $productId]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO products (name, description, price, stock_quantity, unit, category_id, thumbnail, is_active, supplier_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)
                ");
                $stmt->execute([$name, $description, $price, $stockQuantity, $unit, $categoryId ?: null, $imageUrl ?: null, $isActive]);
                $productId = $db->lastInsertId();
            }

            // Handle image upload (only if no URL provided)
            if (empty($imageUrl) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tmpName = $_FILES['image']['tmp_name'];
                $targetPath = $uploadDir . $productId . '.jpg';

                $imageInfo = getimagesize($tmpName);
                if ($imageInfo) {
                    switch ($imageInfo[2]) {
                        case IMAGETYPE_JPEG:
                            $img = imagecreatefromjpeg($tmpName);
                            break;
                        case IMAGETYPE_PNG:
                            $img = imagecreatefrompng($tmpName);
                            break;
                        case IMAGETYPE_GIF:
                            $img = imagecreatefromgif($tmpName);
                            break;
                        case IMAGETYPE_WEBP:
                            $img = imagecreatefromwebp($tmpName);
                            break;
                        default:
                            $img = null;
                    }

                    if ($img) {
                        $maxWidth = 800;
                        $maxHeight = 800;
                        $width = imagesx($img);
                        $height = imagesy($img);

                        if ($width > $maxWidth || $height > $maxHeight) {
                            $ratio = min($maxWidth / $width, $maxHeight / $height);
                            $newWidth = intval($width * $ratio);
                            $newHeight = intval($height * $ratio);
                            $resized = imagecreatetruecolor($newWidth, $newHeight);
                            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagedestroy($img);
                            $img = $resized;
                        }

                        imagejpeg($img, $targetPath, 85);
                        imagedestroy($img);
                    }
                }
            }

            header('Location: ' . $returnUrl);
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    $product = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'stock_quantity' => $stockQuantity,
        'unit' => $unit,
        'category_id' => $categoryId,
        'thumbnail' => $imageUrl,
        'is_active' => $isActive
    ];
}

// Determine current image source
$currentImage = '';
if (!empty($product['thumbnail'])) {
    $currentImage = $product['thumbnail'];
} elseif ($isEdit) {
    $localPath = "/images/products/{$productId}.jpg";
    $currentImage = $localPath;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="edit-container">
    <div class="edit-header">
        <h1><?= $pageTitle ?></h1>
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-outline">Back to Products</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="edit-content">
        <form method="POST" enctype="multipart/form-data" class="edit-form">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
            <div class="form-grid">
                <div class="form-main">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01"
                                   value="<?= htmlspecialchars($product['price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock_quantity" min="0"
                                   value="<?= htmlspecialchars($product['stock_quantity'] ?? $product['stock'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label for="unit">Unit</label>
                            <select id="unit" name="unit">
                                <option value="pc" <?= $product['unit'] === 'pc' ? 'selected' : '' ?>>Piece (pc)</option>
                                <option value="kg" <?= $product['unit'] === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                                <option value="m" <?= $product['unit'] === 'm' ? 'selected' : '' ?>>Meter (m)</option>
                                <option value="sqft" <?= $product['unit'] === 'sqft' ? 'selected' : '' ?>>Sq. Feet (sqft)</option>
                                <option value="bag" <?= $product['unit'] === 'bag' ? 'selected' : '' ?>>Bag</option>
                                <option value="ton" <?= $product['unit'] === 'ton' ? 'selected' : '' ?>>Ton</option>
                                <option value="roll" <?= $product['unit'] === 'roll' ? 'selected' : '' ?>>Roll</option>
                                <option value="bucket" <?= $product['unit'] === 'bucket' ? 'selected' : '' ?>>Bucket</option>
                                <option value="can" <?= $product['unit'] === 'can' ? 'selected' : '' ?>>Can</option>
                                <option value="sheet" <?= $product['unit'] === 'sheet' ? 'selected' : '' ?>>Sheet</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= $product['is_active'] ? 'checked' : '' ?>>
                            Product is active (visible on site)
                        </label>
                    </div>
                </div>

                <div class="form-sidebar">
                    <div class="image-upload-box">
                        <label>Product Image</label>
                        <div class="image-preview" id="imagePreview">
                            <?php if ($currentImage): ?>
                            <img src="<?= htmlspecialchars($currentImage) ?>?t=<?= time() ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <div class="no-image" style="display: none;">No image</div>
                            <?php else: ?>
                            <div class="no-image">No image</div>
                            <?php endif; ?>
                        </div>

                        <div class="image-tabs">
                            <button type="button" class="image-tab active" onclick="showImageTab('url')">URL</button>
                            <button type="button" class="image-tab" onclick="showImageTab('upload')">Upload</button>
                        </div>

                        <div id="urlTab" class="image-input-section">
                            <div class="url-input-group">
                                <input type="url" id="image_url" name="image_url"
                                       value="<?= htmlspecialchars($product['thumbnail'] ?? '') ?>"
                                       placeholder="https://example.com/image.jpg">
                                <button type="button" class="btn-preview" onclick="previewUrl()">
                                    <span class="preview-icon">&#128065;</span>
                                    Preview
                                </button>
                            </div>
                            <small>Paste image URL and click Preview</small>
                        </div>

                        <div id="uploadTab" class="image-input-section" style="display: none;">
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewFile(this)">
                            <label for="image" class="btn btn-outline btn-block">Choose File</label>
                            <small>JPG, PNG, GIF or WebP. Max 5MB.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?= $isEdit ? 'Update Product' : 'Create Product' ?>
                </button>
                <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.edit-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
.edit-header { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.95); padding: 25px 30px; border-radius: 12px; margin-bottom: 20px; }
.edit-header h1 { margin: 0; color: #1e3a5f; }
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.edit-content { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 12px; }
.form-grid { display: grid; grid-template-columns: 1fr 300px; gap: 30px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e3a5f; }
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="url"],
.form-group textarea,
.form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; box-sizing: border-box; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #6366f1; outline: none; }
.form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
.form-row.two-col { grid-template-columns: repeat(2, 1fr); }
.checkbox-group label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.checkbox-group input[type="checkbox"] { width: 20px; height: 20px; }

.image-upload-box { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; }
.image-upload-box > label:first-child { display: block; margin-bottom: 15px; font-weight: 600; color: #1e3a5f; }
.image-preview { width: 100%; height: 200px; background: white; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.no-image { color: #94a3b8; font-size: 0.9rem; }

.image-tabs { display: flex; gap: 5px; margin-bottom: 15px; }
.image-tab { flex: 1; padding: 10px; border: 2px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #64748b; transition: all 0.2s; }
.image-tab.active { background: #6366f1; color: white; border-color: #6366f1; }
.image-tab:hover:not(.active) { background: #f1f5f9; }

.image-input-section { margin-top: 15px; }
.image-input-section input[type="file"] { display: none; }
.image-input-section small { display: block; margin-top: 10px; color: #64748b; font-size: 0.8rem; text-align: center; }

.url-input-group { display: flex; gap: 8px; }
.url-input-group input[type="url"] { flex: 1; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: border-color 0.2s; }
.url-input-group input[type="url"]:focus { border-color: #6366f1; outline: none; }
.btn-preview { display: flex; align-items: center; gap: 6px; padding: 10px 16px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.btn-preview:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-preview:active { transform: translateY(0); }
.btn-preview.loading { opacity: 0.7; pointer-events: none; }
.preview-icon { font-size: 1.1rem; }

.image-preview { position: relative; }
.image-preview .loading-spinner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
.image-preview .error-msg { color: #ef4444; font-size: 0.9rem; padding: 20px; text-align: center; }
.image-preview .success-badge { position: absolute; top: 10px; right: 10px; background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

.btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; text-align: center; }
.btn-lg { padding: 14px 28px; font-size: 1rem; }
.btn-block { display: block; width: 100%; }
.btn-primary { background: #6366f1; color: white; }
.btn-primary:hover { background: #4f46e5; }
.btn-outline { background: transparent; border: 2px solid #e2e8f0; color: #475569; }
.btn-outline:hover { background: #f8fafc; }
.form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 30px; border-top: 1px solid #e2e8f0; }

@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .form-sidebar { order: -1; }
}
</style>

<script>
function showImageTab(tab) {
    document.querySelectorAll('.image-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.image-input-section').forEach(s => s.style.display = 'none');

    if (tab === 'url') {
        document.querySelector('.image-tab:first-child').classList.add('active');
        document.getElementById('urlTab').style.display = 'block';
    } else {
        document.querySelector('.image-tab:last-child').classList.add('active');
        document.getElementById('uploadTab').style.display = 'block';
    }
}

function previewUrl() {
    const urlInput = document.getElementById('image_url');
    const url = urlInput.value.trim();
    const preview = document.getElementById('imagePreview');
    const btn = document.querySelector('.btn-preview');

    if (!url) {
        preview.innerHTML = '<div class="error-msg">Please enter a URL</div>';
        return;
    }

    // Show loading state
    btn.classList.add('loading');
    btn.innerHTML = '<span class="preview-icon">&#8987;</span> Loading...';
    preview.innerHTML = '<div class="loading-spinner"></div>';

    // Create image to test URL
    const img = new Image();
    img.onload = function() {
        preview.innerHTML = '<img src="' + url + '"><span class="success-badge">OK</span>';
        btn.classList.remove('loading');
        btn.innerHTML = '<span class="preview-icon">&#128065;</span> Preview';
    };
    img.onerror = function() {
        preview.innerHTML = '<div class="error-msg">Failed to load image.<br>Check the URL.</div>';
        btn.classList.remove('loading');
        btn.innerHTML = '<span class="preview-icon">&#128065;</span> Preview';
    };
    img.src = url;
}

function previewFile(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            preview.innerHTML = '<div class="error-msg">File too large (max 5MB)</div>';
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '"><span class="success-badge">Ready</span>';
        };
        reader.readAsDataURL(file);
        // Clear URL field when file selected
        document.getElementById('image_url').value = '';
    }
}

// Allow Enter key to trigger preview
document.getElementById('image_url').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        previewUrl();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
