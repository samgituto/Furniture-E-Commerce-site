<?php
/**
 * admin/orders/view.php
 * -----------------------------------------------------------------
 * Full detail for one order: line items, customer + shipping info,
 * payment record, and a form to update order status (which also
 * writes a row to order_status_history for the delivery timeline).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}
$pageTitle = 'Order ' . $order['order_number'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $newStatus = $_POST['order_status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $validStatuses = ['pending','processing','shipped','delivered','cancelled','refunded'];

    if (in_array($newStatus, $validStatuses, true)) {
        $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute([$newStatus, $id]);
        $pdo->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?,?,?)')
            ->execute([$id, $newStatus, $note ?: null]);

        // Refund also flips payment_status
        if ($newStatus === 'refunded') {
            $pdo->prepare("UPDATE orders SET payment_status='refunded' WHERE id=?")->execute([$id]);
        }
        header('Location: ' . BASE_URL . '/admin/orders/view.php?id=' . $id);
        exit;
    }
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$addrStmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ?');
$addrStmt->execute([$order['shipping_address_id']]);
$address = $addrStmt->fetch();

$payStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC');
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll();

$historyStmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY changed_at DESC');
$historyStmt->execute([$id]);
$history = $historyStmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Order <?= e($order['order_number']) ?></h1>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="admin-card p-3 mb-4">
      <h5>Items</h5>
      <table class="table table-sm">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['product_title']) ?></td><td><?= (int) $it['quantity'] ?></td><td><?= money($it['unit_price']) ?></td><td><?= money($it['line_total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <table class="table table-sm w-auto ms-auto">
        <tr><th>Subtotal</th><td><?= money($order['subtotal']) ?></td></tr>
        <tr><th>Discount</th><td>-<?= money($order['discount_amount']) ?></td></tr>
        <tr><th>Shipping</th><td><?= money($order['shipping_fee']) ?></td></tr>
        <tr class="fw-bold"><th>Total</th><td><?= money($order['total_amount']) ?></td></tr>
      </table>
    </div>

    <div class="admin-card p-3 mb-4">
      <h5>Status Timeline</h5>
      <ul class="list-group">
        <?php foreach ($history as $h): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e(ucfirst($h['status'])) ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></span>
            <span class="text-secondary small"><?= e(date('M j, Y H:i', strtotime($h['changed_at']))) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card p-3 mb-4">
      <h5>Customer</h5>
      <p class="mb-1"><?= e($order['customer_name']) ?></p>
      <p class="mb-1 text-secondary small"><?= e($order['customer_email']) ?></p>
      <p class="mb-1 text-secondary small"><?= e($order['customer_phone']) ?></p>
      <?php if ($address): ?>
        <hr>
        <p class="mb-0 small"><?= e($address['address_line1']) ?><br><?= e($address['city']) ?>, <?= e($address['country']) ?></p>
      <?php endif; ?>
    </div>

    <div class="admin-card p-3 mb-4">
      <h5>Payment</h5>
      <p>Method: <?= e(ucfirst($order['payment_method'])) ?></p>
      <p>Status: <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></p>
      <?php foreach ($payments as $pay): ?>
        <p class="small text-secondary mb-1"><?= e($pay['gateway_reference']) ?> — <?= e(ucfirst($pay['status'])) ?></p>
      <?php endforeach; ?>
    </div>

    <div class="admin-card p-3">
      <h5>Update Status</h5>
      <form method="POST">
        <?php csrf_field(); ?>
        <select name="order_status" class="form-select mb-2">
          <?php foreach (['pending','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea name="note" class="form-control mb-2" rows="2" placeholder="Optional note (e.g. tracking number)"></textarea>
        <button type="submit" class="btn btn-brand w-100">Update Status</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
