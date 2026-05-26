<?php
$page_title = 'Damage Report Details';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$report_id = $_GET['id'] ?? 0;

// Get report details
$stmt = $pdo->prepare("
    SELECT 
        dr.*,
        r.rental_id,
        r.pickup_date,
        r.return_date,
        r.status as rental_status,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        v.model as vehicle_model,
        v.plate_number,
        v.type as vehicle_type,
        v.year as vehicle_year
    FROM damage_reports dr
    JOIN rentals r ON dr.rental_id = r.rental_id
    JOIN users u ON r.user_id = u.id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE dr.report_id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    redirect('damage-reports.php');
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-exclamation-triangle"></i> Damage Report Details</h1>
                <p class="text-muted">View complete damage report information</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="damage-reports.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Report Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> Report Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="35%">Report ID:</th>
                                <td>#<?php echo $report['report_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Report Date:</th>
                                <td><?php echo date('F d, Y h:i A', strtotime($report['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Severity:</th>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['severity'] == 'high' ? 'danger' : 
                                            ($report['severity'] == 'medium' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($report['severity']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rental Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Rental Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="35%">Rental ID:</th>
                                <td>#<?php echo $report['rental_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Pickup Date:</th>
                                <td><?php echo date('F d, Y', strtotime($report['pickup_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td><?php echo date('F d, Y', strtotime($report['return_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Rental Status:</th>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['rental_status'] == 'active' ? 'success' : 'secondary'; 
                                    ?>">
                                        <?php echo ucfirst($report['rental_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="35%">Name:</th>
                                <td><?php echo htmlspecialchars($report['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($report['customer_email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($report['customer_phone'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-car"></i> Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="35%">Model:</th>
                                <td><?php echo htmlspecialchars($report['vehicle_model']); ?></td>
                            </tr>
                            <tr>
                                <th>Plate Number:</th>
                                <td><?php echo htmlspecialchars($report['plate_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td><?php echo htmlspecialchars($report['vehicle_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Year:</th>
                                <td><?php echo $report['vehicle_year']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Damage Description -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Damage Description</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Admin Notes -->
            <?php if ($report['admin_notes']): ?>
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Admin Notes</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>