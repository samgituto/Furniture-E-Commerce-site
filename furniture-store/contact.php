<?php
/**
 * contact.php
 * -----------------------------------------------------------------
 * Public contact form. Saves messages to `contact_messages` for the
 * admin to review under admin/messages (see admin dashboard).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Contact Us';
$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($message === '') $errors[] = 'Message cannot be empty.';

    if (!$errors) {
        $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)')
            ->execute([$name, $email, $subject, $message]);
        $sent = true;
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <h1 class="mb-4 text-center">Contact Us</h1>
  <div class="col-md-6 mx-auto">
    <?php if ($sent): ?>
      <div class="alert alert-success">Thanks for reaching out! We'll get back to you shortly.</div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Message</label><textarea name="message" rows="5" class="form-control" required></textarea></div>
        <button type="submit" class="btn btn-brand w-100">Send Message</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
