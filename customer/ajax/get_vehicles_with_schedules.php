<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/constants.php';

header('Content-Type: application/json');

if (!isCustomer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$type = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : null;
$price = isset($_GET['price']) && !empty($_GET['price']) ? (float)$_GET['price'] : null;

try {
    // First, check if there are any schedules at all
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM schedules WHERE is_booked = 0 AND available_date >= CURDATE()");
    $scheduleCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Query to get vehicles with at least one available schedule
    $sql = "
        SELECT 
            v.*,
            COUNT(s.schedule_id) as available_slots
        FROM vehicles v
        INNER JOIN schedules s ON v.vehicle_id = s.vehicle_id 
            AND s.is_booked = 0 
            AND s.available_date >= CURDATE()
        WHERE v.status = 'available'
    ";
    
    if ($type) {
        $sql .= " AND v.type = :type";
    }
    
    if ($price) {
        $sql .= " AND v.price_per_day <= :price";
    }
    
    $sql .= " GROUP BY v.vehicle_id";
    $sql .= " HAVING available_slots > 0";
    $sql .= " ORDER BY v.model ASC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($type) {
        $stmt->bindParam(':type', $type);
    }
    
    if ($price) {
        $stmt->bindParam(':price', $price);
    }
    
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total available slots count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM schedules 
        WHERE is_booked = 0 AND available_date >= CURDATE()
    ");
    $totalSlots = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'vehicles' => $vehicles,
        'total_slots' => $totalSlots['total'],
        'debug' => [
            'schedule_count' => $scheduleCount['count'],
            'vehicle_count' => count($vehicles)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>