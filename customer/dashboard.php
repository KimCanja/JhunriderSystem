<?php
session_start(); // MUST be first
$page_title = 'Dashboard';
require_once '../config/database.php'; // This must come BEFORE header.php

require_once '../config/constants.php'; 
if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Get customer info
$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

// Get recent rentals
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number 
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_rentals = $stmt->fetchAll();

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rentals WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_rentals = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM rentals WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$active_rentals = $stmt->fetch()['active'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM rentals WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_rentals = $stmt->fetch()['pending'];

// Handle car suggestions
$suggested_vehicles = [];
$suggestion_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_suggestions'])) {
    $budget = floatval($_POST['budget'] ?? 0);
    $passengers = intval($_POST['passengers'] ?? 1);
    $purpose = $_POST['purpose'] ?? 'travel';
    
    // Map purpose to vehicle types
    $purpose_mapping = [
        'travel' => ['SUV', 'Sedan', 'Hatchback'],
        'event' => ['Luxury', 'SUV', 'Van'],
        'business' => ['Luxury', 'Sedan'],
        'family' => ['SUV', 'Van', 'MPV'],
        'adventure' => ['SUV', 'Pickup', '4x4'],
        'economy' => ['Hatchback', 'Sedan', 'Compact']
    ];
    
    $allowed_types = $purpose_mapping[$purpose] ?? ['Sedan', 'SUV'];
    
    // Query for suggestions based on budget and purpose
    $placeholders = implode(',', array_fill(0, count($allowed_types), '?'));
    $query = "
        SELECT * FROM vehicles 
        WHERE status = 'available' 
        AND price_per_day <= ?
        AND type IN ($placeholders)
        ORDER BY price_per_day ASC
        LIMIT 6
    ";
    
    $params = array_merge([$budget], $allowed_types);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suggested_vehicles = $stmt->fetchAll();
    
    if (count($suggested_vehicles) > 0) {
        $suggestion_message = "Based on your budget of ₱" . number_format($budget, 2) . 
                              ", {$passengers} passenger(s), and {$purpose} purpose, we found " . 
                              count($suggested_vehicles) . " vehicle(s) for you!";
    } else {
        $suggestion_message = "No vehicles found matching your criteria. Try increasing your budget or changing the purpose.";
    }
}
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-chart-line"></i> Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
            <p class="text-muted">Manage your vehicle rentals and bookings</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon accent">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_rentals; ?></h3>
                    <p>Total Rentals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_rentals; ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $active_rentals; ?></h3>
                    <p>Active Rentals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $customer['damage_incidents_count'] ?? 0; ?></h3>
                    <p>Damage Reports</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Car Suggestion Feature -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white;">
                    <h5 class="mb-0"><i class="fas fa-robot"></i> Car Suggestion</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Budget (₱ per day)</label>
                            <input type="number" name="budget" class="form-control" placeholder="Enter max budget" step="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Number of Passengers</label>
                            <select name="passengers" class="form-select" required>
                                <option value="1">1 passenger</option>
                                <option value="2">2 passengers</option>
                                <option value="3">3 passengers</option>
                                <option value="4">4 passengers</option>
                                <option value="5">5 passengers</option>
                                <option value="6">6 passengers</option>
                                <option value="7">7 passengers</option>
                                <option value="8">8+ passengers</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purpose</label>
                            <select name="purpose" class="form-select" required>
                                <option value="travel">✈️ Travel / Vacation</option>
                                <option value="business">💼 Business</option>
                                <option value="family">👨‍👩‍👧‍👦 Family Trip</option>
                                <option value="event">🎉 Special Event (Wedding, Party)</option>
                                <option value="adventure">🏔️ Adventure / Off-road</option>
                                <option value="economy">💰 Economy / Budget</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="get_suggestions" class="btn btn-primary w-100">
                                <i class="fas fa-magic"></i> Get Suggestions
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($suggestion_message): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> <?php echo $suggestion_message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggested Vehicles -->
    <?php if (count($suggested_vehicles) > 0): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Recommended For You</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($suggested_vehicles as $vehicle): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 suggestion-card">
                                    <?php if (!empty($vehicle['photo_url']) && file_exists('../' . $vehicle['photo_url'])): ?>
                                        <img src="<?php echo BASE_URL . $vehicle['photo_url']; ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($vehicle['model']); ?>"
                                             style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 150px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($vehicle['model']); ?></h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($vehicle['type']); ?>
                                        </p>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-users"></i> Up to <?php echo $vehicle['passenger_capacity'] ?? 4; ?> passengers
                                        </p>
                                        <p class="text-success mb-0">
                                            <strong>₱<?php echo number_format($vehicle['price_per_day'], 2); ?></strong>/day
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <a href="book-rental.php?vehicle_id=<?php echo $vehicle['vehicle_id']; ?>" class="btn btn-sm btn-primary w-100">
                                            <i class="fas fa-calendar-check"></i> Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="browse-vehicles.php" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Browse All Vehicles
                    </a>
                    <a href="my-rentals.php" class="btn btn-secondary me-2">
                        <i class="fas fa-history"></i> View My Rentals
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Rentals -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Rentals</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_rentals) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Plate</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_rentals as $rental): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rental['model']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($rental['plate_number']); ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($rental['return_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $rental['status']; ?>">
                                                    <?php echo ucfirst($rental['status']); ?>
                                                </span>
                                            </td>
                                            <td>₱<?php echo number_format($rental['total_price'], 2); ?></td>
                                            <td>
                                                <a href="rental-details.php?id=<?php echo $rental['rental_id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-inbox"></i> No rentals yet. <a href="browse-vehicles.php">Start booking now!</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    transform: translateY(-4px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-icon.primary {
    background: rgba(10, 37, 64, 0.1);
    color: #0A2540;
}

.stat-icon.accent {
    background: rgba(16, 185, 129, 0.1);
    color: #10B981;
}

.stat-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #F59E0B;
}

.stat-icon.danger {
    background: rgba(239, 68, 68, 0.1);
    color: #EF4444;
}

.stat-content h3 {
    font-size: 28px;
    margin-bottom: 5px;
    color: #0A2540;
}

.stat-content p {
    color: #64748B;
    font-size: 14px;
    margin: 0;
}

.suggestion-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.suggestion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.badge-pending {
    background: #FEF3C7;
    color: #92400E;
    padding: 5px 10px;
    border-radius: 6px;
}

.badge-approved {
    background: #D1FAE5;
    color: #065F46;
    padding: 5px 10px;
    border-radius: 6px;
}

.badge-active {
    background: #DBEAFE;
    color: #1E40AF;
    padding: 5px 10px;
    border-radius: 6px;
}

.badge-completed {
    background: #D1FAE5;
    color: #065F46;
    padding: 5px 10px;
    border-radius: 6px;
}

.badge-cancelled {
    background: #FEE2E2;
    color: #7F1D1D;
    padding: 5px 10px;
    border-radius: 6px;
}
</style>

<?php require_once '../includes/footer.php'; ?>
<!-- SOS Button - Place at the very end -->
<?php require_once '../includes/sos-button.php'; ?>