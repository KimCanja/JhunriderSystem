<?php
$page_title = 'Customer Details';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$customer_id = $_GET['id'] ?? null;

if (!$customer_id) {
    redirect(BASE_URL . 'admin/customers.php');
}

// Get customer info
$stmt = $pdo->prepare("
    SELECT c.*, u.name, u.email, u.created_at
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect(BASE_URL . 'admin/customers.php');
}

// Get rental history
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$customer['user_id']]);
$rentals = $stmt->fetchAll();

// Get damage reports
$stmt = $pdo->prepare("
    SELECT dr.*, v.model, r.rental_id
    FROM damage_reports dr
    JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
    JOIN rentals r ON dr.rental_id = r.rental_id
    WHERE dr.customer_id = ?
    ORDER BY dr.report_date DESC
");
$stmt->execute([$customer_id]);
$damage_reports = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="customers.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['name']); ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Full Name</label>
                            <p><?php echo htmlspecialchars($customer['name']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <p><?php echo htmlspecialchars($customer['email']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Contact Number</label>
                            <p><?php echo htmlspecialchars($customer['contact_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Date of Birth</label>
                            <p><?php echo $customer['birthdate'] ? date('M d, Y', strtotime($customer['birthdate'])) : 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">License Number</label>
                            <p><?php echo htmlspecialchars($customer['license_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Member Since</label>
                            <p><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></p>
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="form-label text-muted">Address</label>
                            <p><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Rental History (<?php echo count($rentals); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($rentals) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rental['model']); ?> (<?php echo htmlspecialchars($rental['plate_number']); ?>)</td>
                                            <td><?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($rental['return_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $rental['status']; ?>">
                                                    <?php echo ucfirst($rental['status']); ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($rental['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No rental history.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Damage Reports -->
            <?php if (count($damage_reports) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Damage Reports (<?php echo count($damage_reports); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Vehicle</th>
                                        <th>Description</th>
                                        <th>Severity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($damage_reports as $report): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($report['model']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($report['description'], 0, 50)); ?>...</td>
                                            <td>
                                                <span class="badge badge-<?php echo $report['severity']; ?>">
                                                    <?php echo ucfirst($report['severity']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
                    <div class="text-center mb-4">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; margin: 0 auto;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <p class="mb-2">
                        <strong>Total Rentals:</strong><br>
                        <span class="h5"><?php echo count($rentals); ?></span>
                    </p>
                    <p class="mb-2">
                        <strong>Damage Incidents:</strong><br>
                        <span class="badge badge-<?php echo $customer['damage_incidents_count'] > 0 ? 'danger' : 'success'; ?>">
                            <?php echo $customer['damage_incidents_count']; ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>Account Status:</strong><br>
                        <span class="badge badge-approved">Active</span>
                    </p>
                </div>
            </div>

            <?php if ($customer['damage_incidents_count'] > 2): ?>
                <div class="card mt-3 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user-shield"></i> Repeat Offender</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">This customer has multiple damage incidents. Consider additional verification or higher deposit for future bookings.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
