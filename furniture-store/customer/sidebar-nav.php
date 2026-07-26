<?php
/**
 * customer/sidebar-nav.php
 * -----------------------------------------------------------------
 * Left-hand navigation shared by every page under /customer/.
 * -----------------------------------------------------------------
 */
$current = basename($_SERVER['PHP_SELF']);
$links = [
    'dashboard.php'       => ['icon' => 'fa-gauge',    'label' => 'Overview'],
    'orders.php'          => ['icon' => 'fa-box',      'label' => 'Order History'],
    'wishlist.php'        => ['icon' => 'fa-heart',    'label' => 'Wishlist'],
    'profile.php'         => ['icon' => 'fa-user',     'label' => 'Profile Information'],
    'change-password.php' => ['icon' => 'fa-lock',     'label' => 'Change Password'],
];
?>
<div class="col-lg-3 mb-4">
  <div class="list-group">
    <?php foreach ($links as $file => $link): ?>
      <a href="<?= BASE_URL ?>/customer/<?= $file ?>" class="list-group-item list-group-item-action <?= $current === $file ? 'active-link' : '' ?>">
        <i class="fa-solid <?= $link['icon'] ?> me-2"></i><?= e($link['label']) ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/logout.php" class="list-group-item list-group-item-action text-danger">
      <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
    </a>
  </div>
</div>
