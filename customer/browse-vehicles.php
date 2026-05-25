<?php
$page_title = 'Browse Vehicles';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-car"></i> Browse Vehicles</h1>
            <p class="text-muted">Find and book your perfect rental vehicle</p>
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

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Vehicle Type</label>
                            <select id="typeFilter" class="form-select">
                                <option value="">All Types</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Price per Day (₱)</label>
                            <input type="number" id="priceFilter" class="form-control" placeholder="Enter max price">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button id="filterBtn" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicles Grid Container -->
    <div id="vehiclesContainer">
        <div class="row">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading vehicles...</span>
                </div>
                <p class="mt-2 text-muted">Loading available vehicles...</p>
            </div>
        </div>
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

.vehicle-card {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;
let currentType = '';
let currentPrice = '';

$(document).ready(function() {
    // Load vehicle types for dropdown
    loadVehicleTypes();
    
    // Load vehicles on page load
    loadVehicles();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadVehicles(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadVehicles(false, true);
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
                    loadVehicles(true);
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
    
    // Filter button click
    $('#filterBtn').on('click', function() {
        currentType = $('#typeFilter').val();
        currentPrice = $('#priceFilter').val();
        loadVehicles(false, false, true);
    });
    
    // Enter key in price filter
    $('#priceFilter').on('keypress', function(e) {
        if (e.which === 13) {
            currentType = $('#typeFilter').val();
            currentPrice = $('#priceFilter').val();
            loadVehicles(false, false, true);
        }
    });
    
    // Load vehicle types for dropdown
    function loadVehicleTypes() {
        $.ajax({
            url: 'ajax/get_vehicle_types.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">All Types</option>';
                    response.types.forEach(type => {
                        options += `<option value="${escapeHtml(type.type)}">${escapeHtml(type.type)}</option>`;
                    });
                    $('#typeFilter').html(options);
                }
            }
        });
    }
    
    // Load vehicles function
    function loadVehicles(silent = false, showNotification = false, showLoading = false) {
        if (showLoading) {
            $('#vehiclesContainer').html(`
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading vehicles...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading available vehicles...</p>
                    </div>
                </div>
            `);
        }
        
        if (!silent) {
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_vehicles_customer.php',
            type: 'GET',
            data: { 
                type: currentType, 
                price: currentPrice 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayVehicles(response.vehicles);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: `Found ${response.vehicles.length} vehicles.`,
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
                if (!silent) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not connect to server.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
                displaySampleVehicles();
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display vehicles in grid
    function displayVehicles(vehicles) {
        if (vehicles.length === 0) {
            $('#vehiclesContainer').html(`
                <div class="row">
                    <div class="col-md-12">
                        <div class="card text-center py-5">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p class="text-muted">No vehicles available matching your criteria.</p>
                            <div>
                                <button class="btn btn-outline-primary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i> Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            return;
        }
        
        let html = '<div class="row">';
        vehicles.forEach(vehicle => {
            let passengerCapacity = vehicle.passenger_capacity || 4;
            let imageHtml = vehicle.photo_url 
                ? `<img src="<?php echo BASE_URL; ?>${vehicle.photo_url}" class="card-img-top" alt="${escapeHtml(vehicle.model)}" style="height: 200px; object-fit: cover;">`
                : `<div style="background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); height: 200px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                    <i class="fas fa-car"></i>
                   </div>`;
            
            html += `
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm vehicle-card">
                        ${imageHtml}
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(vehicle.model)}</h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-id-card"></i> ${escapeHtml(vehicle.plate_number)}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar"></i> Year: ${vehicle.year}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-tag"></i> Type: ${escapeHtml(vehicle.type)}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-users"></i> Capacity: ${passengerCapacity} passengers
                            </p>
                            <p class="text-muted mb-3">
                                <i class="fas fa-tachometer-alt"></i> ${Number(vehicle.current_mileage).toLocaleString()} km
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 text-success">₱${Number(vehicle.price_per_day).toLocaleString()}</h4>
                                    <small class="text-muted">per day</small>
                                </div>
                                <a href="book-rental.php?vehicle_id=${vehicle.vehicle_id}" class="btn btn-primary">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#vehiclesContainer').html(html);
    }
    
    // Reset filters function
    window.resetFilters = function() {
        currentType = '';
        currentPrice = '';
        $('#typeFilter').val('');
        $('#priceFilter').val('');
        loadVehicles(false, false, true);
    };
    
    // Sample vehicles for fallback
    function displaySampleVehicles() {
        const sampleVehicles = [
            {
                vehicle_id: 1,
                model: 'Toyota Fortuner',
                plate_number: 'ABC-1234',
                year: 2022,
                type: 'SUV',
                passenger_capacity: 7,
                current_mileage: 15000,
                price_per_day: 3500,
                photo_url: ''
            },
            {
                vehicle_id: 2,
                model: 'Honda Civic',
                plate_number: 'DEF-5678',
                year: 2023,
                type: 'Sedan',
                passenger_capacity: 5,
                current_mileage: 8000,
                price_per_day: 2500,
                photo_url: ''
            }
        ];
        displayVehicles(sampleVehicles);
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