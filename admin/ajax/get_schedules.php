<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT s.*, v.model, v.plate_number
        FROM schedules s
        JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        ORDER BY s.available_date DESC, s.time_slot ASC
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = [];
    foreach ($schedules as $schedule) {
        $formatted[] = [
            'schedule_id' => $schedule['schedule_id'],
            'model' => $schedule['model'],
            'plate_number' => $schedule['plate_number'],
            'available_date' => date('M d, Y', strtotime($schedule['available_date'])),
            'time_slot' => $schedule['time_slot'],
            'is_booked' => $schedule['is_booked']
        ];
    }
    
    echo json_encode(['success' => true, 'schedules' => $formatted]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>