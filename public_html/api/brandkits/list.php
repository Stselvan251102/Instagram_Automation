<?php
/**
 * API: List Brand Kits
 * GET /api/brandkits/list.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['ok' => true, 'brand_kits' => []]);
}

try {
    $userId = current_user_id();
    if ($userId) {
        $stmt = $pdo->prepare("SELECT * FROM brand_kits WHERE user_id = ? OR user_id IS NULL ORDER BY created_at ASC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM brand_kits ORDER BY created_at ASC");
    }
    $rows = $stmt->fetchAll();

    $brandKits = array_map(function($r) {
        return [
            'id' => $r['id'],
            'name' => $r['name'],
            'handle' => $r['handle'],
            'profileUrl' => $r['profile_url'],
            'logos' => json_decode($r['logos_json'] ?? '[]', true) ?: [],
            'primaryColor' => $r['primary_color'],
            'secondaryColor' => $r['secondary_color'],
            'accentColor' => $r['accent_color'],
            'canvasColor' => $r['canvas_color'],
            'fontHeading' => $r['font_heading'],
            'fontBody' => $r['font_body'],
            'activeLogoIndex' => (int)$r['active_logo_index'],
        ];
    }, $rows);

    json_response(['ok' => true, 'brand_kits' => $brandKits]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
