<?php
/**
 * customer/orders.php
 * -----------------------------------------------------------------
 * Lists every order placed by the logged-in customer.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'Order History';

$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <h1 class="mb-4">Order History</h1>
      <?php if (!$orders): ?>
        <div class="empty-state text-center py-5">
          <i class="fa-solid fa-box-open fs-1 text-secondary mb-3"></i>
          <p>You haven't placed any orders yet.</p>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand">Start Shopping</a>
        </div>
      <?php else: ?>
        <table class="table align-middle">
          <thead><tr><th>Order #</th><th>Date</th><th>Order Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
              <td><span class="badge bg-secondary"><?= e(ucfirst($o['order_status'])) ?></span></td>
              <td><span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
              <td><?= money($o['total_amount']) ?></td>
              <td><a href="<?= BASE_URL ?>/customer/order-detail.php?id=<?= (int) $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
