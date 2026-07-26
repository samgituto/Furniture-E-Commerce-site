<?php
/**
 * admin/orders/index.php
 * -----------------------------------------------------------------
 * Lists all orders with a status filter. Links through to view.php
 * for full detail and status updates.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Manage Orders';

$status = $_GET['status'] ?? '';
$sql = 'SELECT * FROM orders';
$params = [];
if ($status !== '') {
    $sql .= ' WHERE order_status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = ['pending','processing','shipped','delivered','cancelled','refunded'];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Manage Orders</h1>
<div class="mb-3">
  <a href="?status=" class="btn btn-sm <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status === $s ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-card p-3">
  <table class="table table-hover">
    <thead><tr><th>Order #</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= e($o['order_number']) ?></td>
        <td><?= e($o['customer_name']) ?></td>
        <td><span class="badge bg-secondary"><?= e(ucfirst($o['order_status'])) ?></span></td>
        <td><span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
        <td><?= money($o['total_amount']) ?></td>
        <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
        <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= (int) $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
