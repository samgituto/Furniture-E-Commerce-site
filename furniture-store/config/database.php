<?php
/**
 * config/database.php
 * -----------------------------------------------------------------
 * Creates a single shared PDO connection to the MySQL database.
 * Every other file that needs the database includes this file and
 * then uses the $pdo variable.
 * -----------------------------------------------------------------
 */

// --- Edit these four values to match your hosting/XAMPP setup ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'furniture_store');
define('DB_USER', 'root');
define('DB_PASS', '');
// -------------------------------------------------------------------

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // use real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Never leak raw DB errors to visitors (see coding standards).
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Sorry, something went wrong. Please try again later.');
}
