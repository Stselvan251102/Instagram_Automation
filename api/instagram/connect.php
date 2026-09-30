<?php
/**
 * API: Start Instagram / Facebook OAuth flow
 * GET /api/instagram/connect.php
 */

require_once (file_exists(__DIR__ . '/../../config/config.php') ? __DIR__ . '/../../config/config.php' : __DIR__ . '/../../../config/config.php');
require_once (file_exists(__DIR__ . '/../../includes/functions.php') ? __DIR__ . '/../../includes/functions.php' : __DIR__ . '/../../../includes/functions.php');
require_once (file_exists(__DIR__ . '/../../includes/instagram.php') ? __DIR__ . '/../../includes/instagram.php' : __DIR__ . '/../../../includes/instagram.php');

if (empty(INSTAGRAM_APP_ID) || empty(INSTAGRAM_APP_SECRET)) {
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        json_response([
            'ok' => false, 
            'error' => 'Meta App ID and Secret are not configured yet. Set INSTAGRAM_APP_ID and INSTAGRAM_APP_SECRET in .env or Settings.'
        ], 400);
    }
    header('Location: /settings.php?error=missing_credentials');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['ig_oauth_state'] = $state;

$oauthUrl = InstagramService::getOAuthUrl($state);

if (!empty($_GET['json'])) {
    json_response(['ok' => true, 'url' => $oauthUrl]);
}

header("Location: $oauthUrl");
exit;
