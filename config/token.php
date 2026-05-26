<?php
function generateToken() {
    // Generate cryptographically secure random token
    return bin2hex(random_bytes(32));
}

function generateVerificationCode() {
    // Generate 6-digit numeric code
    return sprintf("%06d", random_int(0, 999999));
}

function storeVerificationToken($pdo, $userId, $token, $code) {
    // Delete any existing unverified tokens for this user
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Set expiry to 15 MINUTES from now
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Insert new token
    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, token, code, expires_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $token, $code, $expiresAt]);
}

function verifyEmailCode($pdo, $code) {
    // Debug logging
    error_log("Verifying code: " . $code);
    
    $stmt = $pdo->prepare("
        SELECT ev.*, u.name, u.email 
        FROM email_verifications ev 
        JOIN users u ON ev.user_id = u.id 
        WHERE ev.code = ? 
        ORDER BY ev.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging
    if ($result) {
        error_log("Found record for code: " . $code);
        error_log("Expires at: " . $result['expires_at']);
        error_log("Current time: " . date('Y-m-d H:i:s'));
        
        // Check if expired
        $expiresAt = strtotime($result['expires_at']);
        $now = time();
        
        if ($expiresAt > $now) {
            error_log("Code is valid (not expired)");
            return $result;
        } else {
            error_log("Code has expired");
            return false;
        }
    } else {
        error_log("No record found for code: " . $code);
        return false;
    }
}

function verifyEmailToken($pdo, $token) {
    $stmt = $pdo->prepare("
        SELECT ev.*, u.name, u.email 
        FROM email_verifications ev 
        JOIN users u ON ev.user_id = u.id 
        WHERE ev.token = ? AND ev.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function completeVerification($pdo, $userId) {
    // Mark user as verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Delete verification tokens
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    return true;
}
?>