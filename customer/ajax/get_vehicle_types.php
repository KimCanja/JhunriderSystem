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

try {
    $stmt = $pdo->query("SELECT DISTINCT type FROM vehicles WHERE type IS NOT NULL AND type != '' ORDER BY type ASC");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'types' => $types]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>