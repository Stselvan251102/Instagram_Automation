<?php
/**
 * API: User Logout
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

Auth::logout();
json_response(['ok' => true]);
