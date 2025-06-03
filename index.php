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

if (isset($_GET['logout'])) {
  logout();
  header("Location: /");
  exit;
}

// Handle AJAX requests for page loading
if (isset($_GET['page']) && is_logged_in()) {
  $page = $_GET['page'];
  $allowed_pages = ['Dashboard', 'Rooms', 'CreateRoom', 'EditRoom', 'Reservations', 'CreateReservation', 'EditReservation', 'UserPage', 'Profile', 'RoomTypes', 'CreateRoomType', 'EditRoomType', 'ReservationPurposes', 'CreatePurpose', 'EditPurpose', 'Permissions', 'PermissionChanges', 'ProfileVerification'];

  if (in_array($page, $allowed_pages)) {
    $template_file = __DIR__ . '/templates/' . $page . '.php';
    if (file_exists($template_file)) {
      require $template_file;
      exit;
    }
  }

  // If page not found, return error
  http_response_code(404);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Page not found</h1></div>';
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
  <title>Room Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 h-screen flex">

  <?php if (!is_logged_in()): ?>
    <div class="m-auto w-full max-w-md p-6 bg-white rounded shadow">
      <?php render_auth_component(); ?>
    </div>
  <?php else: ?>
    <?php render_sidebar(); ?>
    <main class="flex-1 p-6 overflow-auto" id="main-content">
      <?php require __DIR__ . '/templates/Dashboard.php'; ?>
    </main>

    <script>
      function loadPage(pageName) {
        const mainContent = document.getElementById('main-content');

        // Show loading state
        mainContent.innerHTML =
          '<div class="flex items-center justify-center h-64"><div class="text-lg">Loading...</div></div>';

        // Fetch page content
        fetch(`?page=${pageName}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Page not found');
            }
            return response.text();
          })
          .then(html => {
            mainContent.innerHTML = html;

            // Update active menu item
            document.querySelectorAll('.sidebar-menu-item').forEach(item => {
              item.classList.remove('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
              item.classList.add('hover:bg-gray-100');
            });

            const activeItem = document.querySelector(`[onclick="loadPage('${pageName}')"]`);
            if (activeItem) {
              activeItem.classList.remove('hover:bg-gray-100');
              activeItem.classList.add('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
            }
          })
          .catch(error => {
            mainContent.innerHTML =
              `<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error loading page: ${error.message}</h1></div>`;
          });
      }

      // Set Dashboard as active on page load
      document.addEventListener('DOMContentLoaded', function() {
        const dashboardItem = document.querySelector(`[onclick="loadPage('Dashboard')"]`);
        if (dashboardItem) {
          dashboardItem.classList.add('bg-blue-100', 'text-blue-700', 'border-r-2', 'border-blue-500');
          dashboardItem.classList.remove('hover:bg-gray-100');
        }
      });
    </script>
  <?php endif; ?>

</body>

</html>