<?php

/**
 * Database Migration Runner
 * 
 * This script runs database migrations in the correct order.
 * Usage: php run-migrations.php [options]
 * 
 * Options:
 *   --dry-run    Show what would be executed without running
 *   --force      Force run all migrations (ignores migration tracking)
 *   --specific   Run a specific migration file (e.g. --specific=001_init.php)
 *   --help       Show this help message
 */

declare(strict_types=1);

// Parse command line arguments
$options = getopt('', ['dry-run', 'force', 'specific:', 'help']);

if (isset($options['help'])) {
  showHelp();
  exit(0);
}

$dryRun = isset($options['dry-run']);
$force = isset($options['force']);
$specific = $options['specific'] ?? null;

echo "=== Database Migration Runner ===\n\n";

if ($dryRun) {
  echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

try {
  // Load environment and connect to database
  $envPath = __DIR__ . '/.env';
  if (!file_exists($envPath)) {
    throw new RuntimeException('.env file not found. Please create it with database configuration.');
  }

  $env = loadEnv($envPath);
  $pdo = connectToDatabase($env);

  // Create migrations tracking table if it doesn't exist
  if (!$dryRun) {
    createMigrationsTable($pdo);
  }

  // Get list of migration files
  $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');

  if (empty($migrationFiles)) {
    echo "❌ No migration files found in migrations/ directory\n";
    exit(1);
  }

  echo "📁 Found " . count($migrationFiles) . " migration file(s):\n";
  foreach ($migrationFiles as $file) {
    echo "   - $file\n";
  }
  echo "\n";

  // Handle specific migration
  if ($specific) {
    runSpecificMigration($pdo, $specific, $dryRun);
    exit(0);
  }

  // Get already run migrations
  $runMigrations = $force ? [] : getRunMigrations($pdo);

  if (!empty($runMigrations)) {
    echo "✅ Already run migrations:\n";
    foreach ($runMigrations as $migration) {
      echo "   - {$migration['filename']} (run on {$migration['executed_at']})\n";
    }
    echo "\n";
  }

  // Filter out already run migrations
  $pendingMigrations = $force ? $migrationFiles : array_filter($migrationFiles, function ($file) use ($runMigrations) {
    return !in_array($file, array_column($runMigrations, 'filename'));
  });

  if (empty($pendingMigrations)) {
    echo "✅ All migrations are up to date!\n";
    exit(0);
  }

  echo "🚀 Pending migrations to run:\n";
  foreach ($pendingMigrations as $file) {
    echo "   - $file\n";
  }
  echo "\n";

  if ($dryRun) {
    echo "🔍 DRY RUN: Would execute " . count($pendingMigrations) . " migration(s)\n";
    exit(0);
  }

  // Check if 001_init.php is in pending migrations (destructive operation)
  $hasInitMigration = in_array('001_init.php', $pendingMigrations);

  // Confirm before running
  if (!$force) {
    if ($hasInitMigration) {
      echo "🚨 WARNING: The 001_init.php migration will DROP and RECREATE the entire database!\n";
      echo "🚨 This will DELETE ALL EXISTING DATA!\n";
      echo "⚠️  Are you absolutely sure you want to continue? Type 'YES' to confirm: ";
    } else {
      echo "⚠️  Are you sure you want to run these migrations? (y/N): ";
    }

    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    $confirmation = trim($line);
    if ($hasInitMigration && $confirmation !== 'YES') {
      echo "❌ Migration cancelled - database destruction not confirmed\n";
      exit(0);
    } elseif (!$hasInitMigration && strtolower($confirmation) !== 'y') {
      echo "❌ Migration cancelled\n";
      exit(0);
    }
  }

  // Run pending migrations
  foreach ($pendingMigrations as $file) {
    runMigration($pdo, $file);
  }

  echo "\n✅ All migrations completed successfully!\n";
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  exit(1);
}

function showHelp(): void
{
  echo "Database Migration Runner\n\n";
  echo "⚠️  WARNING: 001_init.php migration will DROP and RECREATE the entire database!\n\n";
  echo "Usage: php run-migrations.php [options]\n\n";
  echo "Options:\n";
  echo "  --dry-run    Show what would be executed without running\n";
  echo "  --force      Force run all migrations (ignores migration tracking)\n";
  echo "  --specific   Run a specific migration file (e.g. --specific=001_init.php)\n";
  echo "  --help       Show this help message\n\n";
  echo "Examples:\n";
  echo "  php run-migrations.php                    # Run all pending migrations\n";
  echo "  php run-migrations.php --dry-run          # See what would be run\n";
  echo "  php run-migrations.php --force            # Force run all migrations\n";
  echo "  php run-migrations.php --specific=001_init.php  # Run specific migration\n\n";
  echo "Quick Reset:\n";
  echo "  php reset-database.php                    # Complete database reset\n";
}

function loadEnv(string $path): array
{
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

function connectToDatabase(array $env): PDO
{
  $host = $env['DB_HOST'] ?? '127.0.0.1';
  $port = $env['DB_PORT'] ?? '3306';
  $user = $env['DB_USER'] ?? 'root';
  $pass = $env['DB_PASS'] ?? '';
  $dbName = $env['DB_NAME'] ?? 'room_manager';

  try {
    // Connect to MySQL server without specifying database
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Try to use the database, create if it doesn't exist
    try {
      $pdo->exec("USE `$dbName`");
      echo "✅ Connected to existing database: $dbName\n\n";
    } catch (PDOException $e) {
      // Database doesn't exist, will be created by 001_init.php migration
      echo "✅ Connected to MySQL server (database will be created by migration)\n\n";
    }

    return $pdo;
  } catch (PDOException $e) {
    throw new RuntimeException("Database connection failed: " . $e->getMessage());
  }
}

function createMigrationsTable(PDO $pdo): void
{
  $sql = "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
  $pdo->exec($sql);
}

function getMigrationFiles(string $dir): array
{
  if (!is_dir($dir)) {
    throw new RuntimeException("Migrations directory not found: $dir");
  }

  $files = glob($dir . '/*.php');
  $files = array_map('basename', $files);
  sort($files);
  return $files;
}

function getRunMigrations(PDO $pdo): array
{
  try {
    // Check if we're connected to a database
    $pdo->query("SELECT DATABASE()")->fetchColumn();

    // Try to get migrations
    $stmt = $pdo->query("SELECT filename, executed_at FROM migrations ORDER BY filename");
    return $stmt->fetchAll();
  } catch (PDOException $e) {
    // Database or table doesn't exist yet
    return [];
  }
}

function runMigration(PDO $pdo, string $filename): void
{
  $filepath = __DIR__ . '/migrations/' . $filename;

  if (!file_exists($filepath)) {
    throw new RuntimeException("Migration file not found: $filepath");
  }

  echo "🔄 Running migration: $filename\n";

  try {
    // Capture output from migration
    ob_start();
    require $filepath;
    $output = ob_get_clean();

    if (!empty($output)) {
      echo "   Output: " . trim($output) . "\n";
    }

    // If this is 001_init.php, recreate the migrations table since it was dropped
    if ($filename === '001_init.php') {
      createMigrationsTable($pdo);
    }

    // Record migration as completed
    try {
      $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (filename) VALUES (?)");
      $stmt->execute([$filename]);
    } catch (PDOException $e) {
      // If migrations table doesn't exist, create it and try again
      createMigrationsTable($pdo);
      $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (filename) VALUES (?)");
      $stmt->execute([$filename]);
    }

    echo "✅ Completed: $filename\n";
  } catch (Exception $e) {
    // Only call ob_end_clean if there's actually a buffer
    if (ob_get_level() > 0) {
      ob_end_clean();
    }
    throw new RuntimeException("Migration failed ($filename): " . $e->getMessage());
  }
}

function runSpecificMigration(PDO $pdo, string $filename, bool $dryRun): void
{
  $filepath = __DIR__ . '/migrations/' . $filename;

  if (!file_exists($filepath)) {
    echo "❌ Migration file not found: $filename\n";
    exit(1);
  }

  echo "🎯 Running specific migration: $filename\n\n";

  if ($dryRun) {
    echo "🔍 DRY RUN: Would execute $filename\n";
    return;
  }

  runMigration($pdo, $filename);
  echo "\n✅ Specific migration completed!\n";
}
