<?php
/**
 * Database Reset Script
 * 
 * This script completely resets the database by:
 * 1. Dropping the entire database
 * 2. Running the 001_init.php migration to recreate everything fresh
 * 
 * ⚠️  WARNING: This will DELETE ALL DATA in the database!
 * 
 * Usage: php reset-database.php [--force]
 * 
 * Options:
 *   --force    Skip confirmation prompt
 */

declare(strict_types=1);

$options = getopt('', ['force', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$force = isset($options['force']);

echo "🚨 === DATABASE RESET SCRIPT === 🚨\n\n";
echo "⚠️  WARNING: This will completely destroy and recreate your database!\n";
echo "⚠️  ALL DATA WILL BE PERMANENTLY LOST!\n\n";

if (!$force) {
    echo "Are you absolutely sure you want to reset the database?\n";
    echo "Type 'RESET' to confirm (anything else will cancel): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) !== 'RESET') {
        echo "❌ Database reset cancelled\n";
        exit(0);
    }
}

try {
    // Load environment
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        throw new RuntimeException('.env file not found. Please create it with database configuration.');
    }

    $env = loadEnv($envPath);
    
    // Connect and reset database
    echo "\n🔄 Connecting to MySQL server...\n";
    $pdo = connectToMySQLServer($env);
    
    $dbName = $env['DB_NAME'] ?? 'room_manager';
    
    echo "🗑️  Dropping database '$dbName'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    
    echo "🆕 Creating fresh database '$dbName'...\n";
    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$dbName`");
    
    echo "📋 Running initial migration...\n";
    
    // Run the 001_init.php migration directly
    $migrationFile = __DIR__ . '/migrations/001_init.php';
    if (!file_exists($migrationFile)) {
        throw new RuntimeException("001_init.php migration file not found!");
    }
    
    // Capture output from migration
    ob_start();
    require $migrationFile;
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "Migration output:\n" . $output . "\n";
    }
    
    echo "\n✅ Database reset completed successfully!\n";
    echo "🎉 Fresh database with admin user (admin@spst.cz / admin) is ready!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function showHelp(): void {
    echo "Database Reset Script\n\n";
    echo "⚠️  WARNING: This completely destroys and recreates the database!\n\n";
    echo "Usage: php reset-database.php [options]\n\n";
    echo "Options:\n";
    echo "  --force    Skip confirmation prompt\n";
    echo "  --help     Show this help message\n\n";
    echo "Examples:\n";
    echo "  php reset-database.php         # Reset with confirmation\n";
    echo "  php reset-database.php --force # Reset without confirmation\n";
}

function loadEnv(string $path): array {
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = $value;
    }
    return $env;
}

function connectToMySQLServer(array $env): PDO {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        echo "✅ Connected to MySQL server\n";
        return $pdo;
    } catch (PDOException $e) {
        throw new RuntimeException("MySQL connection failed: " . $e->getMessage());
    }
}
?>
