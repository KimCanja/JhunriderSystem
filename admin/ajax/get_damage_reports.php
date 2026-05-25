<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT dr.*, r.rental_id, v.model, v.plate_number, u.name as customer_name
        FROM damage_reports dr
        JOIN rentals r ON dr.rental_id = r.rental_id
        JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
        JOIN customers c ON dr.customer_id = c.customer_id
        JOIN users u ON c.user_id = u.id
        ORDER BY dr.report_date DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = [];
    foreach ($reports as $report) {
        $formatted[] = [
            'report_id' => $report['report_id'],
            'report_date' => date('M d, Y', strtotime($report['report_date'])),
            'customer_name' => $report['customer_name'],
            'vehicle_model' => $report['model'],
            'plate_number' => $report['plate_number'],
            'description' => $report['description'],
            'severity' => $report['severity']
        ];
    }
    
    echo json_encode(['success' => true, 'reports' => $formatted]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>