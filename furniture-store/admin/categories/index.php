<?php
/**
 * admin/categories/index.php
 * -----------------------------------------------------------------
 * Simple single-page CRUD for product categories: list + inline
 * add form + edit/delete actions.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Manage Categories';

$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($name === '') {
        $errors[] = 'Category name is required.';
    } else {
        $slug = slugify($name);
        if ($id > 0) {
            $pdo->prepare('UPDATE categories SET name=?, slug=?, description=? WHERE id=?')
                ->execute([$name, $slug, $description, $id]);
        } else {
            $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?,?,?)')
                ->execute([$name, $slug, $description]);
        }
        header('Location: ' . BASE_URL . '/admin/categories/index.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

if (isset($_GET['delete'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int) $_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

if (isset($_GET['toggle'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active') WHERE id=?")
        ->execute([(int) $_GET['toggle']]);
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.name"
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Manage Categories</h1>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err): ?><?= e($err) ?><?php endforeach; ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-card p-3">
      <h5><?= $editing ? 'Edit Category' : 'Add Category' ?></h5>
      <form method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($editing['description'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-brand w-100"><?= $editing ? 'Update' : 'Add' ?> Category</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-card p-3">
      <table class="table">
        <thead><tr><th>Name</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><?= e($cat['name']) ?></td>
            <td><?= (int) $cat['product_count'] ?></td>
            <td><a href="?toggle=<?= (int) $cat['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="badge <?= $cat['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> text-decoration-none"><?= e(ucfirst($cat['status'])) ?></a></td>
            <td>
              <a href="?edit=<?= (int) $cat['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <a href="?delete=<?= (int) $cat['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="btn btn-sm btn-outline-danger delete-confirm-link"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
