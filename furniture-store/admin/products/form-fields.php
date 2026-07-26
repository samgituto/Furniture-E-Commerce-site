<?php
/**
 * admin/products/form-fields.php
 * -----------------------------------------------------------------
 * Shared <form> body for creating/editing a product. Expects
 * $product (array, empty for a new product) and $categories to be
 * set by the including page (add.php or edit.php).
 * -----------------------------------------------------------------
 */
$p = $product ?? [];
?>
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" value="<?= e($p['title'] ?? '') ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">SKU</label>
    <input type="text" name="sku" class="form-control" value="<?= e($p['sku'] ?? '') ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select" required>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= (isset($p['category_id']) && (int) $p['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Price (<?= CURRENCY_SYMBOL ?>)</label>
    <input type="number" step="0.01" name="price" class="form-control" value="<?= e($p['price'] ?? '') ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Discount Price</label>
    <input type="number" step="0.01" name="discount_price" class="form-control" value="<?= e($p['discount_price'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Stock Quantity</label>
    <input type="number" name="stock_quantity" class="form-control" value="<?= e($p['stock_quantity'] ?? '0') ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Warranty (months)</label>
    <input type="number" name="warranty_months" class="form-control" value="<?= e($p['warranty_months'] ?? '0') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Material</label>
    <input type="text" name="material" class="form-control" value="<?= e($p['material'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Color</label>
    <input type="text" name="color" class="form-control" value="<?= e($p['color'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Dimensions</label>
    <input type="text" name="dimensions" class="form-control" placeholder="200cm x 90cm x 85cm" value="<?= e($p['dimensions'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Weight (kg)</label>
    <input type="number" step="0.01" name="weight_kg" class="form-control" value="<?= e($p['weight_kg'] ?? '') ?>">
  </div>
  <div class="col-md-4 d-flex align-items-center gap-2 mt-4">
    <input type="checkbox" name="assembly_required" class="form-check-input" value="1" <?= !empty($p['assembly_required']) ? 'checked' : '' ?>>
    <label class="form-check-label">Assembly Required</label>
  </div>
  <div class="col-md-4 d-flex align-items-center gap-2 mt-4">
    <input type="checkbox" name="is_featured" class="form-check-input" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>>
    <label class="form-check-label">Featured Product</label>
  </div>
  <div class="col-12">
    <label class="form-label">Short Description</label>
    <input type="text" name="short_description" class="form-control" maxlength="500" value="<?= e($p['short_description'] ?? '') ?>">
  </div>
  <div class="col-12">
    <label class="form-label">Full Description</label>
    <textarea name="full_description" rows="4" class="form-control"><?= e($p['full_description'] ?? '') ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label">Product Image</label>
    <input type="file" name="main_image" class="form-control" accept="image/png,image/jpeg,image/webp" id="imageInput">
    <?php if (!empty($p['main_image'])): ?>
      <img src="<?= BASE_URL . '/' . e(ltrim($p['main_image'], '/')) ?>" width="80" class="mt-2 rounded d-block" id="imagePreviewExisting">
    <?php endif; ?>
    <img id="imagePreview" class="mt-2 rounded d-none" width="80">
  </div>
  <div class="col-md-6">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="active" <?= (($p['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= (($p['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>
</div>
