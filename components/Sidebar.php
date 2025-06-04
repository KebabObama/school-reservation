<?php

function render_sidebar(): void
{
  $user_name = $_SESSION['user_name'] ?? 'User';

  // Check if user is verified
  $is_verified = false;
  try {
    // Make sure to include db.php to get the $pdo connection
    require_once __DIR__ . '/../lib/db.php';

    // Access the global $pdo variable
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $is_verified = (bool)$stmt->fetchColumn();
  } catch (Exception $e) {
    // If error, assume not verified for safety
  }

  echo <<<HTML
<aside class="w-64 bg-white shadow-md h-screen flex flex-col">
  <!-- Header -->
  <div class="p-4 border-b bg-blue-600 text-white">
    <h1 class="font-bold text-xl">Room Manager</h1>
    <p class="text-sm text-blue-100">Welcome, {$user_name}</p>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 overflow-y-auto">
    <div class="p-2">
HTML;

  // Only show these sections if user is verified
  if ($is_verified) {
    echo <<<HTML
      <!-- Main Navigation -->
      <div class="mb-4">
        <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</h3>
        <ul class="space-y-1">
          <li>
            <button onclick="loadPage('Dashboard')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
              </svg>
              Dashboard
            </button>
          </li>
        </ul>
      </div>

      <!-- Room Management -->
      <div class="mb-4">
        <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Room Management</h3>
        <ul class="space-y-1">
          <li>
            <button onclick="loadPage('Rooms')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
              Rooms
            </button>
          </li>
          <li>
            <button onclick="loadPage('RoomTypes')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
              </svg>
              Room Types
            </button>
          </li>
        </ul>
      </div>

      <!-- Reservation Management -->
      <div class="mb-4">
        <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Reservations</h3>
        <ul class="space-y-1">
          <li>
            <button onclick="loadPage('Reservations')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Reservations
            </button>
          </li>
          <li>
            <button onclick="loadPage('ReservationPurposes')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              Purposes
            </button>
          </li>
        </ul>
      </div>

      <!-- User Management -->
      <div class="mb-4">
        <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">User Management</h3>
        <ul class="space-y-1">
          <li>
            <button onclick="loadPage('UserPage')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
              Users
            </button>
          </li>
          <li>
            <button onclick="loadPage('Permissions')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
              Permissions
            </button>
          </li>
        </ul>
      </div>
HTML;
  }

  // Always show Account section with Profile
  echo <<<HTML
      <!-- Account -->
      <div class="mb-4">
        <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</h3>
        <ul class="space-y-1">
          <li>
            <button onclick="loadPage('Profile')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              Profile
            </button>
          </li>
          <li>
            <button onclick="loadPage('PopupDemo')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
              </svg>
              Popup Demo
            </button>
          </li>
          <li>
            <button onclick="loadPage('ReservationTest')" class="sidebar-menu-item w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Reservation Test
            </button>
          </li>
        </ul>
      </div>
HTML;

  // Show verification notice for unverified users
  if (!$is_verified) {
    echo <<<HTML
      <!-- Verification Notice -->
      <div class="mx-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
        <div class="flex">
          <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">Account Pending Verification</h3>
            <div class="mt-1 text-xs text-yellow-700">
              Your account is awaiting verification. Only your profile is accessible.
            </div>
          </div>
        </div>
      </div>
HTML;
  }

  echo <<<HTML
    </div>
  </nav>

  <!-- Footer -->
  <div class="p-4 border-t bg-gray-50">
    <a href="?logout=1" class="flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
      </svg>
      Logout
    </a>
  </div>
</aside>
HTML;
}
