<?php
$page_title = 'Manage Schedules';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';
if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$error = '';
$success = '';

// Handle add schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $available_date = $_POST['available_date'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';

    if (empty($vehicle_id) || empty($available_date) || empty($time_slot)) {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO schedules (vehicle_id, available_date, time_slot, is_booked)
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$vehicle_id, $available_date, $time_slot]);
            $success = 'Schedule added successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to add schedule.';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $schedule_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $success = 'Schedule deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to delete schedule.';
    }
}

// Get schedules
$stmt = $pdo->query("
    SELECT s.*, v.model, v.plate_number
    FROM schedules s
    JOIN vehicles v ON s.vehicle_id = v.vehicle_id
    ORDER BY s.available_date DESC, s.time_slot ASC
");
$schedules = $stmt->fetchAll();

// Get vehicles
$stmt = $pdo->query("SELECT vehicle_id, model, plate_number FROM vehicles ORDER BY model ASC");
$vehicles = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-calendar"></i> Manage Schedules</h1>
            <p class="text-muted">Manage vehicle availability and time slots</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="add_schedule" value="1">

                        <div class="mb-3">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_id" class="form-select" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                        <?php echo htmlspecialchars($vehicle['model']); ?> (<?php echo htmlspecialchars($vehicle['plate_number']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Date</label>
                            <input type="date" name="available_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Time Slot</label>
                            <select name="time_slot" class="form-select" required>
                                <option value="">Select Time</option>
                                <option value="08:00-12:00">08:00 AM - 12:00 PM</option>
                                <option value="12:00-16:00">12:00 PM - 04:00 PM</option>
                                <option value="16:00-20:00">04:00 PM - 08:00 PM</option>
                                <option value="All Day">All Day</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Add Schedule
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Scheduled Availability</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['model']); ?> (<?php echo htmlspecialchars($schedule['plate_number']); ?>)</td>
                                        <td><?php echo date('M d, Y', strtotime($schedule['available_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['time_slot']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $schedule['is_booked'] ? 'warning' : 'success'; ?>">
                                                <?php echo $schedule['is_booked'] ? 'Booked' : 'Available'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="schedules.php?delete=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this schedule?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
</div>

<?php require_once '../includes/footer.php'; ?>
