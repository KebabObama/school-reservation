<?php
declare(strict_types=1);
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath))
  throw new RuntimeException('.env file not found');
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
  if (str_starts_with($line, '#')) continue;
  [$key, $value] = array_map('trim', explode('=', $line, 2));
  $env[$key] = $value;
}
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$dbName = $env['DB_NAME'] ?? 'room_manager';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
try {
  $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  exit('Database connection failed: ' . $e->getMessage());
}