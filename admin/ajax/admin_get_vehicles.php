<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$stmt = $pdo->query("SELECT * FROM vehicles ORDER BY model ASC");
$vehicles = $stmt->fetchAll();

echo json_encode(['success' => true, 'vehicles' => $vehicles]);
?>