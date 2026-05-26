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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $rental_id = $_POST['rental_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $severity = $_POST['severity'] ?? 'medium';
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // Validate inputs
    if (empty($rental_id) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Rental and description are required']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO damage_reports (rental_id, description, severity, admin_notes, report_date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$rental_id, $description, $severity, $admin_notes]);
        
        echo json_encode(['success' => true, 'message' => 'Damage report created successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>