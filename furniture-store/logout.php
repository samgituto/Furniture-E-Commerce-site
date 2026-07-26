<?php
/**
 * logout.php
 * -----------------------------------------------------------------
 * Destroys the session and returns the visitor to the homepage.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/config.php';
logout_user();
header('Location: ' . BASE_URL . '/index.php');
exit;
