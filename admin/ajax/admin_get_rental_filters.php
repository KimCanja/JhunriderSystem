<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$statuses = ['pending', 'approved', 'active', 'completed', 'cancelled'];
$filters = [];
$total = 0;

foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE status = ?");
    $stmt->execute([$status]);
    $count = $stmt->fetchColumn();
    $filters[$status] = $count;
    $total += $count;
}
$filters['total'] = $total;

echo json_encode(['success' => true, 'filters' => $filters]);
?>