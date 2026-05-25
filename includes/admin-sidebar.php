<?php
// Admin Sidebar Navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../uploads/profiles/RentCar.png" alt="TCRCJ" style="width: 45px; height: 45px; object-fit: contain;">
        <span>Tagum City Rent Car Jhunrider</span>
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/rentals.php" class="<?php echo ($current_page === 'rentals.php' || $current_page === 'rental-details.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Rentals
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/vehicles.php" class="<?php echo $current_page === 'vehicles.php' ? 'active' : ''; ?>">
                <i class="fas fa-car"></i> Vehicles
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/damage-reports.php" class="<?php echo $current_page === 'damage-reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Damage Reports
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/schedules.php" class="<?php echo $current_page === 'schedules.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i> Schedules
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/sos-alerts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'sos-alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> SOS Alerts
            </a>
        </li>
        <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <a href="<?php echo BASE_URL; ?>auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Green & Black Color Palette */
    :root {
        --primary-green: #16A34A;
        --primary-green-dark: #15803D;
        --primary-green-light: #22C55E;
        --charcoal: #111827;
        --charcoal-deep: #0B0F14;
        --card-bg: #1F2937;
        --text-dark: #F3F4F6;
        --text-muted: #9CA3AF;
        --border-color: #374151;
        --hover-bg: rgba(22, 163, 74, 0.1);
        --active-bg: rgba(22, 163, 74, 0.15);
    }

    .sidebar {
        background: linear-gradient(135deg, var(--charcoal) 0%, var(--charcoal-deep) 100%);
        color: white;
        min-height: 100vh;
        padding: 20px 0;
        position: fixed;
        width: 280px;
        left: 0;
        top: 0;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        font-family: 'Inter', sans-serif;
    }

    .sidebar-brand {
        padding: 20px 25px;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
        font-family: 'Poppins', sans-serif;
    }

    .sidebar-brand img {
        width: 45px;
        height: 45px;
        object-fit: contain;
    }

    .sidebar-brand span {
        color: white;
        font-size: 14px;
        line-height: 1.3;
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-nav li {
        margin: 5px 0;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 25px;
        color: var(--text-muted);
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
        border-left: 3px solid transparent;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
    }

    .sidebar-nav a:hover {
        background: var(--hover-bg);
        color: white;
        border-left-color: var(--primary-green);
    }

    .sidebar-nav a.active {
        background: var(--active-bg);
        color: white;
        border-left-color: var(--primary-green);
        font-weight: 600;
    }

    .sidebar-nav i {
        width: 24px;
        font-size: 18px;
        color: var(--primary-green);
    }

    .sidebar-nav a:hover i,
    .sidebar-nav a.active i {
        color: var(--primary-green-light);
    }

    /* Custom scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: var(--charcoal-deep);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: var(--primary-green);
        border-radius: 5px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: var(--primary-green-light);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            position: relative;
            min-height: auto;
        }
        
        .main-content {
            margin-left: 0 !important;
        }
    }
</style>