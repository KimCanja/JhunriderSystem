<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get customer info
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();

    // Get stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rentals WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_rentals = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM rentals WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_rentals = $stmt->fetch()['active'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM rentals WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_rentals = $stmt->fetch()['pending'];

    // Get recent rentals
    $stmt = $pdo->prepare("
        SELECT r.*, v.model, v.plate_number 
        FROM rentals r 
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_rentals = $stmt->fetchAll();

    $formatted_rentals = [];
    foreach ($recent_rentals as $rental) {
        $formatted_rentals[] = [
            'rental_id' => $rental['rental_id'],
            'model' => $rental['model'],
            'plate_number' => $rental['plate_number'],
            'pickup_date' => date('M d, Y', strtotime($rental['pickup_date'])),
            'return_date' => date('M d, Y', strtotime($rental['return_date'])),
            'status' => $rental['status'],
            'total_price' => $rental['total_price']
        ];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            ['icon' => 'fas fa-car', 'iconClass' => 'accent', 'value' => $total_rentals, 'label' => 'Total Rentals'],
            ['icon' => 'fas fa-hourglass-half', 'iconClass' => 'primary', 'value' => $pending_rentals, 'label' => 'Pending Approvals'],
            ['icon' => 'fas fa-play-circle', 'iconClass' => 'warning', 'value' => $active_rentals, 'label' => 'Active Rentals'],
            ['icon' => 'fas fa-exclamation-triangle', 'iconClass' => 'danger', 'value' => $customer['damage_incidents_count'] ?? 0, 'label' => 'Damage Reports']
        ],
        'recent_rentals' => $formatted_rentals
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>