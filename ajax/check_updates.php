<?php
header('Content-Type: application/json');
$files = ['index.php', 'auth/register.php', 'auth/login.php'];
$latest_mtime = 0;
foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        if ($mtime > $latest_mtime) {
            $latest_mtime = $mtime;
        }
    }
}
echo json_encode(['modified' => $latest_mtime]);
?>