<?php
$page_title = 'Rental Details';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';
if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$rental_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$rental_id) {
    redirect(BASE_URL . 'customer/my-rentals.php');
}

// Get rental info
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number, v.year, v.type
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
    WHERE r.rental_id = ? AND r.user_id = ?
");
$stmt->execute([$rental_id, $user_id]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect(BASE_URL . 'customer/my-rentals.php');
}

// Get photos
$stmt = $pdo->prepare("SELECT * FROM rental_photos WHERE rental_id = ? ORDER BY type, uploaded_at");
$stmt->execute([$rental_id]);
$photos = $stmt->fetchAll();

// Get damage reports
$stmt = $pdo->prepare("SELECT * FROM damage_reports WHERE rental_id = ? ORDER BY report_date DESC");
$stmt->execute([$rental_id]);
$damage_reports = $stmt->fetchAll();
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="my-rentals.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-file-alt"></i> Rental Details</h1>
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Notes</label>
                            <p><?php echo htmlspecialchars($rental['notes'] ?? 'No notes'); ?></p>
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
                            <i class="fas fa-inbox"></i> No photos uploaded yet.
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
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="mb-2"><strong><?php echo htmlspecialchars($report['description']); ?></strong></p>
                                        <p class="text-muted mb-0">
                                            <small><?php echo date('M d, Y h:i A', strtotime($report['report_date'])); ?></small>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge badge-<?php echo $report['severity']; ?>">
                                            <?php echo ucfirst($report['severity']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($report['admin_notes']): ?>
                                    <hr class="my-2">
                                    <p class="mb-0"><small><strong>Admin Notes:</strong> <?php echo htmlspecialchars($report['admin_notes']); ?></small></p>
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
                    <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 150px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-car"></i>
                    </div>
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
                    <p class="mb-0">
                        <strong>Status:</strong> <span class="badge badge-<?php echo $rental['status']; ?>"><?php echo ucfirst($rental['status']); ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
