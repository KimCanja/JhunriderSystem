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
        SELECT s.*, u.name as user_name, u.email as user_email,
               v.model, v.plate_number
        FROM sos_alerts s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        ORDER BY 
            CASE s.status 
                WHEN 'pending' THEN 1 
                WHEN 'responded' THEN 2 
                ELSE 3 
            END,
            s.created_at DESC
    ");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = [];
    foreach ($alerts as $alert) {
        $formatted[] = [
            'sos_id' => $alert['sos_id'],
            'user_name' => $alert['user_name'],
            'user_email' => $alert['user_email'],
            'alert_type' => $alert['alert_type'],
            'model' => $alert['model'],
            'plate_number' => $alert['plate_number'],
            'message' => $alert['message'],
            'location_lat' => $alert['location_lat'],
            'location_lng' => $alert['location_lng'],
            'created_at' => date('M d, Y h:i A', strtotime($alert['created_at'])),
            'status' => $alert['status']
        ];
    }
    
    echo json_encode(['success' => true, 'alerts' => $formatted]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>