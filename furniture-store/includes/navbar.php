<?php
/**
 * includes/navbar.php
 * -----------------------------------------------------------------
 * Responsive top navigation bar shown on every public page.
 * Shows cart count and switches Login/Account links based on
 * whether a customer is logged in.
 * -----------------------------------------------------------------
 */
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-brand" href="<?= BASE_URL ?>/index.php">
      <i class="fa-solid fa-couch"></i> <?= e(SITE_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/shop.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/contact.php">Contact</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/cart.php" class="text-dark position-relative">
          <i class="fa-solid fa-cart-shopping fs-5"></i>
          <span class="badge rounded-pill bg-brand position-absolute top-0 start-100 translate-middle" id="cartCount">
            <?= (int) cart_count() ?>
          </span>
        </a>
        <?php if (is_logged_in()): ?>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
              <i class="fa-solid fa-user"></i> <?= e($_SESSION['full_name']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (is_admin()): ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/dashboard.php">My Dashboard</a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-brand">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
