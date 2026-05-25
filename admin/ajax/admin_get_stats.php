<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals WHERE DATE(created_at) = CURDATE()");
    $today_rentals = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM rentals WHERE status = 'active'");
    $active_rentals = $stmt->fetch()['active'];

    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM rentals WHERE status = 'pending'");
    $pending_approvals = $stmt->fetch()['pending'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'available'");
    $available_vehicles = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'stats' => [
            ['icon' => 'fas fa-calendar-check', 'color' => 'success', 'value' => $today_rentals, 'label' => "Today's Rentals"],
            ['icon' => 'fas fa-play-circle', 'color' => 'warning', 'value' => $active_rentals, 'label' => 'Active Rentals'],
            ['icon' => 'fas fa-hourglass-half', 'color' => 'primary', 'value' => $pending_approvals, 'label' => 'Pending Approvals'],
            ['icon' => 'fas fa-car', 'color' => 'success', 'value' => $available_vehicles, 'label' => 'Available Vehicles']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>