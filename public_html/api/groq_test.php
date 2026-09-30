<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$key = GROQ_API_KEY ?: OPENAI_API_KEY;

$ch = curl_init('https://api.groq.com/openai/v1/models');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);
$modelIds = [];
if (!empty($data['data'])) {
    foreach ($data['data'] as $m) {
        $modelIds[] = $m['id'];
    }
}

json_response([
    'http_code' => $code,
    'models' => $modelIds,
    'raw' => $data ?? $res
]);
