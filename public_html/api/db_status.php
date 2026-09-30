<?php
/**
 * Safe, Read-Only Database Status & Schema Inspector
 * Carouselfy - Instagram Automation
 * Hostinger Shared Hosting Compatible
 *
 * Strictly Read-Only: Never runs DROP, DELETE, ALTER, TRUNCATE, or UPDATE.
 */

header('Content-Type: application/json; charset=utf-8');

// Load configuration flexibly (works at root or in public_html)
require_once (file_exists(__DIR__ . '/../config/config.php') ? __DIR__ . '/../config/config.php' : __DIR__ . '/../../config/config.php');
require_once (file_exists(__DIR__ . '/../config/database.php') ? __DIR__ . '/../config/database.php' : __DIR__ . '/../../config/database.php');
require_once (file_exists(__DIR__ . '/../includes/functions.php') ? __DIR__ . '/../includes/functions.php' : __DIR__ . '/../../includes/functions.php');

// Safe access verification: allow logged-in users, cron token, or app secret token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$isTokenValid = (!empty($token) && ($token === CRON_SECRET || $token === APP_SECRET));
$isLoggedIn = is_logged_in();

if (!$isTokenValid && !$isLoggedIn) {
    // Only return general connectivity status without sensitive details for unauthenticated requests
    $pdo = Database::getConnection();
    if (!$pdo) {
        json_response([
            'status' => 'not_configured',
            'connected' => false,
            'database' => DB_NAME,
            'message' => 'Database connection credentials not yet configured in .env',
            'help' => 'Set DB_PASSWORD in .env on Hostinger server or authenticate to view schema details.'
        ]);
        exit;
    }
}

$pdo = Database::getConnection();

if (!$pdo) {
    json_response([
        'status' => 'disconnected',
        'connected' => false,
        'database' => DB_NAME,
        'host' => DB_HOST,
        'user' => DB_USER,
        'error' => Database::getLastError(),
        'message' => 'Unable to connect to MySQL database.'
    ], 500);
    exit;
}

try {
    // 1. Fetch all existing tables in the database (strictly read-only)
    $stmt = $pdo->query("SHOW TABLES");
    $rawTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $tables = [];
    foreach ($rawTables as $tableName) {
        // Fetch row count safely
        $countStmt = $pdo->query("SELECT COUNT(*) AS `total` FROM `" . str_replace("`", "", $tableName) . "`");
        $rowCount = (int)($countStmt->fetch()['total'] ?? 0);

        // Fetch column structure safely
        $descStmt = $pdo->query("DESCRIBE `" . str_replace("`", "", $tableName) . "`");
        $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);

        $tables[$tableName] = [
            'row_count' => $rowCount,
            'column_count' => count($columns),
            'columns' => array_map(function($col) {
                return [
                    'field' => $col['Field'],
                    'type' => $col['Type'],
                    'null' => $col['Null'],
                    'key' => $col['Key'],
                    'default' => $col['Default']
                ];
            }, $columns)
        ];
    }

    // Expected application tables
    $expectedTables = [
        'users',
        'brand_kits',
        'projects',
        'instagram_accounts',
        'scheduled_posts',
        'activity_logs',
        'settings'
    ];

    $existingTableNames = array_keys($tables);
    $missingTables = array_values(array_diff($expectedTables, $existingTableNames));
    $extraTables = array_values(array_diff($existingTableNames, $expectedTables));

    json_response([
        'status' => 'ok',
        'connected' => true,
        'database' => DB_NAME,
        'host' => DB_HOST,
        'total_tables' => count($tables),
        'existing_tables' => $existingTableNames,
        'missing_tables' => $missingTables,
        'unrelated_tables_preserved' => $extraTables,
        'tables_metadata' => $tables,
        'ready_for_production' => empty($missingTables)
    ]);

} catch (PDOException $e) {
    json_response([
        'status' => 'error',
        'connected' => true,
        'database' => DB_NAME,
        'error' => 'Query error: ' . $e->getMessage()
    ], 500);
}
