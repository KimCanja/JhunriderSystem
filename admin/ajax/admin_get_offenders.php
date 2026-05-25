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
        SELECT c.customer_id, u.name, c.damage_incidents_count
        FROM customers c
        JOIN users u ON c.user_id = u.id
        WHERE c.damage_incidents_count > 0
        ORDER BY c.damage_incidents_count DESC
        LIMIT 5
    ");
    $offenders = $stmt->fetchAll();

    $formatted = [];
    foreach ($offenders as $offender) {
        $formatted[] = [
            'name' => $offender['name'],
            'incidents' => $offender['damage_incidents_count']
        ];
    }

    echo json_encode(['success' => true, 'offenders' => $formatted]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>