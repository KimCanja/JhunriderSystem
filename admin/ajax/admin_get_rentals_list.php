<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$status = $_GET['status'] ?? '';
$allowed = ['pending', 'approved', 'active', 'completed', 'cancelled', ''];
if (!in_array($status, $allowed)) {
    $status = '';
}

$query = "
    SELECT 
        r.*, 
        v.model, 
        v.plate_number, 
        v.type,
        v.price_per_day,
        u.name, 
        u.email
    FROM rentals r
    INNER JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    INNER JOIN users u ON r.user_id = u.id
";

$params = [];
if (!empty($status)) {
    $query .= " WHERE r.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

$formatted = [];
foreach ($rentals as $rental) {
    $formatted[] = [
        'id' => $rental['rental_id'],
        'customer_name' => $rental['name'],
        'customer_email' => $rental['email'],
        'vehicle_model' => $rental['model'],
        'plate_number' => $rental['plate_number'],
        'pickup_date' => date('M d, Y', strtotime($rental['pickup_date'])),
        'pickup_time' => $rental['pickup_time'] ? date('h:i A', strtotime($rental['pickup_time'])) : '',
        'return_date' => date('M d, Y', strtotime($rental['return_date'])),
        'status' => $rental['status'],
        'total_price' => $rental['total_price']
    ];
}

echo json_encode(['success' => true, 'rentals' => $formatted]);
?>