<?php
session_start();
require_once "../config/db.php";

// Security: Only parents can view this
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "parent") {
    header("Location: ../auth/login.php");
    exit;
}

// Ride ID from URL
if (!isset($_GET["id"])) {
    die("Invalid Ride Request.");
}

$rideId = intval($_GET["id"]);

// Fetch parent ID
$stmtParent = $pdo->prepare("SELECT id FROM parents WHERE user_id=?");
$stmtParent->execute([$_SESSION["user_id"]]);
$parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
$parentId = $parent["id"];

// Fetch ride data
$stmt = $pdo->prepare("
    SELECT r.*, 
           c.name AS child_name, c.school_name, c.class AS class_name,
           u.name AS driver_name, u.phone AS driver_phone,
           u2.name AS parent_name, u2.phone AS parent_phone
    FROM rides r
    JOIN children c ON r.child_id = c.id
    JOIN parents p ON r.parent_id = p.id
    JOIN users u2 ON p.user_id = u2.id
    LEFT JOIN drivers d ON r.driver_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE r.id=? AND r.parent_id=?
");
$stmt->execute([$rideId, $parentId]);
$ride = $stmt->fetch(PDO::FETCH_ASSOC);

// If ride not found
if (!$ride) {
    die("Ride does not exist or you don't have permission to view this.");
}

// Status badge color
$statusColors = [
    "requested" => "#f59e0b",
    "accepted" => "#3b82f6",
    "on_going" => "#10b981",
    "completed" => "#64748b"
];

$statusColor = $statusColors[$ride["status"]] ?? "#94a3b8";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Details | BroTracks</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 820px;
            margin: 2rem auto;
            padding: 1.5rem;
        }

        .header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.1);
            border-left: 5px solid <?= $statusColor ?>;
        }

        .ride-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #a5b4fc;
        }

        .info-row {
            margin-bottom: 1rem;
        }

        .label {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .value {
            font-size: 1rem;
            font-weight: 500;
        }

        .route-box {
            margin-top: 1.5rem;
            border-left: 3px dashed #475569;
            padding-left: 1rem;
        }

        .route-point {
            margin-bottom: 1.5rem;
        }

        .map-box {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            margin-top: 1rem;
            background-size: cover;
            background-position: center;
        }

        .btn-back {
            padding: 10px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            text-decoration: none;
            color: #cbd5e1;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.15);
            color: white;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h2>Ride Details</h2>
        <span class="status-badge"><?= strtoupper($ride["status"]) ?></span>
    </div>

    <a href="rides.php" class="btn-back">← Back to Ride History</a>

    <div class="ride-card">

        <div class="section-title">Passenger</div>
        <div class="info-row"><span class="label">Child:</span> <span class="value"><?= $ride["child_name"] ?></span></div>
        <div class="info-row"><span class="label">School:</span> <span class="value"><?= $ride["school_name"] ?></span></div>
        <div class="info-row"><span class="label">Class:</span> <span class="value"><?= $ride["class_name"] ?></span></div>

        <div class="section-title">Ride Info</div>
        <div class="info-row"><span class="label">Date:</span> <span class="value"><?= $ride["ride_date"] ?></span></div>
        <div class="info-row"><span class="label">Time:</span> <span class="value"><?= date("h:i A", strtotime($ride["ride_time"])) ?></span></div>

        <div class="route-box">
            <div class="route-point">
                <div class="label">Pickup Location</div>
                <div class="value"><?= $ride["pickup_location"] ?></div>
            </div>
            <div class="route-point">
                <div class="label">Drop-off Location</div>
                <div class="value"><?= $ride["drop_location"] ?></div>
            </div>
        </div>

        <div class="section-title">Driver</div>
        <?php if ($ride["driver_name"]): ?>
            <div class="info-row"><span class="label">Driver Name:</span> <span class="value"><?= $ride["driver_name"] ?></span></div>
            <div class="info-row"><span class="label">Driver Phone:</span> <span class="value"><?= $ride["driver_phone"] ?></span></div>
        <?php else: ?>
            <div class="value" style="opacity:0.6;">No driver assigned yet</div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
