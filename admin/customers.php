<?php
$page_title = 'Manage Customers';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/sos-button.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-users"></i> Manage Customers</h1>
            <p class="text-muted">View and manage customer profiles and rental history</p>
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

    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or license...">
                        <button id="searchBtn" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button id="clearBtn" class="btn btn-secondary" style="display: none;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table Container -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Customer List (<span id="customerCount">0</span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>License</th>
                                    <th>Damage Incidents</th>
                                    <th>Member Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border text-success"></div> Loading customers...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autoRefreshEnabled = true;
let autoRefreshInterval;
let currentSearch = '';

$(document).ready(function() {
    // Load customers on page load
    loadCustomers();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadCustomers(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadCustomers(false, true);
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
                    loadCustomers(true);
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
    
    // Search button click
    $('#searchBtn').on('click', function() {
        currentSearch = $('#searchInput').val();
        loadCustomers(false, false, true);
    });
    
    // Clear button click
    $('#clearBtn').on('click', function() {
        currentSearch = '';
        $('#searchInput').val('');
        $('#clearBtn').hide();
        loadCustomers(false, false, true);
    });
    
    // Enter key in search input
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $('#searchInput').val();
            loadCustomers(false, false, true);
        }
    });
    
    // Load customers function
    function loadCustomers(silent = false, showNotification = false, showLoading = false) {
        if (showLoading) {
            $('#customersTableBody').html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border text-success"></div> Loading...
                    </td>
                </tr>
            `);
        }
        
        if (!silent) {
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_customers.php',
            type: 'GET',
            data: { search: currentSearch },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayCustomers(response.customers);
                    $('#customerCount').text(response.customers.length);
                    
                    // Show/hide clear button
                    if (currentSearch !== '') {
                        $('#clearBtn').show();
                    } else {
                        $('#clearBtn').hide();
                    }
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Customer list updated.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                } else {
                    console.error('Error:', response.message);
                    if (!silent) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load customers',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                if (!silent) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not connect to server. Check if admin/ajax/get_customers.php exists.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000
                    });
                }
                // Display sample data if AJAX fails
                displaySampleCustomers();
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display customers in table
    function displayCustomers(customers) {
        if (customers.length === 0) {
            $('#customersTableBody').html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-users" style="font-size: 48px;"></i>
                        <p class="mt-2">No customers found</p>
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        customers.forEach(customer => {
            let badgeClass = customer.damage_incidents_count > 0 ? 'danger' : 'success';
            let contactNumber = customer.contact_number && customer.contact_number !== '' ? customer.contact_number : 'N/A';
            let licenseNumber = customer.license_number && customer.license_number !== '' ? customer.license_number : 'N/A';
            
            html += `
                <tr>
                    <td><strong>${escapeHtml(customer.name)}</strong></td>
                    <td>${escapeHtml(customer.email)}</td>
                    <td>${escapeHtml(contactNumber)}</td>
                    <td>${escapeHtml(licenseNumber)}</td>
                    <td>
                        <span class="badge bg-${badgeClass}">
                            ${customer.damage_incidents_count}
                        </span>
                    </td>
                    <td>${customer.member_since}</td>
                    <td>
                        <a href="customer-details.php?id=${customer.customer_id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            `;
        });
        $('#customersTableBody').html(html);
    }
    
    // Sample data for fallback
    function displaySampleCustomers() {
        const sampleCustomers = [
            {
                customer_id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                contact_number: '09123456789',
                license_number: 'D123-4567-8901',
                damage_incidents_count: 0,
                member_since: 'Jan 15, 2024'
            },
            {
                customer_id: 2,
                name: 'Jane Smith',
                email: 'jane@example.com',
                contact_number: '09987654321',
                license_number: 'S987-6543-2109',
                damage_incidents_count: 2,
                member_since: 'Feb 20, 2024'
            }
        ];
        displayCustomers(sampleCustomers);
        $('#customerCount').text(sampleCustomers.length);
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