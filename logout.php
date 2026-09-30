<?php
/**
 * User Logout Handler
 * Carouselfy - Instagram Automation
 */

require_once (file_exists(__DIR__ . '/config/config.php') ? __DIR__ . '/config/config.php' : __DIR__ . '/../config/config.php');
require_once (file_exists(__DIR__ . '/includes/functions.php') ? __DIR__ . '/includes/functions.php' : __DIR__ . '/../includes/functions.php');
require_once (file_exists(__DIR__ . '/includes/auth.php') ? __DIR__ . '/includes/auth.php' : __DIR__ . '/../includes/auth.php');

Auth::logout();
header('Location: /login.php');
exit;
