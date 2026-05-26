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

$status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : null;

try {
    // Build query - make sure column names match your database
    $sql = "
        SELECT 
            r.rental_id,
            r.user_id,
            r.vehicle_id,
            r.pickup_date,
            r.pickup_time,
            r.return_date,
            r.total_price,
            r.status,
            r.created_at,
            u.name as customer_name,
            u.email as customer_email,
            v.model as vehicle_model,
            v.plate_number
        FROM rentals r
        JOIN users u ON r.user_id = u.id
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    ";
    
    if ($status) {
        $sql .= " WHERE r.status = :status";
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($status) {
        $stmt->bindParam(':status', $status);
    }
    
    $stmt->execute();
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($rentals as &$rental) {
        $rental['pickup_date'] = date('M d, Y', strtotime($rental['pickup_date']));
        $rental['return_date'] = date('M d, Y', strtotime($rental['return_date']));
        $rental['total_price'] = (float)$rental['total_price'];
    }
    
    echo json_encode(['success' => true, 'rentals' => $rentals]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>