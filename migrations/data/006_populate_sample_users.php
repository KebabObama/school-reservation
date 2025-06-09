<?php
declare(strict_types=1);
if (!isset($pdo)) {
  require_once __DIR__ . '/../../lib/db.php';
}
$sampleUsers = [
  ['john.doe@spst.cz', 'John', 'Doe', 'teacher123', true],
  ['jane.smith@spst.cz', 'Jane', 'Smith', 'teacher456', true],
  ['mike.johnson@spst.cz', 'Mike', 'Johnson', 'staff789', true],
  ['sarah.wilson@spst.cz', 'Sarah', 'Wilson', 'manager123', true],
  ['david.brown@spst.cz', 'David', 'Brown', 'teacher789', true],
  ['lisa.davis@spst.cz', 'Lisa', 'Davis', 'student123', false],
  ['tom.miller@spst.cz', 'Tom', 'Miller', 'student456', false],
  ['anna.garcia@spst.cz', 'Anna', 'Garcia', 'teacher456', true],
  ['chris.martinez@spst.cz', 'Chris', 'Martinez', 'staff456', true],
  ['emma.anderson@spst.cz', 'Emma', 'Anderson', 'student789', true],
  ['james.taylor@spst.cz', 'James', 'Taylor', 'teacher789', true],
  ['olivia.thomas@spst.cz', 'Olivia', 'Thomas', 'student456', false],
  ['william.jackson@spst.cz', 'William', 'Jackson', 'manager456', true],
  ['sophia.white@spst.cz', 'Sophia', 'White', 'teacher123', true],
  ['benjamin.harris@spst.cz', 'Benjamin', 'Harris', 'student789', true]
];
$userIds = [];
$insertedCount = 0;
foreach ($sampleUsers as $user) {
  $hash = password_hash($user[3], PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, name, surname, password_hash, is_verified) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$user[0], $user[1], $user[2], $hash, $user[4]]);
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->execute([$user[0]]);
  $userId = $stmt->fetchColumn();
  if ($userId) {
    $userIds[$user[0]] = $userId;
    $insertedCount++;
  }
}
$permissionProfiles = [
  'teacher' => [0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0],
  'staff' => [1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0],
  'manager' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
  'student' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0]
];
$userRoles = [
  'john.doe@spst.cz' => 'teacher',
  'jane.smith@spst.cz' => 'teacher',
  'mike.johnson@spst.cz' => 'staff',
  'sarah.wilson@spst.cz' => 'manager',
  'david.brown@spst.cz' => 'teacher',
  'lisa.davis@spst.cz' => 'student',
  'tom.miller@spst.cz' => 'student',
  'anna.garcia@spst.cz' => 'teacher',
  'chris.martinez@spst.cz' => 'staff',
  'emma.anderson@spst.cz' => 'student',
  'james.taylor@spst.cz' => 'teacher',
  'olivia.thomas@spst.cz' => 'student',
  'william.jackson@spst.cz' => 'manager',
  'sophia.white@spst.cz' => 'teacher',
  'benjamin.harris@spst.cz' => 'student'
];
$permissionsSet = 0;
foreach ($userRoles as $email => $role) {
  if (isset($userIds[$email]) && isset($permissionProfiles[$role])) {
    $userId = $userIds[$email];
    $perms = $permissionProfiles[$role];
    $stmt = $pdo->prepare("
            INSERT IGNORE INTO permissions (
                user_id, rooms_view, rooms_create, rooms_edit, rooms_delete,
                buildings_view, buildings_create, buildings_edit, buildings_delete,
                floors_view, floors_create, floors_edit, floors_delete,
                reservations_view, reservations_create, reservations_edit, reservations_delete, reservations_review_status,
                users_view, users_create, users_edit, users_delete
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    $stmt->execute(array_merge([$userId], $perms));
    if ($stmt->rowCount() > 0) {
      $permissionsSet++;
    }
  }
}