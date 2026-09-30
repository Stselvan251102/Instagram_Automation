<?php
/**
 * User Logout Handler
 * Carouselfy - Instagram Automation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::logout();
header('Location: /login.php');
exit;
