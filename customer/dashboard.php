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
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-chart-line"></i> Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
            <p class="text-muted">Manage your vehicle rentals and bookings</p>
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

    <!-- Stats Cards Container -->
    <div id="statsContainer">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading stats...</span>
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
                    <form id="suggestionForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Budget (₱ per day)</label>
                            <input type="number" name="budget" id="budget" class="form-control" placeholder="Enter max budget" step="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Number of Passengers</label>
                            <select name="passengers" id="passengers" class="form-select" required>
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
                            <select name="purpose" id="purpose" class="form-select" required>
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
                            <button type="submit" class="btn btn-primary w-100" id="suggestionBtn">
                                <i class="fas fa-magic"></i> Get Suggestions
                            </button>
                        </div>
                    </form>
                    
                    <div id="suggestionMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggested Vehicles Container -->
    <div id="suggestedVehiclesContainer"></div>

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

    <!-- Recent Rentals Container -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Rentals</h5>
                </div>
                <div class="card-body">
                    <div id="recentRentalsContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading rentals...</span>
                            </div>
                        </div>
                    </div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;

$(document).ready(function() {
    // Load data on page load
    loadDashboardData();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadDashboardData(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadDashboardData(false, true);
    });
    
    // Toggle auto-refresh
    $('#toggleAutoRefreshBtn').on('click', function() {
        if (autoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            autoRefreshEnabled = false;
            $(this).html('<i class="fas fa-clock"></i> Auto: OFF');
            $(this).removeClass('btn-secondary').addClass('btn-danger');
            Swal.fire({
                icon: 'info',
                title: 'Auto-refresh Disabled',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            autoRefreshInterval = setInterval(function() {
                if (autoRefreshEnabled) {
                    loadDashboardData(true);
                }
            }, 30000);
            autoRefreshEnabled = true;
            $(this).html('<i class="fas fa-clock"></i> Auto: ON');
            $(this).removeClass('btn-danger').addClass('btn-secondary');
            Swal.fire({
                icon: 'success',
                title: 'Auto-refresh Enabled',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
    
    // Suggestion Form Submit
    $('#suggestionForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = {
            budget: $('#budget').val(),
            passengers: $('#passengers').val(),
            purpose: $('#purpose').val()
        };
        
        $('#suggestionBtn').html('<i class="fas fa-spinner fa-spin"></i> Searching...');
        $('#suggestionBtn').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/get_vehicle_suggestions.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displaySuggestionMessage(response.message);
                    displaySuggestedVehicles(response.vehicles);
                } else {
                    $('#suggestionMessage').html(`
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> ${response.message}
                        </div>
                    `);
                    $('#suggestedVehiclesContainer').html('');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to get suggestions'
                });
            },
            complete: function() {
                $('#suggestionBtn').html('<i class="fas fa-magic"></i> Get Suggestions');
                $('#suggestionBtn').prop('disabled', false);
            }
        });
    });
    
    // Load dashboard data
    function loadDashboardData(silent = false, showNotification = false) {
        if (!silent) {
            $('#statsContainer').html(`
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading stats...</span>
                        </div>
                    </div>
                </div>
            `);
            $('#recentRentalsContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading rentals...</span>
                    </div>
                </div>
            `);
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_customer_dashboard.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayStats(response.stats);
                    displayRecentRentals(response.recent_rentals);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Dashboard updated.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                }
            },
            error: function() {
                if (!silent) {
                    displaySampleStats();
                    displaySampleRentals();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display stats
    function displayStats(stats) {
        let html = '<div class="row mb-4">';
        stats.forEach(stat => {
            html += `
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon ${stat.iconClass}">
                            <i class="${stat.icon}"></i>
                        </div>
                        <div class="stat-content">
                            <h3>${stat.value}</h3>
                            <p>${stat.label}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#statsContainer').html(html);
    }
    
    // Display recent rentals
    function displayRecentRentals(rentals) {
        if (rentals.length === 0) {
            $('#recentRentalsContainer').html(`
                <p class="text-muted text-center py-4">
                    <i class="fas fa-inbox"></i> No rentals yet. <a href="browse-vehicles.php">Start booking now!</a>
                </p>
            `);
            return;
        }
        
        let html = `
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
        `;
        
        rentals.forEach(rental => {
            let badgeClass = '';
            switch(rental.status) {
                case 'pending': badgeClass = 'badge-pending'; break;
                case 'approved': badgeClass = 'badge-approved'; break;
                case 'active': badgeClass = 'badge-active'; break;
                case 'completed': badgeClass = 'badge-completed'; break;
                case 'cancelled': badgeClass = 'badge-cancelled'; break;
                default: badgeClass = 'badge-pending';
            }
            
            html += `
                <tr>
                    <td>${escapeHtml(rental.model)}</strong></td>
                    <td><strong>${escapeHtml(rental.plate_number)}</strong></td>
                    <td>${rental.pickup_date}</strong></td>
                    <td>${rental.return_date}</strong></td>
                    <td><span class="badge ${badgeClass}">${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}</span></td>
                    <td>₱${Number(rental.total_price).toLocaleString()}</strong></td>
                    <td>
                        <a href="rental-details.php?id=${rental.rental_id}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </strong>
                </tr>
            `;
        });
        
        html += `</tbody></table></div>`;
        $('#recentRentalsContainer').html(html);
    }
    
    // Display suggestion message
    function displaySuggestionMessage(message) {
        $('#suggestionMessage').html(`
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> ${escapeHtml(message)}
            </div>
        `);
    }
    
    // Display suggested vehicles
    function displaySuggestedVehicles(vehicles) {
        if (vehicles.length === 0) {
            $('#suggestedVehiclesContainer').html('');
            return;
        }
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-star"></i> Recommended For You</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
        `;
        
        vehicles.forEach(vehicle => {
            let passengerCapacity = vehicle.passenger_capacity || 4;
            let imageHtml = vehicle.photo_url 
                ? `<img src="<?php echo BASE_URL; ?>${vehicle.photo_url}" class="card-img-top" alt="${escapeHtml(vehicle.model)}" style="height: 150px; object-fit: cover;">`
                : `<div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 150px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                    <i class="fas fa-car"></i>
                   </div>`;
            
            html += `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 suggestion-card">
                        ${imageHtml}
                        <div class="card-body">
                            <h6 class="card-title">${escapeHtml(vehicle.model)}</h6>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-tag"></i> ${escapeHtml(vehicle.type)}
                            </p>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-users"></i> Up to ${passengerCapacity} passengers
                            </p>
                            <p class="text-success mb-0">
                                <strong>₱${Number(vehicle.price_per_day).toLocaleString()}</strong>/day
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="book-rental.php?vehicle_id=${vehicle.vehicle_id}" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#suggestedVehiclesContainer').html(html);
    }
    
    // Sample data for fallback
    function displaySampleStats() {
        const sampleStats = [
            { icon: 'fas fa-car', iconClass: 'accent', value: '0', label: 'Total Rentals' },
            { icon: 'fas fa-hourglass-half', iconClass: 'primary', value: '0', label: 'Pending Approvals' },
            { icon: 'fas fa-play-circle', iconClass: 'warning', value: '0', label: 'Active Rentals' },
            { icon: 'fas fa-exclamation-triangle', iconClass: 'danger', value: '0', label: 'Damage Reports' }
        ];
        displayStats(sampleStats);
    }
    
    function displaySampleRentals() {
        displayRecentRentals([]);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
<!-- SOS Button - Place at the very end -->
<?php require_once '../includes/sos-button.php'; ?>