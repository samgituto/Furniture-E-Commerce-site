<?php
/**
 * customer/wishlist.php
 * -----------------------------------------------------------------
 * Shows products the customer has saved to their wishlist.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_login();
$pageTitle = 'My Wishlist';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    verify_csrf();
    $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')
        ->execute([$_SESSION['user_id'], (int) $_POST['remove_id']]);
    header('Location: ' . BASE_URL . '/customer/wishlist.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM wishlist w
     JOIN products p ON p.id = w.product_id
     JOIN categories c ON c.id = p.category_id
     WHERE w.user_id = ? ORDER BY w.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
    <div class="col-lg-9">
      <h1 class="mb-4">My Wishlist</h1>
      <?php if (!$items): ?>
        <p class="text-secondary">Your wishlist is empty. Browse the <a href="<?= BASE_URL ?>/shop.php">shop</a> to add items.</p>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($items as $p): ?>
            <div class="col-md-6 col-lg-4">
              <?php include __DIR__ . '/../includes/product-card.php'; ?>
              <form method="POST" class="mt-2">
                <?php csrf_field(); ?>
                <input type="hidden" name="remove_id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-sm btn-outline-danger w-100" type="submit">Remove from Wishlist</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
