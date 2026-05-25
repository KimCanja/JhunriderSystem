<?php
$page_title = 'Check-Out Photos';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$rental_id = $_GET['rental_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$rental_id) {
    redirect(BASE_URL . 'customer/my-rentals.php');
}

// Get rental info
$stmt = $pdo->prepare("
    SELECT r.*, v.model, v.plate_number 
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
    WHERE r.rental_id = ? AND r.user_id = ?
");
$stmt->execute([$rental_id, $user_id]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect(BASE_URL . 'customer/my-rentals.php');
}

// Get existing before photos
$stmt = $pdo->prepare("SELECT * FROM rental_photos WHERE rental_id = ? AND type = 'before'");
$stmt->execute([$rental_id]);
$before_photos = $stmt->fetchAll();
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-camera"></i> Check-Out Photos</h1>
            <p class="text-muted">Upload photos of the vehicle before rental</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Upload Before Rental Photos</h5>
                </div>
                <div class="card-body">
                    <div class="photo-upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><strong>Click to upload or drag and drop</strong></p>
                        <p class="text-muted">PNG, JPG, WebP up to 5MB each</p>
                        <input type="file" id="photoInput" multiple accept="image/*" style="display: none;">
                    </div>

                    <div id="photoPreview" class="photo-grid mt-4"></div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="fas fa-upload"></i> Upload Photos
                        </button>
                        <a href="rental-details.php?id=<?php echo $rental_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rental Summary</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Vehicle:</strong><br>
                        <?php echo htmlspecialchars($rental['model']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Plate:</strong><br>
                        <?php echo htmlspecialchars($rental['plate_number']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Pickup Date:</strong><br>
                        <?php echo date('M d, Y', strtotime($rental['pickup_date'])); ?>
                    </p>
                    <hr>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle"></i> Please take clear photos of the vehicle's exterior and interior condition.
                    </p>
                </div>
            </div>

            <?php if (count($before_photos) > 0): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Uploaded Photos</h5>
                    </div>
                    <div class="card-body">
                        <div class="photo-grid" style="grid-template-columns: repeat(2, 1fr);">
                            <?php foreach ($before_photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($photo['image_path']); ?>" alt="Photo">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const uploadBtn = document.getElementById('uploadBtn');
    let selectedFiles = [];

    // Click to upload
    uploadArea.addEventListener('click', () => photoInput.click());

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#10B981';
        uploadArea.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#E2E8F0';
        uploadArea.style.backgroundColor = '#F8FAFC';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#E2E8F0';
        uploadArea.style.backgroundColor = '#F8FAFC';
        selectedFiles = Array.from(e.dataTransfer.files);
        displayPreview();
    });

    // File input change
    photoInput.addEventListener('change', (e) => {
        selectedFiles = Array.from(e.target.files);
        displayPreview();
    });

    function displayPreview() {
        photoPreview.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const photoDiv = document.createElement('div');
                photoDiv.className = 'photo-item';
                photoDiv.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <span class="photo-label">Photo ${index + 1}</span>
                `;
                photoPreview.appendChild(photoDiv);
            };
            reader.readAsDataURL(file);
        });
        uploadBtn.disabled = selectedFiles.length === 0;
    }

    // Upload
    uploadBtn.addEventListener('click', async () => {
        if (selectedFiles.length === 0) return;

        const formData = new FormData();
        formData.append('rental_id', <?php echo $rental_id; ?>);
        formData.append('type', 'before');
        selectedFiles.forEach(file => {
            formData.append('photos[]', file);
        });

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

        try {
            const response = await fetch('upload-photos.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showToast('Photos uploaded successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'rental-details.php?id=<?php echo $rental_id; ?>';
                }, 1500);
            } else {
                showToast('Upload failed', 'error');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Photos';
            }
        } catch (error) {
            showToast('Upload error', 'error');
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Photos';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
