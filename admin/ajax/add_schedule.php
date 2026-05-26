<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/constants.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $available_date = $_POST['available_date'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    
    // Validate inputs
    if (empty($vehicle_id) || empty($available_date) || empty($time_slot)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Check if schedule already exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM schedules 
        WHERE vehicle_id = ? AND available_date = ? AND time_slot = ?
    ");
    $stmt->execute([$vehicle_id, $available_date, $time_slot]);
    $exists = $stmt->fetchColumn();
    
    if ($exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule already exists for this vehicle, date, and time slot']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO schedules (vehicle_id, available_date, time_slot, is_booked) 
            VALUES (?, ?, ?, 0)
        ");
        $stmt->execute([$vehicle_id, $available_date, $time_slot]);
        
        echo json_encode(['success' => true, 'message' => 'Schedule added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>