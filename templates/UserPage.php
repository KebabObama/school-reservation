<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/user.php';

$users = get_all_users();

echo '<h2 class="text-xl font-bold mb-4">Users</h2>';
echo '<ul class="space-y-2">';
foreach ($users as $user) {
  echo "<li class='p-2 border rounded'>{$user['email']} ({$user['name']} {$user['surname']})</li>";
}
echo '</ul>';
