<?php
/**
 * admin/reports/index.php
 * -----------------------------------------------------------------
 * Sales/product/coupon reports filterable by date range, with a CSV
 * export option (?export=csv) that streams the same filtered data.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Reports';

$type = $_GET['type'] ?? 'sales';
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$rows = [];
$columns = [];

switch ($type) {
    case 'products':
        $columns = ['Product', 'Units Sold', 'Revenue'];
        $stmt = $pdo->prepare(
            "SELECT p.title, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN products p ON p.id = oi.product_id
             WHERE o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY p.id ORDER BY units DESC"
        );
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll();
        break;

    case 'category':
        $columns = ['Category', 'Units Sold', 'Revenue'];
        $stmt = $pdo->prepare(
            "SELECT c.name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             JOIN products p ON p.id = oi.product_id JOIN categories c ON c.id = p.category_id
             WHERE o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY c.id ORDER BY revenue DESC"
        );
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll();
        break;

    case 'coupons':
        $columns = ['Coupon Code', 'Times Used', 'Total Discount Given'];
        $stmt = $pdo->prepare(
            "SELECT co.code, COUNT(cu.id) AS uses, SUM(o.discount_amount) AS total_discount
             FROM coupon_usage cu JOIN coupons co ON co.id = cu.coupon_id JOIN orders o ON o.id = cu.order_id
             WHERE DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY co.id ORDER BY uses DESC"
        );
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll();
        break;

    default: // sales
        $columns = ['Date', 'Orders', 'Revenue'];
        $stmt = $pdo->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS orders, SUM(total_amount) AS revenue
             FROM orders WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY d ORDER BY d DESC"
        );
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll();
}

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $columns);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Reports</h1>

<form method="GET" class="d-flex gap-2 mb-4 flex-wrap align-items-end">
  <div>
    <label class="form-label small">Report Type</label>
    <select name="type" class="form-select">
      <option value="sales" <?= $type === 'sales' ? 'selected' : '' ?>>Daily Sales</option>
      <option value="products" <?= $type === 'products' ? 'selected' : '' ?>>Product Sales</option>
      <option value="category" <?= $type === 'category' ? 'selected' : '' ?>>Category Sales</option>
      <option value="coupons" <?= $type === 'coupons' ? 'selected' : '' ?>>Coupon Usage</option>
    </select>
  </div>
  <div><label class="form-label small">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
  <div><label class="form-label small">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
  <button class="btn btn-brand" type="submit">Filter</button>
  <a class="btn btn-outline-secondary" href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
</form>

<div class="admin-card p-3">
  <table class="table table-sm">
    <thead><tr><?php foreach ($columns as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr><?php foreach ($row as $key => $val): if (is_int($key)) continue; ?><td><?= is_numeric($val) && strpos($key, 'revenue') !== false || strpos($key,'discount') !== false ? money((float) $val) : e((string) $val) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($columns) ?>" class="text-secondary text-center">No data for this range.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
