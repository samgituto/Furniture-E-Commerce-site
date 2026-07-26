<?php
/**
 * apply-coupon.php
 * -----------------------------------------------------------------
 * Validates a submitted coupon code against the `coupons` table and,
 * if valid, stores the computed discount amount in the session so
 * cart.php and checkout.php can display/apply it consistently.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}
verify_csrf();

$code = trim($_POST['coupon_code'] ?? '');
$cart = get_cart();

// Recompute subtotal server-side (never trust client totals)
$subtotal = 0;
if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, price, discount_price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $subtotal += product_price($p) * $cart[$p['id']];
    }
}

unset($_SESSION['coupon_code'], $_SESSION['coupon_discount']);

if ($code === '') {
    $_SESSION['flash'] = 'Please enter a coupon code.';
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

$now = date('Y-m-d H:i:s');
$valid = true;
$error = '';

if (!$coupon) {
    $valid = false; $error = 'Invalid coupon code.';
} elseif ($coupon['starts_at'] && $coupon['starts_at'] > $now) {
    $valid = false; $error = 'This coupon is not active yet.';
} elseif ($coupon['expires_at'] && $coupon['expires_at'] < $now) {
    $valid = false; $error = 'This coupon has expired.';
} elseif ($coupon['usage_limit'] !== null && $coupon['times_used'] >= $coupon['usage_limit']) {
    $valid = false; $error = 'This coupon has reached its usage limit.';
} elseif ($subtotal < $coupon['min_order_amount']) {
    $valid = false; $error = 'Order does not meet the minimum amount of ' . money($coupon['min_order_amount']) . ' for this coupon.';
}

// Per-customer usage limit (logged-in customers only)
if ($valid && is_logged_in() && $coupon['usage_limit_per_customer']) {
    $u = $pdo->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?');
    $u->execute([$coupon['id'], $_SESSION['user_id']]);
    if ((int) $u->fetchColumn() >= $coupon['usage_limit_per_customer']) {
        $valid = false; $error = 'You have already used this coupon.';
    }
}

if (!$valid) {
    $_SESSION['flash'] = $error;
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$discount = $coupon['discount_type'] === 'percentage'
    ? $subtotal * ($coupon['discount_value'] / 100)
    : (float) $coupon['discount_value'];

if ($coupon['max_discount_amount'] !== null) {
    $discount = min($discount, (float) $coupon['max_discount_amount']);
}
$discount = min($discount, $subtotal);

$_SESSION['coupon_code'] = $coupon['code'];
$_SESSION['coupon_discount'] = $discount;
$_SESSION['coupon_id'] = $coupon['id'];
$_SESSION['flash'] = 'Coupon applied successfully!';

header('Location: ' . BASE_URL . '/cart.php');
