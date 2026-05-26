<?php
session_start();
require_once '../config/database.php';
require_once '../config/token.php';
require_once '../config/mail.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$email = $_GET['email'] ?? $_SESSION['pending_verification_email'] ?? '';
$error = '';
$success = '';

if (empty($email)) {
    header("Location: login.php");
    exit();
}

// Handle code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $code = trim($_POST['verification_code']);
    
    // Debug logging
    error_log("Attempting to verify code: " . $code);
    
    // First, check if there's any verification record for this email
    $stmt = $pdo->prepare("
        SELECT ev.*, u.name, u.email 
        FROM email_verifications ev 
        JOIN users u ON ev.user_id = u.id 
        WHERE u.email = ?
        ORDER BY ev.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $verificationRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verificationRecord) {
        error_log("Found verification record for email: " . $email);
        error_log("Stored code: " . $verificationRecord['code']);
        error_log("Entered code: " . $code);
        error_log("Expires at: " . $verificationRecord['expires_at']);
        error_log("Current time: " . date('Y-m-d H:i:s'));
    } else {
        error_log("No verification record found for email: " . $email);
    }
    
    // Now verify the code
    $verification = verifyEmailCode($pdo, $code);
    
    if ($verification) {
        error_log("Verification successful for user: " . $verification['user_id']);
        completeVerification($pdo, $verification['user_id']);
        unset($_SESSION['pending_verification_user_id']);
        unset($_SESSION['pending_verification_email']);
        
        $success = 'Email verified successfully! Redirecting to login...';
        header("refresh:3;url=login.php");
        exit();
    } else {
        error_log("Verification failed for code: " . $code);
        $error = 'Invalid or expired verification code. Please request a new one.';
    }
}

// Handle resend code
if (isset($_GET['resend'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $token = generateToken();
        $code = generateVerificationCode();
        storeVerificationToken($pdo, $user['id'], $token, $code);
        
        if (sendVerificationEmail($email, $user['name'], $code, $token)) {
            $success = 'New verification code sent to your email! Valid for 15 minutes.';
            error_log("New code sent to {$email}: {$code}");
        } else {
            $error = 'Failed to send verification email. Please try again.';
        }
    } else {
        $error = 'User not found or already verified.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Tagum City Rent Car</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .verify-container {
            max-width: 500px;
            width: 100%;
            background: #1F2937;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            text-align: center;
        }
        .verify-icon {
            width: 80px;
            height: 80px;
            background: rgba(22, 163, 74, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .verify-icon i {
            font-size: 40px;
            color: #16A34A;
        }
        h2 {
            color: white;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        .email-display {
            color: #16A34A;
            font-weight: 600;
            word-break: break-all;
        }
        .code-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
            background: #0B0F14;
            border: 1px solid #374151;
            color: white;
            height: 60px;
        }
        .code-input:focus {
            border-color: #16A34A;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .btn-verify {
            background: #16A34A;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-verify:hover {
            background: #15803D;
            transform: translateY(-2px);
        }
        .resend-link {
            color: #16A34A;
            text-decoration: none;
            font-weight: 500;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
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
        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .text-muted {
            color: #9CA3AF !important;
        }
        .form-label {
            color: #F3F4F6;
            font-weight: 500;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-icon">
            <i class="fas fa-envelope"></i>
        </div>
        <h2>Verify Your Email</h2>
        <p class="text-muted">We've sent a verification code to</p>
        <p class="email-display"><?php echo htmlspecialchars($email); ?></p>
        
        <div class="alert alert-warning mt-2">
            <i class="fas fa-hourglass-half"></i> 
            <strong>Code expires in 15 minutes!</strong> Please verify quickly.
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success mt-3">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label">Enter Verification Code</label>
                <input type="text" class="form-control code-input" name="verification_code" 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Verify Email
            </button>
        </form>
        
        <div class="mt-4">
            <p class="text-muted">Didn't receive the code?</p>
            <a href="?resend=1&email=<?php echo urlencode($email); ?>" class="resend-link">
                <i class="fas fa-redo-alt"></i> Resend Verification Code
            </a>
        </div>
        
        <div class="mt-3">
            <a href="login.php" class="text-muted" style="font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        // Auto-submit when 6 digits are entered
        const codeInput = document.querySelector('input[name="verification_code"]');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                // Remove any non-digits
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>