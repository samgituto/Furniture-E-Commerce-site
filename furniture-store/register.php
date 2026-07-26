<?php
/**
 * register.php
 * -----------------------------------------------------------------
 * New customer account creation. Passwords are hashed with
 * password_hash(); emails are checked for uniqueness server-side.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Create Account';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/customer/dashboard.php');
    exit;
}

$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?,?,?,?,\'customer\')');
        $stmt->execute([$name, $email, $hash, $phone]);

        $newUser = ['id' => (int) $pdo->lastInsertId(), 'full_name' => $name, 'role' => 'customer'];
        login_user($newUser);

        header('Location: ' . BASE_URL . '/customer/dashboard.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="col-md-6 mx-auto auth-card p-4">
    <h1 class="h3 mb-4 text-center">Create Your Account</h1>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST" novalidate>
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($name) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($phone) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" minlength="8" required>
          <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password"><i class="fa-solid fa-eye"></i></button>
        </div>
        <div class="form-text">At least 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-brand w-100">Create Account</button>
    </form>
    <p class="text-center mt-3 small">Already have an account? <a href="<?= BASE_URL ?>/login.php">Login</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
