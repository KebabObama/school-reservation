<?php
/**
 * Token Cleanup Script
 * Removes expired authentication tokens from the database
 * This script should be run periodically via cron job
 */

require_once __DIR__ . '/../lib/auth.php';

echo "Starting token cleanup...\n";

// Clean up all expired tokens
cleanupExpiredTokens();

// Get count of remaining tokens
global $pdo;
$stmt = $pdo->query('SELECT COUNT(*) as count FROM tokens');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$remainingTokens = $result['count'];

echo "Token cleanup completed.\n";
echo "Remaining active tokens: {$remainingTokens}\n";

// Optional: Clean up old rate limiting data from sessions
// This would require additional session cleanup logic
echo "Cleanup script finished.\n";
?>
