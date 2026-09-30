<?php
/**
 * API: Get Single Project
 * GET /api/projects/get.php?id=proj_...
 */

require_once (file_exists(__DIR__ . '/../../config/config.php') ? __DIR__ . '/../../config/config.php' : __DIR__ . '/../../../config/config.php');
require_once (file_exists(__DIR__ . '/../../includes/functions.php') ? __DIR__ . '/../../includes/functions.php' : __DIR__ . '/../../../includes/functions.php');

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    json_response(['ok' => false, 'error' => 'Missing project ID.'], 400);
}

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => false, 'error' => 'Database not connected.'], 503);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        json_response(['ok' => false, 'error' => 'Project not found.'], 404);
    }

    $project = [
        'id' => $row['id'],
        'title' => $row['title'],
        'topic' => $row['topic'],
        'category' => $row['category'],
        'aspectRatio' => $row['aspect_ratio'],
        'templateId' => $row['template_id'],
        'brandKitId' => $row['brand_kit_id'],
        'showWatermark' => (bool)$row['show_watermark'],
        'showProgress' => (bool)$row['show_progress'],
        'headerScale' => (float)$row['header_scale'],
        'footerScale' => (float)$row['footer_scale'],
        'slides' => json_decode($row['slides_json'], true) ?: [],
    ];

    json_response([
        'ok' => true,
        'project' => $project
    ]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
