<?php
// auth/logout.php
session_start();

// Destroy all session data
session_destroy();

// Direct redirect with absolute path
header("Location: /JhunriderSystem/index.php");
exit();
?>