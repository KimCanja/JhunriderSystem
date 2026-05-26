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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$rental_id = $_POST['rental_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$rental_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get rental details
    $stmt = $pdo->prepare("SELECT * FROM rentals WHERE rental_id = ?");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rental) {
        throw new Exception('Rental not found');
    }
    
    $new_status = '';
    $message = '';
    $update_vehicle = false;
    $vehicle_status = '';
    
    switch ($action) {
        case 'approve':
            $new_status = 'approved';
            $message = 'Rental booking approved successfully';
            break;
            
        case 'reject':
            $new_status = 'cancelled';
            $message = 'Rental booking rejected';
            break;
            
        case 'start':
            $new_status = 'active';
            $update_vehicle = true;
            $vehicle_status = 'rented';
            $message = 'Rental started successfully';
            break;
            
        case 'complete':
            $new_status = 'completed';
            $update_vehicle = true;
            $vehicle_status = 'available';
            $message = 'Rental completed successfully';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Update rental status
    $stmt = $pdo->prepare("UPDATE rentals SET status = ? WHERE rental_id = ?");
    $stmt->execute([$new_status, $rental_id]);
    
    // Update vehicle status if needed
    if ($update_vehicle) {
        $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE vehicle_id = ?");
        $stmt->execute([$vehicle_status, $rental['vehicle_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>