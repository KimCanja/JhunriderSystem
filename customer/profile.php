<?php
$page_title = 'My Profile';
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isCustomer()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user and customer info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

// If no customer record exists, create one
if (!$customer) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    
    // Fetch the newly created customer
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
}

// Handle profile photo upload
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        $db_path = 'uploads/profiles/' . $filename;
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['profile_photo']['size'] > $max_size) {
            $error = 'File size too large. Maximum 5MB allowed.';
        } elseif (in_array($_FILES['profile_photo']['type'], $allowed_types)) {
            // Delete old photo if exists
            if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
                unlink('../' . $user['profile_photo']);
            }
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$db_path, $user_id]);
                $success = 'Profile photo updated successfully!';
                
                // Update session
                $_SESSION['profile_photo'] = $db_path;
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to upload photo.';
            }
        } else {
            $error = 'Invalid file type. Allowed: JPG, PNG, WEBP, GIF';
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}

// Handle remove photo
if (isset($_POST['remove_photo'])) {
    if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
        unlink('../' . $user['profile_photo']);
    }
    $stmt = $pdo->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
    $success = 'Profile photo removed successfully!';
    
    // Update session
    $_SESSION['profile_photo'] = null;
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Handle update personal info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';

    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $user_id]);

            $stmt = $pdo->prepare("UPDATE customers SET contact_number = ?, address = ?, license_number = ?, birthdate = ? WHERE user_id = ?");
            $stmt->execute([$contact_number, $address, $license_number, $birthdate, $user_id]);

            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $customer = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Update failed. Please try again.';
        }
    }
}
?>

<?php require_once '../includes/customer-navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-user"></i> My Profile</h1>
            <p class="text-muted">Update your personal information and profile picture</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Driver's License Number</label>
                            <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($customer['license_number'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($customer['birthdate'] ?? ''); ?>">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Profile Photo Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])): ?>
                        <img src="<?php echo BASE_URL . $user['profile_photo']; ?>" 
                             alt="Profile Photo" 
                             class="rounded-circle mb-3"
                             style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #10B981;">
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 64px; margin: 0 auto 15px;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" id="photoInput">
                            <small class="text-muted">Max size: 5MB (JPG, PNG, WEBP, GIF)</small>
                        </div>
                        <button type="submit" name="upload_photo" class="btn btn-success btn-sm w-100 mb-2">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                    
                    <?php if (!empty($user['profile_photo'])): ?>
                        <form method="POST" onsubmit="return confirm('Remove your profile photo?')">
                            <button type="submit" name="remove_photo" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> Remove Photo
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Summary</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Member Since:</strong><br>
                        <small><?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></small>
                    </p>
                    <p class="mb-2">
                        <strong>Account Status:</strong><br>
                        <span class="badge badge-approved">Active</span>
                    </p>
                    <p class="mb-2">
                        <strong>Damage Incidents:</strong><br>
                        <span class="badge <?php echo ($customer['damage_incidents_count'] ?? 0) > 0 ? 'badge-danger' : 'badge-success'; ?>">
                            <?php echo $customer['damage_incidents_count'] ?? 0; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-approved {
    background: #10B981;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
}
.badge-danger {
    background: #EF4444;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
}
.badge-success {
    background: #10B981;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
}
</style>

<script>
// Preview image before upload
const photoInput = document.getElementById('photoInput');
if (photoInput) {
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.querySelector('.rounded-circle');
                const placeholder = document.querySelector('.rounded-circle + div, .text-center > div');
                
                if (img) {
                    img.src = event.target.result;
                } else if (placeholder) {
                    // Replace placeholder with image
                    const newImg = document.createElement('img');
                    newImg.src = event.target.result;
                    newImg.className = 'rounded-circle mb-3';
                    newImg.style.width = '150px';
                    newImg.style.height = '150px';
                    newImg.style.objectFit = 'cover';
                    newImg.style.border = '3px solid #10B981';
                    placeholder.parentNode.replaceChild(newImg, placeholder);
                }
            }
            reader.readAsDataURL(file);
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>