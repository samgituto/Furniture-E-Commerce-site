<?php
/**
 * cart.php
 * -----------------------------------------------------------------
 * Displays the current session cart with product details pulled
 * fresh from the database (never trust cached prices), lets the
 * customer update quantities, remove items, and apply a coupon.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Your Cart';

$cart = get_cart();
$items = [];
$subtotal = 0;

if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

    foreach ($cart as $pid => $qty) {
        if (!isset($products[$pid])) {
            continue; // product removed/deactivated since being added
        }
        $p = $products[$pid];
        $price = product_price($p);
        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;
        $items[] = ['product' => $p, 'qty' => $qty, 'line_total' => $lineTotal];
    }
}

$discount = (float) ($_SESSION['coupon_discount'] ?? 0);
$couponCode = $_SESSION['coupon_code'] ?? '';
$total = max(0, $subtotal - $discount);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <h1 class="mb-4">Your Shopping Cart</h1>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-info"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <?php if (!$items): ?>
    <div class="empty-state text-center py-5">
      <i class="fa-solid fa-cart-shopping fs-1 text-secondary mb-3"></i>
      <p>Your cart is empty.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div class="row">
      <div class="col-lg-8">
        <table class="table align-middle">
          <thead>
            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($items as $item): $p = $item['product']; ?>
            <tr>
              <td class="d-flex align-items-center gap-2">
                <img src="<?= $p['main_image'] ? BASE_URL . '/' . e(ltrim($p['main_image'], '/')) : BASE_URL . '/assets/images/placeholder.png' ?>" width="60" class="rounded">
                <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>" class="text-dark text-decoration-none"><?= e($p['title']) ?></a>
              </td>
              <td><?= money(product_price($p)) ?></td>
              <td style="max-width:110px">
                <form action="<?= BASE_URL ?>/cart-action.php" method="POST" class="d-flex gap-1">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <input type="number" name="quantity" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $p['stock_quantity'] ?>" class="form-control form-control-sm">
                  <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fa-solid fa-rotate"></i></button>
                </form>
              </td>
              <td><?= money($item['line_total']) ?></td>
              <td>
                <form action="<?= BASE_URL ?>/cart-action.php" method="POST" class="delete-confirm-form">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-secondary">Continue Shopping</a>
      </div>

      <div class="col-lg-4">
        <div class="summary-card p-4">
          <h5 class="mb-3">Order Summary</h5>
          <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <?php if ($discount > 0): ?>
            <div class="d-flex justify-content-between text-success"><span>Discount (<?= e($couponCode) ?>)</span><span>-<?= money($discount) ?></span></div>
          <?php endif; ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?= money($total) ?></span></div>

          <form action="<?= BASE_URL ?>/apply-coupon.php" method="POST" class="mt-3">
            <?php csrf_field(); ?>
            <label class="form-label small">Discount Code</label>
            <div class="input-group">
              <input type="text" name="coupon_code" class="form-control" value="<?= e($couponCode) ?>" placeholder="Enter code">
              <button class="btn btn-outline-dark" type="submit">Apply</button>
            </div>
          </form>

          <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-brand w-100 mt-3">Proceed to Checkout</a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
