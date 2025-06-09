<?php

/**
 * Unified Database Migration Tool
 * 
 * This script provides a single interface for all database migration operations:
 * - Run pending migrations
 * - Check migration status
 * - Reset database completely
 * - Run specific migrations
 * 
 * Usage: php migrate.php <command> [options]
 * 
 * Commands:
 *   status                Show migration status
 *   run                   Run pending migrations
 *   reset                 Reset database completely (WARNING: destroys all data!)
 *   specific <filename>   Run a specific migration file
 * 
 * Options:
 *   --dry-run            Show what would be executed without running (for 'run' command)
 *   --force              Force run all migrations, ignoring tracking (for 'run' command)
 *   --force              Skip confirmation prompt (for 'reset' command)
 *   --help               Show this help message
 */

declare(strict_types=1);

$command = $argv[1] ?? '';
$options = [];
for ($i = 1; $i < count($argv); $i++) {
  if ($argv[$i] === '--dry-run') {
    $options['dry-run'] = true;
  } elseif ($argv[$i] === '--force') {
    $options['force'] = true;
  } elseif ($argv[$i] === '--help') {
    $options['help'] = true;
  }
}
if (isset($options['help']) || empty($command)) {
  showHelp();
  exit(0);
}
$validCommands = ['status', 'run', 'reset', 'specific'];
if (!in_array($command, $validCommands)) {
  echo "❌ Invalid command: $command\n";
  echo "Valid commands: " . implode(', ', $validCommands) . "\n\n";
  showHelp();
  exit(1);
}

try {
  $envPath = __DIR__ . '/.env';
  if (!file_exists($envPath))
    throw new RuntimeException('.env file not found. Please create it with database configuration.');
  $env = loadEnv($envPath);
  switch ($command) {
    case 'status':
      showMigrationStatus($env);
      break;
    case 'run':
      runMigrations($env, $options);
      break;
    case 'reset':
      resetDatabase($env, $options);
      break;
    case 'specific':
      $filename = $argv[2] ?? '';
      if (empty($filename)) {
        echo "❌ Filename required for 'specific' command\n";
        echo "Usage: php migrate.php specific <filename>\n";
        exit(1);
      }
      runSpecificMigration($env, $filename);
      break;
  }
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  exit(1);
}

// ============================================================================
// COMMAND IMPLEMENTATIONS
// ============================================================================

function showMigrationStatus(array $env): void
{
  echo "=== Migration Status ===\n\n";
  $pdo = connectToDatabase($env);
  $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');
  if (empty($migrationFiles)) {
    echo "❌ No migration files found in migrations/ directory\n";
    exit(1);
  }
  $runMigrations = getRunMigrations($pdo);
  $runMigrationFiles = array_column($runMigrations, 'filename');
  echo "📊 Migration Status:\n\n";
  foreach ($migrationFiles as $file) {
    $isRun = in_array($file, $runMigrationFiles);
    $status = $isRun ? "✅ RUN" : "⏳ PENDING";
    if ($isRun) {
      $runInfo = array_values(array_filter($runMigrations, fn($m) => $m['filename'] === $file));
      $runDate = !empty($runInfo) ? $runInfo[0]['executed_at'] : 'unknown';
      echo "  $status  $file (executed: $runDate)\n";
    } else
      echo "  $status  $file\n";
  }
  $pendingCount = count($migrationFiles) - count($runMigrationFiles);
  echo "\n📈 Summary:\n";
  echo "  Total migrations: " . count($migrationFiles) . "\n";
  echo "  Completed: " . count($runMigrationFiles) . "\n";
  echo "  Pending: " . $pendingCount . "\n";
  if ($pendingCount > 0)
    echo "\n⚠️  You have $pendingCount pending migration(s). Run 'php migrate.php run' to execute them.\n";
  else
    echo "\n✅ All migrations are up to date!\n";
}

function runMigrations(array $env, array $options): void
{
  $dryRun = isset($options['dry-run']);
  $force = isset($options['force']);
  echo "=== Running Migrations ===\n\n";
  if ($dryRun)
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
  if ($force)
    echo "⚠️  FORCE MODE - All migrations will be re-run\n\n";
  $pdo = connectToDatabase($env);
  if (!$dryRun)
    createMigrationsTable($pdo);
  $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');
  if (empty($migrationFiles)) {
    echo "❌ No migration files found in migrations/ directory\n";
    exit(1);
  }

  echo "📁 Found " . count($migrationFiles) . " migration file(s):\n";
  foreach ($migrationFiles as $file)
    echo "   - $file\n";
  echo "\n";
  $runMigrations = $force ? [] : getRunMigrations($pdo);
  if (!empty($runMigrations)) {
    echo "✅ Already run migrations:\n";
    foreach ($runMigrations as $migration)
      echo "   - {$migration['filename']} (run on {$migration['executed_at']})\n";
    echo "\n";
  }
  $pendingMigrations = $force ? $migrationFiles : array_filter($migrationFiles, function ($file) use ($runMigrations) {
    return !in_array($file, array_column($runMigrations, 'filename'));
  });

  if (empty($pendingMigrations)) {
    echo "✅ All migrations are up to date!\n";
    exit(0);
  }

  if ($dryRun) {
    echo "🔍 DRY RUN - Migrations that would be executed:\n";
    foreach ($pendingMigrations as $file)
      echo "   - $file\n";
    echo "\n✅ Dry run completed. Use 'php migrate.php run' to execute these migrations.\n";
    exit(0);
  }
  echo "🔄 Migrations to run:\n";
  foreach ($pendingMigrations as $file) 
    echo "   - $file\n";
  echo "\n";
  foreach ($pendingMigrations as $filename) 
    executeMigration($pdo, $filename);
  echo "\n✅ All migrations completed successfully!\n";
}

function resetDatabase(array $env, array $options): void
{
  $force = isset($options['force']);
  echo "🚨 === DATABASE RESET === 🚨\n\n";
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

  echo "\n🔄 Connecting to MySQL server...\n";
  $pdo = connectToMySQLServer($env);
  $dbName = $env['DB_NAME'] ?? 'room_manager';
  echo "🗑️  Dropping database '$dbName'...\n";
  $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
  echo "🆕 Creating fresh database '$dbName'...\n";
  $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
  $pdo->exec("USE `$dbName`");
  echo "📋 Running database reset...\n";
  $resetFile = __DIR__ . '/migrations/000_reset_database.php';
  if (!file_exists($resetFile)) 
    throw new RuntimeException("000_reset_database.php migration file not found!");
  ob_start();
  require $resetFile;
  $output = ob_get_clean();
  if (!empty($output)) 
    echo "Migration output:\n" . $output . "\n";
  echo "📋 Running all migrations...\n";
  $dbName = $env['DB_NAME'] ?? 'room_manager';
  $pdo->exec("USE `$dbName`");
  runMigrations($env, ['--force']);
  echo "\n✅ Database reset completed successfully!\n";
  echo "🎉 Fresh database with admin user (admin@spst.cz / admin) is ready!\n";
}

function runSpecificMigration(array $env, string $filename): void
{
  echo "=== Running Specific Migration ===\n\n";
  echo "🔄 Running migration: $filename\n\n";
  $pdo = connectToDatabase($env);
  createMigrationsTable($pdo);
  executeMigration($pdo, $filename);
  echo "\n✅ Migration completed successfully!\n";
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function showHelp(): void
{
  echo "Unified Database Migration Tool\n\n";
  echo "Usage: php migrate.php <command> [options]\n\n";
  echo "Commands:\n";
  echo "  status                Show migration status\n";
  echo "  run                   Run pending migrations\n";
  echo "  reset                 Reset database completely (WARNING: destroys all data!)\n";
  echo "  specific <filename>   Run a specific migration file\n\n";
  echo "Options:\n";
  echo "  --dry-run            Show what would be executed without running (for 'run' command)\n";
  echo "  --force              Force run all migrations, ignoring tracking (for 'run' command)\n";
  echo "  --force              Skip confirmation prompt (for 'reset' command)\n";
  echo "  --help               Show this help message\n\n";
  echo "Examples:\n";
  echo "  php migrate.php status                    # Check migration status\n";
  echo "  php migrate.php run                       # Run pending migrations\n";
  echo "  php migrate.php run --dry-run             # Preview what would be run\n";
  echo "  php migrate.php run --force               # Force run all migrations\n";
  echo "  php migrate.php reset                     # Reset database with confirmation\n";
  echo "  php migrate.php reset --force             # Reset database without confirmation\n";
  echo "  php migrate.php specific 001_init.php     # Run specific migration\n";
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
    $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
  } catch (PDOException $e) {
    // Try to connect without database and create it
    try {
      $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
      $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);

      $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
      $pdo->exec("USE `$dbName`");

      return $pdo;
    } catch (PDOException $e2) {
      throw new RuntimeException("Database connection failed: " . $e2->getMessage());
    }
  }
}

function connectToMySQLServer(array $env): PDO
{
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
    return $pdo;
  } catch (PDOException $e) {
    throw new RuntimeException("MySQL connection failed: " . $e->getMessage());
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
  if (!is_dir($dir)) 
    throw new RuntimeException("Migrations directory not found: $dir");
  $files = [];
  $rootFiles = glob($dir . '/*.php');
  $rootFiles = array_map('basename', $rootFiles);
  sort($rootFiles);
  $files = array_merge($files, $rootFiles);
  $tablesDir = $dir . '/tables';
  if (is_dir($tablesDir)) {
    $tableFiles = glob($tablesDir . '/*.php');
    $tableFiles = array_map(function ($file) {
      return 'tables/' . basename($file);
    }, $tableFiles);
    sort($tableFiles);
    $files = array_merge($files, $tableFiles);
  }
  $dataDir = $dir . '/data';
  if (is_dir($dataDir)) {
    $dataFiles = glob($dataDir . '/*.php');
    $dataFiles = array_map(function ($file) {
      return 'data/' . basename($file);
    }, $dataFiles);
    sort($dataFiles);
    $files = array_merge($files, $dataFiles);
  }
  return $files;
}

function getRunMigrations(PDO $pdo): array
{
  try {
    $pdo->query("SELECT DATABASE()")->fetchColumn();
    $stmt = $pdo->query("SELECT filename, executed_at FROM migrations ORDER BY filename");
    return $stmt->fetchAll();
  } catch (PDOException $e) {
    return [];
  }
}

function executeMigration(PDO $pdo, string $filename): void
{
  $filepath = __DIR__ . '/migrations/' . $filename;
  if (!file_exists($filepath)) 
    throw new RuntimeException("Migration file not found: $filepath");
  echo "🔄 Running migration: $filename\n";
  try {
    ob_start();
    require $filepath;
    $output = ob_get_clean();
    if (!empty($output)) 
      echo "   Output: " . trim($output) . "\n";
    if (basename($filename) === '001_init.php') 
      createMigrationsTable($pdo);
    try {
      $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (filename) VALUES (?)");
      $stmt->execute([$filename]);
    } catch (PDOException $e) {
      createMigrationsTable($pdo);
      $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (filename) VALUES (?)");
      $stmt->execute([$filename]);
    }
    echo "✅ Migration completed: $filename\n";
  } catch (Exception $e) {
    echo "❌ Migration failed: $filename\n";
    echo "   Error: " . $e->getMessage() . "\n";
    throw $e;
  }
}