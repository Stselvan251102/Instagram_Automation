<?php
/**
 * API: Save Project to MySQL Database
 * POST /api/projects/save.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$project = $input['project'] ?? null;
if (!$project || !is_array($project) || empty($project['id'])) {
    json_response(['ok' => false, 'error' => 'Invalid project payload.'], 400);
}

$pdo = Database::getConnection();
if (!$pdo) {
    // If DB is not configured, acknowledge save (client keeps localStorage)
    json_response([
        'ok' => true,
        'id' => $project['id'],
        'saved_at' => time(),
        'notice' => 'Project saved locally in browser storage (database not configured).'
    ]);
}

try {
    $userId = current_user_id();
    $id = $project['id'];
    $title = $project['title'] ?? 'Untitled Carousel';
    $topic = $project['topic'] ?? '';
    $category = $project['category'] ?? 'CAROUSEL';
    $aspectRatio = in_array($project['aspectRatio'] ?? '', ['4:5', '1:1', '9:16']) ? $project['aspectRatio'] : '4:5';
    $templateId = $project['templateId'] ?? 'stacked-minimal-midnight';
    $brandKitId = $project['brandKitId'] ?? null;
    $showWatermark = !empty($project['showWatermark']) ? 1 : 0;
    $showProgress = !empty($project['showProgress']) ? 1 : 0;
    $headerScale = isset($project['headerScale']) ? (float)$project['headerScale'] : 1.0;
    $footerScale = isset($project['footerScale']) ? (float)$project['footerScale'] : 1.0;
    $slidesJson = json_encode($project['slides'] ?? []);

    $sql = "INSERT INTO projects 
            (id, user_id, title, topic, category, aspect_ratio, template_id, brand_kit_id, show_watermark, show_progress, header_scale, footer_scale, slides_json, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            title = VALUES(title),
            topic = VALUES(topic),
            category = VALUES(category),
            aspect_ratio = VALUES(aspect_ratio),
            template_id = VALUES(template_id),
            brand_kit_id = VALUES(brand_kit_id),
            show_watermark = VALUES(show_watermark),
            show_progress = VALUES(show_progress),
            header_scale = VALUES(header_scale),
            footer_scale = VALUES(footer_scale),
            slides_json = VALUES(slides_json),
            updated_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $id,
        $userId,
        $title,
        $topic,
        $category,
        $aspectRatio,
        $templateId,
        $brandKitId,
        $showWatermark,
        $showProgress,
        $headerScale,
        $footerScale,
        $slidesJson
    ]);

    // Save brand kits if sent
    if (!empty($input['brandKits']) && is_array($input['brandKits'])) {
        foreach ($input['brandKits'] as $bk) {
            if (empty($bk['id']) || empty($bk['name'])) continue;
            $bkStmt = $pdo->prepare("INSERT INTO brand_kits 
                (id, user_id, name, handle, profile_url, logos_json, primary_color, secondary_color, accent_color, canvas_color, font_heading, font_body, active_logo_index, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                handle = VALUES(handle),
                profile_url = VALUES(profile_url),
                logos_json = VALUES(logos_json),
                primary_color = VALUES(primary_color),
                secondary_color = VALUES(secondary_color),
                accent_color = VALUES(accent_color),
                canvas_color = VALUES(canvas_color),
                font_heading = VALUES(font_heading),
                font_body = VALUES(font_body),
                active_logo_index = VALUES(active_logo_index),
                updated_at = NOW()");
            
            $bkStmt->execute([
                $bk['id'],
                $userId,
                $bk['name'],
                $bk['handle'] ?? '@carouselfy',
                $bk['profileUrl'] ?? '',
                json_encode($bk['logos'] ?? []),
                $bk['primaryColor'] ?? '#6366f1',
                $bk['secondaryColor'] ?? '#22d3ee',
                $bk['accentColor'] ?? '#f472b6',
                $bk['canvasColor'] ?? '#0b1020',
                $bk['fontHeading'] ?? "'Space Grotesk', sans-serif",
                $bk['fontBody'] ?? "'Inter', sans-serif",
                (int)($bk['activeLogoIndex'] ?? 0)
            ]);
        }
    }

    log_activity('project_save', "Saved project: {$title} ({$id})");

    json_response([
        'ok' => true,
        'id' => $id,
        'saved_at' => time()
    ]);
} catch (Exception $e) {
    error_log("Failed to save project: " . $e->getMessage());
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
