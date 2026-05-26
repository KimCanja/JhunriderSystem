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

try {
    // Get SOS alerts with user and vehicle information
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            v.model,
            v.plate_number
        FROM sos_alerts s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN rentals r ON s.rental_id = r.rental_id
        LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        ORDER BY 
            CASE 
                WHEN s.status = 'pending' THEN 1
                WHEN s.status = 'responded' THEN 2
                ELSE 3
            END,
            s.created_at DESC
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($alerts as &$alert) {
        $alert['created_at'] = date('M d, Y h:i A', strtotime($alert['created_at']));
        $alert['alert_type'] = $alert['alert_type'] ?? 'emergency';
        $alert['status'] = $alert['status'] ?? 'pending';
    }
    
    echo json_encode(['success' => true, 'alerts' => $alerts]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>