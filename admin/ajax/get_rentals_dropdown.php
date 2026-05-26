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
    // Get rentals that are active/completed and have vehicles
    $stmt = $pdo->prepare("
        SELECT 
            r.rental_id,
            r.pickup_date,
            r.return_date,
            r.status,
            u.name as customer_name,
            v.model as vehicle_model,
            v.plate_number
        FROM rentals r
        JOIN users u ON r.user_id = u.id
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        WHERE r.status IN ('active', 'completed', 'approved')
        ORDER BY r.rental_id DESC
        LIMIT 50
    ");
    $stmt->execute();
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rental_list = [];
    foreach ($rentals as $rental) {
        $rental_list[] = [
            'rental_id' => $rental['rental_id'],
            'label' => "#{$rental['rental_id']} - {$rental['customer_name']} - {$rental['vehicle_model']} ({$rental['plate_number']}) - Status: {$rental['status']}"
        ];
    }
    
    echo json_encode(['success' => true, 'rentals' => $rental_list]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>