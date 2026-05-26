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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];
    
    try {
        // Check if schedule is booked
        $stmt = $pdo->prepare("SELECT is_booked FROM schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule && $schedule['is_booked'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete booked schedule']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>