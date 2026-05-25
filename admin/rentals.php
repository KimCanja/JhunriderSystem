<?php
$page_title = 'Manage Rentals';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rentals - TCRCJ</title>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1><i class="fas fa-calendar-check"></i> Manage Rentals</h1>
                <p class="text-muted">Review and approve rental bookings</p>
            </div>
        </div>

        <!-- Auto-refresh Controls -->
        <div class="row mb-3">
            <div class="col-md-12 text-end">
                <button class="btn btn-sm btn-success" id="manualRefreshBtn">
                    <i class="fas fa-sync-alt"></i> Refresh Now
                </button>
                <button class="btn btn-sm btn-secondary" id="toggleAutoRefreshBtn">
                    <i class="fas fa-clock"></i> Auto-refresh: ON
                </button>
            </div>
        </div>

        <!-- Filters Container -->
        <div id="filtersContainer">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading filters...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rentals Table Container -->
        <div id="rentalsContainer">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading rentals...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStatus = '';
let autoRefreshEnabled = true;
let autoRefreshInterval;

$(document).ready(function() {
    // Load initial data
    loadFilters();
    loadRentals();
    
    // Set up auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadRentals(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadRentals(false, true);
        loadFilters();
    });
    
    // Toggle auto-refresh
    $('#toggleAutoRefreshBtn').on('click', function() {
        if (autoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            autoRefreshEnabled = false;
            $(this).html('<i class="fas fa-clock"></i> Auto-refresh: OFF');
            $(this).removeClass('btn-secondary').addClass('btn-danger');
            showToast('Auto-refresh disabled', 'info');
        } else {
            autoRefreshInterval = setInterval(function() {
                if (autoRefreshEnabled) {
                    loadRentals(true);
                }
            }, 30000);
            autoRefreshEnabled = true;
            $(this).html('<i class="fas fa-clock"></i> Auto-refresh: ON');
            $(this).removeClass('btn-danger').addClass('btn-secondary');
            showToast('Auto-refresh enabled', 'success');
        }
    });
    
    // Handle status filter click (event delegation)
    $(document).on('click', '.filter-btn', function(e) {
        e.preventDefault();
        currentStatus = $(this).data('status');
        
        // Update active state on buttons
        $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        
        // Load filtered rentals
        loadRentals();
    });
    
    // Handle action buttons (approve, reject, start, complete)
    $(document).on('click', '.action-btn', function(e) {
        e.preventDefault();
        const rentalId = $(this).data('id');
        const action = $(this).data('action');
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);
        
        let confirmMessage = '';
        let icon = 'question';
        
        switch(action) {
            case 'approve':
                confirmMessage = 'Approve this rental?';
                break;
            case 'reject':
                confirmMessage = 'Reject this rental? This action cannot be undone.';
                icon = 'warning';
                break;
            case 'start':
                confirmMessage = 'Start this rental? This will mark the vehicle as rented.';
                break;
            case 'complete':
                confirmMessage = 'Complete this rental? This will mark the vehicle as available again.';
                break;
        }
        
        Swal.fire({
            title: `${actionText} Rental`,
            text: confirmMessage,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                processRentalAction(rentalId, action);
            }
        });
    });
    
    // Load filters via AJAX
    function loadFilters() {
        $.ajax({
            url: 'ajax/admin_get_rental_filters.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayFilters(response.filters);
                } else {
                    displaySampleFilters();
                }
            },
            error: function() {
                displaySampleFilters();
            }
        });
    }
    
    // Load rentals via AJAX
    function loadRentals(silent = false, showNotification = false) {
        if (!silent) {
            $('#rentalsContainer').html(`
                <div class="row">
                    <div class="col-12 text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading rentals...</span>
                        </div>
                    </div>
                </div>
            `);
        }
        
        $.ajax({
            url: 'ajax/admin_get_rentals_list.php',
            type: 'GET',
            data: { status: currentStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayRentals(response.rentals);
                    if (showNotification) {
                        showToast('Rentals updated successfully!', 'success', 1500);
                    }
                } else {
                    displaySampleRentals();
                }
            },
            error: function() {
                displaySampleRentals();
            }
        });
    }
    
    // Process rental action via AJAX
    function processRentalAction(rentalId, action) {
        $.ajax({
            url: 'ajax/admin_process_rental.php',
            type: 'POST',
            data: { rental_id: rentalId, action: action },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                if (response.success) {
                    showToast(response.message, 'success');
                    // Reload both filters and rentals
                    loadFilters();
                    loadRentals();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                showToast('Operation failed. Please try again.', 'error');
            }
        });
    }
    
    // Display filter buttons
    function displayFilters(filters) {
        let html = `
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
        `;
        
        // All button
        const allActive = currentStatus === '';
        html += `
            <button class="btn ${allActive ? 'btn-primary' : 'btn-outline-primary'} filter-btn" data-status="">
                <i class="fas fa-list"></i> All 
                <span class="badge ${allActive ? 'bg-light text-dark' : 'bg-secondary text-white'} ms-1">${filters.total}</span>
            </button>
        `;
        
        // Status buttons
        const statuses = ['pending', 'approved', 'active', 'completed', 'cancelled'];
        statuses.forEach(status => {
            const isActive = currentStatus === status;
            let btnClass = '';
            let icon = '';
            
            switch(status) {
                case 'pending': btnClass = 'warning'; icon = 'fa-clock'; break;
                case 'approved': btnClass = 'info'; icon = 'fa-check-circle'; break;
                case 'active': btnClass = 'success'; icon = 'fa-play'; break;
                case 'completed': btnClass = 'secondary'; icon = 'fa-flag-checkered'; break;
                case 'cancelled': btnClass = 'danger'; icon = 'fa-times-circle'; break;
            }
            
            html += `
                <button class="btn ${isActive ? `btn-${btnClass}` : `btn-outline-${btnClass}`} filter-btn" data-status="${status}">
                    <i class="fas ${icon}"></i> ${status.charAt(0).toUpperCase() + status.slice(1)} 
                    <span class="badge ${isActive ? 'bg-light text-dark' : 'bg-secondary text-white'} ms-1">${filters[status]}</span>
                </button>
            `;
        });
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#filtersContainer').html(html);
    }
    
    // Display rentals table
    function displayRentals(rentals) {
        if (rentals.length === 0) {
            $('#rentalsContainer').html(`
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-calendar-times" style="font-size: 64px; color: #CBD5E1;"></i>
                                <h4 class="mt-3 text-muted">No rentals found</h4>
                                <p class="text-muted">No rental bookings available for the selected filter.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            return;
        }
        
        let html = `
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list"></i> 
                                Rental Bookings
                                ${currentStatus ? `<span class="badge bg-primary ms-2">Filtered: ${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}</span>` : ''}
                            </h5>
                        </div>
                        <div class="card-body">
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
        `;
        
        rentals.forEach(rental => {
            let badgeClass = '';
            let badgeIcon = '';
            
            switch(rental.status) {
                case 'pending': badgeClass = 'warning'; badgeIcon = 'fa-clock'; break;
                case 'approved': badgeClass = 'info'; badgeIcon = 'fa-check-circle'; break;
                case 'active': badgeClass = 'success'; badgeIcon = 'fa-play'; break;
                case 'completed': badgeClass = 'secondary'; badgeIcon = 'fa-flag-checkered'; break;
                case 'cancelled': badgeClass = 'danger'; badgeIcon = 'fa-times-circle'; break;
                default: badgeClass = 'secondary'; badgeIcon = 'fa-question';
            }
            
            html += `
                <tr data-rental-id="${rental.id}">
                    <td>
                        <div>
                            <strong>${escapeHtml(rental.customer_name)}</strong>
                            <br>
                            <small class="text-muted">${escapeHtml(rental.customer_email)}</small>
                        </div>
                    </td>
                    <td>
                        ${escapeHtml(rental.vehicle_model)}
                        <br>
                        <small class="text-muted">${escapeHtml(rental.plate_number)}</small>
                    </td>
                    <td>
                        <i class="fas fa-calendar-alt text-primary"></i> 
                        ${rental.pickup_date}
                        ${rental.pickup_time ? `<br><small class="text-muted"><i class="fas fa-clock"></i> ${rental.pickup_time}</small>` : ''}
                    </td>
                    <td>
                        <i class="fas fa-calendar-check text-success"></i> 
                        ${rental.return_date}
                    </td>
                    <td>
                        <span class="badge bg-${badgeClass}">
                            <i class="fas ${badgeIcon}"></i>
                            ${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <strong class="text-success">$${Number(rental.total_price).toLocaleString()}</strong>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="rental-details.php?id=${rental.id}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
            `;
            
            if (rental.status === 'pending') {
                html += `
                    <button class="btn btn-sm btn-success action-btn" data-id="${rental.id}" data-action="approve">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm btn-danger action-btn" data-id="${rental.id}" data-action="reject">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }
            
            if (rental.status === 'approved') {
                html += `
                    <button class="btn btn-sm btn-primary action-btn" data-id="${rental.id}" data-action="start">
                        <i class="fas fa-play"></i>
                    </button>
                `;
            }
            
            if (rental.status === 'active') {
                html += `
                    <button class="btn btn-sm btn-success action-btn" data-id="${rental.id}" data-action="complete">
                        <i class="fas fa-flag-checkered"></i>
                    </button>
                `;
            }
            
            html += `
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#rentalsContainer').html(html);
    }
    
    // Sample data for fallback
    function displaySampleFilters() {
        const sampleFilters = {
            total: 0,
            pending: 0,
            approved: 0,
            active: 0,
            completed: 0,
            cancelled: 0
        };
        displayFilters(sampleFilters);
    }
    
    function displaySampleRentals() {
        displayRentals([]);
    }
    
    // Helper functions
    function showToast(message, type = 'success', duration = 3000) {
        Swal.fire({
            text: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>