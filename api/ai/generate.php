<?php
/**
 * API: Generate Carousel via AI
 * POST /api/ai/generate.php
 */

require_once (file_exists(__DIR__ . '/../../config/config.php') ? __DIR__ . '/../../config/config.php' : __DIR__ . '/../../../config/config.php');
require_once (file_exists(__DIR__ . '/../../includes/functions.php') ? __DIR__ . '/../../includes/functions.php' : __DIR__ . '/../../../includes/functions.php');
require_once (file_exists(__DIR__ . '/../../includes/ai.php') ? __DIR__ . '/../../includes/ai.php' : __DIR__ . '/../../../includes/ai.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$input = get_json_input();
$topic = trim($input['topic'] ?? '');
$count = isset($input['count']) ? (int)$input['count'] : 7;
$tone = trim($input['tone'] ?? 'Educational');
$apiKey = trim($input['apiKey'] ?? '');
$apiProvider = trim($input['apiProvider'] ?? '');

if (empty($topic)) {
    $topic = 'Healthy Morning Habits';
}
if ($count < 3 || $count > 10) {
    $count = 7;
}

try {
    $result = AIService::generate($topic, $count, $tone, $apiKey, $apiProvider);
    json_response($result);
} catch (Exception $e) {
    error_log("AI Generate Endpoint Error: " . $e->getMessage());
    // Fall back gracefully to topic-aware content generator
    $fallback = AIService::generateFallback($topic, $count, $tone);
    json_response([
        'ok' => true,
        'source' => 'fallback',
        'title' => $fallback['title'],
        'category' => $fallback['category'],
        'slides' => $fallback['slides'],
        'warning' => 'AI service unavailable: ' . $e->getMessage()
    ]);
}
