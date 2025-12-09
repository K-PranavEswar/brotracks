<?php
require "../config/db.php";

$driver_id = $_GET["driver_id"];

$stmt = $pdo->prepare("SELECT latitude, longitude FROM driver_locations WHERE driver_id=?");
$stmt->execute([$driver_id]);
$loc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($loc) {
    echo json_encode([
        "status" => "ok",
        "lat" => $loc["latitude"],
        "lng" => $loc["longitude"]
    ]);
} else {
    echo json_encode(["status" => "no_data"]);
}
?>
