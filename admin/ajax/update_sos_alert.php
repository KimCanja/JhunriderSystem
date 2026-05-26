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

$sos_id = $_POST['sos_id'] ?? null;
$status = $_POST['status'] ?? null;
$admin_response = trim($_POST['admin_response'] ?? '');

// Validate inputs
if (!$sos_id) {
    echo json_encode(['success' => false, 'message' => 'Alert ID is required']);
    exit();
}

if (!$status) {
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit();
}

if (empty($admin_response)) {
    echo json_encode(['success' => false, 'message' => 'Response notes are required']);
    exit();
}

try {
    // Check if the alert exists
    $stmt = $pdo->prepare("SELECT * FROM sos_alerts WHERE sos_id = ?");
    $stmt->execute([$sos_id]);
    $alert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alert) {
        echo json_encode(['success' => false, 'message' => 'Alert not found']);
        exit();
    }
    
    // Update the alert status
    $stmt = $pdo->prepare("
        UPDATE sos_alerts 
        SET status = ?, admin_response = ?, responded_at = NOW() 
        WHERE sos_id = ?
    ");
    $stmt->execute([$status, $admin_response, $sos_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Alert status updated successfully',
        'new_status' => $status
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>