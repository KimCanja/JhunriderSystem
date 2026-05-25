<?php
header("Content-Type: application/json");
// Sample stats data - replace with database queries
$stats = [
    ["icon" => "fas fa-car", "number" => "50+", "label" => "Premium Vehicles"],
    ["icon" => "fas fa-users", "number" => "1000+", "label" => "Happy Customers"],
    ["icon" => "fas fa-clock", "number" => "24/7", "label" => "Support Available"],
    ["icon" => "fas fa-map-marker-alt", "number" => "5+", "label" => "Convenient Locations"]
];
echo json_encode(["success" => true, "stats" => $stats]);
?>