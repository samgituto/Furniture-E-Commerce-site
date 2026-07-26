<?php
/**
 * shop.php
 * -----------------------------------------------------------------
 * Product listing page: keyword search, category filter, min/max
 * price filter, sorting, and pagination. All filtering happens
 * server-side using a dynamically built prepared statement.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Shop';

$search   = trim($_GET['search'] ?? '');
$catId    = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

$where  = ["p.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = '(p.title LIKE :search OR p.short_description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($catId > 0) {
    $where[] = 'p.category_id = :cat';
    $params['cat'] = $catId;
}
if ($minPrice !== null) {
    $where[] = 'COALESCE(NULLIF(p.discount_price,0), p.price) >= :minp';
    $params['minp'] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'COALESCE(NULLIF(p.discount_price,0), p.price) <= :maxp';
    $params['maxp'] = $maxPrice;
}

$orderBy = match ($sort) {
    'oldest'      => 'p.created_at ASC',
    'price_low'   => 'COALESCE(NULLIF(p.discount_price,0), p.price) ASC',
    'price_high'  => 'COALESCE(NULLIF(p.discount_price,0), p.price) DESC',
    'popular'     => 'p.sales_count DESC',
    default       => 'p.created_at DESC',
};

$whereSql = implode(' AND ', $where);

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <h1 class="mb-4">Shop All Furniture</h1>
  <div class="row">
    <!-- Filters sidebar -->
    <div class="col-lg-3 mb-4">
      <form method="GET" class="filter-card p-3">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Search</label>
          <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Search products...">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category</label>
          <select name="category" class="form-select">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>" <?= $catId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3 row g-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Min Price</label>
            <input type="number" name="min_price" class="form-control" value="<?= e($_GET['min_price'] ?? '') ?>">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Max Price</label>
            <input type="number" name="max_price" class="form-control" value="<?= e($_GET['max_price'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Sort By</label>
          <select name="sort" class="form-select">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
          </select>
        </div>
        <button class="btn btn-brand w-100" type="submit">Apply Filters</button>
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-link w-100 mt-1">Clear Filters</a>
      </form>
    </div>

    <!-- Products grid -->
    <div class="col-lg-9">
      <p class="text-secondary"><?= $totalItems ?> product(s) found</p>
      <?php if (!$products): ?>
        <div class="empty-state text-center py-5">
          <i class="fa-solid fa-couch fs-1 text-secondary mb-3"></i>
          <p>No products match your filters. Try adjusting your search.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($products as $p): ?>
            <div class="col-md-6 col-lg-4"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php
                $qs = $_GET; $qs['page'] = $i;
              ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
