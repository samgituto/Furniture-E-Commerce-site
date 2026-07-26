<?php
/**
 * customer/order-detail.php
 * -----------------------------------------------------------------
 * Shows one order's full detail. Also lets the customer submit a
 * review for any item on a delivered order (verified purchase).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();

$orderId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . '/customer/orders.php');
    exit;
}
$pageTitle = 'Order ' . $order['order_number'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    verify_csrf();
    $productId = (int) $_POST['product_id'];
    $rating = max(1, min(5, (int) $_POST['rating']));
    $comment = trim($_POST['comment'] ?? '');

    if ($order['order_status'] === 'delivered') {
        $stmt = $pdo->prepare(
            'INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status)
             VALUES (?,?,?,?,?, "pending")
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = "pending"'
        );
        $stmt->execute([$productId, $_SESSION['user_id'], $orderId, $rating, $comment]);
        $_SESSION['flash'] = 'Thanks! Your review will appear after admin approval.';
        header('Location: ' . BASE_URL . '/customer/order-detail.php?id=' . $orderId);
        exit;
    }
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-info"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); ?>
      <?php endif; ?>

      <h1 class="mb-3">Order <?= e($order['order_number']) ?></h1>
      <p>Status: <span class="badge bg-secondary"><?= e(ucfirst($order['order_status'])) ?></span>
         &nbsp; Payment: <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></p>

      <table class="table">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><?php if ($order['order_status'] === 'delivered'): ?><th>Review</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['product_title']) ?></td>
            <td><?= (int) $it['quantity'] ?></td>
            <td><?= money($it['unit_price']) ?></td>
            <td><?= money($it['line_total']) ?></td>
            <?php if ($order['order_status'] === 'delivered'): ?>
              <td>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#rev<?= (int) $it['product_id'] ?>">Leave Review</button>
                <div class="collapse mt-2" id="rev<?= (int) $it['product_id'] ?>">
                  <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?= (int) $it['product_id'] ?>">
                    <select name="rating" class="form-select form-select-sm mb-1">
                      <option value="5">5 - Excellent</option>
                      <option value="4">4 - Good</option>
                      <option value="3">3 - Average</option>
                      <option value="2">2 - Poor</option>
                      <option value="1">1 - Terrible</option>
                    </select>
                    <textarea name="comment" class="form-control form-control-sm mb-1" rows="2" placeholder="Share your experience"></textarea>
                    <button type="submit" name="submit_review" class="btn btn-sm btn-brand">Submit</button>
                  </form>
                </div>
              </td>
            <?php endif; ?>
          </tr>
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
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
