<?php
/**
 * customer/profile.php
 * -----------------------------------------------------------------
 * Lets the customer view and update their profile information.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'Profile Information';

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $user['id']]);
        if ($check->fetch()) {
            $errors[] = 'That email is already used by another account.';
        }
    }

    if (!$errors) {
        $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?')
            ->execute([$name, $email, $phone, $user['id']]);
        $_SESSION['full_name'] = $name;
        $success = 'Profile updated successfully.';
        $user['full_name'] = $name; $user['email'] = $email; $user['phone'] = $phone;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <h1 class="mb-4">Profile Information</h1>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="POST" class="col-md-8">
        <?php csrf_field(); ?>
        <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>"></div>
        <button type="submit" class="btn btn-brand">Save Changes</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
