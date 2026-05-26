<?php
$page_title = 'Manage Vehicles';
require_once '../config/database.php';
require_once '../config/constants.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $vehicle_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$vehicle_id]);
        header("Location: vehicles.php");
        exit();
    } catch (PDOException $e) {
        $error = 'Failed to delete vehicle.';
    }
}

// Handle add/edit
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
    $photo_url = '';
    if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/vehicles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['vehicle_photo']['name'], PATHINFO_EXTENSION);
        $upload_path = $upload_dir . $filename;
        $photo_url = 'uploads/vehicles/' . $filename;
        move_uploaded_file($_FILES['vehicle_photo']['tmp_name'], $upload_path);
    }

    if ($vehicle_id) {
        // Update
        if ($photo_url) {
            $stmt = $pdo->prepare("UPDATE vehicles SET model=?, plate_number=?, year=?, type=?, passenger_capacity=?, status=?, current_mileage=?, price_per_day=?, photo_url=? WHERE vehicle_id=?");
            $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url, $vehicle_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE vehicles SET model=?, plate_number=?, year=?, type=?, passenger_capacity=?, status=?, current_mileage=?, price_per_day=? WHERE vehicle_id=?");
            $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $vehicle_id]);
        }
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO vehicles (model, plate_number, year, type, passenger_capacity, status, current_mileage, price_per_day, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$model, $plate_number, $year, $type, $passenger_capacity, $status, $current_mileage, $price_per_day, $photo_url]);
    }
    header("Location: vehicles.php");
    exit();
}

// Get vehicle data for editing
$edit_vehicle = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
    $stmt->execute([$edit_id]);
    $edit_vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
require_once '../includes/admin-sidebar.php';
require_once '../includes/sos-button.php';

// Get vehicles
$stmt = $pdo->query("SELECT * FROM vehicles ORDER BY model ASC");
$vehicles = $stmt->fetchAll();
?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-car"></i> Manage Vehicles</h1>
            <p class="text-muted">Add, edit, or delete vehicles from your fleet</p>
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

    <div class="row mb-4">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal" onclick="resetForm()">
                <i class="fas fa-plus"></i> Add Vehicle
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Fleet Inventory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
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
                    <tbody id="vehiclesTableBody">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr data-id="<?php echo $vehicle['vehicle_id']; ?>">
                                <td>
                                    <?php if ($vehicle['photo_url']): ?>
                                        <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                                <td>₱<?php echo number_format($vehicle['price_per_day'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $vehicle['status'] == 'available' ? 'success' : ($vehicle['status'] == 'rented' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($vehicle['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editVehicle(<?php echo $vehicle['vehicle_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $vehicle['vehicle_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this vehicle?')">
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

<!-- Add/Edit Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleModalTitle">Add New Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="vehicleForm">
                <input type="hidden" name="vehicle_id" id="vehicle_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Model</label>
                            <input type="text" name="model" id="model" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Plate Number</label>
                            <input type="text" name="plate_number" id="plate_number" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Year</label>
                            <input type="number" name="year" id="year" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <input type="text" name="type" id="type" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Passenger Capacity</label>
                            <input type="number" name="passenger_capacity" id="passenger_capacity" class="form-control" value="4">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Price per Day (₱)</label>
                            <input type="number" name="price_per_day" id="price_per_day" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Current Mileage (km)</label>
                            <input type="number" name="current_mileage" id="current_mileage" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Vehicle Photo</label>
                        <input type="file" name="vehicle_photo" id="vehicle_photo" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current photo when editing</small>
                    </div>
                    <div id="currentPhoto" style="display: none;">
                        <label>Current Photo:</label>
                        <img id="currentPhotoImg" src="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;

$(document).ready(function() {
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            refreshVehicles();
        }
    }, 30000);
    
    // Manual refresh
    $('#manualRefreshBtn').on('click', function() {
        refreshVehicles(true);
    });
    
    // Toggle auto-refresh
    $('#toggleAutoRefreshBtn').on('click', function() {
        if (autoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            autoRefreshEnabled = false;
            $(this).html('<i class="fas fa-clock"></i> Auto: OFF');
            $(this).removeClass('btn-secondary').addClass('btn-danger');
        } else {
            autoRefreshInterval = setInterval(function() {
                if (autoRefreshEnabled) {
                    refreshVehicles();
                }
            }, 30000);
            autoRefreshEnabled = true;
            $(this).html('<i class="fas fa-clock"></i> Auto: ON');
            $(this).removeClass('btn-danger').addClass('btn-secondary');
        }
    });
    
    // Refresh vehicles function
    function refreshVehicles(showNotification = false) {
        $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: 'ajax/get_vehicles.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateTable(response.vehicles);
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
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
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                if (showNotification) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not connect to server.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000
                    });
                }
            },
            complete: function() {
                $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
            }
        });
    }
    
    // Update table
    function updateTable(vehicles) {
        let html = '';
        vehicles.forEach(v => {
            let badgeClass = v.status === 'available' ? 'success' : (v.status === 'rented' ? 'warning' : 'danger');
            let imageHtml = v.photo_url 
                ? `<img src="<?php echo BASE_URL; ?>${v.photo_url}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">`
                : `<div style="width: 50px; height: 50px; background: #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-car"></i></div>`;
            
            html += `
                <tr>
                    <td>${imageHtml}</td>
                    <td><strong>${escapeHtml(v.model)}</strong></td>
                    <td>${escapeHtml(v.plate_number)}</td>
                    <td>${escapeHtml(v.type)}</td>
                    <td>₱${Number(v.price_per_day).toLocaleString()}</td>
                    <td><span class="badge bg-${badgeClass}">${v.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editVehicle(${v.vehicle_id})"><i class="fas fa-edit"></i></button>
                        <a href="?delete=${v.vehicle_id}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this vehicle?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            `;
        });
        $('#vehiclesTableBody').html(html);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
});

// Edit vehicle function - FIXED
function editVehicle(id) {
    // Fetch vehicle data via AJAX
    $.ajax({
        url: 'ajax/get_vehicle.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const vehicle = response.vehicle;
                
                // Populate modal fields
                $('#vehicle_id').val(vehicle.vehicle_id);
                $('#model').val(vehicle.model);
                $('#plate_number').val(vehicle.plate_number);
                $('#year').val(vehicle.year);
                $('#type').val(vehicle.type);
                $('#passenger_capacity').val(vehicle.passenger_capacity);
                $('#status').val(vehicle.status);
                $('#price_per_day').val(vehicle.price_per_day);
                $('#current_mileage').val(vehicle.current_mileage);
                
                // Show current photo if exists
                if (vehicle.photo_url) {
                    $('#currentPhotoImg').attr('src', '<?php echo BASE_URL; ?>' + vehicle.photo_url);
                    $('#currentPhoto').show();
                } else {
                    $('#currentPhoto').hide();
                }
                
                // Change modal title
                $('#vehicleModalTitle').text('Edit Vehicle');
                
                // Show modal
                $('#addVehicleModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not load vehicle data'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not connect to server'
            });
        }
    });
}

// Reset form function
function resetForm() {
    $('#vehicleForm')[0].reset();
    $('#vehicle_id').val('');
    $('#vehicleModalTitle').text('Add New Vehicle');
    $('#currentPhoto').hide();
    $('#vehicle_photo').val('');
}
</script>

<?php require_once '../includes/footer.php'; ?>