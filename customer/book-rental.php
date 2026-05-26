<?php
$page_title = 'Book Rental';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$vehicle_id = $_GET['vehicle_id'] ?? null;
$schedule_id = $_GET['schedule_id'] ?? null;
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

// If schedule_id is provided, get that specific schedule
if ($schedule_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, v.model, v.plate_number, v.price_per_day, v.year, v.type
        FROM schedules s
        JOIN vehicles v ON s.vehicle_id = v.vehicle_id
        WHERE s.schedule_id = ? AND s.is_booked = 0 AND s.available_date >= CURDATE()
    ");
    $stmt->execute([$schedule_id]);
    $selected_schedule = $stmt->fetch();
    
    if (!$selected_schedule) {
        $error = 'This schedule is no longer available.';
    }
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $pickup_date = $_POST['pickup_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (!$schedule_id) {
        $error = 'Please select a valid schedule.';
    } elseif (empty($pickup_date) || empty($pickup_time)) {
        $error = 'All fields are required.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Lock and verify schedule is still available
            $stmt = $pdo->prepare("
                SELECT s.*, v.price_per_day 
                FROM schedules s
                JOIN vehicles v ON s.vehicle_id = v.vehicle_id
                WHERE s.schedule_id = ? AND s.is_booked = 0 AND s.available_date >= CURDATE()
                FOR UPDATE
            ");
            $stmt->execute([$schedule_id]);
            $schedule = $stmt->fetch();
            
            if (!$schedule) {
                throw new Exception('This time slot is no longer available. Please choose another slot.');
            }
            
            // Calculate return date based on time slot
            $return_date = $schedule['available_date'];
            if ($schedule['time_slot'] == 'All Day') {
                $return_date = $schedule['available_date'];
            } else {
                $return_date = date('Y-m-d', strtotime($schedule['available_date'] . ' +1 day'));
            }
            
            $total_price = $vehicle['price_per_day'];
            
            // Create rental record with schedule_id
            $stmt = $pdo->prepare("
                INSERT INTO rentals (
                    user_id, vehicle_id, schedule_id, pickup_date, pickup_time, 
                    return_date, status, notes, total_price
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $vehicle_id, 
                $schedule_id,
                $pickup_date, 
                $pickup_time, 
                $return_date, 
                $notes, 
                $total_price
            ]);
            
            // Mark schedule as booked
            $stmt = $pdo->prepare("UPDATE schedules SET is_booked = 1 WHERE schedule_id = ?");
            $stmt->execute([$schedule_id]);
            
            $pdo->commit();
            
            $success = 'Booking created successfully! Awaiting admin approval.';
            header("refresh:2;url=my-rentals.php");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Booking failed. Please try again.';
        }
    }
}

// Get available schedules for this vehicle
$stmt = $pdo->prepare("
    SELECT s.*, v.model, v.plate_number, v.price_per_day, v.year, v.type
    FROM schedules s
    JOIN vehicles v ON s.vehicle_id = v.vehicle_id
    WHERE s.vehicle_id = ? 
    AND s.is_booked = 0 
    AND s.available_date >= CURDATE()
    ORDER BY s.available_date ASC, 
    FIELD(s.time_slot, '08:00-12:00', '12:00-16:00', '16:00-20:00', 'All Day')
");
$stmt->execute([$vehicle_id]);
$available_schedules = $stmt->fetchAll();
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<style>
.schedule-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e0e0e0;
    margin-bottom: 15px;
}
.schedule-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.schedule-card.selected {
    border-color: #28a745;
    background: #f0fff4;
    box-shadow: 0 5px 15px rgba(40,167,69,0.2);
}
.schedule-card .time-badge {
    font-size: 18px;
    font-weight: bold;
}
.schedule-card .date-badge {
    background: #007bff;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
}
.slot-available {
    color: #28a745;
}
.time-slot {
    font-size: 16px;
    font-weight: 600;
}
.vehicle-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
}
</style>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-calendar-check"></i> Book Rental</h1>
            <p class="text-muted">Select an available time slot for your rental</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Vehicle Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-car"></i> Selected Vehicle</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($vehicle['model']); ?></h4>
                            <p class="text-muted mb-2">
                                <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar"></i> Year: <?php echo $vehicle['year']; ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-tag"></i> Type: <?php echo htmlspecialchars($vehicle['type']); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-users"></i> Capacity: <?php echo $vehicle['passenger_capacity']; ?> passengers
                            </p>
                            <p class="mb-0">
                                <strong>Price per Day:</strong> <span class="text-success">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <?php if ($vehicle['photo_url']): ?>
                                <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" class="vehicle-image" alt="<?php echo htmlspecialchars($vehicle['model']); ?>">
                            <?php else: ?>
                                <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 120px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; border-radius: 12px;">
                                    <i class="fas fa-car"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Schedules Section -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Available Time Slots</h5>
                </div>
                <div class="card-body">
                    <?php if ($error && !$success): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($available_schedules)): ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fas fa-calendar-times" style="font-size: 48px;"></i>
                            <h4 class="mt-3">No Available Schedules</h4>
                            <p>This vehicle has no available time slots at the moment. Please check back later or choose another vehicle.</p>
                            <a href="browse-vehicles.php" class="btn btn-primary mt-2">
                                <i class="fas fa-arrow-left"></i> Browse Other Vehicles
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="bookingForm">
                            <input type="hidden" name="schedule_id" id="selectedScheduleId" value="<?php echo $schedule_id ?? ''; ?>">
                            
                            <div class="row">
                                <?php foreach ($available_schedules as $schedule): ?>
                                    <div class="col-md-12">
                                        <div class="schedule-card card <?php echo ($schedule_id == $schedule['schedule_id']) ? 'selected' : ''; ?>" 
                                             onclick="selectSchedule(<?php echo $schedule['schedule_id']; ?>, '<?php echo $schedule['available_date']; ?>', '<?php echo $schedule['time_slot']; ?>')">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3">
                                                        <div class="date-badge">
                                                            <i class="fas fa-calendar"></i> 
                                                            <?php echo date('F d, Y', strtotime($schedule['available_date'])); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="time-slot">
                                                            <i class="fas fa-clock text-primary"></i> 
                                                            <?php 
                                                            $time_display = $schedule['time_slot'];
                                                            switch($schedule['time_slot']) {
                                                                case '08:00-12:00':
                                                                    $time_display = '🌅 Morning (8:00 AM - 12:00 PM)';
                                                                    break;
                                                                case '12:00-16:00':
                                                                    $time_display = '☀️ Afternoon (12:00 PM - 4:00 PM)';
                                                                    break;
                                                                case '16:00-20:00':
                                                                    $time_display = '🌙 Evening (4:00 PM - 8:00 PM)';
                                                                    break;
                                                                case 'All Day':
                                                                    $time_display = '📅 Full Day (24 hours)';
                                                                    break;
                                                                default:
                                                                    $time_display = $schedule['time_slot'];
                                                            }
                                                            echo $time_display;
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <span class="badge bg-success slot-available">
                                                            <i class="fas fa-check-circle"></i> Available
                                                        </span>
                                                    </div>
                                                    <div class="col-md-2 text-end">
                                                        <span class="text-success fw-bold">
                                                            ₱<?php echo number_format($vehicle['price_per_day'], 2); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div id="bookingDetails" style="display: <?php echo $schedule_id ? 'block' : 'none'; ?>;">
                                <hr class="mt-4">
                                <h5><i class="fas fa-info-circle"></i> Complete Your Booking</h5>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pickup Date</label>
                                        <input type="text" id="display_pickup_date" class="form-control" readonly>
                                        <input type="hidden" name="pickup_date" id="pickup_date">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pickup Time</label>
                                        <input type="text" id="display_pickup_time" class="form-control" readonly>
                                        <input type="hidden" name="pickup_time" id="pickup_time">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Special Notes (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                                </div>

                                <div class="mb-3 p-3 bg-light rounded">
                                    <label class="form-label">Payment Summary</label>
                                    <div class="d-flex justify-content-between">
                                        <span>Price per day:</span>
                                        <span class="fw-bold">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>Total Price:</span>
                                        <h4 class="text-success mb-0">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></h4>
                                    </div>
                                    <small class="text-muted">Price is per day based on selected time slot</small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check-circle"></i> Confirm Booking
                                    </button>
                                    <a href="browse-vehicles.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Booking Instructions -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> How to Book</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Click on an available time slot above</li>
                        <li class="mb-2">Review the booking details</li>
                        <li class="mb-2">Add any special notes if needed</li>
                        <li class="mb-2">Click "Confirm Booking" to complete</li>
                        <li>Wait for admin approval</li>
                    </ol>
                </div>
            </div>

            <!-- Time Slot Information -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Time Slot Guide</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>🌅 Morning (8AM - 12PM)</strong>
                        <p class="text-muted small mb-0">Pickup: 8AM, Return next day 8AM</p>
                    </div>
                    <div class="mb-3">
                        <strong>☀️ Afternoon (12PM - 4PM)</strong>
                        <p class="text-muted small mb-0">Pickup: 12PM, Return next day 12PM</p>
                    </div>
                    <div class="mb-3">
                        <strong>🌙 Evening (4PM - 8PM)</strong>
                        <p class="text-muted small mb-0">Pickup: 4PM, Return next day 4PM</p>
                    </div>
                    <div>
                        <strong>📅 Full Day (24 hours)</strong>
                        <p class="text-muted small mb-0">Any time, return same time next day</p>
                    </div>
                </div>
            </div>

            <!-- Need Help Card -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-headset"></i> Need Help?</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-phone-alt" style="font-size: 32px; color: #dc3545;"></i>
                    <p class="mt-2 mb-0">Contact our support team:</p>
                    <p class="fw-bold">(123) 456-7890</p>
                    <small class="text-muted">support@tcrci.com</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectSchedule(scheduleId, date, timeSlot) {
    // Update hidden fields
    document.getElementById('selectedScheduleId').value = scheduleId;
    document.getElementById('pickup_date').value = date;
    document.getElementById('pickup_time').value = timeSlot;
    
    // Format display values
    const formattedDate = new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    let displayTime = '';
    switch(timeSlot) {
        case '08:00-12:00':
            displayTime = '8:00 AM - 12:00 PM (Morning)';
            break;
        case '12:00-16:00':
            displayTime = '12:00 PM - 4:00 PM (Afternoon)';
            break;
        case '16:00-20:00':
            displayTime = '4:00 PM - 8:00 PM (Evening)';
            break;
        case 'All Day':
            displayTime = 'Full Day (24 hours)';
            break;
        default:
            displayTime = timeSlot;
    }
    
    document.getElementById('display_pickup_date').value = formattedDate;
    document.getElementById('display_pickup_time').value = displayTime;
    
    // Remove selected class from all cards
    document.querySelectorAll('.schedule-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('selected');
    }
    
    // Show booking details form
    const bookingDetails = document.getElementById('bookingDetails');
    if (bookingDetails) {
        bookingDetails.style.display = 'block';
        // Scroll to booking details
        bookingDetails.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Initialize if schedule is already selected on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($schedule_id && $selected_schedule): ?>
        // Trigger select for pre-selected schedule
        const pickupDate = '<?php echo $selected_schedule['available_date']; ?>';
        const timeSlot = '<?php echo $selected_schedule['time_slot']; ?>';
        selectSchedule(<?php echo $schedule_id; ?>, pickupDate, timeSlot);
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>