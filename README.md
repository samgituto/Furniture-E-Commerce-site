# FurnishHub — Furniture E-Commerce Website (PHP + MySQL)

A complete, responsive e-commerce website for selling furniture, built with
plain PHP 8 (no framework), MySQL/PDO, Bootstrap 5, and vanilla JavaScript.

## Features

- Public storefront: home, shop (search/filter/sort/pagination), product
  details, cart, checkout, order confirmation, about, contact, newsletter.
- Customer accounts: registration, login (with lockout protection),
  password reset, dashboard, order history, order detail with reviews,
  wishlist, profile editing, password change.
- Admin panel: dashboard with sales chart, product management (with secure
  image upload), category management, order management with status
  timeline, customer management, coupon management, review moderation,
  reports with CSV export, website settings.
- Security: `password_hash()`/`password_verify()`, PDO prepared statements
  everywhere, CSRF tokens on every form, output escaping via `htmlspecialchars()`,
  session regeneration on login, login-attempt lockout, secure file-upload
  validation (real MIME-type check + random filenames), role-based access
  control on every admin page.

## 1. System Requirements

- PHP 8.0 or later, with the `pdo_mysql`, `fileinfo`, and `session`
  extensions enabled (all are on by default in most installs).
- MySQL 5.7+ or MariaDB 10.3+.
- A web server: Apache (with `mod_rewrite` optional) or Nginx, or PHP's
  built-in server for local testing.
- XAMPP works well for local development.

## 2. Installation (XAMPP / local)

1. Copy the `furniture-store` folder into your web root:
   - XAMPP on Windows: `C:\xampp\htdocs\furniture-store`
   - XAMPP on macOS/Linux: `/Applications/XAMPP/htdocs/furniture-store` or `/opt/lampp/htdocs/furniture-store`
2. Start Apache and MySQL from the XAMPP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Click **Import**, choose `database.sql`, and click **Go**. This creates
   the `furniture_store` database, all tables, sample categories/products,
   and a default admin account.
5. Open `config/database.php` and confirm the credentials match your MySQL
   setup (XAMPP defaults are usually `root` with an empty password — no
   changes needed).
6. Open `config/config.php` and set `BASE_URL` to match your folder, e.g.
   `http://localhost/furniture-store`.
7. Visit `http://localhost/furniture-store/index.php` in your browser.

## 3. Installation (shared hosting)

1. Upload the entire project via FTP/SFTP or your host's File Manager to
   your domain's public folder (often `public_html`).
2. Create a new MySQL database and user from your hosting control panel
   (e.g. cPanel's "MySQL Databases"), and note the database name,
   username, password, and host.
3. Import `database.sql` using phpMyAdmin (or the `mysql` CLI:
   `mysql -u USERNAME -p DATABASE_NAME < database.sql`).
4. Edit `config/database.php` with your real `DB_HOST`, `DB_NAME`,
   `DB_USER`, and `DB_PASS`.
5. Edit `config/config.php` and set `BASE_URL` to your real domain, e.g.
   `https://yourdomain.com`.
6. Make sure `assets/images/products/` is writable by PHP (usually `755`
   or `775` depending on your host) so the admin panel can save uploaded
   product images.
7. Visit your domain to confirm the site loads.

## 4. Default Administrator Login

The imported `database.sql` creates one admin account:

- **Email:** `admin@furnishhub.test`
- **Password placeholder:** the SQL file inserts a placeholder hash, **not**
  a working password. Generate your own hash before going live:

  ```bash
  php -r "echo password_hash('YourNewStrongPassword', PASSWORD_DEFAULT);"
  ```

  Then update the row in phpMyAdmin (or via SQL):

  ```sql
  UPDATE users SET password_hash = 'PASTE_THE_GENERATED_HASH_HERE'
  WHERE email = 'admin@furnishhub.test';
  ```

  **Do this immediately after installation, before the site is public.**

- Log in at `/login.php` — you'll be redirected to `/admin/dashboard.php`
  automatically because the account's `role` is `admin`.

## 5. Configuration Reference

| File | Purpose |
|---|---|
| `config/database.php` | DB host/name/user/password (PDO connection) |
| `config/config.php` | Site name, `BASE_URL`, shipping rules, upload limits, session setup |
| Admin → Settings page | Editable site name, tagline, contact info, shipping fee, free-shipping threshold (stored in `website_settings` table) |

## 6. Payment Gateway Integration

Checkout ships with a working **test payment gateway** (`checkout.php` →
`process_payment()`) that marks an order `paid` only after basic
server-side validation, so you can exercise the full order flow during
development.

To add a real gateway:

1. Open `checkout.php` and find the `process_payment()` function.
2. Add a new `case` for your gateway (e.g. `'mpesa'`, `'stripe'`, `'paypal'`)
   that calls the gateway's API/SDK and returns `true`/`false` based on the
   real response.
3. Un-comment and update the matching radio button in the checkout form
   HTML further down the same file.
4. Store any gateway-specific credentials (API keys, secrets) as PHP
   constants in `config/config.php` — never commit real keys to source
   control.

M-Pesa (Daraja API), Stripe, and PayPal all expose REST APIs that work well
called via PHP's `curl` or `file_get_contents` — no extra libraries are
required, though Composer packages exist for all three if you prefer.

## 7. Testing Procedures

Manual test checklist:

1. **Browsing:** Home page loads with featured/new products and
   categories; Shop page search/category/price filters and sorting all
   narrow the results correctly; pagination links work.
2. **Cart:** Add a product from a card and from the product page; update
   quantity; remove an item; confirm the cart badge in the navbar updates.
3. **Coupons:** Create a coupon in Admin → Coupons, then apply it on the
   cart page and confirm the discount is reflected in the order summary.
4. **Checkout:** Complete checkout with the Test Payment option; confirm
   stock quantity decreases, the order appears in Customer → Order
   History, and in Admin → Orders.
5. **Reviews:** Mark a test order `delivered` in Admin → Orders, then
   submit a review from Customer → Order Detail; approve it in
   Admin → Reviews; confirm it appears on the product page.
6. **Security:** Try submitting a form without the CSRF token (should be
   rejected); try 5 failed logins in a row (account should lock for 15
   minutes); try uploading a non-image file as a product image (should be
   rejected).
7. **Admin access control:** While logged out (or logged in as a
   customer), try visiting `/admin/dashboard.php` directly — you should be
   redirected to `/login.php`.

## 8. Security Recommendations Before Going Live

- Change the default admin password immediately (see Section 4).
- Set `display_errors` to `0` in production (`config/config.php` already
  does this) and monitor your PHP error log instead.
- Serve the site over HTTPS and set `session.cookie_secure` to `1` in
  `config/config.php`'s `session_set_cookie_params()` call once HTTPS is
  active.
- Keep PHP and MySQL patched and up to date.
- Regularly back up the database (`mysqldump`) and the
  `assets/images/products/` upload folder.
- Consider rate-limiting or adding a CAPTCHA to the contact form and
  registration form if you experience spam.

## 9. Project Structure

```text
furniture-store/
├── admin/                  Admin panel (dashboard, products, categories,
│                           orders, customers, coupons, reviews, reports, settings)
├── assets/                 css/, js/, images/ (uploaded product images live in
│                           assets/images/products/)
├── config/                 database.php, config.php
├── customer/               Customer dashboard, orders, wishlist, profile
├── includes/               header, navbar, footer, functions.php, product-card
├── index.php, shop.php, product.php, cart.php, checkout.php, ...
├── login.php, register.php, logout.php, forgot/reset-password.php
├── database.sql            Full schema + sample data
└── README.md                This file
```

## 10. Known Limitations of This Starter Kit

- Email notifications (order confirmation, password reset, etc.) are not
  wired to a real SMTP server — the password-reset link is shown on
  screen instead, clearly marked "development mode." Add your SMTP
  credentials and a mail library (e.g. PHPMailer) to send real emails.
- Only one payment gateway ("test") is implemented; see Section 6 to add
  real ones.
- Sample product photos included are simple placeholder graphics — replace
  them with real product photography via Admin → Products → Edit.
