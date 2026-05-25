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

$budget = floatval($_POST['budget'] ?? 0);
$passengers = intval($_POST['passengers'] ?? 1);
$purpose = $_POST['purpose'] ?? 'travel';

// Map purpose to vehicle types
$purpose_mapping = [
    'travel' => ['SUV', 'Sedan', 'Hatchback'],
    'event' => ['Luxury', 'SUV', 'Van'],
    'business' => ['Luxury', 'Sedan'],
    'family' => ['SUV', 'Van', 'MPV'],
    'adventure' => ['SUV', 'Pickup', '4x4'],
    'economy' => ['Hatchback', 'Sedan', 'Compact']
];

$allowed_types = $purpose_mapping[$purpose] ?? ['Sedan', 'SUV'];

try {
    // Query for suggestions based on budget and purpose
    $placeholders = implode(',', array_fill(0, count($allowed_types), '?'));
    $query = "
        SELECT * FROM vehicles 
        WHERE status = 'available' 
        AND price_per_day <= ?
        AND type IN ($placeholders)
        ORDER BY price_per_day ASC
        LIMIT 6
    ";
    
    $params = array_merge([$budget], $allowed_types);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suggested_vehicles = $stmt->fetchAll();
    
    if (count($suggested_vehicles) > 0) {
        $message = "Based on your budget of ₱" . number_format($budget, 2) . 
                   ", {$passengers} passenger(s), and {$purpose} purpose, we found " . 
                   count($suggested_vehicles) . " vehicle(s) for you!";
    } else {
        $message = "No vehicles found matching your criteria. Try increasing your budget or changing the purpose.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'vehicles' => $suggested_vehicles
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>