<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    // Get today's rentals
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals WHERE DATE(created_at) = CURDATE()");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_rentals = $row ? (int)$row['total'] : 0;

    // Get active rentals
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM rentals WHERE status = 'active'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $active_rentals = $row ? (int)$row['active'] : 0;

    // Get pending approvals
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM rentals WHERE status = 'pending'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_approvals = $row ? (int)$row['pending'] : 0;

    // Get available vehicles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'available'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $available_vehicles = $row ? (int)$row['total'] : 0;

    // Get today's damage reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports WHERE DATE(report_date) = CURDATE()");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_damage = $row ? (int)$row['total'] : 0;

    // Get total damage reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_damage = $row ? (int)$row['total'] : 0;

    // Get repeat offenders
    $stmt = $pdo->query("
        SELECT c.customer_id, u.name, c.damage_incidents_count
        FROM customers c
        JOIN users u ON c.user_id = u.id
        WHERE c.damage_incidents_count > 0
        ORDER BY c.damage_incidents_count DESC
        LIMIT 5
    ");
    $repeat_offenders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_offenders = [];
    foreach ($repeat_offenders as $offender) {
        $formatted_offenders[] = [
            'name' => htmlspecialchars($offender['name']),
            'incidents' => (int)$offender['damage_incidents_count']
        ];
    }

    // Get recent rentals
    $stmt = $pdo->query("
        SELECT r.*, v.model, u.name
        FROM rentals r
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recent_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_rentals = [];
    foreach ($recent_rentals as $rental) {
        $formatted_rentals[] = [
            'customer' => htmlspecialchars($rental['name']),
            'vehicle' => htmlspecialchars($rental['model']),
            'status' => $rental['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'today_rentals' => $today_rentals,
        'active_rentals' => $active_rentals,
        'pending_approvals' => $pending_approvals,
        'available_vehicles' => $available_vehicles,
        'today_damage' => $today_damage,
        'total_damage' => $total_damage,
        'repeat_offenders_count' => count($repeat_offenders),
        'repeat_offenders' => $formatted_offenders,
        'recent_rentals' => $formatted_rentals
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>