<?php
/**
 * admin/products/add.php
 * -----------------------------------------------------------------
 * Creates a new product. Validates and stores the uploaded image
 * securely: checks real MIME type (not just extension), generates a
 * unique filename, and saves outside of user-guessable paths.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Add Product';

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$errors = [];
$product = $_POST; // repopulate form on validation error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $slug = slugify($title);

    if ($title === '') $errors[] = 'Title is required.';
    if ($sku === '') $errors[] = 'SKU is required.';
    if (!is_numeric($_POST['price'] ?? '')) $errors[] = 'A valid price is required.';

    require __DIR__ . '/upload-helper.php';
    $imagePath = null;
    if (!empty($_FILES['main_image']['name'])) {
        $imagePath = handle_product_image_upload($_FILES['main_image'], $errors);
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, sku, title, slug, short_description, full_description, price,
                discount_price, stock_quantity, material, color, dimensions, weight_kg, warranty_months,
                assembly_required, main_image, status, is_featured)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            (int) $_POST['category_id'], $sku, $title, $slug,
            trim($_POST['short_description'] ?? ''), trim($_POST['full_description'] ?? ''),
            (float) $_POST['price'], $_POST['discount_price'] !== '' ? (float) $_POST['discount_price'] : null,
            (int) $_POST['stock_quantity'], trim($_POST['material'] ?? ''), trim($_POST['color'] ?? ''),
            trim($_POST['dimensions'] ?? ''), $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : null,
            (int) ($_POST['warranty_months'] ?? 0), isset($_POST['assembly_required']) ? 1 : 0,
            $imagePath, $_POST['status'] ?? 'active', isset($_POST['is_featured']) ? 1 : 0,
        ]);
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Add Product</h1>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="admin-card p-4">
  <form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <?php include __DIR__ . '/form-fields.php'; ?>
    <button type="submit" class="btn btn-brand mt-4"><i class="fa-solid fa-save"></i> Save Product</button>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary mt-4">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
