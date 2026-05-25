<?php
$page_title = 'Damage Reports';
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
            <h1><i class="fas fa-exclamation-triangle"></i> Damage Reports</h1>
            <p class="text-muted">Track and manage vehicle damage incidents</p>
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
                    <h5 class="mb-0">Create Report</h5>
                </div>
                <div class="card-body">
                    <div id="alertMessage"></div>

                    <form id="addReportForm">
                        <input type="hidden" name="add_report" value="1">

                        <div class="mb-3">
                            <label class="form-label">Rental</label>
                            <select name="rental_id" id="rental_id" class="form-select" required>
                                <option value="">Select Rental</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Describe the damage..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" id="severity" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="fas fa-save"></i> Create Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Damage Reports (<span id="reportCount">0</span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Description</th>
                                    <th>Severity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reportsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-success"></div> Loading reports...
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
    loadReports();
    loadRentals();
    
    // Auto-refresh every 30 seconds
    autoRefreshInterval = setInterval(function() {
        if (autoRefreshEnabled) {
            loadReports(true);
        }
    }, 30000);
    
    // Manual refresh button
    $('#manualRefreshBtn').on('click', function() {
        loadReports(false, true);
        loadRentals();
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
                    loadReports(true);
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
    
    // Add Report Form Submit
    $('#addReportForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = {
            add_report: 1,
            rental_id: $('#rental_id').val(),
            description: $('#description').val(),
            severity: $('#severity').val(),
            admin_notes: $('#admin_notes').val()
        };
        
        $.ajax({
            url: 'ajax/add_damage_report.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Creating...');
                $('#submitBtn').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#addReportForm')[0].reset();
                    loadReports();
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
                    text: 'Failed to create report'
                });
            },
            complete: function() {
                $('#submitBtn').html('<i class="fas fa-save"></i> Create Report');
                $('#submitBtn').prop('disabled', false);
            }
        });
    });
    
    // Load reports function
    function loadReports(silent = false, showNotification = false) {
        if (!silent) {
            $('#reportsTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="spinner-border text-success"></div> Loading reports...
                    </td>
                </tr>
            `);
            $('#manualRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        $.ajax({
            url: 'ajax/get_damage_reports.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayReports(response.reports);
                    $('#reportCount').text(response.reports.length);
                    
                    if (showNotification) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed!',
                            text: 'Reports updated.',
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
                    displaySampleReports();
                }
            },
            complete: function() {
                if (!silent) {
                    $('#manualRefreshBtn').html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            }
        });
    }
    
    // Load rentals for dropdown
    function loadRentals() {
        $.ajax({
            url: 'ajax/get_rentals_dropdown.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Select Rental</option>';
                    response.rentals.forEach(rental => {
                        options += `<option value="${rental.rental_id}">#${rental.rental_id} - ${escapeHtml(rental.label)}</option>`;
                    });
                    $('#rental_id').html(options);
                }
            }
        });
    }
    
    // Display reports in table
    function displayReports(reports) {
        if (reports.length === 0) {
            $('#reportsTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="fas fa-check-circle" style="font-size: 48px;"></i>
                        <p class="mt-2">No damage reports found</p>
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        reports.forEach(report => {
            let badgeClass = report.severity === 'high' ? 'danger' : (report.severity === 'medium' ? 'warning' : 'info');
            let description = report.description.length > 50 ? report.description.substring(0, 50) + '...' : report.description;
            
            html += `
                <tr>
                    <td>${escapeHtml(report.report_date)}</strong></td>
                    <td>${escapeHtml(report.customer_name)}</strong></td>
                    <td>${escapeHtml(report.vehicle_model)} (${escapeHtml(report.plate_number)})</strong></td>
                    <td>${escapeHtml(description)}</strong></td>
                    <td><span class="badge bg-${badgeClass}">${report.severity.charAt(0).toUpperCase() + report.severity.slice(1)}</span></td>
                    <td>
                        <a href="damage-details.php?id=${report.report_id}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteReport(${report.report_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </strong>
                </tr>
            `;
        });
        $('#reportsTableBody').html(html);
    }
    
    // Delete report function
    window.deleteReport = function(reportId) {
        Swal.fire({
            title: 'Delete Report?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete_damage_report.php',
                    type: 'POST',
                    data: { report_id: reportId },
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
                            loadReports();
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
                            text: 'Failed to delete report'
                        });
                    }
                });
            }
        });
    };
    
    // Sample data for fallback
    function displaySampleReports() {
        const sampleReports = [
            {
                report_id: 1,
                report_date: 'May 26, 2024',
                customer_name: 'John Doe',
                vehicle_model: 'Toyota Fortuner',
                plate_number: 'ABC-1234',
                description: 'Scratch on rear bumper',
                severity: 'medium'
            },
            {
                report_id: 2,
                report_date: 'May 25, 2024',
                customer_name: 'Jane Smith',
                vehicle_model: 'Honda Civic',
                plate_number: 'DEF-5678',
                description: 'Dent on front door',
                severity: 'high'
            }
        ];
        displayReports(sampleReports);
        $('#reportCount').text(sampleReports.length);
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