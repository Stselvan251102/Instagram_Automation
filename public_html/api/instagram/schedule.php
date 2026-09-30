<?php
/**
 * API: Schedule Carousel or Post for Automation
 * POST /api/instagram/schedule.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$accountId = (int)($input['account_id'] ?? 0);
$imageUrls = (array)($input['image_urls'] ?? []);
$caption = trim($input['caption'] ?? '');
$hashtags = trim($input['hashtags'] ?? '');
$scheduledAt = trim($input['scheduled_at'] ?? '');
$projectId = !empty($input['project_id']) ? trim($input['project_id']) : null;

if (empty($accountId)) {
    json_response(['ok' => false, 'error' => 'Select an Instagram account.'], 400);
}
if (empty($imageUrls)) {
    json_response(['ok' => false, 'error' => 'No media image URLs provided.'], 400);
}
if (empty($scheduledAt)) {
    json_response(['ok' => false, 'error' => 'Specify a scheduled date and time.'], 400);
}

// Normalize scheduled date
$ts = strtotime($scheduledAt);
if (!$ts || $ts < time() - 60) {
    json_response(['ok' => false, 'error' => 'Scheduled time must be in the future.'], 400);
}
$scheduledDbFormat = date('Y-m-d H:i:s', $ts);

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => false, 'error' => 'Database connection required for scheduling.'], 503);
}

try {
    // Verify account exists
    $accStmt = $pdo->prepare("SELECT instagram_username FROM instagram_accounts WHERE id = ? AND is_active = 1 LIMIT 1");
    $accStmt->execute([$accountId]);
    $account = $accStmt->fetch();
    if (!$account) {
        json_response(['ok' => false, 'error' => 'Instagram account not found or inactive.'], 404);
    }

    $postType = count($imageUrls) > 1 ? 'carousel' : 'image';
    $userId = current_user_id();

    $stmt = $pdo->prepare("INSERT INTO scheduled_posts 
        (user_id, instagram_account_id, project_id, post_type, media_urls_json, caption, hashtags, scheduled_at, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
    
    $stmt->execute([
        $userId,
        $accountId,
        $projectId,
        $postType,
        json_encode($imageUrls),
        $caption,
        $hashtags,
        $scheduledDbFormat
    ]);

    $id = (int)$pdo->lastInsertId();
    log_activity('post_schedule', "Scheduled post #{$id} for {$scheduledDbFormat} on @{$account['instagram_username']}");

    json_response([
        'ok' => true,
        'message' => "Post successfully scheduled for " . date('M j, Y g:i A', $ts),
        'scheduled_id' => $id,
        'scheduled_at' => $scheduledDbFormat
    ]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
