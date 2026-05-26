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
    $stmt = $pdo->prepare("
        SELECT 
            r.rental_id,
            r.pickup_date,
            r.return_date,
            r.status,
            r.total_price,
            r.created_at,
            u.name as customer_name,
            u.email as customer_email,
            v.model as vehicle_model,
            v.plate_number
        FROM rentals r
        JOIN users u ON r.user_id = u.id
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'rentals' => $rentals
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>