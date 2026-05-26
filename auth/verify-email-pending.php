<?php
session_start();
require_once '../config/database.php';
require_once '../config/token.php';
require_once '../config/mail.php';

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
    
    $verification = verifyEmailCode($pdo, $code);
    
    if ($verification) {
        completeVerification($pdo, $verification['user_id']);
        unset($_SESSION['pending_verification_user_id']);
        unset($_SESSION['pending_verification_email']);
        
        $success = 'Email verified successfully! Redirecting to login...';
        header("refresh:3;url=login.php");
    } else {
        $error = 'Invalid or expired verification code. Please request a new one.';
    }
}


// Handle resend code
if (isset($_GET['resend'])) {
    // Make sure to select the 'name' field
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = FALSE");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $token = generateToken();
        $code = generateVerificationCode();
        storeVerificationToken($pdo, $user['id'], $token, $code);
        
        // Now $user['name'] exists
        sendVerificationEmail($email, $user['name'], $code, $token);
        $success = 'New verification code sent to your email!';
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
        }
        .email-display {
            color: #16A34A;
            font-weight: 600;
        }
        .code-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .btn-verify {
            background: #16A34A;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
        }
        .btn-verify:hover {
            background: #15803D;
        }
        .resend-link {
            color: #16A34A;
            text-decoration: none;
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
        
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label text-white">Enter Verification Code</label>
                <input type="text" class="form-control code-input" name="verification_code" 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <button type="submit" class="btn-verify">Verify Email</button>
        </form>
        
        <div class="mt-4">
            <p class="text-muted">Didn't receive the code?</p>
            <a href="?resend=1&email=<?php echo urlencode($email); ?>" class="resend-link">Resend Verification Code</a>
        </div>
        
        <div class="mt-3">
            <a href="login.php" class="text-muted" style="font-size: 14px;">Back to Login</a>
        </div>
    </div>
</body>
</html>