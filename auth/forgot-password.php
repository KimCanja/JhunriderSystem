<?php
session_start();
require_once '../config/database.php';
require_once '../config/token.php';
require_once '../config/mail.php';

$error = '';
$success = '';
$step = 'request'; // request, verify, reset

// Step 1: Request password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate verification code and token
            $token = generateToken();
            $code = generateVerificationCode();
            
            // Store in password_resets table
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, code, expires_at) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
            ");
            $stmt->execute([$user['id'], $token, $code]);
            
            // Send email with code
            if (sendPasswordResetEmail($email, $user['name'], $code, $token)) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['id'];
                $step = 'verify';
                $success = 'A verification code has been sent to your email. Valid for 15 minutes.';
            } else {
                $error = 'Failed to send reset email. Please try again.';
            }
        } else {
            $error = 'No account found with this email address.';
        }
    }
}

// Step 2: Verify code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['verification_code'] ?? '');
    $email = $_SESSION['reset_email'] ?? '';
    
    if (empty($code)) {
        $error = 'Verification code is required.';
    } else {
        // Verify the code
        $stmt = $pdo->prepare("
            SELECT pr.*, u.name, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.code = ? AND pr.expires_at > NOW() AND u.email = ?
        ");
        $stmt->execute([$code, $email]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            $_SESSION['reset_token'] = $reset['token'];
            $step = 'reset';
            $success = 'Code verified! Please enter your new password.';
        } else {
            $error = 'Invalid or expired verification code. Please request a new one.';
        }
    }
}

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_SESSION['reset_token'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please enter your new password.';
    } elseif (strlen($new_password) != 12) {
        $error = 'Password must be exactly 12 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $error = 'Password must contain at least one special character.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Verify the reset token is still valid
        $stmt = $pdo->prepare("
            SELECT * FROM password_resets 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            // Update user password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $reset['user_id']]);
            
            // Delete used reset token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            // Clear session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_token']);
            
            $success = 'Password changed successfully! Redirecting to login...';
            header("refresh:3;url=login.php");
            exit();
        } else {
            $error = 'Reset token expired. Please request a new password reset.';
            $step = 'request';
        }
    }
}

// Handle resend code
if (isset($_GET['resend'])) {
    $email = $_SESSION['reset_email'] ?? '';
    if ($email) {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $token = generateToken();
            $code = generateVerificationCode();
            
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, code, expires_at) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
            ");
            $stmt->execute([$user['id'], $token, $code]);
            
            if (sendPasswordResetEmail($email, $user['name'], $code, $token)) {
                $success = 'New verification code sent to your email! Valid for 15 minutes.';
            } else {
                $error = 'Failed to send email. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Tagum City Rent Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0B0F14 0%, #111827 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .forgot-container {
            max-width: 500px;
            width: 100%;
            background: #1F2937;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(22, 163, 74, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-circle i {
            font-size: 40px;
            color: #16A34A;
        }
        h2 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        .subtitle {
            text-align: center;
            color: #9CA3AF;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-label {
            color: #F3F4F6;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            height: 50px;
            border: 1px solid #374151;
            border-radius: 10px;
            background: #0B0F14;
            color: white;
        }
        .form-control:focus {
            border-color: #16A34A;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .btn-primary {
            background: #16A34A;
            border: none;
            height: 50px;
            font-weight: 600;
            width: 100%;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background: #15803D;
        }
        .btn-secondary {
            background: #374151;
            border: none;
            height: 50px;
            font-weight: 600;
            width: 100%;
            border-radius: 10px;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
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
        .code-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #9CA3AF;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #16A34A;
        }
        .requirement {
            font-size: 12px;
            color: #9CA3AF;
            margin-top: 5px;
        }
        .requirement.valid {
            color: #10B981;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="icon-circle">
            <i class="fas fa-key"></i>
        </div>
        
        <h2>
            <?php if ($step == 'request'): ?>
                Forgot Password?
            <?php elseif ($step == 'verify'): ?>
                Verify Code
            <?php else: ?>
                Reset Password
            <?php endif; ?>
        </h2>
        
        <p class="subtitle">
            <?php if ($step == 'request'): ?>
                Enter your email address and we'll send you a verification code.
            <?php elseif ($step == 'verify'): ?>
                Enter the 6-digit code sent to your email.
            <?php else: ?>
                Enter your new password.
            <?php endif; ?>
        </p>
        
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
        
        <?php if ($step == 'request'): ?>
            <!-- Step 1: Request Reset -->
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>
                <button type="submit" name="request_reset" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Verification Code
                </button>
            </form>
            
        <?php elseif ($step == 'verify'): ?>
            <!-- Step 2: Verify Code -->
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="verification_code" class="form-control code-input" 
                           placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                </div>
                <button type="submit" name="verify_code" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Verify Code
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="?resend=1" class="text-muted" style="font-size: 14px;">
                    <i class="fas fa-redo-alt"></i> Resend Code
                </a>
            </div>
            
        <?php else: ?>
            <!-- Step 3: Reset Password -->
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" 
                           placeholder="Enter new password" maxlength="12" required>
                    <div class="requirement" id="reqLength">
                        <i class="fas fa-circle"></i> Exactly 12 characters
                    </div>
                    <div class="requirement" id="reqUppercase">
                        <i class="fas fa-circle"></i> At least 1 uppercase letter
                    </div>
                    <div class="requirement" id="reqLowercase">
                        <i class="fas fa-circle"></i> At least 1 lowercase letter
                    </div>
                    <div class="requirement" id="reqNumber">
                        <i class="fas fa-circle"></i> At least 1 number
                    </div>
                    <div class="requirement" id="reqSpecial">
                        <i class="fas fa-circle"></i> At least 1 special character
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="Confirm new password" maxlength="12" required>
                    <div id="passwordMatch" class="requirement"></div>
                </div>
                
                <button type="submit" name="reset_password" class="btn-primary" id="resetBtn" disabled>
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
    
    <script>
        <?php if ($step == 'reset'): ?>
        // Password validation
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
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return true;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match!';
                matchDiv.classList.add('valid');
                return true;
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match!';
                matchDiv.classList.remove('valid');
                return false;
            }
        }
        
        function validateForm() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const isPasswordValid = updateRequirements(password);
            const doPasswordsMatch = password === confirmPassword && password.length > 0;
            const resetBtn = document.getElementById('resetBtn');
            
            resetBtn.disabled = !(isPasswordValid && doPasswordsMatch);
        }
        
        document.getElementById('new_password').addEventListener('input', validateForm);
        document.getElementById('confirm_password').addEventListener('input', function() {
            checkPasswordMatch();
            validateForm();
        });
        
        validateForm();
        <?php endif; ?>
    </script>
</body>
</html>