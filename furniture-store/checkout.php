<?php
/**
 * checkout.php
 * -----------------------------------------------------------------
 * Collects shipping/contact details and payment method, then on POST
 * creates the order + order_items rows inside a DB transaction,
 * decrements stock, records the coupon usage, and (for the test
 * payment gateway) marks the order paid only after server-side
 * validation. Real gateways (Stripe/PayPal/M-Pesa) would plug into
 * the same `process_payment()` step.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
require_login(); // must be logged in to checkout
$pageTitle = 'Checkout';

$cart = get_cart();
if (!$cart) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// Recompute everything server-side
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

$items = [];
$subtotal = 0;
foreach ($cart as $pid => $qty) {
    if (!isset($products[$pid])) continue;
    $p = $products[$pid];
    $price = product_price($p);
    $lineTotal = $price * $qty;
    $subtotal += $lineTotal;
    $items[] = ['product' => $p, 'qty' => $qty, 'price' => $price, 'line_total' => $lineTotal];
}

$discount = (float) ($_SESSION['coupon_discount'] ?? 0);
$couponId = $_SESSION['coupon_id'] ?? null;
$shippingFee = ($subtotal - $discount) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FLAT_FEE;
$total = max(0, $subtotal - $discount) + $shippingFee;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name    = trim($_POST['customer_name'] ?? '');
    $email   = trim($_POST['customer_email'] ?? '');
    $phone   = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['address_line1'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $method  = $_POST['payment_method'] ?? 'test';

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($address === '' || $city === '') $errors[] = 'Delivery address and city are required.';

    // Re-validate stock is still available for every line
    foreach ($items as $item) {
        if ($item['qty'] > $item['product']['stock_quantity']) {
            $errors[] = $item['product']['title'] . ' no longer has enough stock.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Save shipping address
            $addrStmt = $pdo->prepare(
                'INSERT INTO addresses (user_id, recipient_name, phone, address_line1, city, country, is_default)
                 VALUES (?,?,?,?,?,?,0)'
            );
            $addrStmt->execute([$_SESSION['user_id'], $name, $phone, $address, $city, 'Kenya']);
            $addressId = (int) $pdo->lastInsertId();

            $orderNumber = generate_order_number();
            $orderStmt = $pdo->prepare(
                'INSERT INTO orders (order_number, user_id, shipping_address_id, subtotal, discount_amount,
                    shipping_fee, total_amount, coupon_id, order_status, payment_status, payment_method,
                    customer_name, customer_email, customer_phone)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $orderStmt->execute([
                $orderNumber, $_SESSION['user_id'], $addressId, $subtotal, $discount,
                $shippingFee, $total, $couponId, 'pending', 'unpaid', $method,
                $name, $email, $phone,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_title, unit_price, quantity, line_total)
                 VALUES (?,?,?,?,?,?)'
            );
            $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE id = ?');

            foreach ($items as $item) {
                $itemStmt->execute([$orderId, $item['product']['id'], $item['product']['title'], $item['price'], $item['qty'], $item['line_total']]);
                $stockStmt->execute([$item['qty'], $item['qty'], $item['product']['id']]);
            }

            // --- Payment step (modular: swap this block for Stripe/PayPal/M-Pesa) ---
            $paymentSuccess = process_payment($method, $total, $orderNumber);

            $payStmt = $pdo->prepare(
                'INSERT INTO payments (order_id, gateway, amount, status, gateway_reference) VALUES (?,?,?,?,?)'
            );
            $payStmt->execute([$orderId, $method, $total, $paymentSuccess ? 'success' : 'failed', 'TEST-' . strtoupper(bin2hex(random_bytes(4)))]);

            if ($paymentSuccess) {
                $pdo->prepare("UPDATE orders SET payment_status='paid', order_status='processing' WHERE id=?")->execute([$orderId]);
                $pdo->prepare("INSERT INTO order_status_history (order_id, status, note) VALUES (?, 'processing', 'Payment confirmed')")->execute([$orderId]);
            } else {
                $pdo->prepare("INSERT INTO order_status_history (order_id, status, note) VALUES (?, 'pending', 'Payment failed')")->execute([$orderId]);
            }

            // Record coupon usage
            if ($couponId) {
                $pdo->prepare('INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?,?,?)')->execute([$couponId, $_SESSION['user_id'], $orderId]);
                $pdo->prepare('UPDATE coupons SET times_used = times_used + 1 WHERE id = ?')->execute([$couponId]);
            }

            $pdo->commit();

            // Clear cart & coupon session state
            cart_clear();
            unset($_SESSION['coupon_code'], $_SESSION['coupon_discount'], $_SESSION['coupon_id']);

            header('Location: ' . BASE_URL . '/order-success.php?order=' . urlencode($orderNumber));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Checkout failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

/**
 * process_payment()
 * Modular payment step. Currently only implements a "test" gateway
 * that marks the order paid after basic server-side validation, so
 * the checkout flow can be exercised end-to-end during development.
 * To add Stripe/PayPal/M-Pesa: add a case here that calls the real
 * gateway's API and returns true/false based on its response.
 */
function process_payment(string $method, float $amount, string $orderNumber): bool
{
    switch ($method) {
        case 'test':
            // Server-side validation: reject nonsensical amounts.
            return $amount > 0;
        // case 'mpesa':   return mpesa_charge($amount, $orderNumber);
        // case 'stripe':  return stripe_charge($amount, $orderNumber);
        // case 'paypal':  return paypal_charge($amount, $orderNumber);
        default:
            return false;
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <h1 class="mb-4">Checkout</h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-7">
      <form method="POST" class="needs-validation" novalidate>
        <?php csrf_field(); ?>
        <h5 class="mb-3">Contact & Delivery</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" name="customer_name" class="form-control" value="<?= e($_SESSION['full_name'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="customer_email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" name="customer_phone" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Delivery Address</label>
            <textarea name="address_line1" class="form-control" rows="2" required></textarea>
          </div>
        </div>

        <h5 class="mt-4 mb-3">Payment Method</h5>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="payment_method" value="test" id="payTest" checked>
          <label class="form-check-label" for="payTest">Test Payment (development)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="payment_method" value="mpesa" id="payMpesa" disabled>
          <label class="form-check-label text-secondary" for="payMpesa">M-Pesa (coming soon)</label>
        </div>
        <div class="form-check mb-4">
          <input class="form-check-input" type="radio" name="payment_method" value="stripe" id="payStripe" disabled>
          <label class="form-check-label text-secondary" for="payStripe">Card via Stripe (coming soon)</label>
        </div>

        <button type="submit" class="btn btn-brand btn-lg w-100">Place Order</button>
      </form>
    </div>

    <div class="col-lg-5">
      <div class="summary-card p-4">
        <h5 class="mb-3">Order Summary</h5>
        <?php foreach ($items as $item): ?>
          <div class="d-flex justify-content-between small mb-2">
            <span><?= e($item['product']['title']) ?> &times; <?= (int) $item['qty'] ?></span>
            <span><?= money($item['line_total']) ?></span>
          </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
        <?php if ($discount > 0): ?>
          <div class="d-flex justify-content-between text-success"><span>Discount</span><span>-<?= money($discount) ?></span></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between"><span>Shipping</span><span><?= $shippingFee > 0 ? money($shippingFee) : 'Free' ?></span></div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?= money($total) ?></span></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
