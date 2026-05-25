# RentGuard - Car Rental Management System

A comprehensive, professional web-based car rental management platform built with PHP, MySQL, HTML5, CSS3, JavaScript, and Bootstrap 5. RentGuard specializes in damage prevention and tracking through digital photo evidence, repeat-offender alerts, and comprehensive rental management.

## 🎯 Features

### Customer Portal
- **User Registration & Authentication** - Secure account creation with role-based access
- **Vehicle Browsing** - Filter vehicles by type and price
- **Rental Booking** - Easy appointment-style booking with date and time selection
- **Digital Check-Out** - Upload multiple photos of vehicle condition before rental
- **Digital Check-In** - Upload after-rental photos for comparison and damage detection
- **Rental History** - View all past and current rentals with status tracking
- **Profile Management** - Update personal information and driver details

### Admin Portal
- **Dashboard Analytics** - Real-time statistics on rentals, vehicles, and damage reports
- **Rental Management** - Approve/reject booking requests with customer details
- **Vehicle Fleet Management** - Add, edit, delete vehicles with pricing and status
- **Customer Management** - View customer profiles, rental history, and damage records
- **Damage Report System** - Create, track, and manage damage incidents
- **Repeat Offender Alerts** - Automatic flagging of customers with multiple damage incidents
- **Schedule Management** - Manage vehicle availability and time slots
- **Photo Evidence** - Side-by-side comparison of before/after rental photos

### Security Features
- **Password Hashing** - Bcrypt password encryption (password_hash)
- **SQL Injection Protection** - PDO prepared statements for all database queries
- **Session Management** - Secure session handling with role-based access control
- **Form Validation** - Frontend and backend validation on all inputs
- **File Upload Security** - Secure file handling with type and size validation

## 📋 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery
- **Icons**: Font Awesome 6.4
- **Database Access**: PDO with prepared statements

## 🗂️ Project Structure

```
rentguard/
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet with color scheme
│   ├── js/
│   │   └── script.js           # Utility functions and interactions
│   └── images/                 # Image assets
├── config/
│   ├── database.php            # Database connection
│   └── constants.php           # App constants and helper functions
├── includes/
│   ├── header.php              # HTML header template
│   ├── footer.php              # HTML footer template
│   ├── admin-sidebar.php       # Admin navigation
│   └── customer-navbar.php     # Customer navigation
├── auth/
│   ├── login.php               # Login page
│   ├── register.php            # Registration page
│   └── logout.php              # Logout handler
├── admin/
│   ├── dashboard.php           # Admin dashboard
│   ├── vehicles.php            # Vehicle management
│   ├── rentals.php             # Rental approvals
│   ├── rental-details.php      # Rental details view
│   ├── customers.php           # Customer management
│   ├── customer-details.php    # Customer profile
│   ├── damage-reports.php      # Damage report management
│   ├── damage-details.php      # Damage report details
│   └── schedules.php           # Schedule management
├── customer/
│   ├── dashboard.php           # Customer dashboard
│   ├── browse-vehicles.php     # Vehicle browsing
│   ├── book-rental.php         # Booking form
│   ├── my-rentals.php          # Rental history
│   ├── rental-details.php      # Rental details
│   ├── checkout-photos.php     # Pre-rental photo upload
│   ├── checkin-photos.php      # Post-rental photo upload
│   ├── upload-photos.php       # Photo upload handler
│   └── profile.php             # Customer profile
├── uploads/
│   ├── before/                 # Pre-rental photos
│   ├── after/                  # Post-rental photos
│   └── vehicles/               # Vehicle photos
├── sql/
│   └── rentguard.sql           # Database schema
├── index.php                   # Landing page
└── README.md                   # This file
```

## 🗄️ Database Schema

### Users Table
- `id` - Primary key
- `name` - Full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - 'customer' or 'admin'
- `created_at` - Registration timestamp

### Customers Table
- `customer_id` - Primary key
- `user_id` - Foreign key to users
- `contact_number` - Phone number
- `address` - Street address
- `license_number` - Driver's license
- `birthdate` - Date of birth
- `damage_incidents_count` - Damage count tracker

### Vehicles Table
- `vehicle_id` - Primary key
- `model` - Vehicle model
- `plate_number` - License plate (unique)
- `year` - Manufacturing year
- `type` - Vehicle type (Sedan, SUV, etc.)
- `status` - 'available', 'rented', 'maintenance'
- `current_mileage` - Current odometer reading
- `price_per_day` - Daily rental rate

### Rentals Table
- `rental_id` - Primary key
- `user_id` - Foreign key to users
- `vehicle_id` - Foreign key to vehicles
- `pickup_date` - Rental start date
- `return_date` - Rental end date
- `pickup_time` - Pickup time
- `status` - 'pending', 'approved', 'active', 'completed', 'cancelled'
- `notes` - Special requests
- `total_price` - Total rental cost
- `created_at` - Booking timestamp

### Rental Photos Table
- `photo_id` - Primary key
- `rental_id` - Foreign key to rentals
- `type` - 'before' or 'after'
- `image_path` - File path to image
- `uploaded_at` - Upload timestamp

### Damage Reports Table
- `report_id` - Primary key
- `rental_id` - Foreign key to rentals
- `vehicle_id` - Foreign key to vehicles
- `customer_id` - Foreign key to customers
- `report_date` - Report creation timestamp
- `description` - Damage description
- `severity` - 'low', 'medium', 'high'
- `admin_notes` - Additional notes

### Schedules Table
- `schedule_id` - Primary key
- `available_date` - Available date
- `time_slot` - Time slot
- `vehicle_id` - Foreign key to vehicles
- `is_booked` - Booking status

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)
- Composer (optional, for dependency management)

### Step 1: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE rentguard;
```

2. Import the schema:
```bash
mysql -u root -p rentguard < sql/rentguard.sql
```

### Step 2: Configuration

1. Update database credentials in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'rentguard');
```

2. Update base URL in `config/constants.php` if needed:
```php
define('BASE_URL', 'http://localhost/rentguard/');
```

### Step 3: File Permissions

Ensure upload directories are writable:
```bash
chmod 755 uploads/
chmod 755 uploads/before/
chmod 755 uploads/after/
chmod 755 uploads/vehicles/
```

### Step 4: Access the Application

1. Navigate to `http://localhost/rentguard/`
2. Login with demo credentials:
   - **Admin**: admin@rentguard.com / admin123
   - **Customer**: Register a new account

## 🎨 Design & UI

### Color Palette
- **Primary**: Deep Navy Blue (#0A2540) - Trust and professionalism
- **Primary Dark**: Slate (#1E2937) - Depth and contrast
- **Accent**: Emerald Green (#10B981) - Success and approval
- **Danger**: Rose Red (#EF4444) - Damage and alerts
- **Neutral**: White (#FFFFFF) with soft gray (#F8FAFC)

### Typography
- **Headings**: Bold, sans-serif (Inter)
- **Body**: Clean, readable (Inter)
- **Font Size**: Responsive scaling

### Components
- **Cards**: Rounded corners (16-24px), subtle shadows
- **Buttons**: Gradient backgrounds, hover effects
- **Forms**: Clean inputs with focus states
- **Badges**: Color-coded status indicators
- **Tables**: Responsive design with hover effects
- **Modals**: Smooth transitions and animations

## 📱 Responsive Design

The system is fully responsive and works seamlessly on:
- Desktop (1920px and above)
- Laptop (1024px - 1919px)
- Tablet (768px - 1023px)
- Mobile (320px - 767px)

## 🔐 Security Best Practices

1. **Password Security**: Bcrypt hashing with password_hash()
2. **SQL Injection**: PDO prepared statements on all queries
3. **Session Management**: Secure session handling with role checks
4. **File Upload**: Type validation, size limits, secure storage
5. **Input Validation**: Frontend and backend validation
6. **CSRF Protection**: Consider adding token validation for forms
7. **XSS Prevention**: htmlspecialchars() on all output

## 🛠️ Usage Guide

### For Customers

1. **Register**: Create account on registration page
2. **Browse**: Explore available vehicles with filters
3. **Book**: Select vehicle, dates, and times
4. **Check-Out**: Upload photos before rental
5. **Check-In**: Upload photos after rental
6. **Track**: View rental history and damage reports

### For Admins

1. **Dashboard**: Monitor key metrics and alerts
2. **Rentals**: Approve/reject booking requests
3. **Vehicles**: Manage fleet inventory
4. **Customers**: View profiles and rental history
5. **Damage**: Create and track damage reports
6. **Alerts**: Monitor repeat offenders
7. **Schedules**: Manage availability

## 📊 Key Metrics

The dashboard displays:
- Total rentals today
- Active rentals count
- Pending approvals
- Available vehicles
- Today's damage reports
- Total damage reports
- Repeat offenders count

## 🔄 Workflow

### Rental Workflow
1. Customer browses vehicles
2. Customer books rental (status: pending)
3. Admin approves/rejects (status: approved/cancelled)
4. Customer uploads check-out photos
5. Rental becomes active (status: active)
6. Customer uploads check-in photos
7. Admin reviews for damage
8. Rental completed (status: completed)

### Damage Workflow
1. Admin reviews check-in photos
2. Admin creates damage report
3. Report linked to rental and customer
4. Customer damage count incremented
5. If count > 2, customer flagged as repeat offender
6. Repeat offender badge shown on dashboard

## 📝 Notes

- Default admin account: admin@rentguard.com / admin123
- All passwords are hashed with bcrypt
- Photo uploads are stored in organized directories
- Damage counts are automatically tracked
- Repeat offender alerts trigger at 3+ incidents
- All timestamps are in UTC

## 🤝 Support & Maintenance

### Common Issues

**Database Connection Error**
- Check database credentials in config/database.php
- Ensure MySQL server is running
- Verify database name is correct

**Upload Errors**
- Check folder permissions (755)
- Verify disk space availability
- Check file size limits

**Session Issues**
- Clear browser cookies
- Check PHP session configuration
- Verify session directory permissions

## 📄 License

This project is provided as-is for educational and commercial use.

## 👨‍💻 Developer Notes

- Code is well-commented for easy maintenance
- Follow PSR-12 coding standards
- Use prepared statements for all queries
- Validate all user inputs
- Test on multiple browsers
- Keep dependencies updated

---

**RentGuard v1.0** - Professional Car Rental Management System
Built with ❤️ for secure and efficient vehicle rental operations.
