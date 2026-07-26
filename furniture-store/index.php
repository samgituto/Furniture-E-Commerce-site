<?php
/**
 * index.php  (Home Page)
 * -----------------------------------------------------------------
 * Public homepage. Pulls featured products, newest products,
 * and active categories from the database.
 * Connects to: config/config.php, includes/header.php,
 * includes/navbar.php, includes/footer.php.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Home';

$featured = $pdo->query(
    "SELECT * FROM products WHERE status='active' AND is_featured=1 ORDER BY created_at DESC LIMIT 4"
)->fetchAll();

$newest = $pdo->query(
    "SELECT * FROM products WHERE status='active' ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$categories = $pdo->query(
    "SELECT * FROM categories WHERE status='active' ORDER BY name LIMIT 6"
)->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- HERO -->
<section class="hero-section d-flex align-items-center">
  <div class="container py-5">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h1 class="display-5 fw-bold mb-3">Furniture that fits your life</h1>
        <p class="lead text-secondary mb-4">Hand-picked sofas, beds, tables and more — built to last, delivered to your door across Kenya.</p>
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand btn-lg">Shop the Collection</a>
      </div>
      <div class="col-lg-6 text-center d-none d-lg-block">
    <?php 
    $image_url = "assets/images/furniture.jfif"; // Default image   
    ?>
    <img src="<?php echo $image_url; ?>" alt="Hero Couch" class="img-fluid hero-image">
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="container py-5">
  <h2 class="section-title text-center mb-4">Shop by Category</h2>
  <div class="row g-4">
    <?php foreach ($categories as $cat): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <a href="<?= BASE_URL ?>/shop.php?category=<?= (int) $cat['id'] ?>" class="category-card text-decoration-none">
          <div class="category-icon"><i class="fa-solid fa-chair"></i></div>
          <p class="text-dark fw-semibold small mt-2 text-center"><?= e($cat['name']) ?></p>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<?php if ($featured): ?>
<section class="container py-4">
  <h2 class="section-title text-center mb-4">Featured Pieces</h2>
  <div class="row g-4">
    <?php foreach ($featured as $p): ?>
      <div class="col-md-6 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- NEW ARRIVALS -->
<section class="container py-4">
  <h2 class="section-title text-center mb-4">New Arrivals</h2>
  <div class="row g-4">
    <?php foreach ($newest as $p): ?>
      <div class="col-md-6 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
    <?php endforeach; ?>
  </div>
</section>

<!-- WHY BUY FROM US -->
<section class="bg-light py-5 mt-4">
  <div class="container">
    <h2 class="section-title text-center mb-4">Why Buy From Us</h2>
    <div class="row g-4 text-center">
      <div class="col-md-3"><i class="fa-solid fa-truck-fast fs-1 text-brand"></i><p class="fw-semibold mt-2">Fast Delivery</p></div>
      <div class="col-md-3"><i class="fa-solid fa-shield-halved fs-1 text-brand"></i><p class="fw-semibold mt-2">Warranty Included</p></div>
      <div class="col-md-3"><i class="fa-solid fa-rotate-left fs-1 text-brand"></i><p class="fw-semibold mt-2">Easy Returns</p></div>
      <div class="col-md-3"><i class="fa-solid fa-money-bill-wave fs-1 text-brand"></i><p class="fw-semibold mt-2">Secure Payment</p></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="container py-5">
  <h2 class="section-title text-center mb-4">What Customers Say</h2>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="testimonial-card"><p>"The sofa quality exceeded my expectations. Delivery was fast and the team assembled it for free."</p><strong>— Wanjiru K.</strong></div>
    </div>
    <div class="col-md-4">
      <div class="testimonial-card"><p>"Great prices and the dining table looks even better in person. Highly recommend."</p><strong>— Otieno M.</strong></div>
    </div>
    <div class="col-md-4">
      <div class="testimonial-card"><p>"Customer service helped me pick the right bed size. Very happy with my purchase."</p><strong>— Amina S.</strong></div>
    </div>
  </div>
</section>

<!-- NEWSLETTER -->
<section class="newsletter-section py-5">
  <div class="container text-center">
    <h3 class="fw-bold mb-2">Join Our Newsletter</h3>
    <p class="mb-4">Get new arrivals and offers straight to your inbox.</p>
    <form action="newsletter-subscribe.php" method="POST" class="d-flex justify-content-center gap-2 flex-wrap">
      <?php csrf_field(); ?>
      <input type="email" name="email" class="form-control w-auto" placeholder="Your email address" required>
      <button class="btn btn-brand" type="submit">Subscribe</button>
    </form>
  </div>
</section>

<!-- FAQ -->
<section class="container py-5">
  <h2 class="section-title text-center mb-4">Frequently Asked Questions</h2>
  <div class="accordion" id="faqAccordion">
    <div class="accordion-item">
      <h2 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faq1">Do you deliver countrywide?</button></h2>
      <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
        <div class="accordion-body">Yes, we deliver to all major towns in Kenya. Shipping fees are calculated at checkout.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq2">Is assembly included?</button></h2>
      <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
        <div class="accordion-body">Most items requiring assembly are delivered and assembled by our team at no extra cost.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq3">What is your return policy?</button></h2>
      <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
        <div class="accordion-body">Items can be returned within 7 days of delivery if unused and in original packaging.</div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
