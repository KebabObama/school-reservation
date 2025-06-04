<?php
require_once __DIR__ . '/auth.php';

/**
 * Token-based authentication middleware
 * Supports both session-based and token-based authentication
 */
class TokenMiddleware
{
    /**
     * Authenticate request using token or session
     * Returns user data if authenticated, null otherwise
     */
    public static function authenticate(): ?array
    {
        // First try token authentication
        $token = self::extractToken();
        if ($token) {
            $userData = validateAuthToken($token);
            if ($userData) {
                // Set session for backward compatibility
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $userData['user_id'];
                return $userData;
            }
        }

        // Fallback to session authentication
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            // Get user data from database for session-based auth
            global $pdo;
            $stmt = $pdo->prepare('SELECT id, email, name, surname, is_verified FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [
                    'user_id' => (int)$user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'surname' => $user['surname'],
                    'is_verified' => (bool)$user['is_verified']
                ];
            }
        }

        return null;
    }

    /**
     * Extract token from request headers or query parameters
     */
    private static function extractToken(): ?string
    {
        // Check Authorization header (Bearer token)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Check X-Auth-Token header
        if (isset($headers['X-Auth-Token'])) {
            return $headers['X-Auth-Token'];
        }

        // Check query parameter (less secure, but useful for some cases)
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }

        // Check POST parameter
        if (isset($_POST['token'])) {
            return $_POST['token'];
        }

        return null;
    }

    /**
     * Require authentication for API endpoint
     * Sends 401 response and exits if not authenticated
     */
    public static function requireAuth(): array
    {
        $userData = self::authenticate();
        
        if (!$userData) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        return $userData;
    }

    /**
     * Generate CSRF token for forms
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiting for login attempts
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        $window = $windowMinutes * 60;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Clean old attempts
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        // Check if limit exceeded
        if (count($_SESSION[$key]) >= $maxAttempts) {
            return false;
        }

        // Record this attempt
        $_SESSION[$key][] = $now;
        return true;
    }

    /**
     * Get remaining rate limit attempts
     */
    public static function getRemainingAttempts(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        $window = $windowMinutes * 60;

        if (!isset($_SESSION[$key])) {
            return $maxAttempts;
        }

        // Clean old attempts
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        return max(0, $maxAttempts - count($_SESSION[$key]));
    }

    /**
     * Clear rate limit for identifier (useful after successful login)
     */
    public static function clearRateLimit(string $identifier): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = 'rate_limit_' . md5($identifier);
        unset($_SESSION[$key]);
    }
}

/**
 * Helper function for quick authentication check
 */
function requireAuth(): array
{
    return TokenMiddleware::requireAuth();
}

/**
 * Helper function for optional authentication
 */
function getAuthenticatedUser(): ?array
{
    return TokenMiddleware::authenticate();
}
