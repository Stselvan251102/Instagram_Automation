<?php
/**
 * API: Secure File and Rendered Slide Upload
 * POST /api/upload.php
 * Accepts both standard multipart $_FILES and base64 data URLs
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

$uploadDir = UPLOADS_PATH;
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$maxBytes = 15 * 1024 * 1024; // 15MB

// 1. Check for standard multipart file upload
if (!empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_response(['ok' => false, 'error' => 'Upload failed with error code: ' . $file['error']], 400);
    }
    if ($file['size'] > $maxBytes) {
        json_response(['ok' => false, 'error' => 'File size exceeds 15MB limit.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime, $allowedMimes)) {
        json_response(['ok' => false, 'error' => "Invalid file type ({$mime}). Only PNG, JPEG, and WebP are allowed."], 400);
    }

    $ext = $allowedMimes[$mime];
    $filename = 'media_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        json_response(['ok' => false, 'error' => 'Failed to save uploaded file.'], 500);
    }

    $publicUrl = APP_URL . '/uploads/' . $filename;
    json_response([
        'ok' => true,
        'filename' => $filename,
        'url' => $publicUrl,
        'relative_url' => '/uploads/' . $filename,
        'size' => $file['size']
    ]);
}

// 2. Check for base64 encoded data URI (from client canvas html-to-image render)
$input = get_json_input();
if (!empty($input['image'])) {
    $dataUri = $input['image'];
    if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $dataUri, $matches)) {
        $imgType = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2]);
        if ($binary === false) {
            json_response(['ok' => false, 'error' => 'Invalid base64 payload.'], 400);
        }
        if (strlen($binary) > $maxBytes) {
            json_response(['ok' => false, 'error' => 'Payload exceeds 15MB limit.'], 400);
        }

        $filename = 'slide_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $imgType;
        $targetPath = $uploadDir . '/' . $filename;

        if (file_put_contents($targetPath, $binary) === false) {
            json_response(['ok' => false, 'error' => 'Failed to write image buffer.'], 500);
        }

        $publicUrl = APP_URL . '/uploads/' . $filename;
        json_response([
            'ok' => true,
            'filename' => $filename,
            'url' => $publicUrl,
            'relative_url' => '/uploads/' . $filename,
            'size' => strlen($binary)
        ]);
    } else {
        json_response(['ok' => false, 'error' => 'Invalid data URI format.'], 400);
    }
}

json_response(['ok' => false, 'error' => 'No file or image payload received.'], 400);
