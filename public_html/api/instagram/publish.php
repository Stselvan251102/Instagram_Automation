<?php
/**
 * API: Direct Publish Carousel or Image to Instagram
 * POST /api/instagram/publish.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/instagram.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$accountId = (int)($input['account_id'] ?? 0);
$imageUrls = (array)($input['image_urls'] ?? []);
$caption = trim($input['caption'] ?? '');
$hashtags = trim($input['hashtags'] ?? '');
$projectId = !empty($input['project_id']) ? trim($input['project_id']) : null;

if (empty($accountId)) {
    json_response(['ok' => false, 'error' => 'Select an Instagram account to publish to.'], 400);
}
if (empty($imageUrls)) {
    json_response(['ok' => false, 'error' => 'No media image URLs provided for publishing.'], 400);
}

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => false, 'error' => 'Database connection required for publishing.'], 503);
}

try {
    // 1. Fetch connected account
    $stmt = $pdo->prepare("SELECT * FROM instagram_accounts WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();

    if (!$account) {
        json_response(['ok' => false, 'error' => 'Selected Instagram account is inactive or not found.'], 404);
    }

    $igUserId = $account['instagram_business_id'];
    $accessToken = $account['access_token'];

    // Combine caption and hashtags if provided
    $fullCaption = $caption;
    if (!empty($hashtags)) {
        $fullCaption .= "\n\n" . $hashtags;
    }

    // 2. Publish media
    if (count($imageUrls) > 1) {
        $publishResult = InstagramService::publishCarousel($igUserId, $imageUrls, $fullCaption, $accessToken);
        $postType = 'carousel';
    } else {
        $publishResult = InstagramService::publishSingleImage($igUserId, $imageUrls[0], $fullCaption, $accessToken);
        $postType = 'image';
    }

    $igPostId = $publishResult['post_id'] ?? null;

    // 3. Save to scheduled_posts history
    $histStmt = $pdo->prepare("INSERT INTO scheduled_posts 
        (user_id, instagram_account_id, project_id, post_type, media_urls_json, caption, hashtags, scheduled_at, published_at, status, instagram_media_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'published', ?)");
    
    $userId = current_user_id();
    $histStmt->execute([
        $userId,
        $accountId,
        $projectId,
        $postType,
        json_encode($imageUrls),
        $caption,
        $hashtags,
        $igPostId
    ]);

    log_activity('instagram_publish', "Published {$postType} to @{$account['instagram_username']} (Post ID: {$igPostId})");

    json_response([
        'ok' => true,
        'message' => "Successfully published to @{$account['instagram_username']}",
        'post_id' => $igPostId,
        'post_type' => $postType,
        'instagram_url' => "https://www.instagram.com/" . $account['instagram_username'] . "/"
    ]);

} catch (Exception $e) {
    error_log("Instagram Publish API Error: " . $e->getMessage());

    // Record failure in history if account existed
    if (!empty($account)) {
        try {
            $failStmt = $pdo->prepare("INSERT INTO scheduled_posts 
                (user_id, instagram_account_id, project_id, post_type, media_urls_json, caption, hashtags, scheduled_at, status, error_message)
                VALUES (?, ?, ?, 'carousel', ?, ?, ?, NOW(), 'failed', ?)");
            $failStmt->execute([
                current_user_id(),
                $accountId,
                $projectId,
                json_encode($imageUrls),
                $caption,
                $hashtags,
                $e->getMessage()
            ]);
        } catch (Exception $ignored) {}
    }

    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
