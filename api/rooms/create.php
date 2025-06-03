<?php
// api/rooms/create.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check user authentication
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed']);
  exit;
}

require_once __DIR__ . '/../../lib/db.php';

$errors = [];

// Get JSON input or fallback to POST
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
  $input = $_POST;
}

// Sanitize and validate input
$name = trim($input['name'] ?? '');
$room_type_id = !empty($input['room_type_id']) ? (int)$input['room_type_id'] : null;
$capacity = !empty($input['capacity']) ? (int)$input['capacity'] : null;
$location = trim($input['location'] ?? '');
$floor = trim($input['floor'] ?? '');
$building = trim($input['building'] ?? '');
$description = trim($input['description'] ?? '');
$equipment = trim($input['equipment'] ?? '');
$image_url = trim($input['image_url'] ?? '');
$features = $input['features'] ?? [];

if (empty($name)) {
  $errors[] = 'Room name is required.';
}

if (empty($room_type_id)) {
  $errors[] = 'Room type is required.';
}

if (empty($description)) {
  $errors[] = 'Description is required.';
}

if (empty($capacity) || $capacity <= 0) {
  $errors[] = 'Capacity is required and must be a positive number.';
}

if (empty($floor)) {
  $errors[] = 'Floor is required.';
}

if (empty($building)) {
  $errors[] = 'Building is required.';
}

if (empty($location)) {
  $errors[] = 'Location is required.';
}

if (empty($equipment)) {
  $errors[] = 'Equipment information is required.';
}

if (empty($features) || !is_array($features)) {
  $errors[] = 'At least one room feature must be selected.';
}

// Validate room type if provided
if ($room_type_id !== null) {
  try {
    $stmt = $pdo->prepare("SELECT id FROM room_types WHERE id = ?");
    $stmt->execute([$room_type_id]);
    if (!$stmt->fetch()) {
      $errors[] = 'Invalid room type selected.';
    }
  } catch (Exception $e) {
    $errors[] = 'Database error occurred while validating room type.';
  }
}

// Check if room name already exists
if (empty($errors)) {
  try {
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
      $errors[] = 'A room with this name already exists.';
    }
  } catch (Exception $e) {
    $errors[] = 'Database error occurred while checking room name.';
  }
}

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['errors' => $errors]);
  exit;
}

// Insert room
try {
  $stmt = $pdo->prepare("
        INSERT INTO rooms (name, room_type_id, capacity, location, floor, building, description, equipment, image_url, features, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

  $stmt->execute([
    $name,
    $room_type_id,
    $capacity,
    $location,
    $floor,
    $building,
    $description,
    $equipment,
    $image_url ?: null,
    json_encode($features)
  ]);

  $newRoomId = $pdo->lastInsertId();

  http_response_code(201);
  echo json_encode([
    'message' => 'Room created successfully',
    'room_id' => $newRoomId,
    'room_name' => $name
  ]);
  exit;
} catch (Exception $e) {
  http_response_code(500);
  error_log("Room creation error: " . $e->getMessage());
  echo json_encode(['error' => 'Failed to create room. Please try again.']);
  exit;
}
