<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Adjust path based on file location
require_once '../../config/database.php';
require_once '../../config/constants.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No vehicle ID provided']);
    exit();
}

$vehicle_id = $_GET['id'];

try {
    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle) {
        echo json_encode(['success' => true, 'vehicle' => $vehicle]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>