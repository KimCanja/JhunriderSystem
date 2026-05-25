<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$rental_id = $_POST['rental_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$rental_id || !in_array($action, ['approve', 'reject', 'start', 'complete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'approved' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $message = 'Rental approved successfully!';
    } 
    elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $message = 'Rental rejected successfully!';
    }
    elseif ($action === 'start') {
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'active' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        
        // Update vehicle status
        $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $rental = $stmt->fetch();
        if ($rental) {
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE vehicle_id = ?");
            $stmt->execute([$rental['vehicle_id']]);
        }
        $message = 'Rental started successfully!';
    }
    elseif ($action === 'complete') {
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'completed' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        
        // Update vehicle status back to available
        $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $rental = $stmt->fetch();
        if ($rental) {
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ?");
            $stmt->execute([$rental['vehicle_id']]);
        }
        $message = 'Rental completed successfully!';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}
?>