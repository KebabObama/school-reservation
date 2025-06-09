<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}
require_once __DIR__ . '/../../lib/db.php';
try {
  $date = $_GET['date'] ?? '';
  if (empty($date)) {
    throw new Exception('Date parameter is required');
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    throw new Exception('Invalid date format. Use YYYY-MM-DD');
  }
  $stmt = $pdo->prepare("
    SELECT 
      r.id,
      r.title,
      r.description,
      r.start_time,
      r.end_time,
      r.status,
      r.attendees_count,
      r.setup_requirements,
      r.special_requests,
      u.name as user_name,
      u.surname as user_surname,
      u.email as user_email,
      rm.name as room_name,
      rm.capacity as room_capacity,
      b.name as building_name,
      f.name as floor_name,
      rp.name as purpose_name,
      TIME(r.start_time) as start_time_only,
      TIME(r.end_time) as end_time_only
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    LEFT JOIN floors f ON rm.floor_id = f.id
    LEFT JOIN buildings b ON f.building_id = b.id
    LEFT JOIN reservation_purposes rp ON r.purpose_id = rp.id
    WHERE DATE(r.start_time) = ?
    AND r.status IN ('pending', 'accepted')
    ORDER BY r.start_time ASC
  ");
  $stmt->execute([$date]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $dateObj = new DateTime($date);
  $formattedDate = $dateObj->format('l, F j, Y');
  echo json_encode([
    'success' => true,
    'date' => $date,
    'formatted_date' => $formattedDate,
    'reservations' => $reservations,
    'count' => count($reservations)
  ]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}