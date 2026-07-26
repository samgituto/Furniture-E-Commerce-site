<?php
/**
 * login.php
 * -----------------------------------------------------------------
 * Shared login form for customers and admins (role is read from the
 * `users` table after authentication, then routed accordingly).
 * Implements login-attempt lockout: 5 failed attempts locks the
 * account for 15 minutes.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Login';

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/customer/dashboard.php'));
    exit;
}

const MAX_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['locked_until'] && $user['locked_until'] > date('Y-m-d H:i:s')) {
        $errors[] = 'Too many failed attempts. Please try again after ' . LOCKOUT_MINUTES . ' minutes.';
    } elseif ($user && $user['status'] === 'disabled') {
        $errors[] = 'This account has been disabled. Contact support.';
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        // Success: reset attempt counter
        $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);
        login_user($user);

        // Merge guest session cart into DB cart for the logged-in customer
        merge_session_cart_to_db($pdo, $user['id']);

        header('Location: ' . ($user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/customer/dashboard.php'));
        exit;
    } else {
        $errors[] = 'Invalid email or password.';
        if ($user) {
            $attempts = $user['failed_login_attempts'] + 1;
            $lockUntil = null;
            if ($attempts >= MAX_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'));
                $errors[] = 'Account locked after too many failed attempts.';
            }
            $pdo->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?')
                ->execute([$attempts, $lockUntil, $user['id']]);
        }
    }
}

/** Merge the guest session cart ([product_id => qty]) into cart_items on login. */
function merge_session_cart_to_db(PDO $pdo, int $userId): void
{
    $cart = get_cart();
    if (!$cart) return;
    $stmt = $pdo->prepare(
        'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );
    foreach ($cart as $productId => $qty) {
        $stmt->execute([$userId, $productId, $qty]);
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="col-md-5 mx-auto auth-card p-4">
    <h1 class="h3 mb-4 text-center">Login</h1>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST" novalidate>
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" required>
          <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password"><i class="fa-solid fa-eye"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-between small mb-3">
        <a href="<?= BASE_URL ?>/forgot-password.php">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-brand w-100">Login</button>
    </form>
    <p class="text-center mt-3 small">Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
