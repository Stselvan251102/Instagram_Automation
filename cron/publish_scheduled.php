<?php
/**
 * Cron Job Runner: Publish Scheduled Instagram Posts
 * Hostinger Cron Job Target
 *
 * Example Cron Command in Hostinger cPanel:
 *   * * * * * php /home/u123456/public_html/cron/publish_scheduled.php > /dev/null 2>&1
 *
 * Web Webhook Alternative:
 *   https://yourdomain.com/cron/publish_scheduled.php?secret=YOUR_CRON_SECRET
 */

require_once (file_exists(__DIR__ . '/../config/config.php') ? __DIR__ . '/../config/config.php' : __DIR__ . '/../../config/config.php');
require_once (file_exists(__DIR__ . '/../includes/functions.php') ? __DIR__ . '/../includes/functions.php' : __DIR__ . '/../../includes/functions.php');
require_once (file_exists(__DIR__ . '/../includes/instagram.php') ? __DIR__ . '/../includes/instagram.php' : __DIR__ . '/../../includes/instagram.php');

// Check if running from CLI or web
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    $secret = trim($_GET['secret'] ?? $_GET['token'] ?? '');
    if (empty(CRON_SECRET) || !hash_equals(CRON_SECRET, $secret)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden. Invalid cron secret token.']);
        exit;
    }
}

$pdo = Database::getConnection();
if (!$pdo) {
    $msg = "Database not connected. Aborting cron.";
    if ($isCli) {
        echo "[CRON] $msg\n";
    } else {
        json_response(['ok' => false, 'error' => $msg], 503);
    }
    exit(1);
}

try {
    // 1. Fetch pending scheduled posts due for publication
    $stmt = $pdo->prepare("SELECT sp.*, ia.instagram_business_id, ia.access_token, ia.instagram_username 
                           FROM scheduled_posts sp 
                           JOIN instagram_accounts ia ON sp.instagram_account_id = ia.id 
                           WHERE sp.status = 'scheduled' AND sp.scheduled_at <= NOW() 
                           ORDER BY sp.scheduled_at ASC LIMIT 10");
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $processed = 0;
    $succeeded = 0;
    $failed = 0;
    $results = [];

    foreach ($posts as $post) {
        $processed++;
        $postId = (int)$post['id'];
        $igUserId = $post['instagram_business_id'];
        $accessToken = $post['access_token'];
        $imageUrls = json_decode($post['media_urls_json'], true) ?: [];
        $caption = $post['caption'] ?? '';
        $hashtags = $post['hashtags'] ?? '';
        $fullCaption = trim($caption . ($hashtags ? "\n\n" . $hashtags : ''));

        // Lock post status to 'publishing' to prevent double submission
        $lockStmt = $pdo->prepare("UPDATE scheduled_posts SET status = 'publishing', updated_at = NOW() WHERE id = ?");
        $lockStmt->execute([$postId]);

        try {
            if (count($imageUrls) > 1) {
                $res = InstagramService::publishCarousel($igUserId, $imageUrls, $fullCaption, $accessToken);
            } else {
                $res = InstagramService::publishSingleImage($igUserId, $imageUrls[0], $fullCaption, $accessToken);
            }

            $igMediaId = $res['post_id'] ?? null;

            // Mark post as published
            $pubStmt = $pdo->prepare("UPDATE scheduled_posts SET status = 'published', published_at = NOW(), instagram_media_id = ?, updated_at = NOW() WHERE id = ?");
            $pubStmt->execute([$igMediaId, $postId]);

            log_activity('cron_published', "Published scheduled post #{$postId} on @{$post['instagram_username']} (IG ID: {$igMediaId})");

            $succeeded++;
            $results[] = ['id' => $postId, 'status' => 'published', 'instagram_media_id' => $igMediaId];

        } catch (Exception $e) {
            $err = $e->getMessage();
            $failStmt = $pdo->prepare("UPDATE scheduled_posts SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?");
            $failStmt->execute([$err, $postId]);

            log_activity('cron_failed', "Failed to publish scheduled post #{$postId}: {$err}");

            $failed++;
            $results[] = ['id' => $postId, 'status' => 'failed', 'error' => $err];
        }
    }

    $summary = [
        'ok' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'processed' => $processed,
        'succeeded' => $succeeded,
        'failed' => $failed,
        'results' => $results
    ];

    if ($isCli) {
        echo "[CRON] " . json_encode($summary) . "\n";
    } else {
        json_response($summary);
    }

} catch (Exception $e) {
    if ($isCli) {
        echo "[CRON ERROR] " . $e->getMessage() . "\n";
    } else {
        json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}
