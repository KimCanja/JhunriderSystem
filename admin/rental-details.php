<?php
$page_title = 'Rental Details';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$rental_id = $_GET['id'] ?? null;

if (!$rental_id) {
    redirect(BASE_URL . 'admin/rentals.php');
}

// Get rental info
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number, v.year, v.type, u.name, u.email
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.id
    WHERE r.rental_id = ?
");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect(BASE_URL . 'admin/rentals.php');
}

// Get photos
$stmt = $pdo->prepare("SELECT * FROM rental_photos WHERE rental_id = ? ORDER BY type, uploaded_at");
$stmt->execute([$rental_id]);
$photos = $stmt->fetchAll();

// Get damage reports
$stmt = $pdo->prepare("SELECT * FROM damage_reports WHERE rental_id = ? ORDER BY report_date DESC");
$stmt->execute([$rental_id]);
$damage_reports = $stmt->fetchAll();

// Get customer info
$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$rental['user_id']]);
$customer = $stmt->fetch();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="rentals.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-file-alt"></i> Rental #<?php echo $rental['rental_id']; ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Rental Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Booking Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Vehicle</label>
                            <p class="h5"><?php echo htmlspecialchars($rental['model']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Plate Number</label>
                            <p class="h5"><?php echo htmlspecialchars($rental['plate_number']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Pickup Date</label>
                            <p><?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Pickup Time</label>
                            <p><?php echo date('h:i A', strtotime($rental['pickup_time'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Return Date</label>
                            <p><?php echo date('M d, Y', strtotime($rental['return_date'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p><span class="badge badge-<?php echo $rental['status']; ?>"><?php echo ucfirst($rental['status']); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Name</label>
                            <p><?php echo htmlspecialchars($rental['name']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <p><?php echo htmlspecialchars($rental['email']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Contact</label>
                            <p><?php echo htmlspecialchars($customer['contact_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">License Number</label>
                            <p><?php echo htmlspecialchars($customer['license_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Address</label>
                            <p><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="form-label text-muted">Damage Incidents</label>
                            <p>
                                <span class="badge badge-<?php echo $customer['damage_incidents_count'] > 0 ? 'danger' : 'success'; ?>">
                                    <?php echo $customer['damage_incidents_count']; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-images"></i> Check-in/Check-out Photos</h5>
                </div>
                <div class="card-body">
                    <?php if (count($photos) > 0): ?>
                        <div class="photo-grid">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($photo['image_path']); ?>" alt="Photo">
                                    <span class="photo-label"><?php echo ucfirst($photo['type']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-inbox"></i> No photos uploaded.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Damage Reports -->
            <?php if (count($damage_reports) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Damage Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($damage_reports as $report): ?>
                            <div class="alert alert-warning mb-3">
                                <p class="mb-2"><strong><?php echo htmlspecialchars($report['description']); ?></strong></p>
                                <p class="text-muted mb-2">
                                    <small><?php echo date('M d, Y h:i A', strtotime($report['report_date'])); ?></small>
                                </p>
                                <?php if ($report['admin_notes']): ?>
                                    <p class="mb-0"><small><strong>Notes:</strong> <?php echo htmlspecialchars($report['admin_notes']); ?></small></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Rental ID:</strong> #<?php echo $rental['rental_id']; ?>
                    </p>
                    <p class="mb-2">
                        <strong>Vehicle Type:</strong> <?php echo htmlspecialchars($rental['type']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Year:</strong> <?php echo $rental['year']; ?>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong>Total Price:</strong> <span class="text-success h5">$<?php echo number_format($rental['total_price'], 2); ?></span>
                    </p>
                    <p class="mb-3">
                        <strong>Status:</strong> <span class="badge badge-<?php echo $rental['status']; ?>"><?php echo ucfirst($rental['status']); ?></span>
                    </p>
                    <hr>
                    <?php if ($rental['status'] === 'pending'): ?>
                        <form method="POST" action="rentals.php" class="mb-2">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-check"></i> Approve Rental
                            </button>
                        </form>
                        <form method="POST" action="rentals.php">
                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Reject this rental?')">
                                <i class="fas fa-times"></i> Reject Rental
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
