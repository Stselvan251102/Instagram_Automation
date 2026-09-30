<?php
/**
 * Safe, Non-Destructive Database Table Setup
 * Carouselfy - Instagram Automation
 * Hostinger Shared Hosting Compatible
 *
 * Strictly executes CREATE TABLE IF NOT EXISTS.
 * Never runs DROP, DELETE, ALTER, or TRUNCATE.
 */

header('Content-Type: application/json; charset=utf-8');

require_once (file_exists(__DIR__ . '/../config/config.php') ? __DIR__ . '/../config/config.php' : __DIR__ . '/../../config/config.php');
require_once (file_exists(__DIR__ . '/../config/database.php') ? __DIR__ . '/../config/database.php' : __DIR__ . '/../../config/database.php');
require_once (file_exists(__DIR__ . '/../includes/functions.php') ? __DIR__ . '/../includes/functions.php' : __DIR__ . '/../../includes/functions.php');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (empty($token) || ($token !== CRON_SECRET && $token !== APP_SECRET)) {
    json_response(['ok' => false, 'error' => 'Unauthorized. Valid token required.'], 403);
}

$pdo = Database::getConnection();
if (!$pdo) {
    json_response([
        'ok' => false,
        'error' => 'Database not connected: ' . Database::getLastError()
    ], 500);
}

$sqlFile = dirname(__DIR__) . '/database.sql';
if (!file_exists($sqlFile)) {
    $sqlFile = dirname(__DIR__, 2) . '/database.sql';
}
if (!file_exists($sqlFile)) {
    $sqlFile = __DIR__ . '/../../database.sql';
}

if (!file_exists($sqlFile)) {
    json_response(['ok' => false, 'error' => 'database.sql schema file not found.'], 404);
}

$sqlContent = file_get_contents($sqlFile);

// Strip SQL comments cleanly before statement parsing
$sqlClean = preg_replace('/--.*$/m', '', $sqlContent);
$sqlClean = preg_replace('/\/\*.*?\*\//s', '', $sqlClean);

$rawStatements = explode(';', $sqlClean);
$statements = [];
foreach ($rawStatements as $stmt) {
    $stmt = trim($stmt);
    if (!empty($stmt) && preg_match('/^\s*(CREATE TABLE|SET)/i', $stmt)) {
        $statements[] = $stmt;
    }
}

$executed = [];
$errors = [];

foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        if (preg_match('/CREATE TABLE IF NOT EXISTS `?([a-zA-Z0-9_]+)`?/i', $stmt, $m)) {
            $executed[] = $m[1];
        }
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

json_response([
    'ok' => empty($errors),
    'database' => DB_NAME,
    'tables_created_or_verified' => array_values(array_unique($executed)),
    'errors' => $errors
]);
