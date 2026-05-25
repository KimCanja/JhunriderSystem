<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$model = $_POST['model'] ?? '';
$plate_number = $_POST['plate_number'] ?? '';
$year = $_POST['year'] ?? '';
$type = $_POST['type'] ?? '';
$passenger_capacity = $_POST['passenger_capacity'] ?? 4;
$status = $_POST['status'] ?? 'available';
$current_mileage = $_POST['current_mileage'] ?? 0;
$price_per_day = $_POST['price_per_day'] ?? 0;
$photo_url = '';

// Handle photo upload
if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/vehicles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['vehicle_photo']['name'], PATHINFO_EXTENSION);
    $upload_path = $upload_dir . $filename;
    $photo_url = 'uploads/vehicles/' . $filename;
    move_uploaded_file($_FILES['vehicle_photo']['tmp_name'], $upload_path);
}

try {
    $stmt = $pdo->prepare("INSERT INTO vehicles (model, plate_number, year, type, passenger_capacity, status, current_mileage, price_per_day, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url]);
    
    echo json_encode(['success' => true, 'message' => 'Vehicle added successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Plate number may already exist.']);
}
?>