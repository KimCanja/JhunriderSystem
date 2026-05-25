<?php
$page_title = 'Book Rental';
require_once '../includes/header.php';
require_once '../config/database.php';


if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$vehicle_id = $_GET['vehicle_id'] ?? null;
$error = '';
$success = '';

if (!$vehicle_id) {
    redirect(BASE_URL . 'customer/browse-vehicles.php');
}

// Get vehicle info
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect(BASE_URL . 'customer/browse-vehicles.php');
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_date = $_POST['pickup_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($pickup_date) || empty($return_date) || empty($pickup_time)) {
        $error = 'All fields are required.';
    } else {
        $pickup = new DateTime($pickup_date);
        $return = new DateTime($return_date);
        
        if ($return <= $pickup) {
            $error = 'Return date must be after pickup date.';
        } else {
            $days = $return->diff($pickup)->days;
            $total_price = $vehicle['price_per_day'] * $days;

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rentals (user_id, vehicle_id, pickup_date, return_date, pickup_time, status, notes, total_price)
                    VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $vehicle_id, $pickup_date, $return_date, $pickup_time, $notes, $total_price]);
                
                $success = 'Booking created successfully! Awaiting admin approval.';
                header("refresh:2;url=my-rentals.php");
            } catch (PDOException $e) {
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-calendar-check"></i> Book Rental</h1>
            <p class="text-muted">Complete your vehicle booking</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Booking Details</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Pickup Date</label>
                            <input type="date" name="pickup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pickup Time</label>
                            <input type="time" name="pickup_time" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Return Date</label>
                            <input type="date" name="return_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Any special requests or notes..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Confirm Booking
                        </button>
                        <a href="browse-vehicles.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vehicle Summary</h5>
                </div>
                <div class="card-body">
                    <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 150px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-car"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($vehicle['model']); ?></h5>
                    <p class="text-muted mb-2">
                        <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-calendar"></i> <?php echo $vehicle['year']; ?>
                    </p>
                    <p class="text-muted mb-3">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($vehicle['type']); ?>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong>Price per Day:</strong> <span class="text-success">$<?php echo number_format($vehicle['price_per_day'], 2); ?></span>
                    </p>
                    <p class="mb-0">
                        <strong>Status:</strong> <span class="badge badge-approved">Available</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
