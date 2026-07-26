<?php
/**
 * includes/footer.php
 * -----------------------------------------------------------------
 * Site footer + closing </body></html> shared by every public page.
 * -----------------------------------------------------------------
 */
?>
<footer class="bg-dark text-light pt-5 pb-3 mt-5">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <h5 class="fw-bold"><i class="fa-solid fa-couch"></i> <?= e(SITE_NAME) ?></h5>
        <p class="text-secondary small"><?= e(SITE_TAGLINE) ?>. Quality furniture delivered across Kenya.</p>
        <div class="d-flex gap-3 fs-5">
          <a href="#" class="text-light"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" class="text-light"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="text-light"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold">Quick Links</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= BASE_URL ?>/shop.php" class="footer-link">Shop</a></li>
          <li><a href="<?= BASE_URL ?>/about.php" class="footer-link">About Us</a></li>
          <li><a href="<?= BASE_URL ?>/contact.php" class="footer-link">Contact</a></li>
          <li><a href="<?= BASE_URL ?>/customer/dashboard.php" class="footer-link">My Account</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold">Contact Us</h6>
        <ul class="list-unstyled small text-secondary">
          <li><i class="fa-solid fa-envelope me-2"></i>hello@furnishhub.test</li>
          <li><i class="fa-solid fa-phone me-2"></i>+254 700 000000</li>
          <li><i class="fa-solid fa-location-dot me-2"></i>Nairobi, Kenya</li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-center text-secondary small mb-0">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
