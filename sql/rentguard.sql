-- RentGuard Database Schema

CREATE DATABASE IF NOT EXISTS rentguard;
USE rentguard;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    -- Add profile_photo column to users table
ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER role;
-- Add phone column to users table
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email;

-- Update existing users with sample phone numbers (optional)
UPDATE users SET phone = '09' || FLOOR(RAND() * 1000000000) WHERE phone IS NULL AND role = 'customer';
);

-- Customers table (extended profile)
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    license_number VARCHAR(50),
    birthdate DATE,
    damage_incidents_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    year INT,
    type VARCHAR(50),
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    current_mileage INT DEFAULT 0,
    photo_url VARCHAR(255),
    price_per_day DECIMAL(10, 2) DEFAULT 0.00
    -- Add passenger_capacity column to vehicles table
ALTER TABLE vehicles ADD COLUMN passenger_capacity INT DEFAULT 4 AFTER type;
);

-- Rentals table
CREATE TABLE IF NOT EXISTS rentals (
    rental_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time VARCHAR(50),
    return_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_dates (pickup_date, return_date)
);
-- Add schedule_id column to rentals table
ALTER TABLE rentals ADD COLUMN schedule_id INT NULL AFTER vehicle_id;

-- Add foreign key constraint (optional but recommended)
ALTER TABLE rentals ADD FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE SET NULL;

-- Add index for better performance
ALTER TABLE rentals ADD INDEX idx_schedule (schedule_id);

-- Rental Photos table
CREATE TABLE IF NOT EXISTS rental_photos (
    photo_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    type ENUM('before', 'after') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE CASCADE
);

-- Damage Reports table
CREATE TABLE IF NOT EXISTS damage_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    admin_notes TEXT,
    report_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE CASCADE,
    INDEX idx_rental (rental_id),
    INDEX idx_severity (severity),
    INDEX idx_date (report_date)
);


-- Create schedules table if not exists
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    available_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    is_booked TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (vehicle_id, available_date, time_slot),
    INDEX idx_date (available_date),
    INDEX idx_booked (is_booked)
);




--NEW ADD 
-- Drop existing table if needed (WARNING: This will delete existing alerts)
-- DROP TABLE IF EXISTS sos_alerts;

DROP TABLE IF EXISTS sos_alerts;


-- Create sos_alerts table
--CREATE TABLE IF NOT EXISTS sos_alerts (
   -- sos_id INT AUTO_INCREMENT PRIMARY KEY,
   -- user_id INT NOT NULL,
   --- rental_id INT NULL,
    --alert_type ENUM('emergency', 'accident', 'mechanical', 'assistance') DEFAULT 'emergency',
   -- message TEXT,
   -- location_lat DECIMAL(10, 8),
   -- location_lng DECIMAL(11, 8),
   -- status ENUM('pending', 'responded', 'resolved') DEFAULT 'pending',
    --admin_response TEXT,
    --created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    --responded_at DATETIME NULL,
    --FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    --FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE SET NULL,
   -- INDEX idx_status (status),
    --INDEX idx_user (user_id),
   -- INDEX idx_created (created_at)--
); --IGNORE--

-- Insert sample data for testing
--INSERT INTO sos_alerts (user_id, alert_type, message, location_lat, location_lng, status, created_at)
--SELECT 
   --- id,
    --'emergency',
   -- 'Need immediate roadside assistance!',
   -- 7.4467,
    --125.8099,
    --'pending',
   -- NOW()
--FROM users WHERE role = 'customer' LIMIT 1;
-- Create sos_alerts table
CREATE TABLE IF NOT EXISTS sos_alerts (
    sos_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rental_id INT NULL,
    alert_type ENUM('emergency', 'accident', 'mechanical', 'assistance') DEFAULT 'emergency',
    message TEXT,
    location_lat DECIMAL(10, 8),
    location_lng DECIMAL(11, 8),
    status ENUM('pending', 'responded', 'resolved') DEFAULT 'pending',
    admin_response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

-- Insert sample data for testing
INSERT INTO sos_alerts (user_id, alert_type, message, location_lat, location_lng, status, created_at)
SELECT 
    id,
    'emergency',
    'Need immediate roadside assistance!',
    7.4467,
    125.8099,
    'pending',
    NOW()
FROM users WHERE role = 'customer' LIMIT 1;
-- Email verification tokens table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_code (code)
);
-- Check the expires_at column in email_verifications table
ALTER TABLE email_verifications MODIFY expires_at DATETIME NOT NULL;

-- Optional: Add an index for better performance on expiration queries
ALTER TABLE email_verifications ADD INDEX idx_expires_at (expires_at);


-- Add verified column to users table (if not exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL;

-- Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_code (code),
    INDEX idx_expires (expires_at)
);


-- Insert default admin (password: admin123) - plaintext as requested
INSERT INTO users (name, email, password, role) 
VALUES 
('System Admin', 'admin@rentguard.com', 'admin123', 'admin')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role);