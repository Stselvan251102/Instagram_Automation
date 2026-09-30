<?php
/**
 * API: User Login
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

$result = Auth::login($email, $password);
json_response($result, $result['ok'] ? 200 : 401);
