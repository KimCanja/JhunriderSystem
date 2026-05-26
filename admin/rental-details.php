<?php
$page_title = 'Rental Details';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../config/constants.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$rental_id = $_GET['id'] ?? 0;

// Get rental details - REMOVED u.phone
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.name as customer_name,
        u.email as customer_email,
        v.model as vehicle_model,
        v.plate_number,
        v.type as vehicle_type,
        v.price_per_day
    FROM rentals r
    JOIN users u ON r.user_id = u.id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.rental_id = ?
");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rental) {
    redirect('rentals.php');
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-calendar-check"></i> Rental Details</h1>
                <p class="text-muted">View complete rental information</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="rentals.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Rentals
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Rental Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Rental Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Rental ID:</th>
                                <td>#<?php echo $rental['rental_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $rental['status'] == 'pending' ? 'warning' : 
                                            ($rental['status'] == 'approved' ? 'info' : 
                                            ($rental['status'] == 'active' ? 'success' : 
                                            ($rental['status'] == 'completed' ? 'secondary' : 'danger'))); 
                                    ?>">
                                        <?php echo ucfirst($rental['status']); ?>
                                    </span>
                                 </td>
                            </tr>
                            <tr>
                                <th>Booking Date:</th>
                                <td><?php echo date('F d, Y h:i A', strtotime($rental['created_at'])); ?></td>
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
                                <th width="40%">Name:</th>
                                <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($rental['customer_email']); ?></td>
                            </tr>
                            <!-- Removed Phone Row -->
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
                                <th width="40%">Model:</th>
                                <td><?php echo htmlspecialchars($rental['vehicle_model']); ?></td>
                            </tr>
                            <tr>
                                <th>Plate Number:</th>
                                <td><?php echo htmlspecialchars($rental['plate_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td><?php echo htmlspecialchars($rental['vehicle_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Price/Day:</th>
                                <td>₱<?php echo number_format($rental['price_per_day'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rental Schedule -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-calendar"></i> Rental Schedule</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Pickup Date:</th>
                                <td><?php echo date('F d, Y', strtotime($rental['pickup_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Pickup Time:</th>
                                <td><?php echo htmlspecialchars($rental['pickup_time'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td><?php echo date('F d, Y', strtotime($rental['return_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Total Days:</th>
                                <td>
                                    <?php 
                                    $days = (strtotime($rental['return_date']) - strtotime($rental['pickup_date'])) / (60 * 60 * 24);
                                    echo ceil($days) . ' days';
                                    ?>
                                 </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="20%">Total Price:</th>
                                <td><strong class="text-success">₱<?php echo number_format($rental['total_price'], 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Actions</h5>
                        <div class="btn-group" role="group">
                            <?php if ($rental['status'] == 'pending'): ?>
                                <button class="btn btn-success action-btn" data-id="<?php echo $rental['rental_id']; ?>" data-action="approve">
                                    <i class="fas fa-check"></i> Approve Rental
                                </button>
                                <button class="btn btn-danger action-btn" data-id="<?php echo $rental['rental_id']; ?>" data-action="reject">
                                    <i class="fas fa-times"></i> Reject Rental
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($rental['status'] == 'approved'): ?>
                                <button class="btn btn-primary action-btn" data-id="<?php echo $rental['rental_id']; ?>" data-action="start">
                                    <i class="fas fa-play"></i> Start Rental
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($rental['status'] == 'active'): ?>
                                <button class="btn btn-success action-btn" data-id="<?php echo $rental['rental_id']; ?>" data-action="complete">
                                    <i class="fas fa-flag-checkered"></i> Complete Rental
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Handle action buttons
    $('.action-btn').on('click', function(e) {
        e.preventDefault();
        const rentalId = $(this).data('id');
        const action = $(this).data('action');
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);
        
        Swal.fire({
            title: `${actionText} Rental`,
            text: `Are you sure you want to ${action} this rental?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            if (result.isConfirmed) {
                processAction(rentalId, action);
            }
        });
    });
    
    function processAction(rentalId, action) {
        $.ajax({
            url: 'ajax/admin_process_rental.php',
            type: 'POST',
            data: { rental_id: rentalId, action: action },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function(response) {
                Swal.close();
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
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
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Operation failed'
                });
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>