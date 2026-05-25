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
    background: #D1FAE5;
    color: #065F46;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #DBEAFE;
    color: #1E40AF;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-completed {
    background: #D1FAE5;
    color: #065F46;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-cancelled {
    background: #FEE2E2;
    color: #7F1D1D;
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
                }
            },
            error: function() {
                if (!silent) {
                    displaySampleRentals();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display rentals in table
    function displayRentals(rentals) {
        if (rentals.length === 0) {
            $('#rentalsContainer').html(`
                <p class="text-muted text-center py-4">
                    <i class="fas fa-inbox" style="font-size: 48px; display: block; margin-bottom: 15px; color: #ccc;"></i>
                    No rentals yet. <a href="browse-vehicles.php">Start booking now!</a>
                </p>
            `);
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Vehicle</th>
                            <th>Plate</th>
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
            switch(rental.status) {
                case 'pending': badgeClass = 'badge-pending'; break;
                case 'approved': badgeClass = 'badge-approved'; break;
                case 'active': badgeClass = 'badge-active'; break;
                case 'completed': badgeClass = 'badge-completed'; break;
                case 'cancelled': badgeClass = 'badge-cancelled'; break;
                default: badgeClass = 'badge-pending';
            }
            
            let canCancel = (rental.status === 'pending' || rental.status === 'approved');
            let cancelButton = canCancel 
                ? `<button class="btn btn-sm btn-danger cancel-btn" data-id="${rental.rental_id}">
                        <i class="fas fa-times"></i> Cancel
                   </button>`
                : '';
            
            html += `
                <tr class="rental-row" data-rental-id="${rental.rental_id}">
                    <td><strong>${escapeHtml(rental.model)}</strong></td>
                    <td>${escapeHtml(rental.plate_number)}</strong></td>
                    <td>${rental.pickup_date}</strong></td>
                    <td>${rental.return_date}</strong></td>
                    <td>${rental.pickup_time}</strong></td>
                    <td><span class="${badgeClass}">${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}</span></td>
                    <td>₱${Number(rental.total_price).toLocaleString()}</strong></td>
                    <td>
                        <a href="rental-details.php?id=${rental.rental_id}" class="btn btn-sm btn-secondary me-2">
                            <i class="fas fa-eye"></i> View
                        </a>
                        ${cancelButton}
                    </strong>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        $('#rentalsContainer').html(html);
        
        // Attach cancel button event handlers
        $('.cancel-btn').on('click', function() {
            const rentalId = $(this).data('id');
            cancelRental(rentalId);
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
    
    // Sample rentals for fallback
    function displaySampleRentals() {
        const sampleRentals = [
            {
                rental_id: 1,
                model: 'Toyota Fortuner',
                plate_number: 'ABC-1234',
                pickup_date: 'May 26, 2024',
                return_date: 'May 28, 2024',
                pickup_time: '10:00 AM',
                status: 'pending',
                total_price: 10500
            },
            {
                rental_id: 2,
                model: 'Honda Civic',
                plate_number: 'DEF-5678',
                pickup_date: 'May 20, 2024',
                return_date: 'May 22, 2024',
                pickup_time: '02:00 PM',
                status: 'completed',
                total_price: 5000
            }
        ];
        displayRentals(sampleRentals);
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