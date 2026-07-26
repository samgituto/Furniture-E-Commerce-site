<?php
/**
 * admin/dashboard.php
 * -----------------------------------------------------------------
 * Admin landing page: key metrics, a monthly sales chart (Chart.js
 * via CDN), recent orders, and best-selling products.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Admin Dashboard';

$totalRevenue  = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$totalOrders   = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers= (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$bestSellers = $pdo->query(
    "SELECT id, title, sales_count, price FROM products ORDER BY sales_count DESC LIMIT 5"
)->fetchAll();

$monthly = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(total_amount) AS total
     FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym"
)->fetchAll();
$chartLabels = array_column($monthly, 'ym');
$chartValues = array_map('floatval', array_column($monthly, 'total'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/sidebar.php';
?>
<div class="admin-content">
  <h1 class="mb-4">Dashboard</h1>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="admin-stat-card"><i class="fa-solid fa-money-bill-wave"></i><h3><?= money($totalRevenue) ?></h3><p>Total Revenue</p></div></div>
    <div class="col-md-3"><div class="admin-stat-card"><i class="fa-solid fa-receipt"></i><h3><?= $totalOrders ?></h3><p>Total Orders</p></div></div>
    <div class="col-md-3"><div class="admin-stat-card"><i class="fa-solid fa-users"></i><h3><?= $totalCustomers ?></h3><p>Total Customers</p></div></div>
    <div class="col-md-3"><div class="admin-stat-card"><i class="fa-solid fa-couch"></i><h3><?= $totalProducts ?></h3><p>Total Products</p></div></div>
  </div>
  <?php if ($pendingOrders > 0): ?>
    <div class="alert alert-warning"><?= $pendingOrders ?> order(s) awaiting processing.
      <a href="<?= BASE_URL ?>/admin/orders/index.php?status=pending">Review now</a>.</div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="admin-card p-3">
        <h5>Monthly Sales (last 6 months)</h5>
        <canvas id="salesChart" height="220"></canvas>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="admin-card p-3">
        <h5>Best-Selling Products</h5>
        <table class="table table-sm">
          <thead><tr><th>Product</th><th>Sold</th></tr></thead>
          <tbody>
          <?php foreach ($bestSellers as $b): ?>
            <tr><td><?= e($b['title']) ?></td><td><?= (int) $b['sales_count'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="admin-card p-3 mt-4">
    <h5>Recent Orders</h5>
    <table class="table table-sm">
      <thead><tr><th>Order #</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= (int) $o['id'] ?>"><?= e($o['order_number']) ?></a></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><span class="badge bg-secondary"><?= e(ucfirst($o['order_status'])) ?></span></td>
          <td><span class="badge bg-<?= $o['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
          <td><?= money($o['total_amount']) ?></td>
          <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Revenue (<?= CURRENCY_SYMBOL ?>)',
      data: <?= json_encode($chartValues) ?>,
      borderColor: '#8b5e3c',
      backgroundColor: 'rgba(139,94,60,0.15)',
      tension: 0.3,
      fill: true
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
