<?php
/**
 * customer/change-password.php
 * -----------------------------------------------------------------
 * Lets a logged-in customer change their password after confirming
 * their current one.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'Change Password';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $confirm) $errors[] = 'New passwords do not match.';

    if (!$errors) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $_SESSION['user_id']]);
        $success = 'Password updated successfully.';
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <h1 class="mb-4">Change Password</h1>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="POST" class="col-md-8">
        <?php csrf_field(); ?>
        <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
        <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" minlength="8" required></div>
        <button type="submit" class="btn btn-brand">Update Password</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
