<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT r.*, v.model, u.name
        FROM rentals r
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $rentals = $stmt->fetchAll();

    $formatted = [];
    foreach ($rentals as $rental) {
        $formatted[] = [
            'customer' => $rental['name'],
            'vehicle' => $rental['model'],
            'status' => $rental['status']
        ];
    }

    echo json_encode(['success' => true, 'rentals' => $formatted]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>