<?php
$page_title = 'My Rentals';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Get all rentals
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number 
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$rentals = $stmt->fetchAll();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_rental'])) {
    $rental_id = $_POST['rental_id'] ?? '';
    
    $stmt = $pdo->prepare("SELECT status FROM rentals WHERE rental_id = ? AND user_id = ?");
    $stmt->execute([$rental_id, $user_id]);
    $rental = $stmt->fetch();
    
    if ($rental && in_array($rental['status'], ['pending', 'approved'])) {
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        header("refresh:1;url=my-rentals.php");
    }
}
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-history"></i> My Rentals</h1>
            <p class="text-muted">View and manage your rental bookings</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (count($rentals) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Plate</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Pickup Time</th>
                                        <th>Status</th>
                                        <th>Total Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rental['model']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($rental['plate_number']); ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($rental['return_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($rental['pickup_time'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $rental['status']; ?>">
                                                    <?php echo ucfirst($rental['status']); ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($rental['total_price'], 2); ?></td>
                                            <td>
                                                <a href="rental-details.php?id=<?php echo $rental['rental_id']; ?>" class="btn btn-sm btn-secondary me-2">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if (in_array($rental['status'], ['pending', 'approved'])): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                                        <button type="submit" name="cancel_rental" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this rental?')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-inbox"></i> No rentals yet. <a href="browse-vehicles.php">Start booking now!</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
