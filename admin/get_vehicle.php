<?php
// admin/get_vehicle.php
require_once '../config/database.php';
require_once '../config/constants.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $vehicle_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$vehicle_id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle) {
            echo json_encode($vehicle);
        } else {
            echo json_encode(['error' => 'Vehicle not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'No vehicle ID provided']);
}
?>