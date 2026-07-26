<?php
/**
 * admin/customers/view.php
 * -----------------------------------------------------------------
 * Shows one customer's profile and their full order history.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role='customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}
$pageTitle = $customer['full_name'];

$ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$ordersStmt->execute([$id]);
$orders = $ordersStmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4"><?= e($customer['full_name']) ?></h1>
<div class="admin-card p-3 mb-4">
  <p class="mb-1"><strong>Email:</strong> <?= e($customer['email']) ?></p>
  <p class="mb-1"><strong>Phone:</strong> <?= e($customer['phone']) ?></p>
  <p class="mb-0"><strong>Joined:</strong> <?= e(date('M j, Y', strtotime($customer['created_at']))) ?></p>
</div>

<div class="admin-card p-3">
  <h5>Order History</h5>
  <table class="table table-sm">
    <thead><tr><th>Order #</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= (int) $o['id'] ?>"><?= e($o['order_number']) ?></a></td>
        <td><span class="badge bg-secondary"><?= e(ucfirst($o['order_status'])) ?></span></td>
        <td><span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
        <td><?= money($o['total_amount']) ?></td>
        <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
