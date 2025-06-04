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

if (isset($_GET['page']) && is_logged_in()) {
  $page = $_GET['page'];
  $allowed_pages = ['Dashboard', 'Rooms', 'CreateRoom', 'EditRoom', 'Reservations', 'CreateReservation', 'EditReservation', 'UserPage', 'Profile', 'RoomTypes', 'CreateRoomType', 'EditRoomType', 'ReservationPurposes', 'CreatePurpose', 'EditPurpose', 'Permissions', 'PermissionChanges', 'ProfileVerification', 'PopupDemo', 'ReservationTest'];

  // Check if user is verified, if not, only allow access to Profile page
  $is_verified = false;
  try {
    require_once __DIR__ . '/lib/db.php';
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $is_verified = (bool)$stmt->fetchColumn();

    if (!$is_verified && $page !== 'Profile') {
      require __DIR__ . '/templates/Profile.php';
      exit;
    }
  } catch (Exception $e) {
    // If there's an error checking verification, default to Profile for safety
    if ($page !== 'Profile') {
      require __DIR__ . '/templates/Profile.php';
      exit;
    }
  }

  if (in_array($page, $allowed_pages)) {
    $template_file = __DIR__ . '/templates/' . $page . '.php';
    if (file_exists($template_file)) {
      require $template_file;
      exit;
    }
  }
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
      // Check if user is verified
      try {
        require_once __DIR__ . '/lib/db.php';
        $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $is_verified = (bool)$stmt->fetchColumn();

        if (!$is_verified) {
          echo '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                <p class="font-bold">Account Not Verified</p>
                <p>Your account is pending verification. Until verified, you can only access your profile page.</p>
              </div>';
          require __DIR__ . '/templates/Profile.php';
        } else {
          require __DIR__ . '/templates/Dashboard.php';
        }
      } catch (Exception $e) {
        require __DIR__ . '/templates/Dashboard.php';
      }
      ?>
  </main>

  <script>
  function loadPage(pageName) {
    <?php
        // Add verification check for client-side navigation
        try {
          $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
          $stmt->execute([$_SESSION['user_id']]);
          $is_verified = (bool)$stmt->fetchColumn();

          if (!$is_verified) {
            echo "if (pageName !== 'Profile') {
                popupSystem.warning('Your account is pending verification. You can only access your profile page.', 'Access Restricted');
                return;
              }";
          }
        } catch (Exception $e) {
          // If error, don't restrict navigation
        }
        ?>

    const mainContent = document.getElementById('main-content');
    mainContent.innerHTML =
      '<div class="flex items-center justify-center h-64"><div class="text-lg">Loading...</div></div>';
    fetch(`?page=${pageName}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Page not found');
        }
        return response.text();
      })
      .then(html => {
        mainContent.innerHTML = html;
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