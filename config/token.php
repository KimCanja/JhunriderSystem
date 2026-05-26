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
    
    // Set expiry to 24 hours from now
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Insert new token
    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, token, code, expires_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $token, $code, $expiresAt]);
}

function verifyEmailCode($pdo, $code) {
    $stmt = $pdo->prepare("
        SELECT ev.*, u.name, u.email 
        FROM email_verifications ev 
        JOIN users u ON ev.user_id = u.id 
        WHERE ev.code = ? AND ev.expires_at > NOW()
        ORDER BY ev.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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
    $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE, verified_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Delete verification tokens
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    return true;
}
?>