<?php
$page_title = 'Damage Report Details';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$report_id = $_GET['id'] ?? null;

if (!$report_id) {
    redirect(BASE_URL . 'admin/damage-reports.php');
}

// Get damage report
$stmt = $pdo->prepare("
    SELECT dr.*, r.rental_id, v.model, v.plate_number, u.name, u.email, c.damage_incidents_count
    FROM damage_reports dr
    JOIN rentals r ON dr.rental_id = r.rental_id
    JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
    JOIN customers c ON dr.customer_id = c.customer_id
    JOIN users u ON c.user_id = u.id
    WHERE dr.report_id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    redirect(BASE_URL . 'admin/damage-reports.php');
}

// Get rental photos for comparison
$stmt = $pdo->prepare("SELECT * FROM rental_photos WHERE rental_id = ? ORDER BY type, uploaded_at");
$stmt->execute([$report['rental_id']]);
$photos = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="damage-reports.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-exclamation-triangle"></i> Damage Report #<?php echo $report['report_id']; ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Report Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Report Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Report Date</label>
                            <p><?php echo date('M d, Y h:i A', strtotime($report['report_date'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Severity</label>
                            <p><span class="badge badge-<?php echo $report['severity']; ?>"><?php echo ucfirst($report['severity']); ?></span></p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Description</label>
                            <p><?php echo htmlspecialchars($report['description']); ?></p>
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="form-label text-muted">Admin Notes</label>
                            <p><?php echo htmlspecialchars($report['admin_notes'] ?? 'No notes'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle & Rental Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Vehicle & Rental Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Vehicle</label>
                            <p><?php echo htmlspecialchars($report['model']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Plate Number</label>
                            <p><?php echo htmlspecialchars($report['plate_number']); ?></p>
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="form-label text-muted">Rental ID</label>
                            <p><a href="rental-details.php?id=<?php echo $report['rental_id']; ?>" class="btn btn-sm btn-secondary">View Rental #<?php echo $report['rental_id']; ?></a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photos Comparison -->
            <?php if (count($photos) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-images"></i> Before & After Photos</h5>
                    </div>
                    <div class="card-body">
                        <div class="photo-grid">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($photo['image_path']); ?>" alt="Photo">
                                    <span class="photo-label"><?php echo ucfirst($photo['type']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Name:</strong><br>
                        <?php echo htmlspecialchars($report['name']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($report['email']); ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Total Damage Incidents:</strong><br>
                        <span class="badge badge-<?php echo $report['damage_incidents_count'] > 2 ? 'danger' : ($report['damage_incidents_count'] > 0 ? 'warning' : 'success'); ?>">
                            <?php echo $report['damage_incidents_count']; ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Alert Badge -->
            <?php if ($report['damage_incidents_count'] > 2): ?>
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user-shield"></i> Repeat Offender Alert</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">This customer has <strong><?php echo $report['damage_incidents_count']; ?> damage incidents</strong>. Consider requiring a higher deposit or additional insurance for future rentals.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
