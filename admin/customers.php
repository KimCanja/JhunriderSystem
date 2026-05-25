<?php
$page_title = 'Manage Customers';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';
if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

// Get search filter
$search = $_GET['search'] ?? '';

// Get customers
$query = "
    SELECT c.*, u.name, u.email, u.created_at
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE u.role = 'customer'
";

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.license_number LIKE ?)";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $search_term = '%' . $search . '%';
    $stmt->execute([$search_term, $search_term, $search_term]);
} else {
    $stmt->execute();
}
$customers = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-users"></i> Manage Customers</h1>
            <p class="text-muted">View and manage customer profiles and rental history</p>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or license..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="customers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Customer List (<?php echo count($customers); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>License</th>
                                    <th>Damage Incidents</th>
                                    <th>Member Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($customer['license_number'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $customer['damage_incidents_count'] > 0 ? 'danger' : 'success'; ?>">
                                                <?php echo $customer['damage_incidents_count']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <a href="customer-details.php?id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
