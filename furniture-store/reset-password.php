<?php
/**
 * reset-password.php
 * -----------------------------------------------------------------
 * Step 2 of password reset. Validates the token from the emailed
 * link against the stored hash and expiry, then lets the user set a
 * new password.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Set New Password';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$uid   = (int) ($_GET['uid'] ?? ($_POST['uid'] ?? 0));
$tokenHash = hash('sha256', $token);

$errors = [];
$success = false;

$stmt = $pdo->prepare(
    'SELECT * FROM password_resets WHERE user_id = ? AND token_hash = ? AND used = 0 AND expires_at > NOW()
     ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$uid, $tokenHash]);
$reset = $stmt->fetch();

if (!$reset) {
    $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $uid]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);
        $success = true;
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="col-md-5 mx-auto auth-card p-4">
    <h1 class="h3 mb-4 text-center">Set New Password</h1>
    <?php if ($success): ?>
      <div class="alert alert-success">Your password has been updated. <a href="<?= BASE_URL ?>/login.php">Login now</a>.</div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <?php if ($reset): ?>
        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <input type="hidden" name="uid" value="<?= (int) $uid ?>">
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
          </div>
          <button type="submit" class="btn btn-brand w-100">Update Password</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
