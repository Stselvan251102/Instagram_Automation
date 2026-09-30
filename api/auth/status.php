<?php
/**
 * API: Authentication Status
 * GET /api/auth/status.php
 */

require_once (file_exists(__DIR__ . '/../../config/config.php') ? __DIR__ . '/../../config/config.php' : __DIR__ . '/../../../config/config.php');
require_once (file_exists(__DIR__ . '/../../includes/functions.php') ? __DIR__ . '/../../includes/functions.php' : __DIR__ . '/../../../includes/functions.php');

$user = current_user();

json_response([
    'ok' => true,
    'logged_in' => is_logged_in(),
    'user' => $user,
    'csrf_token' => generate_csrf_token()
]);
