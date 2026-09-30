<?php
/**
 * API: List Projects
 * GET /api/projects/list.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => true, 'projects' => []]);
}

try {
    $userId = current_user_id();
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, title, topic, category, aspect_ratio, template_id, updated_at, created_at, 
                               JSON_LENGTH(slides_json) as slides_count 
                               FROM projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 50");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT id, title, topic, category, aspect_ratio, template_id, updated_at, created_at, 
                             JSON_LENGTH(slides_json) as slides_count 
                             FROM projects ORDER BY updated_at DESC LIMIT 50");
    }
    $projects = $stmt->fetchAll();

    json_response([
        'ok' => true,
        'projects' => $projects
    ]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
