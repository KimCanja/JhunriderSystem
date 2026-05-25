<?php
$page_title = 'Manage Vehicles';

// First, handle all POST/GET redirects BEFORE any output
require_once '../config/database.php';
require_once '../config/constants.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $vehicle_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT photo_url FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$vehicle_id]);
        $vehicle = $stmt->fetch();
        
        if ($vehicle && $vehicle['photo_url']) {
            $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/rentguard/' . $vehicle['photo_url'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$vehicle_id]);
        $success = 'Vehicle deleted successfully!';
        header("Location: vehicles.php");
        exit();
    } catch (PDOException $e) {
        $error = 'Failed to delete vehicle.';
    }
}

// Handle add/edit with photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $model = trim($_POST['model'] ?? '');
    $plate_number = trim($_POST['plate_number'] ?? '');
    $year = $_POST['year'] ?? '';
    $type = trim($_POST['type'] ?? '');
    $passenger_capacity = $_POST['passenger_capacity'] ?? 4;
    $status = $_POST['status'] ?? 'available';
    $current_mileage = $_POST['current_mileage'] ?? 0;
    $price_per_day = $_POST['price_per_day'] ?? 0;
    
    // Handle photo upload
    $photo_url = $_POST['existing_photo'] ?? '';
    if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/vehicles/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['vehicle_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        $db_path = 'uploads/vehicles/' . $filename;
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (in_array($_FILES['vehicle_photo']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['vehicle_photo']['tmp_name'], $upload_path)) {
                if (!empty($photo_url) && file_exists('../' . $photo_url)) {
                    unlink('../' . $photo_url);
                }
                $photo_url = $db_path;
            } else {
                $error = 'Failed to upload photo.';
            }
        } else {
            $error = 'Invalid file type. Only JPG, PNG, and WEBP are allowed.';
        }
    }

    if (empty($model) || empty($plate_number) || empty($year) || empty($type)) {
        $error = 'All fields are required.';
    } else {
        try {
            if ($vehicle_id) {
                // Update existing vehicle
                if ($photo_url) {
                    $stmt = $pdo->prepare("
                        UPDATE vehicles 
                        SET model = ?, plate_number = ?, year = ?, type = ?, passenger_capacity = ?,
                            status = ?, current_mileage = ?, price_per_day = ?, photo_url = ?
                        WHERE vehicle_id = ?
                    ");
                    $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url, $vehicle_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE vehicles 
                        SET model = ?, plate_number = ?, year = ?, type = ?, passenger_capacity = ?,
                            status = ?, current_mileage = ?, price_per_day = ?
                        WHERE vehicle_id = ?
                    ");
                    $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $vehicle_id]);
                }
                $success = 'Vehicle updated successfully!';
                header("Location: vehicles.php");
                exit();
            } else {
                // Insert new vehicle
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles (model, plate_number, year, type, passenger_capacity, status, current_mileage, price_per_day, photo_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url]);
                $success = 'Vehicle added successfully!';
                header("Location: vehicles.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Operation failed. Plate number may already exist.';
        }
    }
}

// Now include header (after all redirects)
require_once '../includes/header.php';
require_once '../includes/admin-sidebar.php';
require_once '../includes/sos-button.php';

// Get vehicles
$stmt = $pdo->query("SELECT * FROM vehicles ORDER BY model ASC");
$vehicles = $stmt->fetchAll();

// Get vehicle to edit (for populating the modal)
$edit_vehicle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_vehicle = $stmt->fetch();
}
?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-car"></i> Manage Vehicles</h1>
            <p class="text-muted">Add, edit, or delete vehicles from your fleet</p>
        </div>
    </div>

    <!-- Add Vehicle Button -->
    <div class="row mb-4">
        <div class="col-12">
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="fas fa-plus"></i> Add Vehicle
            </button>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by model or plate number...">
                    <button class="search-btn" onclick="filterTable()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div class="filter-group">
                    <label>Filter by Status:</label>
                    <select id="statusFilter" class="filter-select" onchange="filterTable()">
                        <option value="all">All Status</option>
                        <option value="available">Available</option>
                        <option value="rented">Rented</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Fleet Inventory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="vehicle-table" id="vehicleTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Model</th>
                            <th>Plate No</th>
                            <th>Type</th>
                            <th>Price/Day</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr data-status="<?php echo $vehicle['status']; ?>">
                                <td>
                                    <?php if ($vehicle['photo_url']): ?>
                                        <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" alt="<?php echo htmlspecialchars($vehicle['model']); ?>" class="vehicle-thumb">
                                    <?php else: ?>
                                        <div class="vehicle-thumb-placeholder">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($vehicle['model']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                                <td class="vehicle-price">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></td>
                                <td>
                                    <?php
                                    $badge_class = '';
                                    switch($vehicle['status']) {
                                        case 'available':
                                            $badge_class = 'badge-active';
                                            break;
                                        case 'rented':
                                            $badge_class = 'badge-approved';
                                            break;
                                        case 'maintenance':
                                            $badge_class = 'badge-cancelled';
                                            break;
                                        default:
                                            $badge_class = 'badge-pending';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($vehicle['status']); ?></span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editVehicleModal" onclick="populateEditForm(<?php echo $vehicle['vehicle_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="vehicles.php?delete=<?php echo $vehicle['vehicle_id']; ?>" class="btn-delete" onclick="return confirm('Delete this vehicle?')">
                                        <i class="fas fa-trash"></i> Delete
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

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" placeholder="e.g., Toyota Fortuner" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plate Number</label>
                            <input type="text" name="plate_number" class="form-control" placeholder="ABC-1234" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" placeholder="2024" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" name="type" class="form-control" placeholder="SUV, Sedan, Truck" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-users"></i> Number of Passengers</label>
                            <select name="passenger_capacity" class="form-select" required>
                                <option value="2">2 passengers (Coupe)</option>
                                <option value="4" selected>4 passengers (Sedan/Compact)</option>
                                <option value="5">5 passengers (SUV/Sedan)</option>
                                <option value="6">6 passengers (MPV/SUV)</option>
                                <option value="7">7 passengers (SUV/Van)</option>
                                <option value="8">8 passengers (Van)</option>
                                <option value="10">10+ passengers (Large Van/Bus)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Day (₱)</label>
                            <input type="number" name="price_per_day" class="form-control" step="0.01" placeholder="3500" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Mileage</label>
                            <input type="number" name="current_mileage" class="form-control" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vehicle Photo</label>
                        <input type="file" name="vehicle_photo" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small class="text-muted">Upload vehicle image (JPG, PNG, WEBP). Max size: 5MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-save">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                    <input type="hidden" name="existing_photo" id="edit_existing_photo">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="edit_model" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plate Number</label>
                            <input type="text" name="plate_number" id="edit_plate_number" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" id="edit_year" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" name="type" id="edit_type" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-users"></i> Number of Passengers</label>
                            <select name="passenger_capacity" id="edit_passenger_capacity" class="form-select" required>
                                <option value="2">2 passengers (Coupe)</option>
                                <option value="4">4 passengers (Sedan/Compact)</option>
                                <option value="5">5 passengers (SUV/Sedan)</option>
                                <option value="6">6 passengers (MPV/SUV)</option>
                                <option value="7">7 passengers (SUV/Van)</option>
                                <option value="8">8 passengers (Van)</option>
                                <option value="10">10+ passengers (Large Van/Bus)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Day (₱)</label>
                            <input type="number" name="price_per_day" id="edit_price_per_day" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Mileage</label>
                            <input type="number" name="current_mileage" id="edit_current_mileage" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vehicle Photo</label>
                        <div id="currentPhotoPreview" class="mb-2">
                            <img id="currentPhotoImg" src="" alt="Current Photo" style="width: 100%; max-height: 150px; object-fit: cover; border-radius: 8px;">
                        </div>
                        <input type="file" name="vehicle_photo" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small class="text-muted">Upload new image to replace current photo</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-save">Update Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .main-content {
        margin-left: 280px;
        padding: 30px;
        background: #F3F4F6;
        min-height: 100vh;
    }

    .btn-add {
        background: #059669;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-add:hover {
        background: #15803D;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        color: white;
    }

    .filter-bar {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }

    .search-box {
        position: relative;
        display: flex;
        gap: 10px;
        flex: 1;
        max-width: 400px;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        pointer-events: none;
    }

    .search-box input {
        flex: 1;
        height: 42px;
        padding: 0 15px 0 40px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        background: white;
    }

    .search-box input:focus {
        border-color: #16A34A;
        outline: none;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }

    .search-btn {
        background: #16A34A;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0 18px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .search-btn:hover {
        background: #15803D;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-group label {
        font-weight: 500;
        color: #1F2937;
        font-size: 14px;
        white-space: nowrap;
    }

    .filter-select {
        height: 42px;
        padding: 0 15px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        min-width: 150px;
    }

    .filter-select:focus {
        border-color: #16A34A;
        outline: none;
    }

    .card {
        background: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .card-header {
        background: white;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 24px;
        font-weight: 600;
    }

    .vehicle-table {
        width: 100%;
        border-collapse: collapse;
    }

    .vehicle-table thead th {
        background: #F9FAFB;
        color: #1F2937;
        font-weight: 600;
        font-size: 13px;
        padding: 15px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }

    .vehicle-table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #E5E7EB;
        color: #1F2937;
        font-size: 14px;
    }

    .vehicle-table tbody tr:hover {
        background: #F9FAFB;
    }

    .vehicle-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }

    .vehicle-thumb-placeholder {
        width: 50px;
        height: 50px;
        background: #1F2937;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vehicle-thumb-placeholder i {
        color: #16A34A;
        font-size: 20px;
    }

    .vehicle-price {
        font-weight: 700;
        color: #16A34A;
    }

    .badge-active {
        background: #3B82F6;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 90px;
        text-align: center;
    }
    .badge-approved {
        background: #10B981;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 90px;
        text-align: center;
    }
    .badge-cancelled {
        background: #EF4444;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 90px;
        text-align: center;
    }
    .badge-pending {
        background: #F59E0B;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 90px;
        text-align: center;
    }

    .action-buttons {
        white-space: nowrap;
    }

    .btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: transparent;
        border: 1px solid #16A34A;
        color: #16A34A;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-right: 8px;
        cursor: pointer;
    }

    .btn-edit:hover {
        background: #DCFCE7;
        color: #15803D;
    }

    .btn-delete {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: transparent;
        border: 1px solid #DC2626;
        color: #DC2626;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-delete:hover {
        background: #FEE2E2;
        color: #991B1B;
    }

    .modal-content {
        border-radius: 16px;
        border: none;
    }

    .modal-header {
        background: white;
        border-bottom: 1px solid #E5E7EB;
        padding: 20px 24px;
    }

    .modal-title {
        font-weight: 600;
        color: #1F2937;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #E5E7EB;
        gap: 10px;
    }

    .form-label {
        font-weight: 500;
        color: #1F2937;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .form-control, .form-select {
        height: 45px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        background: #F9FAFB;
    }

    .form-control:focus, .form-select:focus {
        border-color: #16A34A;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        outline: none;
    }

    .btn-cancel {
        background: transparent;
        border: 1px solid #E5E7EB;
        color: #1F2937;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
    }

    .btn-cancel:hover {
        background: #F3F4F6;
        border-color: #16A34A;
    }

    .btn-save {
        background: #16A34A;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        font-weight: 600;
    }

    .btn-save:hover {
        background: #15803D;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        .filter-bar {
            flex-direction: column;
        }
        .search-box {
            max-width: 100%;
        }
    }
</style>

<script>
    function filterTable() {
        var searchValue = document.getElementById('searchInput').value.toLowerCase();
        var statusValue = document.getElementById('statusFilter').value;
        var rows = document.querySelectorAll('#vehicleTable tbody tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var status = row.getAttribute('data-status');
            var model = row.cells[1] ? row.cells[1].innerText.toLowerCase() : '';
            var plate = row.cells[2] ? row.cells[2].innerText.toLowerCase() : '';
            
            var matchesSearch = model.indexOf(searchValue) > -1 || plate.indexOf(searchValue) > -1;
            var matchesStatus = statusValue === 'all' || status === statusValue;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
    
    // Also allow Enter key to search
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filterTable();
        }
    });
    
    // Run filter on page load to ensure everything is visible
    document.addEventListener('DOMContentLoaded', function() {
        filterTable();
    });

    function populateEditForm(vehicleId) {
        // Fetch vehicle data via AJAX
        fetch(`get_vehicle.php?id=${vehicleId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_vehicle_id').value = data.vehicle_id;
                document.getElementById('edit_model').value = data.model;
                document.getElementById('edit_plate_number').value = data.plate_number;
                document.getElementById('edit_year').value = data.year;
                document.getElementById('edit_type').value = data.type;
                document.getElementById('edit_passenger_capacity').value = data.passenger_capacity;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('edit_price_per_day').value = data.price_per_day;
                document.getElementById('edit_current_mileage').value = data.current_mileage;
                document.getElementById('edit_existing_photo').value = data.photo_url;
                
                if (data.photo_url) {
                    document.getElementById('currentPhotoImg').src = '<?php echo BASE_URL; ?>' + data.photo_url;
                } else {
                    document.getElementById('currentPhotoImg').src = '';
                }
            });
    }
</script>

<?php require_once '../includes/footer.php'; ?>