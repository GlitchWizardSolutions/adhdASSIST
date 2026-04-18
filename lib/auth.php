<?php
/**
 * ADHD Dashboard - Authentication & Session Management
 */

require_once __DIR__ . '/database.php';

class Auth {
    const SESSION_TIMEOUT = 86400; // 24 hours
    const SESSION_COOKIE_NAME = 'adhd_session';

    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Register new user
     */
    public static function register($email, $password, $firstName = '', $lastName = '') {
        try {
            $pdo = db();

            // Check if user exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email already registered'];
            }

            // Hash password
            $hashedPassword = self::hashPassword($password);

            // Insert user
            $stmt = $pdo->prepare('
                INSERT INTO users (email, password, first_name, last_name)
                VALUES (?, ?, ?, ?)
            ');
            $result = $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);

            if ($result) {
                $userId = $pdo->lastInsertId();
                
                // Create user settings entry
                $settingsStmt = $pdo->prepare('
                    INSERT INTO user_settings (user_id)
                    VALUES (?)
                ');
                $settingsStmt->execute([$userId]);

                return ['success' => true, 'user_id' => $userId];
            }

            return ['success' => false, 'error' => 'Registration failed'];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Login user and create session
     */
    public static function login($email, $password) {
        try {
            $pdo = db();

            // Get user
            $stmt = $pdo->prepare('
                SELECT id, password, is_active, is_verified
                FROM users
                WHERE email = ?
            ');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }

            if (!$user['is_active']) {
                return ['success' => false, 'error' => 'Account is inactive'];
            }

            // Verify password
            if (!self::verifyPassword($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }

            // Create session
            $sessionResult = self::createSession($user['id']);
            if (!$sessionResult['success']) {
                return $sessionResult;
            }

            // Update last login
            $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);

            return [
                'success' => true,
                'user_id' => $user['id'],
                'session_token' => $sessionResult['token']
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Create a new session
     */
    public static function createSession($userId) {
        try {
            $pdo = db();

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_TIMEOUT);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Insert session
            $stmt = $pdo->prepare('
                INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ');
            $result = $stmt->execute([$userId, $token, $ipAddress, $userAgent, $expiresAt]);

            if ($result) {
                // Set session cookie
                setcookie(
                    self::SESSION_COOKIE_NAME,
                    $token,
                    time() + self::SESSION_TIMEOUT,
                    '/',
                    '',
                    true,
                    true
                );

                return ['success' => true, 'token' => $token];
            }

            return ['success' => false, 'error' => 'Failed to create session'];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Validate current session
     */
    public static function validateSession() {
        try {
            $token = $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;

            if (!$token) {
                return ['success' => false, 'authenticated' => false];
            }

            $pdo = db();

            // Get session
            $stmt = $pdo->prepare('
                SELECT user_id, expires_at
                FROM sessions
                WHERE session_token = ? AND is_active = TRUE
            ');
            $stmt->execute([$token]);
            $session = $stmt->fetch();

            if (!$session) {
                return ['success' => false, 'authenticated' => false];
            }

            // Check expiration
            if (strtotime($session['expires_at']) < time()) {
                // Invalidate session
                $invalidStmt = $pdo->prepare('UPDATE sessions SET is_active = FALSE WHERE session_token = ?');
                $invalidStmt->execute([$token]);

                return ['success' => false, 'authenticated' => false];
            }

            // Update last activity
            $updateStmt = $pdo->prepare('UPDATE sessions SET last_activity = NOW() WHERE session_token = ?');
            $updateStmt->execute([$token]);

            // IMPORTANT: Set $_SESSION variables so API endpoints can access them
            $_SESSION['user_id'] = $session['user_id'];
            $_SESSION['session_token'] = $token;

            return [
                'success' => true,
                'authenticated' => true,
                'user_id' => $session['user_id']
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'authenticated' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public static function logout() {
        try {
            $token = $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;

            if ($token) {
                $pdo = db();
                $stmt = $pdo->prepare('UPDATE sessions SET is_active = FALSE WHERE session_token = ?');
                $stmt->execute([$token]);
            }

            // Clear cookie
            setcookie(self::SESSION_COOKIE_NAME, '', time() - 3600, '/', '', true, true);

            return ['success' => true];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        $session = self::validateSession();

        if (!$session['authenticated']) {
            return null;
        }

        try {
            $pdo = db();
            $stmt = $pdo->prepare('
                SELECT id, email, username, first_name, last_name, avatar_url, phone_number, 
                       mobile_carrier, notification_phone, mailing_address, timezone, theme_preference, 
                       low_energy_mode, task_reschedule_mode, daily_habits_reset_time, is_verified, role, created_at
                FROM users
                WHERE id = ?
            ');
            $stmt->execute([$session['user_id']]);
            return $stmt->fetch();

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        $session = self::validateSession();
        return $session['authenticated'] === true;
    }

    /**
     * Require authentication (redirect if not authenticated)
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            header('Location: /public_html/adhdASSIST/views/login.php');
            exit;
        }
    }
}

// Auto-validate session on include
Auth::validateSession();
?>
