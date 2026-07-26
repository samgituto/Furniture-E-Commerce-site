<?php
/**
 * newsletter-subscribe.php
 * -----------------------------------------------------------------
 * Handles the homepage newsletter signup form.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (email) VALUES (?)
             ON DUPLICATE KEY UPDATE status = "subscribed"'
        );
        $stmt->execute([$email]);
        $_SESSION['flash'] = 'Thanks for subscribing!';
    } else {
        $_SESSION['flash'] = 'Please enter a valid email address.';
    }
}
header('Location: ' . BASE_URL . '/index.php');
exit;
