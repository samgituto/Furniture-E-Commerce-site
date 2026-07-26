<?php
/**
 * forgot-password.php
 * -----------------------------------------------------------------
 * Step 1 of password reset: customer enters their email, we create a
 * random token, store only its SHA-256 hash in `password_resets`
 * (so a leaked DB doesn't expose usable tokens), and would normally
 * email the raw token as a link to reset-password.php. Since SMTP is
 * not configured in this starter kit, the reset link is shown on
 * screen for development/testing purposes only.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Forgot Password';

$message = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message whether or not the email exists,
    // to avoid leaking which emails are registered.
    $message = 'If an account exists for that email, a reset link has been sent.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)')
            ->execute([$user['id'], $tokenHash, $expires]);

        // In production: email this link via includes/mailer.php instead of displaying it.
        $devLink = BASE_URL . '/reset-password.php?token=' . $token . '&uid=' . $user['id'];
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="col-md-5 mx-auto auth-card p-4">
    <h1 class="h3 mb-4 text-center">Reset Your Password</h1>
    <?php if ($message): ?>
      <div class="alert alert-info"><?= e($message) ?></div>
      <?php if ($devLink): ?>
        <div class="alert alert-warning small">Development mode — no email server configured. Reset link:<br>
          <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-brand w-100">Send Reset Link</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
