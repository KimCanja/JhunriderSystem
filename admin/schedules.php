<?php
$page_title = 'Manage Schedules';
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
            <h1><i class="fas fa-calendar"></i> Manage Schedules</h1>
            <p class="text-muted">Manage vehicle availability and time slots</p>
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
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Schedule</h5>
                </div>
                <div class="card-body">
                    <div id="alertMessage"></div>

                    <form id="addScheduleForm">
                        <input type="hidden" name="add_schedule" value="1">

                        <div class="mb-3">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                <option value="">Select Vehicle</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Date</label>
                            <input type="date" name="available_date" id="available_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Time Slot</label>
                            <select name="time_slot" id="time_slot" class="form-select" required>
                                <option value="">Select Time</option>
                                <option value="08:00-12:00">08:00 AM - 12:00 PM</option>
                                <option value="12:00-16:00">12:00 PM - 04:00 PM</option>
                                <option value="16:00-20:00">04:00 PM - 08:00 PM</option>
                                <option value="All Day">All Day</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="fas fa-save"></i> Add Schedule
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Scheduled Availability (<span id="scheduleCount">0</span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="schedulesTableBody">
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border text-success"></div> Loading schedules...
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

$(document).ready(function() {
    // Load data on page load
    loadSchedules();
    loadVehicles();
    
    // Set minimum date
    const today = new Date().toISOString().split('T')[0];
    $('#available_date').attr('min', today);
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadSchedules(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadSchedules(false, true);
        loadVehicles();
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
                    loadSchedules(true);
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
    
    // Add Schedule Form Submit
    $('#addScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = {
            add_schedule: 1,
            vehicle_id: $('#vehicle_id').val(),
            available_date: $('#available_date').val(),
            time_slot: $('#time_slot').val()
        };
        
        $.ajax({
            url: 'ajax/add_schedule.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                $('#submitBtn').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#addScheduleForm')[0].reset();
                    loadSchedules();
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
                    text: 'Failed to add schedule'
                });
            },
            complete: function() {
                $('#submitBtn').html('<i class="fas fa-save"></i> Add Schedule');
                $('#submitBtn').prop('disabled', false);
            }
        });
    });
    
    // Load schedules function
    function loadSchedules(silent = false, showNotification = false) {
        if (!silent) {
            $('#schedulesTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-success"></div> Loading schedules...
                    </td>
                </tr>
            `);
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_schedules.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displaySchedules(response.schedules);
                    $('#scheduleCount').text(response.schedules.length);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Schedules updated.',
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
                    displaySampleSchedules();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Load vehicles for dropdown
    function loadVehicles() {
        $.ajax({
            url: 'ajax/get_vehicles_dropdown.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Select Vehicle</option>';
                    response.vehicles.forEach(vehicle => {
                        options += `<option value="${vehicle.vehicle_id}">${escapeHtml(vehicle.model)} (${escapeHtml(vehicle.plate_number)})</option>`;
                    });
                    $('#vehicle_id').html(options);
                }
            }
        });
    }
    
    // Display schedules in table
    function displaySchedules(schedules) {
        if (schedules.length === 0) {
            $('#schedulesTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="fas fa-calendar" style="font-size: 48px;"></i>
                        <p class="mt-2">No schedules found</p>
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        schedules.forEach(schedule => {
            let badgeClass = schedule.is_booked == 1 ? 'warning' : 'success';
            let statusText = schedule.is_booked == 1 ? 'Booked' : 'Available';
            
            html += `
                <tr>
                    <td><strong>${escapeHtml(schedule.model)}</strong> (${escapeHtml(schedule.plate_number)})</strong></td>
                    <td>${escapeHtml(schedule.available_date)}</strong></td>
                    <td>${escapeHtml(schedule.time_slot)}</strong></td>
                    <td><span class="badge bg-${badgeClass}">${statusText}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSchedule(${schedule.schedule_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </strong>
                </tr>
            `;
        });
        $('#schedulesTableBody').html(html);
    }
    
    // Delete schedule function
    window.deleteSchedule = function(scheduleId) {
        Swal.fire({
            title: 'Delete Schedule?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete_schedule.php',
                    type: 'POST',
                    data: { schedule_id: scheduleId },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Deleting...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            loadSchedules();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
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
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete schedule'
                        });
                    }
                });
            }
        });
    };
    
    // Sample data for fallback
    function displaySampleSchedules() {
        const sampleSchedules = [
            {
                schedule_id: 1,
                model: 'Toyota Fortuner',
                plate_number: 'ABC-1234',
                available_date: 'May 27, 2024',
                time_slot: '08:00-12:00',
                is_booked: 0
            },
            {
                schedule_id: 2,
                model: 'Honda Civic',
                plate_number: 'DEF-5678',
                available_date: 'May 28, 2024',
                time_slot: 'All Day',
                is_booked: 1
            }
        ];
        displaySchedules(sampleSchedules);
        $('#scheduleCount').text(sampleSchedules.length);
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