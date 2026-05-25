<?php
$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';
if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals WHERE DATE(created_at) = CURDATE()");
$today_rentals = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as active FROM rentals WHERE status = 'active'");
$active_rentals = $stmt->fetch()['active'];

$stmt = $pdo->query("SELECT COUNT(*) as pending FROM rentals WHERE status = 'pending'");
$pending_approvals = $stmt->fetch()['pending'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'available'");
$available_vehicles = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports WHERE DATE(report_date) = CURDATE()");
$today_damage = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM damage_reports");
$total_damage = $stmt->fetch()['total'];

// Get repeat offenders
$stmt = $pdo->query("
    SELECT c.customer_id, u.name, c.damage_incidents_count
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE c.damage_incidents_count > 0
    ORDER BY c.damage_incidents_count DESC
    LIMIT 5
");
$repeat_offenders = $stmt->fetchAll();

// Get recent rentals
$stmt = $pdo->query("
    SELECT r.*, v.model, u.name
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$recent_rentals = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
            <p class="text-muted">Welcome to Admin Portal</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon accent">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_rentals; ?></h3>
                    <p>Today's Rentals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $active_rentals; ?></h3>
                    <p>Active Rentals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_approvals; ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon accent">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $available_vehicles; ?></h3>
                    <p>Available Vehicles</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Damage Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_damage; ?></h3>
                    <p>Today's Damage Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-alert-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_damage; ?></h3>
                    <p>Total Damage Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($repeat_offenders); ?></h3>
                    <p>Repeat Offenders Flagged</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Repeat Offenders -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-shield"></i> Repeat Offenders</h5>
                </div>
                <div class="card-body">
                    <?php if (count($repeat_offenders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Incidents</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($repeat_offenders as $offender): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($offender['name']); ?></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?php echo $offender['damage_incidents_count']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No repeat offenders at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Rentals -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Rentals</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_rentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['name']); ?></td>
                                        <td><?php echo htmlspecialchars($rental['model']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $rental['status']; ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
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

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="rentals.php" class="btn btn-primary me-2">
                        <i class="fas fa-calendar-check"></i> Manage Rentals
                    </a>
                    <a href="vehicles.php" class="btn btn-primary me-2">
                        <i class="fas fa-car"></i> Manage Vehicles
                    </a>
                    <a href="damage-reports.php" class="btn btn-primary me-2">
                        <i class="fas fa-exclamation-triangle"></i> View Damage Reports
                    </a>
                    <a href="customers.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Manage Customers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
