<?php
/**
 * cart-action.php
 * -----------------------------------------------------------------
 * Handles all cart mutations (add / remove / update quantity) posted
 * from product cards, the product page, or the cart page itself.
 * Supports both a normal form POST (redirects back) and an AJAX POST
 * (returns JSON) so the same endpoint powers both.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

verify_csrf();

$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

$action    = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity  = max(1, (int) ($_POST['quantity'] ?? 1));

$response = ['success' => false, 'message' => ''];

if ($productId > 0) {
    $stmt = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        $response['message'] = 'Product not found.';
    } else {
        switch ($action) {
            case 'add':
                $current = $_SESSION['cart'][$productId] ?? 0;
                $newQty = min($current + $quantity, max(1, (int) $product['stock_quantity']));
                cart_update_qty($productId, $newQty);
                $response = ['success' => true, 'message' => 'Added to cart.'];
                break;

            case 'remove':
                cart_remove($productId);
                $response = ['success' => true, 'message' => 'Removed from cart.'];
                break;

            case 'update':
                $qty = min($quantity, max(1, (int) $product['stock_quantity']));
                cart_update_qty($productId, $qty);
                $response = ['success' => true, 'message' => 'Cart updated.'];
                break;

            default:
                $response['message'] = 'Unknown action.';
        }
    }
} else {
    $response['message'] = 'Invalid product.';
}

$response['cart_count'] = cart_count();

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Non-AJAX fallback: redirect back with a flash message
$_SESSION['flash'] = $response['message'];

if (!empty($_POST['buy_now'])) {
    header('Location: ' . BASE_URL . '/checkout.php');
} else {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/cart.php'));
}
exit;
