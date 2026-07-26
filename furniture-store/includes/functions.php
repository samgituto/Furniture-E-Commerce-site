<?php
/**
 * includes/functions.php
 * -----------------------------------------------------------------
 * Reusable helper functions shared by every page: output escaping,
 * CSRF protection, auth helpers, formatting, and small data lookups.
 * Included automatically by config/config.php.
 * -----------------------------------------------------------------
 */

/* ------------------------- Output / security ------------------------- */

/** Escape a string for safe HTML output (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Generate (or reuse) a CSRF token for the current session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Print a hidden CSRF input field for a <form>. */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify a submitted CSRF token; call at the top of every POST handler. */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

/* ------------------------------ Auth ---------------------------------- */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Redirect guests away from customer-only pages. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Redirect non-admins away from admin pages. */
function require_admin(): void
{
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Log a user in: regenerates the session ID to prevent session fixation. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

/* ---------------------------- Formatting -------------------------------*/

function money(float $amount): string
{
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** Effective selling price for a product (discount price if set). */
function product_price(array $product): float
{
    return !empty($product['discount_price']) && $product['discount_price'] > 0
        ? (float) $product['discount_price']
        : (float) $product['price'];
}

/* ------------------------------ Cart -----------------------------------
 * Cart is kept in the PHP session as [product_id => quantity] so guests
 * can shop without an account. On login, the session cart is merged into
 * the `cart_items` DB table (see includes/auth.php).
 * ------------------------------------------------------------------- */

function get_cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(get_cart());
}

function cart_add(int $productId, int $qty = 1): void
{
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_update_qty(int $productId, int $qty): void
{
    if ($qty <= 0) {
        cart_remove($productId);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/* ----------------------------- Misc ------------------------------------ */

/** Generate a unique, human-readable order number. */
function generate_order_number(): string
{
    return 'FH-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/** Fetch a single website setting from the DB (with fallback default). */
function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM website_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}
