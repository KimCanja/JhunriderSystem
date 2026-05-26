<?php
require_once '../config/database.php';

$email = $_GET['email'] ?? '';

if ($email) {
    $stmt = $pdo->prepare("SELECT id, name, email, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Checking email: " . htmlspecialchars($email) . "\n\n";
    if ($user) {
        echo "User found:\n";
        print_r($user);
    } else {
        echo "User not found!";
    }
    echo "</pre>";
}
?>

<form method="GET">
    <input type="email" name="email" placeholder="Enter email to check" required>
    <button type="submit">Check User</button>
</form>