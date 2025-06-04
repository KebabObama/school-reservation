<?php
/**
 * Migration Status Checker
 * 
 * This script shows the current status of database migrations.
 * Usage: php migration-status.php
 */

declare(strict_types=1);

echo "=== Migration Status ===\n\n";

try {
    // Load environment and connect to database
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        throw new RuntimeException('.env file not found. Please create it with database configuration.');
    }

    $env = loadEnv($envPath);
    $pdo = connectToDatabase($env);

    // Get list of migration files
    $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');
    
    if (empty($migrationFiles)) {
        echo "❌ No migration files found in migrations/ directory\n";
        exit(1);
    }

    // Get already run migrations
    $runMigrations = getRunMigrations($pdo);
    $runMigrationFiles = array_column($runMigrations, 'filename');

    echo "📊 Migration Status:\n\n";
    
    foreach ($migrationFiles as $file) {
        $isRun = in_array($file, $runMigrationFiles);
        $status = $isRun ? "✅ RUN" : "⏳ PENDING";
        
        if ($isRun) {
            $runInfo = array_filter($runMigrations, fn($m) => $m['filename'] === $file)[0];
            $runDate = $runInfo['executed_at'];
            echo "  $status  $file (executed: $runDate)\n";
        } else {
            echo "  $status  $file\n";
        }
    }

    $pendingCount = count($migrationFiles) - count($runMigrationFiles);
    
    echo "\n📈 Summary:\n";
    echo "  Total migrations: " . count($migrationFiles) . "\n";
    echo "  Completed: " . count($runMigrationFiles) . "\n";
    echo "  Pending: " . $pendingCount . "\n";

    if ($pendingCount > 0) {
        echo "\n⚠️  You have $pendingCount pending migration(s). Run 'php run-migrations.php' to execute them.\n";
    } else {
        echo "\n✅ All migrations are up to date!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
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

function connectToDatabase(array $env): PDO {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';
    $dbName = $env['DB_NAME'] ?? 'room_manager';

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        echo "✅ Connected to database: $dbName\n\n";
        return $pdo;
    } catch (PDOException $e) {
        throw new RuntimeException("Database connection failed: " . $e->getMessage());
    }
}

function getMigrationFiles(string $dir): array {
    if (!is_dir($dir)) {
        throw new RuntimeException("Migrations directory not found: $dir");
    }

    $files = glob($dir . '/*.php');
    $files = array_map('basename', $files);
    sort($files);
    return $files;
}

function getRunMigrations(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT filename, executed_at FROM migrations ORDER BY filename");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Table doesn't exist yet, no migrations have been run
        return [];
    }
}
?>
