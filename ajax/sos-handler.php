<?php
// ajax/sos-handler.php - FINAL WORKING VERSION
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
require_once '../config/database.php';
ob_end_clean();

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo '{"success":false,"message":"Not logged in"}';
    exit;
}

if (!isset($_POST['action']) || $_POST['action'] !== 'send_sos') {
    echo '{"success":false,"message":"Invalid action"}';
    exit;
}

$user_id = $_SESSION['user_id'];
$alert_type = $_POST['alert_type'] ?? 'emergency';
$message = $_POST['message'] ?? '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sos_alerts (
        sos_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        alert_type VARCHAR(50) NOT NULL,
        message TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("INSERT INTO sos_alerts (user_id, alert_type, message, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $alert_type, $message]);
    
    echo '{"success":true,"message":"Alert sent to admin"}';
    
} catch (Exception $e) {
    echo '{"success":false,"message":"Database error"}';
}
?>