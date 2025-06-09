<?php
require_once __DIR__ . '/auth.php';
class TokenMiddleware
{
  public static function authenticate(): ?array
  {
    $token = self::extractToken();
    if ($token) {
      $userData = validateAuthToken($token);
      if ($userData) {
        if (session_status() === PHP_SESSION_NONE)
          session_start();
        $_SESSION['user_id'] = $userData['user_id'];
        return $userData;
      }
    }
    if (session_status() === PHP_SESSION_NONE)
      session_start();
    if (isset($_SESSION['user_id'])) {
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
  private static function extractToken(): ?string
  {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
      $authHeader = $headers['Authorization'];
      if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches))
        return $matches[1];
    }
    if (isset($headers['X-Auth-Token']))
      return $headers['X-Auth-Token'];
    if (isset($_GET['token']))
      return $_GET['token'];
    if (isset($_POST['token'])) {
      return $_POST['token'];
    }
    return null;
  }
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
  public static function generateCSRFToken(): string
  {
    if (session_status() === PHP_SESSION_NONE)
      session_start();
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
  }
  public static function validateCSRFToken(string $token): bool
  {
    if (session_status() === PHP_SESSION_NONE) 
      session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
  }
  public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): bool
  {
    if (session_status() === PHP_SESSION_NONE) 
      session_start();
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    $window = $windowMinutes * 60;
    if (!isset($_SESSION[$key])) 
      $_SESSION[$key] = [];
    $_SESSION[$key] = array_filter($_SESSION[$key], function ($timestamp) use ($now, $window) {
      return ($now - $timestamp) < $window;
    });
    if (count($_SESSION[$key]) >= $maxAttempts) 
      return false;
    $_SESSION[$key][] = $now;
    return true;
  }
  public static function getRemainingAttempts(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): int
  {
    if (session_status() === PHP_SESSION_NONE) 
      session_start();
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    $window = $windowMinutes * 60;
    if (!isset($_SESSION[$key])) 
      return $maxAttempts;
    $_SESSION[$key] = array_filter($_SESSION[$key], function ($timestamp) use ($now, $window) {
      return ($now - $timestamp) < $window;
    });
    return max(0, $maxAttempts - count($_SESSION[$key]));
  }
  public static function clearRateLimit(string $identifier): void
  {
    if (session_status() === PHP_SESSION_NONE) 
      session_start();
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
  }
}
function requireAuth(): array
{
  return TokenMiddleware::requireAuth();
}
function getAuthenticatedUser(): ?array
{
  return TokenMiddleware::authenticate();
}