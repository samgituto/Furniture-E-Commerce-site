<?php
/**
 * admin/coupons/index.php
 * -----------------------------------------------------------------
 * Lists discount coupons and provides an add/edit form (single page,
 * same pattern as categories).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
require_admin();
$pageTitle = 'Manage Coupons';

$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);

    if ($code === '') $errors[] = 'Coupon code is required.';
    if (!is_numeric($_POST['discount_value'] ?? '')) $errors[] = 'A valid discount value is required.';

    if (!$errors) {
        $params = [
            $code,
            $_POST['discount_type'],
            (float) $_POST['discount_value'],
            (float) ($_POST['min_order_amount'] ?: 0),
            $_POST['max_discount_amount'] !== '' ? (float) $_POST['max_discount_amount'] : null,
            $_POST['usage_limit'] !== '' ? (int) $_POST['usage_limit'] : null,
            (int) ($_POST['usage_limit_per_customer'] ?: 1),
            $_POST['starts_at'] ?: null,
            $_POST['expires_at'] ?: null,
            $_POST['status'] ?? 'active',
        ];
        if ($id > 0) {
            $params[] = $id;
            $pdo->prepare(
                'UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order_amount=?, max_discount_amount=?,
                 usage_limit=?, usage_limit_per_customer=?, starts_at=?, expires_at=?, status=? WHERE id=?'
            )->execute($params);
        } else {
            $pdo->prepare(
                'INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount,
                 usage_limit, usage_limit_per_customer, starts_at, expires_at, status) VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute($params);
        }
        header('Location: ' . BASE_URL . '/admin/coupons/index.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
if (isset($_GET['delete'], $_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int) $_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/coupons/index.php');
    exit;
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../sidebar.php';
?>
<h1 class="mb-4">Manage Coupons</h1>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err): ?><?= e($err) ?><?php endforeach; ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="admin-card p-3">
      <h5><?= $editing ? 'Edit Coupon' : 'Add Coupon' ?></h5>
      <form method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="mb-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= e($editing['code'] ?? '') ?>" required></div>
        <div class="mb-2">
          <label class="form-label">Discount Type</label>
          <select name="discount_type" class="form-select">
            <option value="percentage" <?= (($editing['discount_type'] ?? '') === 'percentage') ? 'selected' : '' ?>>Percentage</option>
            <option value="fixed" <?= (($editing['discount_type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Fixed Amount</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Discount Value</label><input type="number" step="0.01" name="discount_value" class="form-control" value="<?= e($editing['discount_value'] ?? '') ?>" required></div>
        <div class="mb-2"><label class="form-label">Min Order Amount</label><input type="number" step="0.01" name="min_order_amount" class="form-control" value="<?= e($editing['min_order_amount'] ?? '0') ?>"></div>
        <div class="mb-2"><label class="form-label">Max Discount Amount</label><input type="number" step="0.01" name="max_discount_amount" class="form-control" value="<?= e($editing['max_discount_amount'] ?? '') ?>"></div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label">Total Usage Limit</label><input type="number" name="usage_limit" class="form-control" value="<?= e($editing['usage_limit'] ?? '') ?>"></div>
          <div class="col-6"><label class="form-label">Per-Customer Limit</label><input type="number" name="usage_limit_per_customer" class="form-control" value="<?= e($editing['usage_limit_per_customer'] ?? '1') ?>"></div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label">Starts At</label><input type="date" name="starts_at" class="form-control" value="<?= e(substr($editing['starts_at'] ?? '', 0, 10)) ?>"></div>
          <div class="col-6"><label class="form-label">Expires At</label><input type="date" name="expires_at" class="form-control" value="<?= e(substr($editing['expires_at'] ?? '', 0, 10)) ?>"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= (($editing['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($editing['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <button type="submit" class="btn btn-brand w-100"><?= $editing ? 'Update' : 'Add' ?> Coupon</button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="admin-card p-3">
      <table class="table table-sm">
        <thead><tr><th>Code</th><th>Value</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td><?= e($c['code']) ?></td>
            <td><?= $c['discount_type'] === 'percentage' ? e($c['discount_value']) . '%' : money($c['discount_value']) ?></td>
            <td><?= (int) $c['times_used'] ?><?= $c['usage_limit'] ? ' / ' . (int) $c['usage_limit'] : '' ?></td>
            <td><?= $c['expires_at'] ? e(date('M j, Y', strtotime($c['expires_at']))) : '—' ?></td>
            <td><span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
            <td>
              <a href="?edit=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <a href="?delete=<?= (int) $c['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="btn btn-sm btn-outline-danger delete-confirm-link"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
