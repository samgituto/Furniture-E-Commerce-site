<?php
/**
 * config/config.php
 * -----------------------------------------------------------------
 * Site-wide constants and session/error setup. Included at the very
 * top of every public-facing entry file (before any HTML is output),
 * because it starts the PHP session.
 * -----------------------------------------------------------------
 */

// Show errors while developing; turn display_errors OFF in production.
error_reporting(E_ALL);
ini_set('display_errors', '0'); // never show raw errors to visitors
ini_set('log_errors', '1');

// --- Site settings (mirrors the `website_settings` DB table defaults) ---
define('SITE_NAME', 'FurnishHub');
define('SITE_TAGLINE', 'Furniture that fits your life');
define('CURRENCY_SYMBOL', 'KSh');
define('BASE_URL', 'http://localhost/furniture-store'); // change for production

// --- Paths ---
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/images/products/');
define('UPLOAD_URL', BASE_URL . '/assets/images/products/');

// --- Business rules ---
define('SHIPPING_FLAT_FEE', 1500.00);
define('FREE_SHIPPING_THRESHOLD', 50000.00);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_SIZE', 4 * 1024 * 1024); // 4MB

// --- Secure session cookie settings (must run before session_start) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,   // JS cannot read the session cookie
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
