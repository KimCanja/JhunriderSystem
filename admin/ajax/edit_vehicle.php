<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$vehicle_id = $_POST['vehicle_id'] ?? 0;
$model = $_POST['model'] ?? '';
$plate_number = $_POST['plate_number'] ?? '';
$year = $_POST['year'] ?? '';
$type = $_POST['type'] ?? '';
$passenger_capacity = $_POST['passenger_capacity'] ?? 4;
$status = $_POST['status'] ?? 'available';
$current_mileage = $_POST['current_mileage'] ?? 0;
$price_per_day = $_POST['price_per_day'] ?? 0;
$photo_url = $_POST['existing_photo'] ?? '';

// Handle new photo upload
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
    $stmt = $pdo->prepare("UPDATE vehicles SET model=?, plate_number=?, year=?, type=?, passenger_capacity=?, status=?, current_mileage=?, price_per_day=?, photo_url=? WHERE vehicle_id=?");
    $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url, $vehicle_id]);
    
    echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed.']);
}
?>