<?php
session_start();
require "../config/db.php";

// 1. Ensure Parent Login
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "parent") {
    header("Location: ../auth/login.php");
    exit;
}

$rideId = $_GET["ride_id"];

// 2. Verify ride belongs to this parent
$stmt = $pdo->prepare("
    SELECT r.*, d.id AS driver_id, u.name AS driver_name
    FROM rides r
    JOIN drivers d ON r.driver_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE r.id = ? AND r.parent_id = (
        SELECT id FROM parents WHERE user_id = ?
    )
");
$stmt->execute([$rideId, $_SESSION["user_id"]]);
$ride = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ride) {
    die("Invalid Ride Access");
}

$driverId = $ride["driver_id"];
$driverName = $ride["driver_name"];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Tracking | BroTracks</title>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAP_API_KEY"></script>

    <style>
        body {
            margin: 0;
            background: #0f172a;
            color: white;
            font-family: Arial, sans-serif;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        .header {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.7);
            padding: 15px 25px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            font-size: 1.1rem;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="header">
    🚐 Tracking Driver: <b><?php echo htmlspecialchars($driverName); ?></b>
</div>

<div id="map"></div>

<script>
let map;
let marker;

// Initialize Map
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 16,
        center: { lat: 8.5241, lng: 76.9366 }, // Default: Trivandrum (You can change)
        styles: [
            { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#e2e8f0" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
            {
                featureType: "road",
                elementType: "geometry",
                stylers: [{ color: "#475569" }]
            }
        ]
    });

    marker = new google.maps.Marker({
        map: map,
        title: "Driver Location",
        icon: {
            url: "https://cdn-icons-png.flaticon.com/512/854/854894.png",
            scaledSize: new google.maps.Size(45, 45)
        }
    });

    fetchLocation();
    setInterval(fetchLocation, 3000);
}

// Fetch Latest Driver Location
function fetchLocation() {
    fetch("fetch_driver_location.php?driver_id=<?php echo $driverId; ?>")
        .then(response => response.json())
        .then(data => {
            if (data.status === "ok") {
                let lat = parseFloat(data.lat);
                let lng = parseFloat(data.lng);

                let position = { lat: lat, lng: lng };
                
                marker.setPosition(position);
                map.setCenter(position);
            }
        })
        .catch(err => console.error("Error fetching live GPS:", err));
}

window.onload = initMap;
</script>

</body>
</html>
