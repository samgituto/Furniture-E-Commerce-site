<?php
/**
 * includes/product-card.php
 * -----------------------------------------------------------------
 * Reusable product card partial. Expects a $p array (one row from
 * the `products` table) to already be set by the including page.
 * Used by index.php, shop.php, and product.php (related products).
 * -----------------------------------------------------------------
 */
$hasDiscount = !empty($p['discount_price']) && $p['discount_price'] > 0 && $p['discount_price'] < $p['price'];
$displayPrice = $hasDiscount ? $p['discount_price'] : $p['price'];
$img = !empty($p['main_image']) ? BASE_URL . '/' . ltrim($p['main_image'], '/') : BASE_URL . '/assets/images/placeholder.png';
?>
<div class="card product-card h-100">
  <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>">
    <img src="<?= e($img) ?>" class="card-img-top product-card-img" alt="<?= e($p['title']) ?>" loading="lazy">
  </a>
  <div class="card-body d-flex flex-column">
    <p class="text-uppercase small text-secondary mb-1"><?= e($p['category_name'] ?? '') ?></p>
    <h6 class="card-title mb-1">
      <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>" class="text-dark text-decoration-none"><?= e($p['title']) ?></a>
    </h6>
    <p class="small text-secondary flex-grow-1"><?= e($p['short_description']) ?></p>
    <div class="mb-2">
      <span class="fw-bold text-brand"><?= money((float) $displayPrice) ?></span>
      <?php if ($hasDiscount): ?>
        <span class="text-decoration-line-through text-secondary small ms-2"><?= money((float) $p['price']) ?></span>
      <?php endif; ?>
    </div>
    <div class="d-grid gap-2">
      <form action="<?= BASE_URL ?>/cart-action.php" method="POST" class="add-to-cart-form">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
        <button type="submit" class="btn btn-brand btn-sm w-100"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
      </form>
      <a href="<?= BASE_URL ?>/product.php?slug=<?= e($p['slug']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
    </div>
  </div>
</div>
