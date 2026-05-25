<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/encryption.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = 'customer'; // Force role to customer only
    $terms = isset($_POST['terms']) ? true : false;

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) != 12) {
        $error = 'Password must be exactly 12 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms & Conditions.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Email already registered.';
        } else {
            $plain_password = $password;

            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $plain_password, $role]);
                
                $user_id = $pdo->lastInsertId();

                // Always create customer record since role is always 'customer'
                $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
                $stmt->execute([$user_id]);

                $success = 'Registration successful! Redirecting to login...';
                header("refresh:2;url=login.php");
                exit();
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
    
    $_POST = array();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Tagum City Rent Car Jhunrider</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0B0F14 0%, #111827 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            --white: #FFFFFF;
            --success: #10B981;
            --danger: #EF4444;
        }

        /* Split Layout Container */
        .register-wrapper {
            display: flex;
            max-width: 1100px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Left Panel - Branding */
        .branding-panel {
            background: linear-gradient(135deg, var(--charcoal) 0%, var(--charcoal-deep) 100%);
            width: 40%;
            padding: 50px 40px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .branding-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800') center/cover;
            opacity: 0.1;
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 60px;
        }

        .brand-logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 8px;
        }

        .brand-logo span {
            color: white;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
        }

        .brand-text h2 {
            color: white;
            font-size: 32px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 20px;
        }

        .brand-text .green-line {
            width: 60px;
            height: 4px;
            background: var(--primary-green);
            margin-bottom: 20px;
            border-radius: 2px;
        }

        .brand-text p {
            color: rgba(255,255,255,0.7);
            font-size: 15px;
            line-height: 1.6;
        }

        .brand-features {
            margin-top: 50px;
        }

        .brand-features .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }

        .brand-features .feature i {
            color: var(--primary-green);
            font-size: 18px;
        }

        /* Right Panel - Form */
        .form-panel {
            width: 60%;
            padding: 50px 40px;
            background: var(--card-bg);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            color: white;
            margin-bottom: 8px;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 14px;
        }

        /* Input wrapper for proper icon positioning */
        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 10;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            z-index: 10;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .input-wrapper .toggle-password:hover {
            color: var(--primary-green);
        }

        .form-control {
            height: 50px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0 15px;
            font-size: 14px;
            background: var(--charcoal-deep);
            transition: all 0.3s ease;
            width: 100%;
            color: white;
        }

        /* Padding for fields with left icon */
        .input-wrapper.has-left-icon .form-control {
            padding-left: 42px;
        }

        /* Padding for password fields with both left lock and right eye */
        .input-wrapper.password-field .form-control {
            padding-left: 42px;
            padding-right: 42px;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            background: var(--charcoal);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* Customer Info Box */
        .customer-info {
            background: rgba(22, 163, 74, 0.1);
            border: 1px solid var(--primary-green);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-info i {
            color: var(--primary-green);
            font-size: 18px;
        }

        .customer-info span {
            color: var(--text-dark);
            font-size: 13px;
        }

        /* Password Requirements */
        .password-requirements {
            margin-top: 10px;
            font-size: 12px;
        }

        .requirement {
            color: var(--text-muted);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement.valid {
            color: var(--success);
        }

        .requirement i {
            width: 14px;
            font-size: 12px;
        }

        /* Terms Checkbox */
        .terms-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .terms-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-green);
        }

        .terms-checkbox label {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }

        .terms-checkbox a {
            color: var(--primary-green);
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        /* Primary Button */
        .btn-register {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 10px;
            height: 50px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-register:hover:not(:disabled) {
            background: var(--primary-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 12px 15px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #FCA5A5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #86EFAC;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .register-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            .branding-panel, .form-panel {
                width: 100%;
            }
            .branding-panel {
                padding: 40px;
                text-align: center;
            }
            .brand-text .green-line {
                margin-left: auto;
                margin-right: auto;
            }
            .brand-features {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .form-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <!-- Left Branding Panel -->
        <div class="branding-panel">
            <div class="brand-content">
                <div class="brand-logo">
                    <img src="../uploads/profiles/RentCar.png" alt="TCRCJ">
                    <span>Tagum City Rent Car Jhunrider</span>
                </div>
                <div class="brand-text">
                    <h2>Join Now</h2>
                    <div class="green-line"></div>
                    <p>Your journey starts here. Register today and experience hassle-free car rentals with premium service.</p>
                </div>
                <div class="brand-features">
                    <div class="feature"><i class="fas fa-check-circle"></i> Wide Vehicle Selection</div>
                    <div class="feature"><i class="fas fa-check-circle"></i> Affordable Rates</div>
                    <div class="feature"><i class="fas fa-check-circle"></i> 24/7 Customer Support</div>
                    <div class="feature"><i class="fas fa-check-circle"></i> Secure Bookings</div>
                </div>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="form-panel">
            <div class="form-header">
                <h1>Create an account</h1>
                <p>Fill in your details to get started</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" autocomplete="off">
                <!-- Full Name Field -->
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-wrapper has-left-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control" name="name" id="fullname" placeholder="Enter your full name" required>
                    </div>
                </div>

                <!-- Email Field -->
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper has-left-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" required>
                    </div>
                </div>

                <!-- Customer Info Box (Replaces Role Selector) -->
                <div class="customer-info">
                    <i class="fas fa-user-circle"></i>
                    <span>You are registering as a <strong>Customer</strong>. You'll be able to browse and rent vehicles once registered.</span>
                </div>

                <!-- Password Field (Lock icon left, Eye icon right) -->
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper password-field">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" maxlength="12" placeholder="Enter password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                    </div>
                    <div class="password-requirements" id="passwordRequirements">
                        <div class="requirement" id="reqLength"><i class="fas fa-circle"></i> Exactly 12 characters</div>
                        <div class="requirement" id="reqUppercase"><i class="fas fa-circle"></i> At least 1 uppercase letter</div>
                        <div class="requirement" id="reqLowercase"><i class="fas fa-circle"></i> At least 1 lowercase letter</div>
                        <div class="requirement" id="reqNumber"><i class="fas fa-circle"></i> At least 1 number</div>
                        <div class="requirement" id="reqSpecial"><i class="fas fa-circle"></i> At least 1 special character</div>
                    </div>
                </div>

                <!-- Confirm Password Field (Lock icon left, Eye icon right) -->
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-wrapper password-field">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="12" placeholder="Confirm password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <div id="passwordMatch" class="mt-2" style="font-size: 12px;"></div>
                </div>

                <!-- Terms Checkbox -->
                <div class="terms-checkbox">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn btn-register" id="submitBtn">Create Account</button>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password validation functions
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function updateRequirements(password) {
            const isValidLength = password.length === 12;
            const isValidUppercase = /[A-Z]/.test(password);
            const isValidLowercase = /[a-z]/.test(password);
            const isValidNumber = /[0-9]/.test(password);
            const isValidSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            updateRequirementUI('reqLength', isValidLength);
            updateRequirementUI('reqUppercase', isValidUppercase);
            updateRequirementUI('reqLowercase', isValidLowercase);
            updateRequirementUI('reqNumber', isValidNumber);
            updateRequirementUI('reqSpecial', isValidSpecial);

            return isValidLength && isValidUppercase && isValidLowercase && isValidNumber && isValidSpecial;
        }

        function updateRequirementUI(elementId, isValid) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            if (isValid) {
                element.classList.add('valid');
                icon.classList.remove('fa-circle');
                icon.classList.add('fa-check-circle');
            } else {
                element.classList.remove('valid');
                icon.classList.remove('fa-check-circle');
                icon.classList.add('fa-circle');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return true;
            }

            if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #10B981;"></i> Passwords match!';
                matchDiv.style.color = '#10B981';
                return true;
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #EF4444;"></i> Passwords do not match!';
                matchDiv.style.color = '#EF4444';
                return false;
            }
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const isPasswordValid = updateRequirements(password);
            const doPasswordsMatch = password === confirmPassword;
            const submitBtn = document.getElementById('submitBtn');
            
            submitBtn.disabled = !(isPasswordValid && doPasswordsMatch && password.length === 12);
        }

        document.getElementById('password').addEventListener('input', function() {
            updateRequirements(this.value);
            checkPasswordMatch();
            validateForm();
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            checkPasswordMatch();
            validateForm();
        });

        // Initial validation
        validateForm();
    </script>
</body>
</html>