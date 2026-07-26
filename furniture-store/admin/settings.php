<?php
/**
 * admin/settings.php
 * -----------------------------------------------------------------
 * Lets the admin edit key/value rows in `website_settings` (site
 * name, contact info, shipping fee, free-shipping threshold, etc).
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Website Settings';

$editableKeys = [
    'site_name'               => 'Site Name',
    'site_tagline'            => 'Site Tagline',
    'contact_email'           => 'Contact Email',
    'contact_phone'           => 'Contact Phone',
    'currency_symbol'         => 'Currency Symbol',
    'shipping_flat_fee'       => 'Flat Shipping Fee',
    'free_shipping_threshold' => 'Free Shipping Threshold',
];

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($editableKeys as $key => $label) {
        $value = trim($_POST[$key] ?? '');
        $stmt = $pdo->prepare(
            'INSERT INTO website_settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }
    $success = 'Settings updated successfully.';
}

$rows = $pdo->query('SELECT setting_key, setting_value FROM website_settings')->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/sidebar.php';
?>
<h1 class="mb-4">Website Settings</h1>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<div class="admin-card p-4 col-lg-6">
  <form method="POST">
    <?php csrf_field(); ?>
    <?php foreach ($editableKeys as $key => $label): ?>
      <div class="mb-3">
        <label class="form-label"><?= e($label) ?></label>
        <input type="text" name="<?= e($key) ?>" class="form-control" value="<?= e($rows[$key] ?? '') ?>">
      </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-brand">Save Settings</button>
  </form>
</div>
<p class="text-secondary small mt-3">Note: BASE_URL and database credentials are set in <code>config/config.php</code> and <code>config/database.php</code> directly, since changing them requires a server restart in most hosting setups.</p>
<?php include __DIR__ . '/footer.php'; ?>
