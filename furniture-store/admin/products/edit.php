<?php
/**
 * admin/products/edit.php
 * -----------------------------------------------------------------
 * Edits an existing product, reusing the same validated image-upload
 * routine as add.php (loaded via include so we don't duplicate it).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Edit Product';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $sku = trim($_POST['sku'] ?? '');

    if ($title === '') $errors[] = 'Title is required.';
    if ($sku === '') $errors[] = 'SKU is required.';
    if (!is_numeric($_POST['price'] ?? '')) $errors[] = 'A valid price is required.';

    $imagePath = $product['main_image'];
    if (!empty($_FILES['main_image']['name'])) {
        require __DIR__ . '/upload-helper.php';
        $newImage = handle_product_image_upload($_FILES['main_image'], $errors);
        if ($newImage) {
            $imagePath = $newImage;
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id=?, sku=?, title=?, slug=?, short_description=?, full_description=?,
                price=?, discount_price=?, stock_quantity=?, material=?, color=?, dimensions=?, weight_kg=?,
                warranty_months=?, assembly_required=?, main_image=?, status=?, is_featured=? WHERE id=?'
        );
        $stmt->execute([
            (int) $_POST['category_id'], $sku, $title, slugify($title),
            trim($_POST['short_description'] ?? ''), trim($_POST['full_description'] ?? ''),
            (float) $_POST['price'], $_POST['discount_price'] !== '' ? (float) $_POST['discount_price'] : null,
            (int) $_POST['stock_quantity'], trim($_POST['material'] ?? ''), trim($_POST['color'] ?? ''),
            trim($_POST['dimensions'] ?? ''), $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : null,
            (int) ($_POST['warranty_months'] ?? 0), isset($_POST['assembly_required']) ? 1 : 0,
            $imagePath, $_POST['status'] ?? 'active', isset($_POST['is_featured']) ? 1 : 0, $id,
        ]);
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    } else {
        $product = array_merge($product, $_POST);
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Edit Product</h1>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="admin-card p-4">
  <form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <?php include __DIR__ . '/form-fields.php'; ?>
    <button type="submit" class="btn btn-brand mt-4"><i class="fa-solid fa-save"></i> Update Product</button>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary mt-4">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
