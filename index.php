<?php
require_once 'config/constants.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isAdmin()) {
        redirect(BASE_URL . 'JhunriderSystem/admin/dashboard.php');
    } else {
        redirect(BASE_URL . 'JhunriderSystem/customer/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TCRCJ - Secure Car Rental Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- SweetAlert2 for better notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #1F2937;
            background: #F3F4F6;
        }

        h1, h2, h3, h4, .heading-font {
            font-family: 'Poppins', sans-serif;
        }

        /* Green & Black Color Palette */
        :root {
            --primary-green: #16A34A;
            --primary-green-dark: #15803D;
            --primary-green-light: #22C55E;
            --charcoal: #111827;
            --charcoal-deep: #0B0F14;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --bg-light: #F3F4F6;
            --white: #FFFFFF;
        }

        /* Navigation */
        .navbar {
            background: var(--white);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: var(--charcoal);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark);
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .nav-link:hover {
            color: var(--primary-green);
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background: var(--primary-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
            border-radius: 8px;
            padding: 8px 22px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--charcoal) 0%, var(--charcoal-deep) 100%);
            min-height: 85vh;
            display: flex;
            align-items: center;
            padding: 80px 0;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1600') center/cover;
            opacity: 0.2;
            pointer-events: none;
        }

        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 20px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
        }

        /* Stats Banner */
        .stats-banner {
            background: var(--white);
            padding: 60px 0;
            margin-top: -40px;
            position: relative;
            z-index: 10;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.05);
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            color: var(--primary-green);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-icon {
            font-size: 40px;
            color: var(--primary-green-light);
            margin-bottom: 15px;
            opacity: 0.8;
        }

        /* CTA Banner */
        .cta-banner {
            background: linear-gradient(135deg, var(--primary-green-dark) 0%, var(--primary-green) 100%);
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
            background-size: 50px 50px;
            animation: moveDots 20s linear infinite;
        }

        @keyframes moveDots {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 50px); }
        }

        .cta-banner h2 {
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .cta-banner p {
            font-size: 20px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .btn-cta {
            background: white;
            color: var(--primary-green);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: var(--primary-green-dark);
        }

        /* Simple Booking Form */
        .simple-booking {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
        }

        .simple-booking h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--charcoal);
            margin-bottom: 15px;
        }

        .simple-booking p {
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .location-select {
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .location-select select {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .location-select select:focus {
            border-color: var(--primary-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }

        /* Feature Cards */
        .feature-card {
            background: var(--white);
            border-radius: 12px;
            padding: 40px 25px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(22, 163, 74, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .feature-icon i {
            font-size: 40px;
            color: var(--primary-green);
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--charcoal);
        }

        .feature-card p {
            color: var(--text-muted);
        }

        /* Section Titles */
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 36px;
            color: var(--charcoal);
            margin-bottom: 15px;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 18px;
            margin-bottom: 50px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .loading-content .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Notification Toast */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-green);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Footer */
        .footer {
            background: var(--charcoal);
            color: white;
            padding: 60px 0 20px;
        }

        .footer h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-green);
        }

        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            color: white;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            background: var(--primary-green);
            transform: translateY(-3px);
        }

        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            color: rgba(255,255,255,0.6);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }
            .hero p {
                font-size: 16px;
            }
            .section-title {
                font-size: 28px;
            }
            .stat-number {
                font-size: 32px;
            }
            .cta-banner h2 {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Updating content...</p>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="toast-notification" id="toastNotification">
        <i class="fas fa-check-circle"></i> <span id="toastMessage">Content updated!</span>
    </div>

    <!-- Header / Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="uploads/profiles/RentCar.png" alt="TCRCJ" style="width: 50px; height: 50px; object-fit: contain;">Tagum City Rent Car Jhunrider
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#" data-section="home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-section="about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-section="contact">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="auth/login.php" class="btn btn-outline-custom">Login</a>
                    <a href="auth/register.php" class="btn btn-primary-custom">Book Now</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div id="mainContent">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h1 style="margin-bottom: 20px; font-size: 52px;">Tagum City Rent Car Jhunrider</h1>
                        <p>Affordable. Reliable. Hassle-Free.</p>
                    </div>
                </div>
                
                <!-- Simple Booking Form -->
                <div class="row justify-content-center mt-4">
                    <div class="col-lg-6">
                        <div class="simple-booking">
                            <h3>Find Your Perfect Ride</h3>
                            <p>Select your preferred location to get started</p>
                            <div class="location-select">
                                <select id="location" class="form-select">
                                    <option value="Tagum City">📍 Tagum City</option>
                                    <option value="Davao City">📍 Davao City</option>
                                    <option value="Samal Island">📍 Samal Island</option>
                                </select>
                            </div>
                            <button class="btn btn-primary-custom" id="exploreBtn">
                                <i class="fas fa-car"></i> Explore Vehicles
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Banner -->
        <div class="stats-banner">
            <div class="container">
                <div class="row" id="statsContainer">
                    <!-- Stats loaded via AJAX -->
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section class="py-5" style="background: var(--bg-light);">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title">Why Choose Us</h2>
                    <p class="section-subtitle">We provide the best car rental experience</p>
                </div>
                <div class="row g-4" id="featuresContainer">
                    <!-- Features loaded via AJAX -->
                </div>
            </div>
        </section>

        <!-- CTA Banner -->
        <div class="cta-banner">
            <div class="container">
                <h2>Ready to Hit the Road?</h2>
                <p>Experience the freedom of the open road with our premium vehicles</p>
                <button class="btn btn-cta" id="ctaBookNowBtn">
                    <i class="fas fa-calendar-check"></i> Book Your Ride Now
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4><i class="fas fa-car" style="color: var(--primary-green);"></i> Tagum City Rent Car Jhunrider</h4>
                    <p style="color: rgba(255,255,255,0.7);">Your trusted partner for quality car rentals. Safe, reliable, and affordable.</p>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/jun.natividad.58323"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#" data-section="home">Home</a></li>
                        <li><a href="#" data-section="about">About Us</a></li>
                        <li><a href="#" data-section="contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h4>Contact Info</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt" style="color: var(--primary-green);"></i> Tagum City, Davao del Norte</li>
                        <li><i class="fas fa-phone" style="color: var(--primary-green);"></i> +63 9486318837</li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h4>Business Hours</h4>
                    <ul class="footer-links">
                        <li>Mon-Fri: 8:00 AM - 7:00 PM</li>
                        <li>Saturday: 9:00 AM - 6:00 PM</li>
                        <li>Sunday: 10:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2026 Tagum City Rent Car Jhunrider. All rights reserved. | Professional Car Rental Management System</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
$(document).ready(function() {
    // Load all dynamic content on page load
    loadStats();
    loadFeatures();
    
    // Set up automatic refresh every 30 seconds (optional)
    let autoRefreshInterval = setInterval(function() {
        refreshAllContent();
    }, 30000); // Refresh every 30 seconds
    
    // Handle explore button
    $('#exploreBtn').on('click', function() {
        const location = $('#location').val();
        showToast('Please login to explore vehicles in ' + location + '!');
        setTimeout(function() {
            window.location.href = 'auth/login.php';
        }, 1500);
    });
    
    // Handle CTA book now button
    $('#ctaBookNowBtn').on('click', function() {
        showToast('Please login to continue booking!');
        setTimeout(function() {
            window.location.href = 'auth/login.php';
        }, 1500);
    });
    
    // Handle navigation clicks with AJAX
    $('[data-section]').on('click', function(e) {
        e.preventDefault();
        const section = $(this).data('section');
        loadSection(section);
    });
    
    // Function to load stats via AJAX
    function loadStats() {
        $.ajax({
            url: 'ajax/get_stats.php',
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                if (response.success) {
                    updateStats(response.stats);
                } else {
                    loadSampleStats();
                }
                hideLoading();
            },
            error: function() {
                loadSampleStats();
                hideLoading();
            }
        });
    }
    
    // Function to load features via AJAX
    function loadFeatures() {
        $.ajax({
            url: 'ajax/get_features.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateFeatures(response.features);
                } else {
                    loadSampleFeatures();
                }
            },
            error: function() {
                loadSampleFeatures();
            }
        });
    }
    
    // Function to load different sections via AJAX
    function loadSection(section) {
        showLoading();
        
        $.ajax({
            url: 'ajax/load_section.php',
            type: 'POST',
            data: { section: section },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#mainContent').html(response.content);
                    showToast('Section loaded: ' + section);
                } else {
                    showToast('Error loading section', 'error');
                }
                hideLoading();
            },
            error: function() {
                hideLoading();
                showToast('Failed to load section', 'error');
            }
        });
    }
    
    // Function to refresh all content
    function refreshAllContent() {
        loadStats();
        loadFeatures();
        showToast('Content refreshed automatically!', 'info');
    }
    
    // Update stats display
    function updateStats(stats) {
        let html = '';
        stats.forEach(stat => {
            html += `
                <div class="col-md-3 col-sm-6">
                    <div class="stat-item" onclick="handleStatClick('${stat.label}')">
                        <div class="stat-icon">
                            <i class="${stat.icon}"></i>
                        </div>
                        <div class="stat-number">${stat.number}</div>
                        <div class="stat-label">${stat.label}</div>
                    </div>
                </div>
            `;
        });
        $('#statsContainer').html(html);
    }
    
    // Update features display
    function updateFeatures(features) {
        let html = '';
        features.forEach(feature => {
            html += `
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="${feature.icon}"></i>
                        </div>
                        <h3>${feature.title}</h3>
                        <p>${feature.description}</p>
                    </div>
                </div>
            `;
        });
        $('#featuresContainer').html(html);
    }
    
    // Sample stats for fallback
    function loadSampleStats() {
        const sampleStats = [
            { icon: 'fas fa-car', number: '50+', label: 'Premium Vehicles' },
            { icon: 'fas fa-users', number: '1000+', label: 'Happy Customers' },
            { icon: 'fas fa-clock', number: '24/7', label: 'Support Available' },
            { icon: 'fas fa-map-marker-alt', number: '5+', label: 'Convenient Locations' }
        ];
        updateStats(sampleStats);
    }
    
    // Sample features for fallback
    function loadSampleFeatures() {
        const sampleFeatures = [
            { icon: 'fas fa-car-side', title: 'Wide Vehicle Selection', description: 'Choose from SUVs, Sedans, Luxury cars, and more' },
            { icon: 'fas fa-tag', title: 'Affordable Pricing', description: 'Best rates with no hidden fees' },
            { icon: 'fas fa-map-marker-alt', title: 'Multiple Locations', description: 'Convenient pickup points across the city' },
            { icon: 'fas fa-lock', title: 'Secure Booking', description: 'Safe and encrypted transactions' }
        ];
        updateFeatures(sampleFeatures);
    }
    
    // Show loading overlay
    function showLoading() {
        $('#loadingOverlay').addClass('active');
    }
    
    // Hide loading overlay
    function hideLoading() {
        $('#loadingOverlay').removeClass('active');
    }
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = $('#toastNotification');
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        $('#toastMessage').html(`<i class="fas ${icon}"></i> ${message}`);
        toast.css('background', type === 'success' ? '#16A34A' : (type === 'error' ? '#EF4444' : '#3B82F6'));
        toast.fadeIn(300);
        setTimeout(function() {
            toast.fadeOut(300);
        }, 3000);
    }
    
    // Handle stat item click
    window.handleStatClick = function(label) {
        showToast(`Showing information about: ${label}`);
    };
    
    // Monitor for file changes (for development)
    let lastModified = null;
    function checkForUpdates() {
        $.ajax({
            url: 'ajax/check_updates.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.modified && response.modified !== lastModified) {
                    lastModified = response.modified;
                    showToast('Content has been updated! Refreshing...', 'info');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            }
        });
    }
    
    // Check for updates every 5 seconds (for development)
    // Uncomment if you want auto-refresh when files change
    // setInterval(checkForUpdates, 5000);
    
    // Manual refresh button (optional - add to page if needed)
    // You can add a refresh button anywhere on the page
    $('body').append('<div style="position: fixed; bottom: 20px; left: 20px; z-index: 10000;"><button id="manualRefreshBtn" class="btn btn-primary-custom" style="border-radius: 50px; padding: 10px 20px;"><i class="fas fa-sync-alt"></i> Refresh</button></div>');
    
    $('#manualRefreshBtn').on('click', function() {
        refreshAllContent();
        showToast('Content refreshed manually!');
    });
});

// Watch for PHP file changes (requires additional setup)
// This uses EventSource for real-time updates
if (typeof(EventSource) !== "undefined") {
    var source = new EventSource("ajax/live_updates.php");
    source.onmessage = function(event) {
        var data = JSON.parse(event.data);
        if (data.updated) {
            showToast('New updates available! Refreshing content...', 'info');
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    };
}
</script>

<!-- Create the necessary AJAX endpoint files -->
<?php
// This PHP code will create the AJAX endpoint files automatically
// Create ajax directory if it doesn't exist
if (!file_exists('ajax')) {
    mkdir('ajax', 0777, true);
}

// Create get_stats.php
$get_stats = '<?php
header("Content-Type: application/json");
// Sample stats data - replace with database queries
$stats = [
    ["icon" => "fas fa-car", "number" => "50+", "label" => "Premium Vehicles"],
    ["icon" => "fas fa-users", "number" => "1000+", "label" => "Happy Customers"],
    ["icon" => "fas fa-clock", "number" => "24/7", "label" => "Support Available"],
    ["icon" => "fas fa-map-marker-alt", "number" => "5+", "label" => "Convenient Locations"]
];
echo json_encode(["success" => true, "stats" => $stats]);
?>';
file_put_contents('ajax/get_stats.php', $get_stats);

// Create get_features.php
$get_features = '<?php
header("Content-Type: application/json");
$features = [
    ["icon" => "fas fa-car-side", "title" => "Wide Vehicle Selection", "description" => "Choose from SUVs, Sedans, Luxury cars, and more"],
    ["icon" => "fas fa-tag", "title" => "Affordable Pricing", "description" => "Best rates with no hidden fees"],
    ["icon" => "fas fa-map-marker-alt", "title" => "Multiple Locations", "description" => "Convenient pickup points across the city"],
    ["icon" => "fas fa-lock", "title" => "Secure Booking", "description" => "Safe and encrypted transactions"]
];
echo json_encode(["success" => true, "features" => $features]);
?>';
file_put_contents('ajax/get_features.php', $get_features);

echo "<!-- AJAX endpoints created successfully -->";
?>
</body>
</html>