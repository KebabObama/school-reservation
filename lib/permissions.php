<?php
require_once __DIR__ . '/db.php';

function hasPermission($userId, $permission)
{
  global $pdo;

  try {
    $stmt = $pdo->prepare("SELECT $permission FROM permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetchColumn();

    return (bool)$result;
  } catch (Exception $e) {
    return false;
  }
}

function hasAnyPermission($userId, $permissions)
{
  foreach ($permissions as $permission) {
    if (hasPermission($userId, $permission)) {
      return true;
    }
  }
  return false;
}

function hasAllPermissions($userId, $permissions)
{
  foreach ($permissions as $permission) {
    if (!hasPermission($userId, $permission)) {
      return false;
    }
  }
  return true;
}


function getUserPermissions($userId)
{
  global $pdo;

  try {
    $stmt = $pdo->prepare("SELECT * FROM permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: [];
  } catch (Exception $e) {
    return [];
  }
}


function canViewRooms($userId)
{
  return hasPermission($userId, 'rooms_view');
}

function canCreateRooms($userId)
{
  return hasPermission($userId, 'rooms_create');
}

function canEditRooms($userId)
{
  return hasPermission($userId, 'rooms_edit');
}

function canDeleteRooms($userId)
{
  return hasPermission($userId, 'rooms_delete');
}

function canViewBuildings($userId)
{
  return hasPermission($userId, 'buildings_view');
}

function canCreateBuildings($userId)
{
  return hasPermission($userId, 'buildings_create');
}

function canEditBuildings($userId)
{
  return hasPermission($userId, 'buildings_edit');
}

function canDeleteBuildings($userId)
{
  return hasPermission($userId, 'buildings_delete');
}

function canViewFloors($userId)
{
  return hasPermission($userId, 'floors_view');
}

function canCreateFloors($userId)
{
  return hasPermission($userId, 'floors_create');
}

function canEditFloors($userId)
{
  return hasPermission($userId, 'floors_edit');
}

function canDeleteFloors($userId)
{
  return hasPermission($userId, 'floors_delete');
}

function canViewReservations($userId)
{
  return hasPermission($userId, 'reservations_view');
}

function canCreateReservations($userId)
{
  return hasPermission($userId, 'reservations_create');
}

function canEditReservations($userId)
{
  return hasPermission($userId, 'reservations_edit');
}

function canDeleteReservations($userId)
{
  return hasPermission($userId, 'reservations_delete');
}

function canReviewReservationStatus($userId)
{
  return hasPermission($userId, 'reservations_review_status');
}

function canViewUsers($userId)
{
  return hasPermission($userId, 'users_view');
}

function canCreateUsers($userId)
{
  return hasPermission($userId, 'users_create');
}

function canEditUsers($userId)
{
  return hasPermission($userId, 'users_edit');
}

function canDeleteUsers($userId)
{
  return hasPermission($userId, 'users_delete');
}

function canEditSpecificReservation($userId, $reservationUserId)
{
  return $userId == $reservationUserId || canEditReservations($userId);
}

function canDeleteSpecificReservation($userId, $reservationUserId)
{
  return $userId == $reservationUserId || canDeleteReservations($userId);
}

function getPermissionCategories()
{
  return [
    'rooms' => [
      'rooms_view' => 'View Rooms',
      'rooms_create' => 'Create Rooms',
      'rooms_edit' => 'Edit Rooms',
      'rooms_delete' => 'Delete Rooms'
    ],
    'buildings' => [
      'buildings_view' => 'View Buildings',
      'buildings_create' => 'Create Buildings',
      'buildings_edit' => 'Edit Buildings',
      'buildings_delete' => 'Delete Buildings'
    ],
    'floors' => [
      'floors_view' => 'View Floors',
      'floors_create' => 'Create Floors',
      'floors_edit' => 'Edit Floors',
      'floors_delete' => 'Delete Floors'
    ],
    'reservations' => [
      'reservations_view' => 'View Reservations',
      'reservations_create' => 'Create Reservations',
      'reservations_edit' => 'Edit Reservations',
      'reservations_delete' => 'Delete Reservations',
      'reservations_review_status' => 'Review Reservation Status'
    ],
    'users' => [
      'users_view' => 'View Users',
      'users_create' => 'Create Users',
      'users_edit' => 'Edit Users',
      'users_delete' => 'Delete Users'
    ]
  ];
}

function getAllPermissionNames()
{
  $categories = getPermissionCategories();
  $permissions = [];
  foreach ($categories as $category)
    $permissions = array_merge($permissions, array_keys($category));
  return $permissions;
}
