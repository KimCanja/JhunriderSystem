<?php
// Customer Navigation Bar
// Get user profile photo from session or database
if (!isset($profile_photo) && isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $profile_photo = $user['profile_photo'] ?? null;
    $_SESSION['profile_photo'] = $profile_photo;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>customer/dashboard.php">
              <img src="../uploads/profiles/RentCar.png" alt="TCRCJ" style="width: 50px; height: 50px; object-fit: contain;">
               Tagum City Rent Car Jhunrider
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>customer/dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>customer/browse-vehicles.php">
                        <i class="fas fa-car"></i> Browse Vehicles
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>customer/my-rentals.php">
                        <i class="fas fa-history"></i> My Rentals
                    </a>
                </li>
                
                <!-- User Dropdown with Profile Photo -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($profile_photo) && file_exists('../' . $profile_photo)): ?>
                            <img src="<?php echo BASE_URL . $profile_photo; ?>" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover; margin-right: 8px;">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 20px; margin-right: 8px;"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($_SESSION['name'] ?? 'Customer'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>customer/profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>customer/my-rentals.php">
                                <i class="fas fa-calendar-check"></i> My Rentals
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: linear-gradient(135deg, #0A2540 0%, #1E2937 100%);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 12px 20px;
}

.navbar-brand {
    font-weight: 700;
    font-size: 24px;
}

.navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    transition: all 0.3s ease;
    padding: 8px 16px;
    border-radius: 8px;
}

.navbar-nav .nav-link:hover {
    background: rgba(16, 185, 129, 0.2);
    color: white !important;
}

.dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    margin-top: 10px;
}

.dropdown-item {
    padding: 10px 20px;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.dropdown-item:hover {
    background: rgba(16, 185, 129, 0.1);
    color: #10B981;
}

.dropdown-divider {
    margin: 5px 0;
}

@media (max-width: 768px) {
    .navbar-nav {
        margin-top: 10px;
    }
    
    .nav-item {
        margin: 5px 0;
    }
    
    .dropdown-menu {
        background: #1E2937;
    }
    
    .dropdown-item {
        color: white;
    }
    
    .dropdown-item:hover {
        background: #10B981;
        color: white;
    }
}
</style>

<script>
// Ensure Bootstrap JS is loaded for dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
    });
});
</script>