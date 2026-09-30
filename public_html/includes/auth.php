<?php
/**
 * Authentication Module
 * Secure session handling and user management
 */

require_once __DIR__ . '/functions.php';

class Auth {
    /**
     * Authenticate user with email and password
     */
    public static function login(string $email, string $password): array {
        $email = trim(strtolower($email));
        if (empty($email) || empty($password)) {
            return ['ok' => false, 'error' => 'Please provide both email and password.'];
        }

        $pdo = Database::getConnection();
        if (!$pdo) {
            // Demo mode if database is not configured
            if ($email === 'admin@carouselfy.local' && $password === 'admin123') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'Admin';
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'admin';
                return ['ok' => true, 'user' => ['id' => 1, 'name' => 'Admin', 'email' => $email, 'role' => 'admin']];
            }
            return ['ok' => false, 'error' => 'Database connection unavailable and invalid credentials.'];
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['ok' => false, 'error' => 'Invalid email or password.'];
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            log_activity('user_login', 'User logged in: ' . $email);

            return [
                'ok' => true,
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Authentication failed: ' . $e->getMessage()];
        }
    }

    /**
     * Register a new user
     */
    public static function register(string $name, string $email, string $password): array {
        $name = trim($name);
        $email = trim(strtolower($email));

        if (empty($name) || strlen($name) < 2) {
            return ['ok' => false, 'error' => 'Name must be at least 2 characters long.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Please provide a valid email address.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Password must be at least 6 characters long.'];
        }

        $pdo = Database::getConnection();
        if (!$pdo) {
            return ['ok' => false, 'error' => 'Database connection required for user registration.'];
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['ok' => false, 'error' => 'An account with this email address already exists.'];
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $insert->execute([$name, $email, $hash]);
            $userId = (int)$pdo->lastInsertId();

            // Create default brand kit for new user
            $brandInsert = $pdo->prepare("INSERT INTO brand_kits (id, user_id, name, handle, profile_url, logos_json, primary_color, secondary_color, accent_color, canvas_color, font_heading, font_body, active_logo_index) VALUES (?, ?, ?, ?, ?, '[]', '#6366f1', '#22d3ee', '#f472b6', '#0b1020', \"'Space Grotesk', sans-serif\", \"'Inter', sans-serif\", 0)");
            $brandInsert->execute([uid('bk'), $userId, $name, '@' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $name)), APP_URL]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';

            log_activity('user_register', 'New user registered: ' . $email);

            return [
                'ok' => true,
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'user',
                ]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Registration error: ' . $e->getMessage()];
        }
    }

    /**
     * Terminate current session
     */
    public static function logout(): void {
        log_activity('user_logout', 'User logged out');
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
