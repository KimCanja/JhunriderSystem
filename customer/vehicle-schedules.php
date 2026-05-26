<?php
$page_title = 'Vehicle Schedules';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$vehicle_id = $_GET['vehicle_id'] ?? 0;

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

// Get available schedules for this vehicle
$stmt = $pdo->prepare("
    SELECT s.*
    FROM schedules s
    WHERE s.vehicle_id = ? 
    AND s.is_booked = 0 
    AND s.available_date >= CURDATE()
    ORDER BY s.available_date ASC,
    FIELD(s.time_slot, '08:00-12:00', '12:00-16:00', '16:00-20:00', 'All Day')
");
$stmt->execute([$vehicle_id]);
$schedules = $stmt->fetchAll();

// Group schedules by date
$schedules_by_date = [];
foreach ($schedules as $schedule) {
    $date = $schedule['available_date'];
    if (!isset($schedules_by_date[$date])) {
        $schedules_by_date[$date] = [];
    }
    $schedules_by_date[$date][] = $schedule;
}
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<style>
.schedule-card {
    transition: all 0.3s ease;
    margin-bottom: 15px;
    border: 2px solid #e0e0e0;
}
.schedule-card:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #10B981;
}
.date-header {
    background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%);
    color: white;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
}
.time-slot {
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}
.time-slot:last-child {
    border-bottom: none;
}
.btn-book {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    transition: all 0.3s ease;
}
.btn-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
}
.vehicle-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
}
.vehicle-detail-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}
.vehicle-price {
    font-size: 28px;
    font-weight: bold;
    color: #10B981;
}
.time-icon {
    font-size: 24px;
    margin-right: 15px;
}
.slot-available {
    background: #d4edda;
    color: #155724;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.no-slots {
    text-align: center;
    padding: 50px;
    background: #f8f9fa;
    border-radius: 10px;
}
</style>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-calendar-alt text-success"></i> Available Schedules</h1>
            <p class="text-muted">Select a time slot to book <?php echo htmlspecialchars($vehicle['model']); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="browse-vehicles.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Vehicles
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Vehicle Details Column -->
        <div class="col-md-4">
            <div class="vehicle-detail-card">
                <?php if ($vehicle['photo_url']): ?>
                    <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" class="vehicle-image" alt="<?php echo htmlspecialchars($vehicle['model']); ?>">
                <?php else: ?>
                    <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 200px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; border-radius: 10px;">
                        <i class="fas fa-car"></i>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <h3><?php echo htmlspecialchars($vehicle['model']); ?></h3>
                    <p class="text-muted">
                        <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-calendar"></i> Year: <?php echo $vehicle['year']; ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-tag"></i> Type: <?php echo htmlspecialchars($vehicle['type']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-users"></i> Capacity: <?php echo $vehicle['passenger_capacity']; ?> passengers
                    </p>
                    <hr>
                    <div class="text-center">
                        <div class="vehicle-price">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></div>
                        <small class="text-muted">per day</small>
                    </div>
                </div>
            </div>
            
            <!-- Booking Instructions -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> How to Book</h6>
                </div>
                <div class="card-body">
                    <ol class="mb-0 small">
                        <li class="mb-2">Select your preferred date and time slot</li>
                        <li class="mb-2">Click "Book This Slot"</li>
                        <li class="mb-2">Review your booking details</li>
                        <li class="mb-2">Confirm your booking</li>
                        <li>Wait for admin approval</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Available Schedules Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Available Time Slots</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <div class="no-slots">
                            <i class="fas fa-calendar-times" style="font-size: 64px; color: #ccc;"></i>
                            <h4 class="mt-3 text-muted">No Available Slots</h4>
                            <p class="text-muted">This vehicle has no available time slots at the moment.</p>
                            <p class="text-muted">Please check back later or choose another vehicle.</p>
                            <a href="browse-vehicles.php" class="btn btn-primary mt-2">
                                <i class="fas fa-car"></i> Browse Other Vehicles
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($schedules_by_date as $date => $day_schedules): ?>
                            <div class="date-header">
                                <i class="fas fa-calendar-day"></i> 
                                <strong><?php echo date('l, F d, Y', strtotime($date)); ?></strong>
                            </div>
                            
                            <?php foreach ($day_schedules as $schedule): ?>
                                <div class="time-slot">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <div class="d-flex align-items-center">
                                                <div class="time-icon">
                                                    <?php 
                                                    switch($schedule['time_slot']) {
                                                        case '08:00-12:00':
                                                            echo '🌅';
                                                            break;
                                                        case '12:00-16:00':
                                                            echo '☀️';
                                                            break;
                                                        case '16:00-20:00':
                                                            echo '🌙';
                                                            break;
                                                        case 'All Day':
                                                            echo '📅';
                                                            break;
                                                        default:
                                                            echo '🕐';
                                                    }
                                                    ?>
                                                </div>
                                                <div>
                                                    <strong class="time-slot-text">
                                                        <?php 
                                                        switch($schedule['time_slot']) {
                                                            case '08:00-12:00':
                                                                echo 'Morning (8:00 AM - 12:00 PM)';
                                                                break;
                                                            case '12:00-16:00':
                                                                echo 'Afternoon (12:00 PM - 4:00 PM)';
                                                                break;
                                                            case '16:00-20:00':
                                                                echo 'Evening (4:00 PM - 8:00 PM)';
                                                                break;
                                                            case 'All Day':
                                                                echo 'Full Day (24 hours)';
                                                                break;
                                                            default:
                                                                echo htmlspecialchars($schedule['time_slot']);
                                                        }
                                                        ?>
                                                    </strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> 
                                                        <?php 
                                                        if ($schedule['time_slot'] == 'All Day') {
                                                            echo 'Pickup any time, return same time next day';
                                                        } else {
                                                            echo 'Pickup during slot, return next day same time';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="slot-available">
                                                <i class="fas fa-check-circle"></i> Available
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong class="text-success">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></strong>
                                            <br>
                                            <small>/day</small>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <a href="book-rental.php?vehicle_id=<?php echo $vehicle_id; ?>&schedule_id=<?php echo $schedule['schedule_id']; ?>" 
                                               class="btn btn-book btn-sm">
                                                <i class="fas fa-calendar-check"></i> Book Slot
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> 
                            <small>Click "Book Slot" to proceed with your rental booking. You'll need to confirm your details before finalizing.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Optional: Add filter functionality
$(document).ready(function() {
    // You can add date filtering here if needed
    console.log('Vehicle schedules page loaded');
});
</script>

<?php require_once '../includes/footer.php'; ?>