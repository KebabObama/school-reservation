<?php
require_once __DIR__ . '/db.php';

function generateSecureToken(): string
{
  return bin2hex(random_bytes(32));
}

function createAuthToken(int $userId, int $expirationHours = 24): array
{
  global $pdo;
  $token = generateSecureToken();
  $expiresAt = date('Y-m-d H:i:s', time() + ($expirationHours * 3600));
  cleanupExpiredTokens($userId);
  $stmt = $pdo->prepare('INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
  $stmt->execute([$userId, $token, $expiresAt]);
  return [
    'token' => $token,
    'expires_at' => $expiresAt,
    'expires_in' => $expirationHours * 3600
  ];
}

function validateAuthToken(string $token): ?array
{
  global $pdo;
  $stmt = $pdo->prepare('
        SELECT t.user_id, t.expires_at, u.email, u.name, u.surname, u.is_verified
        FROM tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token = ? AND t.expires_at > NOW()
    ');
  $stmt->execute([$token]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$result)
    return null;
  return [
    'user_id' => (int)$result['user_id'],
    'email' => $result['email'],
    'name' => $result['name'],
    'surname' => $result['surname'],
    'is_verified' => (bool)$result['is_verified'],
    'expires_at' => $result['expires_at']
  ];
}

function cleanupExpiredTokens(?int $userId = null): void
{
  global $pdo;

  if ($userId) {
    $stmt = $pdo->prepare('DELETE FROM tokens WHERE user_id = ? AND expires_at <= NOW()');
    $stmt->execute([$userId]);
  } else {
    $stmt = $pdo->prepare('DELETE FROM tokens WHERE expires_at <= NOW()');
    $stmt->execute();
  }
}

function revokeToken(string $token): bool
{
  global $pdo;
  $stmt = $pdo->prepare('DELETE FROM tokens WHERE token = ?');
  $stmt->execute([$token]);
  return $stmt->rowCount() > 0;
}

function revokeAllUserTokens(int $userId): void
{
  global $pdo;
  $stmt = $pdo->prepare('DELETE FROM tokens WHERE user_id = ?');
  $stmt->execute([$userId]);
}

function loginWithToken(string $email, string $password): array
{
  global $pdo;
  $stmt = $pdo->prepare('SELECT id, password_hash, is_verified FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user || !password_verify($password, $user['password_hash']))
    return ['success' => false, 'error' => 'Invalid email or password'];
  if (!$user['is_verified'])
    return ['success' => false, 'error' => 'Account not verified'];
  $_SESSION['user_id'] = $user['id'];
  $tokenData = createAuthToken($user['id']);
  return [
    'success' => true,
    'user_id' => $user['id'],
    'token' => $tokenData['token'],
    'expires_at' => $tokenData['expires_at'],
    'expires_in' => $tokenData['expires_in']
  ];
}

function login(string $email, string $password): void
{
  $result = loginWithToken($email, $password);
}

function register(string $email, string $name, string $surname, string $password): void
{
  global $pdo;

  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
  $stmt->execute([$email]);
  if ($stmt->fetch())
    return;
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (email, name, surname, password_hash, is_verified) VALUES (?, ?, ?, ?, 0)');
  $stmt->execute([$email, $name, $surname, $hash]);
}

function logout(?string $token = null): void
{
  if ($token)
    revokeToken($token);
  if (isset($_SESSION['user_id']))
    revokeAllUserTokens($_SESSION['user_id']);
  session_destroy();
}
