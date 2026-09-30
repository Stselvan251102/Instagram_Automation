<?php
/**
 * API: List Connected Instagram Accounts
 * GET /api/instagram/accounts.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => true, 'accounts' => []]);
}

try {
    $userId = current_user_id();
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, page_name, instagram_business_id, instagram_username, profile_picture_url, followers_count, is_active, token_expires_at, updated_at 
                               FROM instagram_accounts WHERE (user_id = ? OR user_id IS NULL) AND is_active = 1 ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT id, page_name, instagram_business_id, instagram_username, profile_picture_url, followers_count, is_active, token_expires_at, updated_at 
                             FROM instagram_accounts WHERE is_active = 1 ORDER BY updated_at DESC");
    }
    $accounts = $stmt->fetchAll();

    json_response([
        'ok' => true,
        'accounts' => $accounts
    ]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
