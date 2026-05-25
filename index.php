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
            --status-available-bg: #DCFCE7;
            --status-available-text: #166534;
            --status-booked-bg: #FEE2E2;
            --status-booked-text: #991B1B;
            --status-pending-bg: #FEF9C3;
            --status-pending-text: #854D0E;
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

        .navbar-brand i {
            color: var(--primary-green);
            margin-right: 8px;
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

        .btn-accent {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-accent:hover {
            background: var(--primary-green-dark);
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

        /* Booking Form */
        .booking-form {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .booking-form input,
        .booking-form select {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .booking-form input:focus,
        .booking-form select:focus {
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

        /* Vehicle Cards */
        .vehicle-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            height: 100%;
            border: 1px solid var(--border-color);
        }

        .vehicle-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }

        .vehicle-image {
            height: 220px;
            overflow: hidden;
        }

        .vehicle-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .vehicle-card:hover .vehicle-image img {
            transform: scale(1.05);
        }

        .vehicle-info {
            padding: 20px;
        }

        .vehicle-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--charcoal);
            margin-bottom: 5px;
        }

        .vehicle-type {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .vehicle-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-green-light);
            margin: 15px 0;
        }

        .vehicle-price span {
            font-size: 14px;
            font-weight: 400;
            color: var(--text-muted);
        }

        /* Offer Section */
        .offer-section {
            background: var(--primary-green);
            padding: 60px 0;
            text-align: center;
        }

        .offer-section h2 {
            font-size: 42px;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
        }

        .offer-section p {
            font-size: 18px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
        }

        /* Testimonial Cards */
        .testimonial-card {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }

        .testimonial-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            object-fit: cover;
        }

        .rating i {
            color: var(--primary-green-light);
            margin: 0 2px;
        }

        .testimonial-text {
            color: var(--text-dark);
            font-style: italic;
            margin: 20px 0;
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
            .offer-section h2 {
                font-size: 28px;
            }
            .section-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

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
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#vehicles">Vehicles</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="auth/login.php" class="btn btn-outline-custom">Login</a>
                    <a href="auth/register.php" class="btn btn-primary-custom">Book Now</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 20px;">
                        <h1 style="margin: 0; font-size: 42px;">Tagum City Rent Car Jhunrider</h1>
                    </div>
                    <p>Affordable. Reliable. Hassle-Free.</p>
                </div>
            </div>
            
            <!-- Booking Form -->
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="booking-form">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option>Pickup Location</option>
                                    <option>Tagum City</option>
                                    <option>Davao City</option>
                                    <option>Samal Island</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" placeholder="Pickup Date">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" placeholder="Return Date">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary-custom w-100">Search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" style="background: var(--bg-light);">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Why Choose Us</h2>
                <p class="section-subtitle">We provide the best car rental experience</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <h3>Wide Vehicle Selection</h3>
                        <p>Choose from SUVs, Sedans, Luxury cars, and more</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h3>Affordable Pricing</h3>
                        <p>Best rates with no hidden fees</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Multiple Locations</h3>
                        <p>Convenient pickup points across the city</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Secure Booking</h3>
                        <p>Safe and encrypted transactions</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vehicle Listing Section -->
    <section id="vehicles" class="py-5">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Fleet</h2>
                <p class="section-subtitle">Choose from our wide selection of quality vehicles</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600" alt="Toyota Fortuner">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Toyota Fortuner</h3>
                            <p class="vehicle-type"><i class="fas fa-suv"></i> SUV • 7 Seater</p>
                            <div class="vehicle-price">₱3,500 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600" alt="Honda Civic">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Honda Civic</h3>
                            <p class="vehicle-type"><i class="fas fa-car"></i> Sedan • 5 Seater</p>
                            <div class="vehicle-price">₱2,500 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600" alt="Mitsubishi Montero">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Mitsubishi Montero</h3>
                            <p class="vehicle-type"><i class="fas fa-suv"></i> SUV • 7 Seater</p>
                            <div class="vehicle-price">₱3,800 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=600" alt="Toyota Vios">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Toyota Vios</h3>
                            <p class="vehicle-type"><i class="fas fa-car"></i> Sedan • 5 Seater</p>
                            <div class="vehicle-price">₱1,800 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1553440569-bcc63803a83d?w=600" alt="Ford Raptor">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Ford Raptor</h3>
                            <p class="vehicle-type"><i class="fas fa-truck"></i> Pickup • 5 Seater</p>
                            <div class="vehicle-price">₱4,500 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="https://images.unsplash.com/photo-1580274455191-1c62238fa333?w=600" alt="Nissan Navara">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">Nissan Navara</h3>
                            <p class="vehicle-type"><i class="fas fa-truck"></i> Pickup • 5 Seater</p>
                            <div class="vehicle-price">₱3,200 <span>/ day</span></div>
                            <button class="btn btn-primary-custom w-100">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <button class="btn btn-outline-custom">View All Vehicles <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </section>

    <!-- Special Offer Section -->
    <section class="offer-section">
        <div class="container">
            <h2>Weekend Promo – 15% Off</h2>
            <p>Book any vehicle for 3 days or more this weekend and get 15% discount!</p>
            <button class="btn btn-accent">Claim Offer Now</button>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="py-5" style="background: var(--bg-light);">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-subtitle">Trusted by thousands of happy renters</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/1.jpg" alt="Customer" class="testimonial-image">
                        <h4>Maria Santos</h4>
                        <div class="rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Great service! The car was clean and the booking process was super easy. Highly recommend!"</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/2.jpg" alt="Customer" class="testimonial-image">
                        <h4>John Dela Cruz</h4>
                        <div class="rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Affordable rates and friendly staff. Will definitely rent again from TCRCJ!"</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/3.jpg" alt="Customer" class="testimonial-image">
                        <h4>Anna Reyes</h4>
                        <div class="rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"The vehicle was in perfect condition. Best car rental experience I've had!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4><i class="fas fa-car" style="color: var(--primary-green);"></i> Tagum City Rent Car Jhunrider</h4>
                    <p style="color: rgba(255,255,255,0.7);">Your trusted partner for quality car rentals. Safe, reliable, and affordable.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#vehicles">Vehicles</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h4>Contact Info</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt" style="color: var(--primary-green);"></i> Tagum City, Davao del Norte</li>
                        <li><i class="fas fa-phone" style="color: var(--primary-green);"></i> +63 9486318837</li>
                        <li><i class="fas fa-envelope" style="color: var(--primary-green);"></i> https://www.facebook.com/jun.natividad.58323</li>
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
</body>
</html>