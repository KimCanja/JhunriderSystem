<?php
$page_title = 'Manage Rentals';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$success = '';
$error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = $_POST['rental_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($rental_id && in_array($action, ['approve', 'reject', 'start', 'complete'])) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'approved' WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                $success = 'Rental approved successfully!';
            } 
            elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                $success = 'Rental rejected successfully!';
            }
            elseif ($action === 'start') {
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'active' WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                
                // Update vehicle status
                $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                $rental = $stmt->fetch();
                if ($rental) {
                    $stmt = $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE vehicle_id = ?");
                    $stmt->execute([$rental['vehicle_id']]);
                }
                $success = 'Rental started successfully!';
            }
            elseif ($action === 'complete') {
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'completed' WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                
                // Update vehicle status back to available
                $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE rental_id = ?");
                $stmt->execute([$rental_id]);
                $rental = $stmt->fetch();
                if ($rental) {
                    $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ?");
                    $stmt->execute([$rental['vehicle_id']]);
                }
                $success = 'Rental completed successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Operation failed: ' . $e->getMessage();
        }
    }
}

// Get filter - sanitize input properly
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed_statuses = ['pending', 'approved', 'active', 'completed', 'cancelled', ''];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Get rentals using prepared statement (FIXED SQL)
$query = "
    SELECT 
        r.*, 
        v.model, 
        v.plate_number, 
        v.type,
        v.price_per_day,
        u.name, 
        u.email
    FROM rentals r
    INNER JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    INNER JOIN users u ON r.user_id = u.id
";

$params = [];
if (!empty($status_filter)) {
    $query .= " WHERE r.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

// Get counts for each status
$status_counts = [];
$statuses = ['pending', 'approved', 'active', 'completed', 'cancelled'];
foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE status = ?");
    $stmt->execute([$status]);
    $status_counts[$status] = $stmt->fetchColumn();
}
$total_count = array_sum($status_counts);
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-calendar-check"></i> Manage Rentals</h1>
            <p class="text-muted">Review and approve rental bookings</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="rentals.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list"></i> All 
                            <span class="badge bg-light text-dark ms-1"><?php echo $total_count; ?></span>
                        </a>
                        <a href="rentals.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            <i class="fas fa-clock"></i> Pending 
                            <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['pending']; ?></span>
                        </a>
                        <a href="rentals.php?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-info' : 'btn-outline-info'; ?>">
                            <i class="fas fa-check-circle"></i> Approved 
                            <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['approved']; ?></span>
                        </a>
                        <a href="rentals.php?status=active" class="btn <?php echo $status_filter === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-play"></i> Active 
                            <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['active']; ?></span>
                        </a>
                        <a href="rentals.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-flag-checkered"></i> Completed 
                            <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['completed']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> 
                        Rental Bookings
                        <?php if (!empty($status_filter)): ?>
                            <span class="badge bg-primary ms-2">Filtered: <?php echo ucfirst($status_filter); ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($rentals) === 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times" style="font-size: 64px; color: #CBD5E1;"></i>
                            <h4 class="mt-3 text-muted">No rentals found</h4>
                            <p class="text-muted">No rental bookings available for the selected filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Total Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
    <?php foreach ($rentals as $rental): ?>
        <tr>
            <td>
                <div>
                    <strong><?php echo htmlspecialchars($rental['name']); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo htmlspecialchars($rental['email']); ?></small>
                </div>
            </td>
            <td>
                <?php echo htmlspecialchars($rental['model']); ?>
                <br>
                <small class="text-muted"><?php echo htmlspecialchars($rental['plate_number']); ?></small>
            </td>
            <td>
                <i class="fas fa-calendar-alt text-primary"></i> 
                <?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?>
                <?php if ($rental['pickup_time']): ?>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($rental['pickup_time'])); ?>
                    </small>
                <?php endif; ?>
            </td>
            <td>
                <i class="fas fa-calendar-check text-success"></i> 
                <?php echo date('M d, Y', strtotime($rental['return_date'])); ?>
            </td>
            <td>
                <?php
                // Status badge mapping
                $status_classes = [
                    'pending' => 'warning',
                    'approved' => 'info', 
                    'active' => 'success',
                    'completed' => 'secondary',
                    'cancelled' => 'danger'
                ];
                $status_class = $status_classes[$rental['status']] ?? 'secondary';
                
                $status_icons = [
                    'pending' => 'fa-clock',
                    'approved' => 'fa-check-circle',
                    'active' => 'fa-play',
                    'completed' => 'fa-flag-checkered',
                    'cancelled' => 'fa-times-circle'
                ];
                $status_icon = $status_icons[$rental['status']] ?? 'fa-question';
                ?>
                <span class="badge bg-<?php echo $status_class; ?>">
                    <i class="fas <?php echo $status_icon; ?>"></i>
                    <?php echo ucfirst($rental['status']); ?>
                </span>
            </td>
            <td>
                <strong class="text-success">$<?php echo number_format($rental['total_price'], 2); ?></strong>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <a href="rental-details.php?id=<?php echo $rental['rental_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                    </a>
                    
                    <?php if ($rental['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Approve this rental?')">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reject this rental? This action cannot be undone.')">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($rental['status'] === 'approved'): ?>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Start this rental? This will mark the vehicle as rented.')">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($rental['status'] === 'active'): ?>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Complete this rental? This will mark the vehicle as available again.')">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-flag-checkered"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once '../includes/footer.php'; ?>