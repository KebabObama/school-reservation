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
  // Get month and year from query parameters
  $month = $_GET['month'] ?? date('m');
  $year = $_GET['year'] ?? date('Y');
  
  // Validate month and year
  if (!is_numeric($month) || !is_numeric($year) || $month < 1 || $month > 12) {
    throw new Exception('Invalid month or year');
  }
  
  // Calculate first and last day of the month
  $firstDay = sprintf('%04d-%02d-01', $year, $month);
  $lastDay = date('Y-m-t', strtotime($firstDay));
  
  // Get reservations for the month with room and user information
  $stmt = $pdo->prepare("
    SELECT 
      r.id,
      r.title,
      r.start_time,
      r.end_time,
      r.status,
      r.attendees_count,
      u.name as user_name,
      u.surname as user_surname,
      rm.name as room_name,
      DATE(r.start_time) as reservation_date
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    WHERE DATE(r.start_time) BETWEEN ? AND ?
    AND r.status IN ('pending', 'accepted')
    ORDER BY r.start_time ASC
  ");
  
  $stmt->execute([$firstDay, $lastDay]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Group reservations by date
  $reservationsByDate = [];
  foreach ($reservations as $reservation) {
    $date = $reservation['reservation_date'];
    if (!isset($reservationsByDate[$date])) {
      $reservationsByDate[$date] = [];
    }
    $reservationsByDate[$date][] = $reservation;
  }
  
  // Get count of reservations per day for the calendar
  $dailyCounts = [];
  foreach ($reservationsByDate as $date => $dayReservations) {
    $dailyCounts[$date] = count($dayReservations);
  }
  
  echo json_encode([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'reservations' => $reservationsByDate,
    'daily_counts' => $dailyCounts
  ]);
  
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
