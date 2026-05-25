<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    $search = $_GET['search'] ?? '';
    
    // Build query
    $query = "
        SELECT c.*, u.name, u.email, u.created_at
        FROM customers c
        JOIN users u ON c.user_id = u.id
        WHERE u.role = 'customer'
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.license_number LIKE ?)";
        $search_term = '%' . $search . '%';
        $params = [$search_term, $search_term, $search_term];
    }
    
    $query .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for display
    $formatted_customers = [];
    foreach ($customers as $customer) {
        $formatted_customers[] = [
            'customer_id' => $customer['customer_id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
            'contact_number' => $customer['contact_number'] ?? '',
            'license_number' => $customer['license_number'] ?? '',
            'damage_incidents_count' => (int)$customer['damage_incidents_count'],
            'member_since' => date('M d, Y', strtotime($customer['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'customers' => $formatted_customers
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>