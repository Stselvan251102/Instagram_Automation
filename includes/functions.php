<?php
/**
 * Shared Helper Functions
 * Carouselfy - Instagram Carousel Automation Studio
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Send JSON response and exit
 */
function json_response($data, int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
    }
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Read and decode incoming JSON request body
 */
function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * XSS-safe output sanitization
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Token Management
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Authentication Helpers
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Creator',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            json_response(['ok' => false, 'error' => 'Authentication required.'], 401);
        }
        header("Location: $redirect");
        exit;
    }
}

/**
 * Generate unique IDs similar to JavaScript client
 */
function uid(string $prefix = 'id'): string {
    return $prefix . '_' . substr(base_convert((string)microtime(true) * 10000, 10, 36), -6) . '_' . substr(bin2hex(random_bytes(4)), 0, 6);
}

/**
 * Activity Logger
 */
function log_activity(string $action, ?string $details = null): void {
    $pdo = Database::getConnection();
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute([current_user_id(), $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently log
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
