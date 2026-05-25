<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$vehicle_id = $_POST['vehicle_id'] ?? 0;

try {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    $stmt->execute([$vehicle_id]);
    
    echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Delete failed.']);
}
?>