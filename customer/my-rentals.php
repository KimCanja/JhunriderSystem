<?php
$page_title = 'My Rentals';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user_id = $_SESSION['user_id'];
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-history"></i> My Rentals</h1>
            <p class="text-muted">View and manage your rental bookings</p>
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

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div id="rentalsContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading rentals...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading your rentals...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-pending {
    background: #FEF3C7;
    color: #92400E;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-approved {
    background: #DBEAFE;
    color: #1E40AF;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #D1FAE5;
    color: #065F46;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-completed {
    background: #E5E7EB;
    color: #374151;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-cancelled {
    background: #FEE2E2;
    color: #991F1B;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.table tbody tr {
    transition: background-color 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 5px 12px;
    font-size: 12px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.rental-row {
    animation: fadeIn 0.3s ease-out;
}

.return-checklist {
    text-align: left;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 10px 0;
}
.return-checklist ul {
    margin-bottom: 0;
    padding-left: 20px;
}
.return-checklist li {
    margin: 5px 0;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;

$(document).ready(function() {
    // Load rentals on page load
    loadRentals();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadRentals(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadRentals(false, true);
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
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            autoRefreshInterval = setInterval(function() {
                if (autoRefreshEnabled) {
                    loadRentals(true);
                }
            }, 30000);
            autoRefreshEnabled = true;
            $(this).html('<i class="fas fa-clock"></i> Auto: ON');
            $(this).removeClass('btn-danger').addClass('btn-secondary');
            Swal.fire({
                icon: 'success',
                title: 'Auto-refresh Enabled',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
    
    // Load rentals function
    function loadRentals(silent = false, showNotification = false) {
        if (!silent) {
            $('#rentalsContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading rentals...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading your rentals...</p>
                </div>
            `);
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_my_rentals.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayRentals(response.rentals);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: `Found ${response.rentals.length} rentals.`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                } else {
                    console.error('Error:', response.message);
                    displayEmptyState();
                }
            },
            error: function() {
                if (!silent) {
                    displayEmptyState();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display empty state
    function displayEmptyState() {
        $('#rentalsContainer').html(`
            <div class="text-center py-5">
                <i class="fas fa-calendar-times" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                <h5 class="text-muted">No Rentals Found</h5>
                <p class="text-muted">You haven't made any rental bookings yet.</p>
                <a href="browse-vehicles.php" class="btn btn-primary mt-2">
                    <i class="fas fa-car"></i> Browse Vehicles
                </a>
            </div>
        `);
    }
    
    // Display rentals in table
    function displayRentals(rentals) {
        if (rentals.length === 0) {
            displayEmptyState();
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Vehicle</th>
                            <th>Plate Number</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Pickup Time</th>
                            <th>Status</th>
                            <th>Total Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        rentals.forEach(rental => {
            let badgeClass = '';
            let statusIcon = '';
            switch(rental.status) {
                case 'pending':
                    badgeClass = 'badge-pending';
                    statusIcon = 'fa-clock';
                    break;
                case 'approved':
                    badgeClass = 'badge-approved';
                    statusIcon = 'fa-check-circle';
                    break;
                case 'active':
                    badgeClass = 'badge-active';
                    statusIcon = 'fa-play-circle';
                    break;
                case 'completed':
                    badgeClass = 'badge-completed';
                    statusIcon = 'fa-flag-checkered';
                    break;
                case 'cancelled':
                    badgeClass = 'badge-cancelled';
                    statusIcon = 'fa-times-circle';
                    break;
                default:
                    badgeClass = 'badge-pending';
                    statusIcon = 'fa-clock';
            }
            
            let canCancel = (rental.status === 'pending' || rental.status === 'approved');
            let cancelButton = canCancel 
                ? `<button class="btn btn-sm btn-danger cancel-btn me-1" data-id="${rental.rental_id}">
                        <i class="fas fa-times"></i> Cancel
                   </button>`
                : '';
            
            let returnButton = (rental.status === 'active')
                ? `<button class="btn btn-sm btn-success return-btn" data-id="${rental.rental_id}">
                        <i class="fas fa-undo-alt"></i> Return
                   </button>`
                : '';
            
            let viewButton = `<a href="rental-details.php?id=${rental.rental_id}" class="btn btn-sm btn-secondary me-1">
                                  <i class="fas fa-eye"></i> View
                              </a>`;
            
            html += `
                <tr class="rental-row" data-rental-id="${rental.rental_id}">
                    <td><strong>${escapeHtml(rental.model)}</strong></td>
                    <td>${escapeHtml(rental.plate_number)}</td>
                    <td><i class="fas fa-calendar-alt text-primary me-1"></i>${rental.pickup_date}</td>
                    <td><i class="fas fa-calendar-check text-success me-1"></i>${rental.return_date}</td>
                    <td><i class="fas fa-clock text-info me-1"></i>${rental.pickup_time || 'N/A'}</td>
                    <td><span class="${badgeClass}"><i class="fas ${statusIcon} me-1"></i>${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}</span></td>
                    <td><strong class="text-success">₱${Number(rental.total_price).toLocaleString()}</strong></td>
                    <td>
                        ${viewButton}
                        ${cancelButton}
                        ${returnButton}
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
                <div class="mt-3 alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Important Notes:</strong>
                    <ul class="mb-0 mt-2">
                        <li>You can only cancel <strong>Pending</strong> or <strong>Approved</strong> bookings</li>
                        <li>Active rentals can be <strong>Returned</strong> - a return confirmation will be sent to admin</li>
                        <li>Completed rentals are final and cannot be modified</li>
                    </ul>
                </div>
            </div>
        `;
        
        $('#rentalsContainer').html(html);
        
        // Attach cancel button event handlers
        $('.cancel-btn').on('click', function() {
            const rentalId = $(this).data('id');
            cancelRental(rentalId);
        });
        
        // Attach return button event handlers
        $('.return-btn').on('click', function() {
            const rentalId = $(this).data('id');
            returnRental(rentalId);
        });
    }
    
    // Cancel rental function
    function cancelRental(rentalId) {
        Swal.fire({
            title: 'Cancel Rental?',
            text: 'Are you sure you want to cancel this rental? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/cancel_rental.php',
                    type: 'POST',
                    data: { rental_id: rentalId },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            loadRentals();
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to cancel rental'
                        });
                    }
                });
            }
        });
    }
    
    // Return rental function
    function returnRental(rentalId) {
        Swal.fire({
            title: 'Return Vehicle?',
            text: 'Please confirm you are returning the vehicle.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, return vehicle',
            cancelButtonText: 'Not yet',
            html: `
                <div class="return-checklist">
                    <p><strong>Before returning, please ensure:</strong></p>
                    <ul>
                        <li>✓ Vehicle is clean inside and out</li>
                        <li>✓ No new damages to the vehicle</li>
                        <li>✓ Fuel tank is filled (as per agreement)</li>
                        <li>✓ All personal belongings are removed</li>
                        <li>✓ Keys and documents are ready</li>
                    </ul>
                </div>
            `
        }).then((result) => {
            if (result.isConfirmed) {
                // Ask for confirmation again
                Swal.fire({
                    title: 'Final Confirmation',
                    text: 'Once returned, this action cannot be undone. Have you inspected the vehicle?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, return now',
                    cancelButtonText: 'No, check again'
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        processReturn(rentalId);
                    }
                });
            }
        });
    }
    
    // Process return via AJAX
    function processReturn(rentalId) {
        $.ajax({
            url: 'ajax/return_rental.php',
            type: 'POST',
            data: { rental_id: rentalId },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing Return...',
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
                    loadRentals();
                    Swal.fire({
                        icon: 'success',
                        title: 'Returned Successfully!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Return Failed',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process return. Please try again.'
                });
            }
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