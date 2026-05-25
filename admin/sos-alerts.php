<?php
$page_title = 'SOS Alerts';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

// Get SOS alerts
$stmt = $pdo->query("
    SELECT s.*, u.name as user_name, u.email as user_email,
           v.model, v.plate_number
    FROM sos_alerts s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN vehicles v ON s.vehicle_id = v.vehicle_id
    ORDER BY s.created_at DESC
");
$alerts = $stmt->fetchAll();

// Update alert status
if (isset($_POST['update_status'])) {
    $sos_id = $_POST['sos_id'];
    $status = $_POST['status'];
    $admin_response = $_POST['admin_response'];
    
    $stmt = $pdo->prepare("
        UPDATE sos_alerts 
        SET status = ?, responded_at = NOW(), responded_by = ?, admin_response = ?
        WHERE sos_id = ?
    ");
    $stmt->execute([$status, $_SESSION['user_id'], $admin_response, $sos_id]);
    $success = "Alert status updated!";
    
    // Refresh page
    header("Location: sos-alerts.php");
    exit;
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-exclamation-triangle text-danger"></i> SOS Emergency Alerts</h1>
            <p class="text-muted">Respond to customer emergency alerts</p>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Active Alerts</h5>
                </div>
                <div class="card-body">
                    <?php if (count($alerts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Alert Type</th>
                                        <th>Vehicle</th>
                                        <th>Message</th>
                                        <th>Location</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alerts as $alert): ?>
                                        <tr>
                                            <td>#<?php echo $alert['sos_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($alert['user_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($alert['user_email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $alert['alert_type'] == 'emergency' ? 'danger' : 
                                                        ($alert['alert_type'] == 'accident' ? 'warning' : 
                                                        ($alert['alert_type'] == 'mechanical' ? 'info' : 'primary')); 
                                                ?>">
                                                    <?php echo ucfirst($alert['alert_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($alert['model'] ?? 'N/A'); ?>
                                                <br>
                                                <small><?php echo htmlspecialchars($alert['plate_number'] ?? ''); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($alert['message'], 0, 50)); ?>...</td>
                                            <td>
                                                <?php if ($alert['location_lat'] && $alert['location_lng']): ?>
                                                    <a href="https://maps.google.com/?q=<?php echo $alert['location_lat']; ?>,<?php echo $alert['location_lng']; ?>" target="_blank">
                                                        View Map
                                                    </a>
                                                <?php else: ?>
                                                    No location
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($alert['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $alert['status'] == 'pending' ? 'danger' : 
                                                        ($alert['status'] == 'responded' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo ucfirst($alert['status']); ?>
                                                </span>
                                             </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="respondToAlert(<?php echo $alert['sos_id']; ?>, '<?php echo htmlspecialchars($alert['user_name']); ?>')">
                                                    Respond
                                                </button>
                                             </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No SOS alerts found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Respond to SOS Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sos_id" id="sos_id">
                    <div class="mb-3">
                        <label>Customer: <strong id="customer_name"></strong></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="responded">Responded - In Progress</option>
                            <option value="resolved">Resolved - Issue Fixed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Response Notes</label>
                        <textarea name="admin_response" class="form-control" rows="3" required placeholder="Describe the action taken..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function respondToAlert(id, name) {
    document.getElementById('sos_id').value = id;
    document.getElementById('customer_name').innerHTML = name;
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}
</script>
<script>

setInterval(function() {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.querySelector('.table-responsive');
            const currentTable = document.querySelector('.table-responsive');
            if (newTable && currentTable) {
                currentTable.innerHTML = newTable.innerHTML;
            }
        });
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>