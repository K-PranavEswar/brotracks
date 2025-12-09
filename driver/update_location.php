<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id=?");
$stmt->execute([$_SESSION["user_id"]]);
$driver = $stmt->fetch();

$driverId = $driver["id"];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live GPS Tracking | BroTracks</title>
    <style>
        body {
            background:#0f172a;
            color:white;
            font-family:Arial, sans-serif;
            text-align:center;
            padding-top:50px;
        }
        .status-box {
            display:inline-block;
            padding:15px 30px;
            background:#1e293b;
            border-radius:12px;
            border:1px solid #334155;
            box-shadow:0 0 15px rgba(16,185,129,0.4);
        }
    </style>
</head>
<body>

<h2>🚐 Live GPS Tracking Active</h2>
<p>BroTracks is broadcasting your location every <b>10 seconds</b>.</p>

<div class="status-box">
    Sending location… <span id="gps-status" style="color:#34d399;">Waiting for signal</span>
</div>

<script>
let driverId = <?php echo $driverId; ?>;

function sendGPS() {
    navigator.geolocation.getCurrentPosition((pos) => {
        let lat = pos.coords.latitude;
        let lng = pos.coords.longitude;

        document.getElementById("gps-status").innerHTML = 
            "Lat: " + lat.toFixed(5) + " | Lng: " + lng.toFixed(5);

        fetch("update_location_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "driver_id=" + driverId + "&lat=" + lat + "&lng=" + lng
        });
    });
}

setInterval(sendGPS, 10000);
sendGPS();
</script>

</body>
</html>
