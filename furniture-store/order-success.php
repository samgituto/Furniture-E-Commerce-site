<?php
/**
 * order-success.php
 * -----------------------------------------------------------------
 * Confirmation page shown after checkout. Looks up the order by its
 * order_number and verifies it belongs to the logged-in customer
 * before displaying it.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'Order Confirmed';

$orderNumber = $_GET['order'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ?');
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . '/customer/dashboard.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5 text-center">
  <i class="fa-solid fa-circle-check text-success" style="font-size:4rem"></i>
  <h1 class="mt-3">Thank you, your order is confirmed!</h1>
  <p class="text-secondary">Order number: <strong><?= e($order['order_number']) ?></strong></p>
  <p>Payment status: <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></p>

  <div class="col-md-6 mx-auto mt-4 text-start">
    <table class="table">
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['product_title']) ?> &times; <?= (int) $it['quantity'] ?></td><td class="text-end"><?= money($it['line_total']) ?></td></tr>
      <?php endforeach; ?>
      <tr class="fw-bold"><td>Total</td><td class="text-end"><?= money($order['total_amount']) ?></td></tr>
    </table>
  </div>

  <a href="<?= BASE_URL ?>/customer/orders.php" class="btn btn-brand mt-3">View My Orders</a>
  <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-secondary mt-3">Continue Shopping</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
