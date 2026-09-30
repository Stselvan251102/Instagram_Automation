<?php
/**
 * API: Delete Project
 * POST /api/projects/delete.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$id = trim($input['id'] ?? ($_GET['id'] ?? ''));
if (empty($id)) {
    json_response(['ok' => false, 'error' => 'Missing project ID.'], 400);
}

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => false, 'error' => 'Database not connected.'], 503);
}

try {
    $userId = current_user_id();
    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$id, $userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
    }

    log_activity('project_delete', "Deleted project {$id}");
    json_response(['ok' => true]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
