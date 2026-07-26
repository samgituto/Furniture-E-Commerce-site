<?php
/**
 * admin/sidebar.php
 * -----------------------------------------------------------------
 * Responsive admin sidebar + top bar. Included by every admin page
 * right after includes/header.php (does NOT include the public
 * navbar/footer — the admin panel has its own layout).
 * -----------------------------------------------------------------
 */
$current = basename($_SERVER['PHP_SELF']);
$section = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="admin-wrapper d-flex">
  <aside class="admin-sidebar">
    <div class="p-3 border-bottom border-secondary">
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-white text-decoration-none fw-bold fs-5">
        <i class="fa-solid fa-couch"></i> <?= e(SITE_NAME) ?> Admin
      </a>
    </div>
    <ul class="nav flex-column p-2">
      <li class="nav-item"><a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'products' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/products/index.php"><i class="fa-solid fa-couch me-2"></i>Products</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'categories' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/categories/index.php"><i class="fa-solid fa-tags me-2"></i>Categories</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'orders' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/orders/index.php"><i class="fa-solid fa-receipt me-2"></i>Orders</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'customers' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/customers/index.php"><i class="fa-solid fa-users me-2"></i>Customers</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'coupons' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/coupons/index.php"><i class="fa-solid fa-ticket me-2"></i>Coupons</a></li>
      <li class="nav-item"><a class="nav-link <?= $current === 'reviews.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/reviews.php"><i class="fa-solid fa-star me-2"></i>Reviews</a></li>
      <li class="nav-item"><a class="nav-link <?= $section === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/reports/index.php"><i class="fa-solid fa-chart-line me-2"></i>Reports</a></li>
      <li class="nav-item"><a class="nav-link <?= $current === 'settings.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/settings.php"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
      <li class="nav-item mt-3"><a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
    </ul>
  </aside>
  <div class="admin-main flex-grow-1">
    <nav class="admin-topbar d-flex justify-content-between align-items-center px-4 py-2">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <span class="fw-semibold"><?= e($pageTitle ?? 'Admin') ?></span>
      <span class="text-secondary small"><i class="fa-solid fa-user-shield me-1"></i><?= e($_SESSION['full_name']) ?></span>
    </nav>
    <main class="p-4">
