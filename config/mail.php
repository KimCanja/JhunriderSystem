<?php
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($toEmail, $toName, $code, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - UPDATE WITH YOUR EMAIL SETTINGS
        $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Set to DEBUG_SERVER for testing
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';        // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jhunridertagumrentcar@gmail.com';  // Your email
        $mail->Password   = 'qrvw djzt ndsu byxs';     // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Tagum City Rent Car');
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Tagum City Rent Car';
        
        // Email body
        $verificationLink = "http://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . urlencode($token);
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #16A34A; padding-bottom: 20px; }
                .code-box { background: #f0fdf4; border: 2px solid #16A34A; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .code { font-size: 32px; font-weight: bold; color: #16A34A; letter-spacing: 5px; }
                .button { display: inline-block; background: #16A34A; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Tagum City Rent Car Jhunrider</h2>
                    <p>Verify Your Email Address</p>
                </div>
                <p>Hello <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                <p>Thank you for registering! Please verify your email address to complete your registration.</p>
                
                <div class='code-box'>
                    <p>Your verification code is:</p>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <p>Or click the button below:</p>
                <div style='text-align: center;'>
                    <a href='" . $verificationLink . "' class='button'>Verify Email</a>
                </div>
                
                <p>This code will expire in <strong>24 hours</strong>.</p>
                <div class='footer'>
                    <p>If you didn't create an account, please ignore this email.</p>
                    <p>&copy; 2025 Tagum City Rent Car Jhunrider. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Your verification code is: $code\n\nOr visit: $verificationLink\n\nThis code expires in 24 hours.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Alternative: Using PHP mail() function (simpler but less reliable)
function sendVerificationEmailSimple($toEmail, $toName, $code, $token) {
    $subject = "Verify Your Email - Tagum City Rent Car";
    $verificationLink = "http://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . urlencode($token);
    
    $message = "
    Hello $toName,
    
    Thank you for registering!
    
    Your verification code is: $code
    
    Or click this link to verify: $verificationLink
    
    This code expires in 24 hours.
    
    Regards,
    Tagum City Rent Car Jhunrider
    ";
    
    $headers = "From: no-reply@yourdomain.com\r\n";
    $headers .= "Reply-To: support@yourdomain.com\r\n";
    
    return mail($toEmail, $subject, $message, $headers);
}
?>