<?php

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



function showMigrationStatus(array $env): void
{
  $pdo = connectToDatabase($env);
  $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');
  if (empty($migrationFiles)) {
    echo "No migration files found\n";
    exit(1);
  }
  $runMigrations = getRunMigrations($pdo);
  $runMigrationFiles = array_column($runMigrations, 'filename');
  foreach ($migrationFiles as $file) {
    $isRun = in_array($file, $runMigrationFiles);
    $status = $isRun ? "RUN" : "PENDING";
    echo "$status $file\n";
  }
  $pendingCount = count($migrationFiles) - count($runMigrationFiles);
  echo "Total: " . count($migrationFiles) . ", Completed: " . count($runMigrationFiles) . ", Pending: " . $pendingCount . "\n";
}

function runMigrations(array $env, array $options): void
{
  $dryRun = isset($options['dry-run']);
  $force = isset($options['force']);
  $pdo = connectToDatabase($env);
  if (!$dryRun)
    createMigrationsTable($pdo);
  $migrationFiles = getMigrationFiles(__DIR__ . '/migrations');
  if (empty($migrationFiles)) {
    echo "No migration files found\n";
    exit(1);
  }

  $runMigrations = $force ? [] : getRunMigrations($pdo);
  $pendingMigrations = $force ? $migrationFiles : array_filter($migrationFiles, function ($file) use ($runMigrations) {
    return !in_array($file, array_column($runMigrations, 'filename'));
  });

  if (empty($pendingMigrations)) {
    echo "All migrations up to date\n";
    exit(0);
  }

  if ($dryRun) {
    foreach ($pendingMigrations as $file)
      echo "Would run: $file\n";
    exit(0);
  }

  foreach ($pendingMigrations as $filename)
    executeMigration($pdo, $filename);
  echo "Migrations completed\n";
}

function resetDatabase(array $env, array $options): void
{
  $force = isset($options['force']);
  if (!$force) {
    echo "Type 'RESET' to confirm database reset: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    if (trim($line) !== 'RESET') {
      echo "Cancelled\n";
      exit(0);
    }
  }

  $pdo = connectToMySQLServer($env);
  $dbName = $env['DB_NAME'] ?? 'room_manager';
  $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
  $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
  $pdo->exec("USE `$dbName`");
  $resetFile = __DIR__ . '/migrations/000_reset_database.php';
  if (!file_exists($resetFile))
    throw new RuntimeException("000_reset_database.php migration file not found!");
  ob_start();
  require $resetFile;
  ob_get_clean();
  $pdo->exec("USE `$dbName`");
  runMigrations($env, ['force' => true]);
  echo "Database reset completed\n";
}

function runSpecificMigration(array $env, string $filename): void
{
  $pdo = connectToDatabase($env);
  createMigrationsTable($pdo);
  executeMigration($pdo, $filename);
  echo "Migration completed\n";
}



function showHelp(): void
{
  echo "Usage: php migrate.php <command> [options]\n";
  echo "Commands: status, run, reset, specific\n";
  echo "Options: --dry-run, --force, --help\n";
}

function loadEnv(string $path): array
{
  $env = [];
  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) continue;
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
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    throw new RuntimeException("Invalid database name.");
  }

  $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Better security
  ];

  try {
    // First attempt: Connect with the database name
    $pdo = new PDO("$dsn;dbname=$dbName", $user, $pass, $options);
    return $pdo;
  } catch (PDOException $e) {
    // Second attempt: Connect without DB and try to create it
    try {
      $pdo = new PDO($dsn, $user, $pass, $options);

      // Create DB if it doesn't exist
      $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
      $pdo->exec("USE `$dbName`");

      return $pdo;
    } catch (PDOException $e) {
      throw new RuntimeException("Database connection failed: " . $e->getMessage());
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
    // Check if the migrations table exists first
    $tableExists = $pdo->query(
      "SELECT 1 FROM information_schema.tables 
             WHERE table_schema = DATABASE() AND table_name = 'migrations'"
    )->fetchColumn();

    if (!$tableExists) {
      throw new RuntimeException("Migrations table does not exist.");
    }

    // Fetch all migrations safely with prepared statements
    $stmt = $pdo->prepare("SELECT filename, executed_at FROM migrations ORDER BY filename");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $ex) {
    error_log("Failed to fetch migrations: " . $ex->getMessage());
    return [];
  }
}

/**
 * Executes a migration file and records it in the migrations table.
 *
 * @param PDO $pdo Database connection object.
 * @param string $filename The migration filename (e.g., "001_init.php").
 * @throws RuntimeException If the migration file is missing or SQL execution fails.
 */
function executeMigration(PDO $pdo, string $filename): void
{
  $migrationsDir = __DIR__ . '/migrations/';
  $filepath = $migrationsDir . $filename;

  // Validate filename to prevent directory traversal
  if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.php$/', $filename)) {
    throw new RuntimeException("Invalid migration filename: $filename");
  }

  // Check if the file exists
  if (!file_exists($filepath)) {
    throw new RuntimeException("Migration file not found: $filepath");
  }

  // Start transaction (for atomicity)
  $pdo->beginTransaction();

  try {
    // Execute the migration file
    ob_start();
    require $filepath;
    ob_end_clean(); // Discard output

    // Special case: If it's the initial migration, ensure the table exists
    if (basename($filename) === '001_init.php') {
      createMigrationsTable($pdo);
    }

    // Record the migration (with retry logic if table doesn't exist)
    try {
      $stmt = $pdo->prepare("INSERT INTO migrations (filename, executed_at) VALUES (?, NOW())");
      $stmt->execute([$filename]);
    } catch (PDOException $e) {
      // Check if the error is due to a missing migrations table (PHP 7.x compatible)
      if ($e->getCode() === '42S02' || strpos($e->getMessage(), 'migrations') !== false) {
        createMigrationsTable($pdo);
        $stmt = $pdo->prepare("INSERT INTO migrations (filename, executed_at) VALUES (?, NOW())");
        $stmt->execute([$filename]);
      } else {
        throw $e; // Re-throw other PDOExceptions
      }
    }

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    throw new RuntimeException("Migration failed: " . $e->getMessage(), 0, $e);
  }
}
