<?php
/**
 * includes/header.php
 * -----------------------------------------------------------------
 * Opening <html>/<head> markup shared by every public page.
 * Expects config/config.php to already be included by the caller.
 * A page-specific $pageTitle variable may be set before including this.
 * -----------------------------------------------------------------
 */
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
<meta name="description" content="<?= e(SITE_TAGLINE) ?>">

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Site stylesheet -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
