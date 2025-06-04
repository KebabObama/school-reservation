<?php

declare(strict_types=1);

echo "🗑️  Resetting database completely...\n";

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    throw new RuntimeException('.env file not found');
}

// Parse .env file into array
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with($line, '#')) continue;
    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $env[$key] = $value;
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$dbName = $env['DB_NAME'] ?? 'room_manager';

// Connect to MySQL server without DB to drop and recreate database
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "Dropping existing database '$dbName' if it exists...\n";
$pdo->exec("DROP DATABASE IF EXISTS `$dbName`");

echo "Creating fresh database '$dbName'...\n";
$pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$pdo->exec("USE `$dbName`");

echo "✅ Database reset completed successfully\n";