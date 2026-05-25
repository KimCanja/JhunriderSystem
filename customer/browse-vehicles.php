<?php
$page_title = 'Browse Vehicles';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

// Get filters
$type_filter = $_GET['type'] ?? '';
$price_filter = $_GET['price'] ?? '';

// Build query
$query = "SELECT * FROM vehicles WHERE status = 'available'";
$params = [];

if ($type_filter) {
    $query .= " AND type = ?";
    $params[] = $type_filter;
}

if ($price_filter) {
    $query .= " AND price_per_day <= ?";
    $params[] = $price_filter;
}

$query .= " ORDER BY model ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Get unique vehicle types
$stmt = $pdo->query("SELECT DISTINCT type FROM vehicles WHERE type IS NOT NULL ORDER BY type");
$vehicle_types = $stmt->fetchAll();
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-car"></i> Browse Vehicles</h1>
            <p class="text-muted">Find and book your perfect rental vehicle</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Vehicle Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($vehicle_types as $vtype): ?>
                                    <option value="<?php echo htmlspecialchars($vtype['type']); ?>" <?php echo $type_filter === $vtype['type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vtype['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Price per Day (₱)</label>
                            <input type="number" name="price" class="form-control" placeholder="Enter max price" value="<?php echo htmlspecialchars($price_filter); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicles Grid -->
    <div class="row">
        <?php if (count($vehicles) > 0): ?>
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <!-- Vehicle Photo -->
                        <?php if (!empty($vehicle['photo_url']) && file_exists('../' . $vehicle['photo_url'])): ?>
                            <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($vehicle['model']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 200px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                                <i class="fas fa-car"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($vehicle['model']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar"></i> Year: <?php echo $vehicle['year']; ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-tag"></i> Type: <?php echo htmlspecialchars($vehicle['type']); ?>
                            </p>
                            <p class="text-muted mb-3">
                                <i class="fas fa-tachometer-alt"></i> <?php echo number_format($vehicle['current_mileage']); ?> miles
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 text-success">₱<?php echo number_format($vehicle['price_per_day'], 2); ?></h4>
                                    <small class="text-muted">per day</small>
                                </div>
                                <a href="book-rental.php?vehicle_id=<?php echo $vehicle['vehicle_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-md-12">
                <div class="card text-center py-5">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <p class="text-muted">No vehicles available matching your criteria.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.card-img-top {
    transition: transform 0.3s ease;
}

.card:hover .card-img-top {
    transform: scale(1.05);
}

.btn-primary {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
}
</style>

<?php require_once '../includes/footer.php'; ?>