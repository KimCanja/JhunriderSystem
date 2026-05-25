<?php

define('AES_KEY', 'your_very_strong_secret_key_32_chars_min_!@#2026'); 

// You can also generate a random one once:
// $key = bin2hex(random_bytes(32)); echo $key;

function aes_encrypt($plain_text) {
    if (empty($plain_text)) return '';

    $key = AES_KEY;
    $method = 'AES-256-CBC';
    
    // Generate random IV (Initialization Vector)
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    // Encrypt
    $encrypted = openssl_encrypt(
        $plain_text, 
        $method, 
        $key, 
        OPENSSL_RAW_DATA, 
        $iv
    );
    
    // Combine IV + encrypted data and base64 encode for safe database storage
    $combined = base64_encode($iv . $encrypted);
    
    return $combined;
}

function aes_decrypt($encrypted_text) {
    if (empty($encrypted_text)) return '';
    
    $key = AES_KEY;
    $method = 'AES-256-CBC';
    
    // Decode from base64
    $combined = base64_decode($encrypted_text);
    
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($combined, 0, $iv_length);
    $encrypted = substr($combined, $iv_length);
    
    // Decrypt
    $decrypted = openssl_decrypt(
        $encrypted, 
        $method, 
        $key, 
        OPENSSL_RAW_DATA, 
        $iv
    );
    
    return $decrypted !== false ? $decrypted : '';
}
?>