<?php
/**
 * customer/dashboard.php
 * -----------------------------------------------------------------
 * Logged-in customer's dashboard overview: quick stats + recent orders.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'My Dashboard';

$userId = $_SESSION['user_id'];

$orderCount = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$orderCount->execute([$userId]);
$totalOrders = (int) $orderCount->fetchColumn();

$spentStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id = ? AND payment_status='paid'");
$spentStmt->execute([$userId]);
$totalSpent = (float) $spentStmt->fetchColumn();

$recentStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$recentStmt->execute([$userId]);
$recentOrders = $recentStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <h1 class="mb-4">Welcome back, <?= e($_SESSION['full_name']) ?></h1>
      <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="stat-card"><h3><?= $totalOrders ?></h3><p>Total Orders</p></div></div>
        <div class="col-md-6"><div class="stat-card"><h3><?= money($totalSpent) ?></h3><p>Total Spent</p></div></div>
      </div>

      <h5 class="mb-3">Recent Orders</h5>
      <?php if (!$recentOrders): ?>
        <p class="text-secondary">You haven't placed any orders yet.</p>
      <?php else: ?>
        <table class="table">
          <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/customer/order-detail.php?id=<?= (int) $o['id'] ?>"><?= e($o['order_number']) ?></a></td>
              <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
              <td><span class="badge bg-secondary"><?= e(ucfirst($o['order_status'])) ?></span></td>
              <td><?= money($o['total_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
