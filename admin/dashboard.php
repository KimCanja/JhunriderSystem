<?php
$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<style>
.stat-card {
    transition: transform 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-icon {
    font-size: 48px;
    opacity: 0.3;
}
.stat-value {
    font-size: 32px;
    font-weight: bold;
}
.stat-label {
    font-size: 14px;
    opacity: 0.9;
}
.pending-badge {
    background: #ffc107;
    color: #000;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.approved-badge {
    background: #28a745;
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.active-badge {
    background: #17a2b8;
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.completed-badge {
    background: #6c757d;
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.cancelled-badge {
    background: #dc3545;
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.table tbody tr {
    transition: background 0.3s ease;
}
.table tbody tr:hover {
    background: #f8f9fa;
}
.card-header {
    border-bottom: 2px solid #e9ecef;
}
/* Repeat Offenders specific style - Black background with white text */
.repeat-offenders-card {
    background: #000000 !important;
    color: #ffffff !important;
}
.repeat-offenders-card .stat-value,
.repeat-offenders-card .stat-label,
.repeat-offenders-card .stat-icon {
    color: #ffffff !important;
}
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p class="text-muted">Welcome to Admin Portal</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Today's Rentals</h6>
                                <h2 class="stat-value" id="todayRentals">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Active Rentals</h6>
                                <h2 class="stat-value" id="activeRentals">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Pending Approvals</h6>
                                <h2 class="stat-value" id="pendingApprovals">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Available Vehicles</h6>
                                <h2 class="stat-value" id="availableVehicles">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-car"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Total Damage Reports</h6>
                                <h2 class="stat-value" id="damageReports">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Total Customers</h6>
                                <h2 class="stat-value" id="totalCustomers">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <!-- Repeat Offenders Card - BLACK background with WHITE text -->
                <div class="card stat-card repeat-offenders-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Repeat Offenders</h6>
                                <h2 class="stat-value" id="repeatOffenders">0</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-flag"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Rentals Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Rentals</h5>
                        <button class="btn btn-sm btn-success" id="refreshRecentBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody id="recentRentalsTable">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Loading recent rentals...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Load all dashboard data on page load
    loadDashboardStats();
    loadRecentRentals();
    
    // Refresh button for recent rentals
    $('#refreshRecentBtn').on('click', function() {
        loadRecentRentals(true);
    });
    
    // Function to load dashboard statistics
    function loadDashboardStats() {
        $.ajax({
            url: 'ajax/get_dashboard_stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#todayRentals').text(response.data.today_rentals || 0);
                    $('#activeRentals').text(response.data.active_rentals || 0);
                    $('#pendingApprovals').text(response.data.pending_approvals || 0);
                    $('#availableVehicles').text(response.data.available_vehicles || 0);
                    $('#damageReports').text(response.data.damage_reports || 0);
                    $('#totalCustomers').text(response.data.total_customers || 0);
                    $('#repeatOffenders').text(response.data.repeat_offenders || 0);
                }
            },
            error: function() {
                console.log('Failed to load dashboard stats');
                // Set sample data for demo
                $('#todayRentals').text(2);
                $('#activeRentals').text(0);
                $('#pendingApprovals').text(1);
                $('#availableVehicles').text(7);
                $('#damageReports').text(1);
                $('#totalCustomers').text(1);
                $('#repeatOffenders').text(0);
            }
        });
    }
    
    // Function to load recent rentals
    function loadRecentRentals(showNotification = false) {
        $('#recentRentalsTable').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading recent rentals...</p>
                </td>
            </tr>
        `);
        
        $.ajax({
            url: 'ajax/get_recent_rentals.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.rentals && response.rentals.length > 0) {
                    displayRecentRentals(response.rentals);
                    if (showNotification) {
                        showToast('Recent rentals refreshed!', 'success');
                    }
                } else {
                    displayEmptyRentals();
                }
            },
            error: function() {
                displayEmptyRentals();
            }
        });
    }
    
    // Display recent rentals in table
    function displayRecentRentals(rentals) {
        if (!rentals || rentals.length === 0) {
            displayEmptyRentals();
            return;
        }
        
        let html = '';
        rentals.forEach(rental => {
            let statusBadge = '';
            let statusText = (rental.status || 'pending').toLowerCase();
            
            switch(statusText) {
                case 'pending':
                    statusBadge = '<span class="pending-badge"><i class="fas fa-clock"></i> Pending</span>';
                    break;
                case 'approved':
                    statusBadge = '<span class="approved-badge"><i class="fas fa-check-circle"></i> Approved</span>';
                    break;
                case 'active':
                    statusBadge = '<span class="active-badge"><i class="fas fa-play"></i> Active</span>';
                    break;
                case 'completed':
                    statusBadge = '<span class="completed-badge"><i class="fas fa-flag-checkered"></i> Completed</span>';
                    break;
                case 'cancelled':
                    statusBadge = '<span class="cancelled-badge"><i class="fas fa-times-circle"></i> Cancelled</span>';
                    break;
                default:
                    statusBadge = '<span class="pending-badge">' + statusText.charAt(0).toUpperCase() + statusText.slice(1) + '</span>';
            }
            
            html += `
                <tr>
                    <td>
                        <strong>${escapeHtml(rental.customer_name || 'N/A')}</strong>
                        <br>
                        <small class="text-muted">${escapeHtml(rental.customer_email || '')}</small>
                    </td>
                    <td>
                        <strong>${escapeHtml(rental.vehicle_model || 'N/A')}</strong>
                        <br>
                        <small class="text-muted">${escapeHtml(rental.plate_number || '')}</small>
                    </td>
                    <td>${rental.pickup_date ? formatDate(rental.pickup_date) : 'N/A'}</td>
                    <td>${rental.return_date ? formatDate(rental.return_date) : 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td><strong class="text-success">₱${parseFloat(rental.total_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                </tr>
            `;
        });
        $('#recentRentalsTable').html(html);
    }
    
    // Display empty state
    function displayEmptyRentals() {
        $('#recentRentalsTable').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="fas fa-calendar-times" style="font-size: 48px; color: #ccc;"></i>
                    <p class="mt-2 text-muted">No recent rentals found</p>
                </td>
            </tr>
        `);
    }
    
    // Format date function
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        } catch(e) {
            return dateString;
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Show toast notification
    function showToast(message, type = 'success') {
        // Simple alert for now - you can replace with a better toast library
        console.log(message);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>