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

$type_filter = $_GET['type'] ?? '';
$price_filter = $_GET['price'] ?? '';

try {
    // Build query
    $query = "SELECT * FROM vehicles WHERE status = 'available'";
    $params = [];

    if (!empty($type_filter)) {
        $query .= " AND type = ?";
        $params[] = $type_filter;
    }

    if (!empty($price_filter) && is_numeric($price_filter)) {
        $query .= " AND price_per_day <= ?";
        $params[] = $price_filter;
    }

    $query .= " ORDER BY model ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>