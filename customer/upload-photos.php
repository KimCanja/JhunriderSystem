<?php
require_once '../config/database.php';
require_once '../config/constants.php';

if (!isCustomer()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$rental_id = $_POST['rental_id'] ?? null;
$photo_type = $_POST['type'] ?? null; // 'before' or 'after'

if (!$rental_id || !$photo_type || !in_array($photo_type, ['before', 'after'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid parameters']));
}

// Verify rental belongs to user
$stmt = $pdo->prepare("SELECT rental_id FROM rentals WHERE rental_id = ? AND user_id = ?");
$stmt->execute([$rental_id, $_SESSION['user_id']]);
if ($stmt->rowCount() === 0) {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

// Handle file uploads
$uploaded_files = [];
$upload_dir = UPLOAD_PATH . $photo_type . '/';

// Create directory if not exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['photos'])) {
    $files = $_FILES['photos'];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_type = $files['type'][$i];

        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file_type, $allowed_types) || $file_size > 5 * 1024 * 1024) {
            continue;
        }

        // Generate unique filename
        $new_filename = 'rental_' . $rental_id . '_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
        $file_path = $upload_dir . $new_filename;
        $relative_path = 'uploads/' . $photo_type . '/' . $new_filename;

        if (move_uploaded_file($file_tmp, $file_path)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rental_photos (rental_id, type, image_path, uploaded_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$rental_id, $photo_type, $relative_path]);
                $uploaded_files[] = [
                    'id' => $pdo->lastInsertId(),
                    'path' => $relative_path,
                    'type' => $photo_type
                ];
            } catch (PDOException $e) {
                unlink($file_path);
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'uploaded_files' => $uploaded_files,
    'count' => count($uploaded_files)
]);
?>
