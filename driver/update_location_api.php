<?php
require "../config/db.php";

$driver_id = $_POST["driver_id"];
$lat = $_POST["lat"];
$lng = $_POST["lng"];

// Insert or Update latest location
$stmt = $pdo->prepare("
    INSERT INTO driver_locations (driver_id, latitude, longitude, updated_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        latitude = VALUES(latitude),
        longitude = VALUES(longitude),
        updated_at = NOW()
");
$stmt->execute([$driver_id, $lat, $lng]);

echo "OK";
