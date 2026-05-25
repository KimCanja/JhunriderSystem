<?php
$page_title = 'SOS Alerts';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'auth/login.php');
}
?>

<?php require_once '../includes/admin-sidebar.php'; ?>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-exclamation-triangle text-danger"></i> SOS Emergency Alerts</h1>
            <p class="text-muted">Respond to customer emergency alerts</p>
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
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Active Alerts (<span id="alertCount">0</span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
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
                            <tbody id="alertsTableBody">
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="spinner-border text-danger"></div> Loading alerts...
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

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="responseForm">
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
                        <select name="status" id="status" class="form-select" required>
                            <option value="responded">Responded - In Progress</option>
                            <option value="resolved">Resolved - Issue Fixed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Response Notes</label>
                        <textarea name="admin_response" id="admin_response" class="form-control" rows="3" required placeholder="Describe the action taken..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitResponseBtn">
                        <i class="fas fa-paper-plane"></i> Submit Response
                    </button>
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
    // Load alerts on page load
    loadAlerts();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadAlerts(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadAlerts(false, true);
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
                    loadAlerts(true);
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
    
    // Response Form Submit
    $('#responseForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = {
            sos_id: $('#sos_id').val(),
            status: $('#status').val(),
            admin_response: $('#admin_response').val()
        };
        
        $.ajax({
            url: 'ajax/update_sos_alert.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submitResponseBtn').html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                $('#submitResponseBtn').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#responseModal').modal('hide');
                    $('#responseForm')[0].reset();
                    loadAlerts();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update alert status'
                });
            },
            complete: function() {
                $('#submitResponseBtn').html('<i class="fas fa-paper-plane"></i> Submit Response');
                $('#submitResponseBtn').prop('disabled', false);
            }
        });
    });
    
    // Load alerts function
    function loadAlerts(silent = false, showNotification = false) {
        if (!silent) {
            $('#alertsTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center">
                        <div class="spinner-border text-danger"></div> Loading alerts...
                    </td>
                </tr>
            `);
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_sos_alerts.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayAlerts(response.alerts);
                    $('#alertCount').text(response.alerts.length);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Alerts updated.',
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
            error: function() {
                if (!silent) {
                    displaySampleAlerts();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Display alerts in table
    function displayAlerts(alerts) {
        if (alerts.length === 0) {
            $('#alertsTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="fas fa-check-circle" style="font-size: 48px;"></i>
                        <p class="mt-2">No SOS alerts found</p>
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        alerts.forEach(alert => {
            let typeBadgeClass = '';
            switch(alert.alert_type) {
                case 'emergency': typeBadgeClass = 'danger'; break;
                case 'accident': typeBadgeClass = 'warning'; break;
                case 'mechanical': typeBadgeClass = 'info'; break;
                default: typeBadgeClass = 'primary';
            }
            
            let statusBadgeClass = '';
            switch(alert.status) {
                case 'pending': statusBadgeClass = 'danger'; break;
                case 'responded': statusBadgeClass = 'warning'; break;
                default: statusBadgeClass = 'success';
            }
            
            let message = alert.message ? (alert.message.length > 50 ? alert.message.substring(0, 50) + '...' : alert.message) : 'No message';
            
            let locationHtml = (alert.location_lat && alert.location_lng) 
                ? `<a href="https://maps.google.com/?q=${alert.location_lat},${alert.location_lng}" target="_blank" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-map-marker-alt"></i> View Map
                   </a>`
                : '<span class="text-muted">No location</span>';
            
            let vehicleInfo = alert.model 
                ? `<strong>${escapeHtml(alert.model)}</strong><br><small>${escapeHtml(alert.plate_number || '')}</small>`
                : 'N/A';
            
            html += `
                <tr>
                    <td><strong>#${alert.sos_id}</strong></td>
                    <td>
                        <strong>${escapeHtml(alert.user_name)}</strong>
                        <br>
                        <small class="text-muted">${escapeHtml(alert.user_email)}</small>
                    </td>
                    <td><span class="badge bg-${typeBadgeClass}">${alert.alert_type.charAt(0).toUpperCase() + alert.alert_type.slice(1)}</span></td>
                    <td>${vehicleInfo}</strong></td>
                    <td>${escapeHtml(message)}</strong></td>
                    <td>${locationHtml}</strong></td>
                    <td><small>${escapeHtml(alert.created_at)}</small></strong></td>
                    <td><span class="badge bg-${statusBadgeClass}">${alert.status.charAt(0).toUpperCase() + alert.status.slice(1)}</span></strong></td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="respondToAlert(${alert.sos_id}, '${escapeHtml(alert.user_name)}')">
                            <i class="fas fa-reply"></i> Respond
                        </button>
                    </strong>
                </tr>
            `;
        });
        $('#alertsTableBody').html(html);
    }
    
    // Sample data for fallback
    function displaySampleAlerts() {
        const sampleAlerts = [
            {
                sos_id: 1,
                user_name: 'John Doe',
                user_email: 'john@example.com',
                alert_type: 'emergency',
                model: 'Toyota Fortuner',
                plate_number: 'ABC-1234',
                message: 'Need immediate assistance!',
                location_lat: '7.4467',
                location_lng: '125.8099',
                created_at: '2024-05-26 14:30:00',
                status: 'pending'
            }
        ];
        displayAlerts(sampleAlerts);
        $('#alertCount').text(sampleAlerts.length);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// Global function for respond button
function respondToAlert(id, name) {
    document.getElementById('sos_id').value = id;
    document.getElementById('customer_name').innerHTML = name;
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>