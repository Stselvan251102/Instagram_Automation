<?php
/**
 * API: Handle OAuth Callback from Meta / Facebook
 * GET /api/instagram/callback.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/instagram.php';

$code = trim($_GET['code'] ?? '');
$state = trim($_GET['state'] ?? '');
$savedState = $_SESSION['ig_oauth_state'] ?? '';

if (empty($code)) {
    $errorReason = $_GET['error_description'] ?? ($_GET['error'] ?? 'Authorization was cancelled or failed.');
    header('Location: /accounts.php?error=' . urlencode($errorReason));
    exit;
}

// State validation
if (empty($savedState) || !hash_equals($savedState, $state)) {
    header('Location: /accounts.php?error=' . urlencode('Invalid OAuth state parameter. Potential CSRF detected.'));
    exit;
}
unset($_SESSION['ig_oauth_state']);

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: /accounts.php?error=' . urlencode('Database not connected.'));
    exit;
}

try {
    // 1. Exchange code for short-lived token
    $tokenData = InstagramService::exchangeCodeForToken($code);
    if (empty($tokenData['access_token'])) {
        throw new Exception('Did not receive access token from Meta.');
    }
    $shortToken = $tokenData['access_token'];

    // 2. Exchange for long-lived (60 days) access token
    $longTokenData = InstagramService::getLongLivedToken($shortToken);
    $accessToken = $longTokenData['access_token'] ?? $shortToken;
    $expiresIn = (int)($longTokenData['expires_in'] ?? 5184000); // 60 days default
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

    // 3. Discover connected Instagram business accounts
    $accounts = InstagramService::getConnectedAccounts($accessToken);
    if (empty($accounts)) {
        throw new Exception('No Instagram Professional / Business Account linked to your Facebook Pages. Please convert your Instagram to a Business/Creator account and connect it to a Facebook Page.');
    }

    $userId = current_user_id();

    // 4. Save accounts to database
    $upsert = $pdo->prepare("INSERT INTO instagram_accounts 
        (user_id, page_id, page_name, instagram_business_id, instagram_username, access_token, token_expires_at, profile_picture_url, followers_count, is_active, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
        page_name = VALUES(page_name),
        instagram_username = VALUES(instagram_username),
        access_token = VALUES(access_token),
        token_expires_at = VALUES(token_expires_at),
        profile_picture_url = VALUES(profile_picture_url),
        followers_count = VALUES(followers_count),
        is_active = 1,
        updated_at = NOW()");

    $connectedNames = [];
    foreach ($accounts as $acc) {
        $upsert->execute([
            $userId,
            $acc['page_id'],
            $acc['page_name'],
            $acc['instagram_business_id'],
            $acc['instagram_username'],
            $acc['page_access_token'],
            $expiresAt,
            $acc['profile_picture_url'],
            $acc['followers_count']
        ]);
        $connectedNames[] = '@' . $acc['instagram_username'];
    }

    log_activity('instagram_connect', 'Connected Instagram accounts: ' . implode(', ', $connectedNames));

    header('Location: /accounts.php?connected=' . count($accounts) . '&handle=' . urlencode($connectedNames[0] ?? ''));
    exit;
} catch (Exception $e) {
    error_log("Instagram OAuth Callback Error: " . $e->getMessage());
    header('Location: /accounts.php?error=' . urlencode($e->getMessage()));
    exit;
}
