<?php
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        // Get user from database including verification status
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if email is verified
            if (!$user['is_verified']) {
                $error = 'Please verify your email before logging in. 
                          <a href="verify-email-pending.php?email=' . urlencode($user['email']) . '" class="alert-link" style="color: #16A34A; text-decoration: underline;">Click here to verify</a>';
            } 
            // Plain text password comparison (as used in your original code)
            elseif ($password === $user['password']) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: /JhunriderSystem/admin/dashboard.php");
                    exit();
                } else {
                    header("Location: /JhunriderSystem/customer/dashboard.php");
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Email not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tagum City Rent Car Jhunrider</title>
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
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        /* Split Layout Container */
        .login-wrapper {
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 380px;
            width: 100%;
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

        /* Input wrapper for positioning */
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

        .input-wrapper .clear-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
            display: none;
            z-index: 10;
        }

        .input-wrapper .clear-btn:hover {
            color: var(--danger);
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
            color: var(--white) !important;  /* CHANGED: Force white text */
        }

        /* Padding for email with icon */
        .input-wrapper.email-field .form-control {
            padding-left: 42px;
            padding-right: 42px;
        }

        /* Padding for password with icon on left and eye on right */
        .input-wrapper.password-field .form-control {
            padding-left: 42px;
            padding-right: 42px;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            background: var(--charcoal);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            outline: none;
            color: var(--white) !important;  /* CHANGED: Keep white on focus */
        }

        /* Ensure input text stays white when autofilled by browser */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: white !important;
            -webkit-box-shadow: 0 0 0px 1000px var(--charcoal-deep) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* Forgot Password */
        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-green);
        }

        /* Primary Button */
        .btn-login {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 10px;
            height: 50px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--primary-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .register-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Alert Messages */
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

        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .alert-link {
            color: #16A34A;
            text-decoration: underline;
            font-weight: 600;
        }

        .alert-link:hover {
            color: #22C55E;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 480px;
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
            .login-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Branding Panel -->
        <div class="branding-panel">
            <div class="brand-content">
                <div class="brand-logo">
                    <img src="../uploads/profiles/RentCar.png" alt="TCRCJ">
                    <span>Tagum City Rent Car Jhunrider</span>
                </div>
                <div class="brand-text">
                    <h2>Welcome Back</h2>
                    <div class="green-line"></div>
                    <p>Book. Drive. Repeat.</p>
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
            <div class="login-card">
                <div class="form-header">
                    <h1>Sign In</h1>
                    <p>Enter your credentials to access your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-wrapper email-field">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autocomplete="off">
                            <span class="clear-btn" id="clearEmail" onclick="clearField('email')">
                                <i class="fas fa-times-circle"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper password-field">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>
                        <div class="forgot-password">
                            <a href="forgot-password.php">Forgot Password?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">Sign In</button>
                </form>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function clearField(fieldId) {
            const field = document.getElementById(fieldId);
            field.value = '';
            field.focus();
            
            const clearBtn = document.getElementById('clearEmail');
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
        }

        // Show/hide clear button for email field
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.addEventListener('input', function() {
                const clearBtn = document.getElementById('clearEmail');
                if (this.value.length > 0) {
                    clearBtn.style.display = 'block';
                } else {
                    clearBtn.style.display = 'none';
                }
            });
            
            // Trigger on page load if there's a value
            if (emailField.value.length > 0) {
                const clearBtn = document.getElementById('clearEmail');
                if (clearBtn) clearBtn.style.display = 'block';
            }
        }

        // Form submit loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('loginBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>