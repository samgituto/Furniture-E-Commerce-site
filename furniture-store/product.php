<?php
/**
 * product.php
 * -----------------------------------------------------------------
 * Product details page, reached via ?slug=xxx. Shows full details,
 * gallery, related products, and approved customer reviews.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';

$slug = $_GET['slug'] ?? '';
if ($slug === '') {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.id AS cat_id
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.status = 'active'"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="container py-5 text-center"><h2>Product not found</h2>
          <a href="' . BASE_URL . '/shop.php" class="btn btn-brand mt-3">Back to Shop</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// track a simple view count
$pdo->prepare('UPDATE products SET views_count = views_count + 1 WHERE id = ?')->execute([$product['id']]);

$pageTitle = $product['title'];

// Gallery images
$imgStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
$imgStmt->execute([$product['id']]);
$gallery = $imgStmt->fetchAll();

// Approved reviews + average rating
$revStmt = $pdo->prepare(
    "SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC"
);
$revStmt->execute([$product['id']]);
$reviews = $revStmt->fetchAll();
$avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

// Related products (same category)
$relStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id != ? AND p.status='active' LIMIT 4"
);
$relStmt->execute([$product['cat_id'], $product['id']]);
$related = $relStmt->fetchAll();

$hasDiscount = !empty($product['discount_price']) && $product['discount_price'] > 0 && $product['discount_price'] < $product['price'];
$mainImg = !empty($product['main_image']) ? BASE_URL . '/' . ltrim($product['main_image'], '/') : BASE_URL . '/assets/images/placeholder.png';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-6">
      <img src="<?= e($mainImg) ?>" class="img-fluid rounded product-gallery-main" alt="<?= e($product['title']) ?>">
      <?php if ($gallery): ?>
        <div class="d-flex gap-2 mt-3">
          <?php foreach ($gallery as $img): ?>
            <img src="<?= BASE_URL . '/' . e(ltrim($img['image_path'], '/')) ?>" class="product-thumb rounded">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-6">
      <p class="text-uppercase small text-secondary"><?= e($product['category_name']) ?></p>
      <h1 class="h2 fw-bold"><?= e($product['title']) ?></h1>
      <div class="mb-2">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa-solid fa-star <?= $i <= round($avgRating) ? 'text-warning' : 'text-secondary' ?>"></i>
        <?php endfor; ?>
        <span class="small text-secondary">(<?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?>)</span>
      </div>

      <div class="mb-3">
        <span class="fs-3 fw-bold text-brand"><?= money($hasDiscount ? (float) $product['discount_price'] : (float) $product['price']) ?></span>
        <?php if ($hasDiscount): ?>
          <span class="text-decoration-line-through text-secondary ms-2"><?= money((float) $product['price']) ?></span>
        <?php endif; ?>
      </div>

      <p><?= nl2br(e($product['full_description'])) ?></p>

      <table class="table table-sm w-auto">
        <tr><th>Material</th><td><?= e($product['material']) ?></td></tr>
        <tr><th>Color</th><td><?= e($product['color']) ?></td></tr>
        <tr><th>Dimensions</th><td><?= e($product['dimensions']) ?></td></tr>
        <tr><th>Weight</th><td><?= e($product['weight_kg']) ?> kg</td></tr>
        <tr><th>Warranty</th><td><?= (int) $product['warranty_months'] ?> months</td></tr>
        <tr><th>Assembly Required</th><td><?= $product['assembly_required'] ? 'Yes' : 'No' ?></td></tr>
        <tr><th>Availability</th><td><?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' in stock' : '<span class="text-danger">Out of stock</span>' ?></td></tr>
      </table>

      <form action="<?= BASE_URL ?>/cart-action.php" method="POST" class="d-flex gap-2 mt-3">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock_quantity'] ?>" class="form-control" style="max-width:100px" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
        <button type="submit" class="btn btn-brand" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
        <button type="submit" name="buy_now" value="1" class="btn btn-outline-dark" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>Buy Now</button>
      </form>
    </div>
  </div>

  <!-- Reviews -->
  <div class="mt-5">
    <h3 class="mb-3">Customer Reviews</h3>
    <?php if (!$reviews): ?>
      <p class="text-secondary">No reviews yet for this product.</p>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="review-card mb-3">
        <div class="d-flex justify-content-between">
          <strong><?= e($r['full_name']) ?></strong>
          <span><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star <?= $i <= $r['rating'] ? 'text-warning' : 'text-secondary' ?>"></i><?php endfor; ?></span>
        </div>
        <p class="mb-0 small text-secondary"><?= e($r['comment']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Related products -->
  <?php if ($related): ?>
  <div class="mt-5">
    <h3 class="mb-4">Related Products</h3>
    <div class="row g-4">
      <?php foreach ($related as $p): ?>
        <div class="col-md-6 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
