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
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports WHERE DATE(report_date) = CURDATE()");
    $today_damage = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports");
    $total_damage = $stmt->fetch()['total'];

    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT c.customer_id) as count
        FROM customers c
        WHERE c.damage_incidents_count > 0
    ");
    $repeat_offenders = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            ['icon' => 'fas fa-exclamation-triangle', 'color' => 'danger', 'value' => $today_damage, 'label' => "Today's Damage Reports"],
            ['icon' => 'fas fa-alert-circle', 'color' => 'danger', 'value' => $total_damage, 'label' => 'Total Damage Reports'],
            ['icon' => 'fas fa-user-shield', 'color' => 'danger', 'value' => $repeat_offenders, 'label' => 'Repeat Offenders Flagged']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>