<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT r.*, v.model, v.plate_number 
        FROM rentals r 
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_rentals = [];
    foreach ($rentals as $rental) {
        $formatted_rentals[] = [
            'rental_id' => $rental['rental_id'],
            'model' => $rental['model'],
            'plate_number' => $rental['plate_number'],
            'pickup_date' => date('M d, Y', strtotime($rental['pickup_date'])),
            'return_date' => date('M d, Y', strtotime($rental['return_date'])),
            'pickup_time' => date('h:i A', strtotime($rental['pickup_time'])),
            'status' => $rental['status'],
            'total_price' => $rental['total_price']
        ];
    }
    
    echo json_encode(['success' => true, 'rentals' => $formatted_rentals]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>