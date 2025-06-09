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
function is_logged_in(): bool
{
  return isset($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Room Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/lib/js/auth-manager.js"></script>
</head>

<body class="bg-gray-100 h-screen flex">
  <?php if (!is_logged_in()): ?>
    <div class="m-auto w-full max-w-md p-6 bg-white rounded shadow">
      <?php render_auth_component(); ?>
    </div>
  <?php else: ?>
    <!-- Mobile menu button -->
    <button id="mobile-menu-button" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-blue-600 text-white rounded-md shadow-lg">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>

    <!-- Sidebar overlay for mobile -->
    <div id="sidebar-overlay" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    <?php render_sidebar(); ?>
    <main class="flex-1 p-6 overflow-auto pt-16 md:pt-6" id="main-content">
      <?php
      require __DIR__ . '/templates/Dashboard.php';
      ?>
    </main>
    <script>
      async function loadPage(pageName, params = {}) {
        const mainContent = document.getElementById('main-content');
        mainContent.innerHTML =
          '<div class="flex items-center justify-center h-64"><div class="text-lg">Loading...</div></div>';
        try {
          const requestData = {
            page: pageName,
            ...params
          };
          const response = await authManager.apiRequest('/api/navigation/load-page.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData),
            credentials: 'same-origin'
          });
          if (!response.ok)
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          const html = await response.text();
          mainContent.innerHTML = html;

          // Close mobile sidebar when navigating to a new page
          closeMobileSidebar();

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
          await authManager.logout();
        } catch (error) {
          console.error('Logout error:', error);
          authManager.clearTokens();
          window.location.reload();
        }
      }
      // Responsive sidebar functionality
      function toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const menuButton = document.getElementById('mobile-menu-button');

        if (sidebar.classList.contains('translate-x-0')) {
          // Close sidebar
          sidebar.classList.remove('translate-x-0');
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
          menuButton.classList.remove('hidden');
        } else {
          // Open sidebar
          sidebar.classList.remove('-translate-x-full');
          sidebar.classList.add('translate-x-0');
          overlay.classList.remove('hidden');
          menuButton.classList.add('hidden');
        }
      }

      function closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const menuButton = document.getElementById('mobile-menu-button');

        if (window.innerWidth < 768) { // Only on mobile
          sidebar.classList.remove('translate-x-0');
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
          menuButton.classList.remove('hidden');
        }
      }

      document.addEventListener('DOMContentLoaded', function() {
        const dashboardItem = document.querySelector(`[onclick="loadPage('Dashboard')"]`);
        if (dashboardItem) {
          dashboardItem.classList.add('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
          dashboardItem.classList.remove('hover:bg-gray-100');
        }

        // Mobile menu button event listener
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        if (mobileMenuButton) {
          mobileMenuButton.addEventListener('click', toggleMobileSidebar);
        }

        // Overlay click to close sidebar
        const overlay = document.getElementById('sidebar-overlay');
        if (overlay) {
          overlay.addEventListener('click', closeMobileSidebar);
        }

        // Handle window resize
        window.addEventListener('resize', function() {
          if (window.innerWidth >= 768) {
            // Desktop view - ensure sidebar is visible and overlay is hidden
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const menuButton = document.getElementById('mobile-menu-button');

            if (sidebar) {
              sidebar.classList.remove('-translate-x-full');
              sidebar.classList.add('translate-x-0');
            }
            if (overlay) {
              overlay.classList.add('hidden');
            }
            if (menuButton) {
              menuButton.classList.remove('hidden');
            }
          } else {
            // Mobile view - close sidebar by default
            closeMobileSidebar();
          }
        });

        // Handle ESC key to close mobile sidebar
        document.addEventListener('keydown', function(event) {
          if (event.key === 'Escape' && window.innerWidth < 768) {
            closeMobileSidebar();
          }
        });
      });
    </script>
  <?php endif; ?>
  <div id="popup-container"></div>
  <div id="notification-container"
    class="fixed bottom-4 right-4 w-80 z-[9999] flex flex-col-reverse gap-2 pointer-events-none items-end"></div>
  <link rel="stylesheet" href="/components/popups/popup-styles.css">
  <script src="/components/popups/PopupSystem.js"></script>
  <script src="/components/popups/NotificationCard.js"></script>
  <script src="/components/popups/ConfirmDialog.js"></script>
  <script src="/components/popups/InputDialog.js"></script>
  <script src="/components/popups/AlertDialog.js"></script>
  <script src="/components/popups/FormHandler.js"></script>
</body>

</html>