<?php
/**
 * admin/reviews.php
 * -----------------------------------------------------------------
 * Lets the admin approve, reject, or delete customer product reviews.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Manage Reviews';

if (isset($_GET['approve'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([(int) $_GET['approve']]);
    header('Location: ' . BASE_URL . '/admin/reviews.php');
    exit;
}
if (isset($_GET['reject'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([(int) $_GET['reject']]);
    header('Location: ' . BASE_URL . '/admin/reviews.php');
    exit;
}
if (isset($_GET['delete'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([(int) $_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/reviews.php');
    exit;
}

$filter = $_GET['status'] ?? 'pending';
$sql = "SELECT r.*, u.full_name, p.title AS product_title FROM reviews r
        JOIN users u ON u.id = r.user_id JOIN products p ON p.id = r.product_id";
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/sidebar.php';
?>
<h1 class="mb-4">Manage Reviews</h1>
<div class="mb-3">
  <a href="?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-brand' : 'btn-outline-secondary' ?>">Pending</a>
  <a href="?status=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-brand' : 'btn-outline-secondary' ?>">Approved</a>
  <a href="?status=rejected" class="btn btn-sm <?= $filter === 'rejected' ? 'btn-brand' : 'btn-outline-secondary' ?>">Rejected</a>
  <a href="?status=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
</div>

<div class="admin-card p-3">
  <?php if (!$reviews): ?>
    <p class="text-secondary">No reviews in this category.</p>
  <?php endif; ?>
  <?php foreach ($reviews as $r): ?>
    <div class="border-bottom pb-3 mb-3">
      <div class="d-flex justify-content-between">
        <strong><?= e($r['full_name']) ?></strong>
        <span><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star <?= $i <= $r['rating'] ? 'text-warning' : 'text-secondary' ?>"></i><?php endfor; ?></span>
      </div>
      <p class="small text-secondary mb-1">On: <?= e($r['product_title']) ?></p>
      <p class="mb-2"><?= e($r['comment']) ?></p>
      <span class="badge bg-secondary mb-2"><?= e(ucfirst($r['status'])) ?></span><br>
      <a href="?approve=<?= (int) $r['id'] ?>&csrf=<?= e(csrf_token()) ?>&status=<?= $filter ?>" class="btn btn-sm btn-outline-success">Approve</a>
      <a href="?reject=<?= (int) $r['id'] ?>&csrf=<?= e(csrf_token()) ?>&status=<?= $filter ?>" class="btn btn-sm btn-outline-warning">Reject</a>
      <a href="?delete=<?= (int) $r['id'] ?>&csrf=<?= e(csrf_token()) ?>&status=<?= $filter ?>" class="btn btn-sm btn-outline-danger delete-confirm-link">Delete</a>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
