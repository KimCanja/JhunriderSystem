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

$rental_id = $_POST['rental_id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    // Check if rental exists and belongs to user
    $stmt = $pdo->prepare("SELECT status FROM rentals WHERE rental_id = ? AND user_id = ?");
    $stmt->execute([$rental_id, $user_id]);
    $rental = $stmt->fetch();
    
    if (!$rental) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        exit();
    }
    
    if (!in_array($rental['status'], ['pending', 'approved'])) {
        echo json_encode(['success' => false, 'message' => 'This rental cannot be cancelled']);
        exit();
    }
    
    // Update rental status to cancelled
    $stmt = $pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ?");
    $stmt->execute([$rental_id]);
    
    // Get vehicle_id to update vehicle status if needed
    $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE rental_id = ?");
    $stmt->execute([$rental_id]);
    $rental_data = $stmt->fetch();
    
    if ($rental_data) {
        // Update vehicle status back to available if it was rented
        $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ? AND status = 'rented'");
        $stmt->execute([$rental_data['vehicle_id']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Rental cancelled successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>