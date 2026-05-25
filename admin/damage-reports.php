<?php
$page_title = 'Damage Reports';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';
if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$error = '';
$success = '';

// Handle new damage report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $rental_id = $_POST['rental_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $severity = $_POST['severity'] ?? 'low';
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if (empty($rental_id) || empty($description)) {
        $error = 'Rental and description are required.';
    } else {
        // Get rental info
        $stmt = $pdo->prepare("SELECT vehicle_id, user_id FROM rentals WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $rental = $stmt->fetch();

        if ($rental) {
            // Get customer id
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$rental['user_id']]);
            $customer = $stmt->fetch();

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO damage_reports (rental_id, vehicle_id, customer_id, description, severity, admin_notes, report_date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$rental_id, $rental['vehicle_id'], $customer['customer_id'], $description, $severity, $admin_notes]);

                // Update customer damage count
                $stmt = $pdo->prepare("
                    UPDATE customers SET damage_incidents_count = damage_incidents_count + 1 
                    WHERE customer_id = ?
                ");
                $stmt->execute([$customer['customer_id']]);

                $success = 'Damage report created successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to create report.';
            }
        } else {
            $error = 'Rental not found.';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $report_id = $_GET['delete'];
    try {
        // Get report to find customer
        $stmt = $pdo->prepare("SELECT customer_id FROM damage_reports WHERE report_id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();

        // Delete report
        $stmt = $pdo->prepare("DELETE FROM damage_reports WHERE report_id = ?");
        $stmt->execute([$report_id]);

        // Decrease customer damage count
        $stmt = $pdo->prepare("
            UPDATE customers SET damage_incidents_count = GREATEST(0, damage_incidents_count - 1)
            WHERE customer_id = ?
        ");
        $stmt->execute([$report['customer_id']]);

        $success = 'Damage report deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to delete report.';
    }
}

// Get all damage reports
$stmt = $pdo->query("
    SELECT dr.*, r.rental_id, v.model, v.plate_number, u.name
    FROM damage_reports dr
    JOIN rentals r ON dr.rental_id = r.rental_id
    JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
    JOIN users u ON dr.customer_id = (SELECT user_id FROM customers WHERE customer_id = dr.customer_id)
    ORDER BY dr.report_date DESC
");
$damage_reports = $stmt->fetchAll();

// Get rentals for dropdown
$stmt = $pdo->query("
    SELECT r.rental_id, CONCAT(v.model, ' - ', u.name) as label
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.id
    WHERE r.status IN ('active', 'completed')
    ORDER BY r.rental_id DESC
    LIMIT 20
");
$rentals = $stmt->fetchAll();
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-exclamation-triangle"></i> Damage Reports</h1>
            <p class="text-muted">Track and manage vehicle damage incidents</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create Report</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="add_report" value="1">

                        <div class="mb-3">
                            <label class="form-label">Rental</label>
                            <select name="rental_id" class="form-select" required>
                                <option value="">Select Rental</option>
                                <?php foreach ($rentals as $rental): ?>
                                    <option value="<?php echo $rental['rental_id']; ?>">
                                        #<?php echo $rental['rental_id']; ?> - <?php echo htmlspecialchars($rental['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the damage..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Create Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Damage Reports</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Description</th>
                                    <th>Severity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($damage_reports as $report): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($report['name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['model']); ?> (<?php echo htmlspecialchars($report['plate_number']); ?>)</td>
                                        <td><?php echo htmlspecialchars(substr($report['description'], 0, 50)); ?>...</td>
                                        <td>
                                            <span class="badge badge-<?php echo $report['severity']; ?>">
                                                <?php echo ucfirst($report['severity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="damage-details.php?id=<?php echo $report['report_id']; ?>" class="btn btn-sm btn-secondary me-1">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="damage-reports.php?delete=<?php echo $report['report_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this report?')">
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
