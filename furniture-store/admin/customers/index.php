<?php
/**
 * admin/customers/index.php
 * -----------------------------------------------------------------
 * Lists all customers with order counts/total spend, and lets the
 * admin activate/disable an account.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Manage Customers';

if (isset($_GET['toggle'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("UPDATE users SET status = IF(status='active','disabled','active') WHERE id = ? AND role='customer'")
        ->execute([(int) $_GET['toggle']]);
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
        (SELECT COALESCE(SUM(total_amount),0) FROM orders o WHERE o.user_id = u.id AND o.payment_status='paid') AS total_spent
        FROM users u WHERE u.role = 'customer'";
$params = [];
if ($search !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Manage Customers</h1>
<form method="GET" class="mb-3 d-flex gap-2">
  <input type="text" name="search" class="form-control w-auto" placeholder="Search by name or email" value="<?= e($search) ?>">
  <button class="btn btn-outline-secondary" type="submit">Search</button>
</form>

<div class="admin-card p-3">
  <table class="table table-hover">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
      <tr>
        <td><?= e($c['full_name']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><?= e($c['phone']) ?></td>
        <td><?= (int) $c['order_count'] ?></td>
        <td><?= money($c['total_spent']) ?></td>
        <td><a href="?toggle=<?= (int) $c['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> text-decoration-none"><?= e(ucfirst($c['status'])) ?></a></td>
        <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
        <td><a href="<?= BASE_URL ?>/admin/customers/view.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
