<?php
$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

// DIRECT DATABASE QUERIES (NO AJAX) - This will show if data exists
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
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
                <p class="text-muted">Welcome to Admin Portal</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-sm btn-success me-2" id="manualRefreshBtn">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-sm btn-secondary" id="toggleAutoRefreshBtn">
                    <i class="fas fa-clock"></i> Auto: ON
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                        <h3 class="mb-0" id="today_rentals"><?php echo $today_rentals; ?></h3>
                        <p class="text-muted mb-0">Today's Rentals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-play-circle fa-2x text-warning mb-2"></i>
                        <h3 class="mb-0" id="active_rentals"><?php echo $active_rentals; ?></h3>
                        <p class="text-muted mb-0">Active Rentals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-hourglass-half fa-2x text-primary mb-2"></i>
                        <h3 class="mb-0" id="pending_approvals"><?php echo $pending_approvals; ?></h3>
                        <p class="text-muted mb-0">Pending Approvals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-car fa-2x text-success mb-2"></i>
                        <h3 class="mb-0" id="available_vehicles"><?php echo $available_vehicles; ?></h3>
                        <p class="text-muted mb-0">Available Vehicles</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Damage Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h3 class="mb-0" id="today_damage"><?php echo $today_damage; ?></h3>
                        <p class="text-muted mb-0">Today's Damage Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-alert-circle fa-2x text-danger mb-2"></i>
                        <h3 class="mb-0" id="total_damage"><?php echo $total_damage; ?></h3>
                        <p class="text-muted mb-0">Total Damage Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-shield fa-2x text-danger mb-2"></i>
                        <h3 class="mb-0" id="repeat_offenders_count"><?php echo count($repeat_offenders); ?></h3>
                        <p class="text-muted mb-0">Repeat Offenders Flagged</p>
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
                    <div class="card-body" id="repeat_offenders_list">
                        <?php if (count($repeat_offenders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                                <td><span class="badge bg-danger"><?php echo $offender['damage_incidents_count']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                 
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
                    <div class="card-body" id="recent_rentals_list">
                        <?php if (count($recent_rentals) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Vehicle</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <!--gidelete nko-->
                                  
                                
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No recent rentals found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

      <!--giremove nko-->
  <!-- Quick Actions -->
      <!--  <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="rentals.php" class="btn btn-primary me-2 mb-2">
                            <i class="fas fa-calendar-check"></i> Manage Rentals
                        </a>
                        <a href="vehicles.php" class="btn btn-primary me-2 mb-2">
                            <i class="fas fa-car"></i> Manage Vehicles
                        </a>
                        <a href="damage-reports.php" class="btn btn-primary me-2 mb-2">
                            <i class="fas fa-exclamation-triangle"></i> View Damage Reports
                        </a>
                        <a href="customers.php" class="btn btn-primary mb-2">
                            <i class="fas fa-users"></i> Manage Customers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;

$(document).ready(function() {
    // Set up auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            refreshDashboard(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        refreshDashboard(false);
    });
    
    // Toggle auto-refresh
    $('#toggleAutoRefreshBtn').on('click', function() {
        if (autoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            autoRefreshEnabled = false;
            $(this).html('<i class="fas fa-clock"></i> Auto: OFF');
            $(this).removeClass('btn-secondary').addClass('btn-danger');
            Swal.fire({
                icon: 'info',
                title: 'Auto-refresh Disabled',
                text: 'Dashboard will no longer auto-refresh.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            autoRefreshInterval = setInterval(function() {
                if (autoRefreshEnabled) {
                    refreshDashboard(true);
                }
            }, 30000);
            autoRefreshEnabled = true;
            $(this).html('<i class="fas fa-clock"></i> Auto: ON');
            $(this).removeClass('btn-danger').addClass('btn-secondary');
            Swal.fire({
                icon: 'success',
                title: 'Auto-refresh Enabled',
                text: 'Dashboard will auto-refresh every 30 seconds.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
    
    // Function to refresh dashboard data via AJAX
    function refreshDashboard(silent = false) {
        if (!silent) {
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        }
        
        $.ajax({
            url: 'ajax/get_dashboard_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update stats cards
                    $('#today_rentals').text(response.today_rentals);
                    $('#active_rentals').text(response.active_rentals);
                    $('#pending_approvals').text(response.pending_approvals);
                    $('#available_vehicles').text(response.available_vehicles);
                    $('#today_damage').text(response.today_damage);
                    $('#total_damage').text(response.total_damage);
                    $('#repeat_offenders_count').text(response.repeat_offenders_count);
                    
                    // Update repeat offenders list
                    if (response.repeat_offenders.length > 0) {
                        let offendersHtml = `
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Customer</th><th>Incidents</th></tr>
                                    </thead>
                                    <tbody>
                        `;
                        $.each(response.repeat_offenders, function(index, offender) {
                            offendersHtml += `
                                <tr>
                                    <td>${escapeHtml(offender.name)}</td>
                                    <td><span class="badge bg-danger">${offender.incidents}</span></td>
                                </tr>
                            `;
                        });
                        offendersHtml += `</tbody></div>`;
                        $('#repeat_offenders_list').html(offendersHtml);
                    } else {
                        $('#repeat_offenders_list').html('<p class="text-muted text-center py-3">No repeat offenders at this time.</p>');
                    }
                    
                    // Update recent rentals list
                    if (response.recent_rentals.length > 0) {
                        let rentalsHtml = `
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Customer</th><th>Vehicle</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                        `;
                        $.each(response.recent_rentals, function(index, rental) {
                            let badgeClass = rental.status === 'active' ? 'success' : (rental.status === 'pending' ? 'warning' : 'secondary');
                            rentalsHtml += `
                                <tr>
                                    <td>${escapeHtml(rental.customer)}</td>
                                    <td>${escapeHtml(rental.vehicle)}</td>
                                    <td><span class="badge bg-${badgeClass}">${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}</span></td>
                                </tr>
                            `;
                        });
                        rentalsHtml += `</tbody></div>`;
                        $('#recent_rentals_list').html(rentalsHtml);
                    } else {
                        $('#recent_rentals_list').html('<p class="text-muted text-center py-3">No recent rentals found.</p>');
                    }
                    
                    if (!silent) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Dashboard data has been updated.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                } else {
                    if (!silent) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to refresh dashboard data.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            },
            error: function() {
                if (!silent) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not connect to server.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>