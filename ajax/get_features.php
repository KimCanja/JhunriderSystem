<?php
header("Content-Type: application/json");
$features = [
    ["icon" => "fas fa-car-side", "title" => "Wide Vehicle Selection", "description" => "Choose from SUVs, Sedans, Luxury cars, and more"],
    ["icon" => "fas fa-tag", "title" => "Affordable Pricing", "description" => "Best rates with no hidden fees"],
    ["icon" => "fas fa-map-marker-alt", "title" => "Multiple Locations", "description" => "Convenient pickup points across the city"],
    ["icon" => "fas fa-lock", "title" => "Secure Booking", "description" => "Safe and encrypted transactions"]
];
echo json_encode(["success" => true, "features" => $features]);
?>