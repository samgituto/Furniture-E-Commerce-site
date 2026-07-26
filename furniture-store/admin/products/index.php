<?php
/**
 * admin/products/index.php
 * -----------------------------------------------------------------
 * Lists all products with quick actions to edit, delete, or toggle
 * active/inactive status.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Manage Products';

// Toggle active/inactive
if (isset($_GET['toggle'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("UPDATE products SET status = IF(status='active','inactive','active') WHERE id = ?")
        ->execute([(int) $_GET['toggle']]);
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}

// Delete
if (isset($_GET['delete'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE p.title LIKE ? OR p.sku LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>Manage Products</h1>
  <a href="<?= BASE_URL ?>/admin/products/add.php" class="btn btn-brand"><i class="fa-solid fa-plus"></i> Add Product</a>
</div>

<form method="GET" class="mb-3 d-flex gap-2">
  <input type="text" name="search" class="form-control w-auto" placeholder="Search by title or SKU" value="<?= e($search) ?>">
  <button class="btn btn-outline-secondary" type="submit">Search</button>
</form>

<div class="admin-card p-3">
  <table class="table table-hover align-middle">
    <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><img src="<?= $p['main_image'] ? BASE_URL . '/' . e(ltrim($p['main_image'], '/')) : BASE_URL . '/assets/images/placeholder.png' ?>" width="50" class="rounded"></td>
        <td><?= e($p['title']) ?><br><span class="text-secondary small"><?= e($p['sku']) ?></span></td>
        <td><?= e($p['category_name']) ?></td>
        <td><?= money(product_price($p)) ?></td>
        <td><?= (int) $p['stock_quantity'] ?></td>
        <td>
          <a href="?toggle=<?= (int) $p['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> text-decoration-none">
            <?= e(ucfirst($p['status'])) ?>
          </a>
        </td>
        <td><?= $p['is_featured'] ? '<i class="fa-solid fa-star text-warning"></i>' : '' ?></td>
        <td>
          <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
          <a href="?delete=<?= (int) $p['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="btn btn-sm btn-outline-danger delete-confirm-link"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
