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
  $month = $_GET['month'] ?? date('m');
  $year = $_GET['year'] ?? date('Y');
  if (!is_numeric($month) || !is_numeric($year) || $month < 1 || $month > 12)
    throw new Exception('Invalid month or year');
  $startDate = sprintf('%04d-%02d-01', $year, $month);
  $endDate = date('Y-m-t', strtotime($startDate));
  $stmt = $pdo->prepare("
    SELECT 
      r.id,
      r.title,
      r.start_time,
      r.end_time,
      r.status,
      r.attendees_count,
      u.name AS user_name,
      u.surname AS user_surname,
      rm.name AS room_name,
      DATE(r.start_time) AS reservation_date
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    WHERE DATE(r.start_time) BETWEEN ? AND ?
      AND r.status IN ('pending', 'accepted')
    ORDER BY r.start_time ASC
  ");
  $stmt->execute([$startDate, $endDate]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $byDate = [];
  foreach ($reservations as $res)
    $byDate[$res['reservation_date']][] = $res;
  echo json_encode([
    'success' => true,
    'month' => (int)$month,
    'year' => (int)$year,
    'reservations' => $byDate,
  ]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}