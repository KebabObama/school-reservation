<?php
session_start();
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/components/Auth.php';
require_once __DIR__ . '/components/Sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'login') {
    login($_POST['email'] ?? '', $_POST['password'] ?? '');
  } elseif ($_POST['action'] === 'register') {
    register(
      $_POST['email'] ?? '',
      $_POST['name'] ?? '',
      $_POST['surname'] ?? '',
      $_POST['password'] ?? ''
    );
  }
  header("Location: /");
  exit;
}

// All navigation is now handled via POST requests to /api/navigation/load-page.php
// Logout is handled via POST requests to /api/auth/logout.php

function is_logged_in(): bool
{
  return isset($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Room Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/components/popups/popups.css">
</head>

<body class="bg-gray-100 h-screen flex">
  <?php if (!is_logged_in()): ?>
    <div class="m-auto w-full max-w-md p-6 bg-white rounded shadow">
      <?php render_auth_component(); ?>
    </div>
  <?php else: ?>
    <?php render_sidebar(); ?>
    <main class="flex-1 p-6 overflow-auto" id="main-content">
      <?php
      require __DIR__ . '/templates/Dashboard.php';
      ?>
    </main>

    <script>
      async function loadPage(pageName) {
        const mainContent = document.getElementById('main-content');
        mainContent.innerHTML =
          '<div class="flex items-center justify-center h-64"><div class="text-lg">Loading...</div></div>';

        try {
          const response = await fetch('/api/navigation/load-page.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              page: pageName
            }),
            credentials: 'same-origin'
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }

          const html = await response.text();
          mainContent.innerHTML = html;

          // Update sidebar active state
          document.querySelectorAll('.sidebar-menu-item').forEach(item => {
            item.classList.remove('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
            item.classList.add('hover:bg-gray-100');
          });
          const activeItem = document.querySelector(`[onclick="loadPage('${pageName}')"]`);
          if (activeItem) {
            activeItem.classList.remove('hover:bg-gray-100');
            activeItem.classList.add('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
          }
        } catch (error) {
          mainContent.innerHTML =
            `<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error loading page: ${error.message}</h1></div>`;
        }
      }

      async function logout() {
        try {
          const response = await fetch('/api/auth/logout.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
          });

          if (response.ok) {
            // Redirect to home page after successful logout
            window.location.href = '/';
          } else {
            const result = await response.json();
            alert('Logout failed: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network error during logout: ' + error.message);
        }
      }
      document.addEventListener('DOMContentLoaded', function() {
        const dashboardItem = document.querySelector(`[onclick="loadPage('Dashboard')"]`);
        if (dashboardItem) {
          dashboardItem.classList.add('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
          dashboardItem.classList.remove('hover:bg-gray-100');
        }
      });
    </script>
  <?php endif; ?>

  <!-- Popup System Container -->
  <div id="popup-container"></div>
  <div id="notification-container" class="w-80 fixed right-0 p-1 flex flex-col-reverse"></div>

  <!-- Popup System Scripts -->
  <script src="/components/popups/PopupSystem.js"></script>
  <script src="/components/popups/NotificationCard.js"></script>
  <script src="/components/popups/ConfirmDialog.js"></script>
  <script src="/components/popups/InputDialog.js"></script>
  <script src="/components/popups/AlertDialog.js"></script>
  <script src="/components/popups/FormHandler.js"></script>

</body>

</html>