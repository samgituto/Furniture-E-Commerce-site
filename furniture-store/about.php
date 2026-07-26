<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <h1 class="mb-4 text-center">About <?= e(SITE_NAME) ?></h1>
  <div class="col-md-8 mx-auto">
    <p><?= e(SITE_NAME) ?> is a Kenyan furniture retailer dedicated to bringing quality, durable furniture to homes and offices across the country. We work with trusted local craftsmen and international suppliers to offer a wide range of sofas, beds, dining sets, storage, and office furniture — all backed by warranty and fast, reliable delivery.</p>
    <p>Our mission is simple: furniture that fits your life, your space, and your budget.</p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
