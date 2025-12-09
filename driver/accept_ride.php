<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header("Location: ../auth/login.php");
    exit;
}

$rideId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($rideId <= 0) {
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id=?");
$stmt->execute([$_SESSION["user_id"]]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$driver) {
    header("Location: notifications.php");
    exit;
}
$driverId = $driver["id"];

$check = $pdo->prepare("SELECT driver_id, status FROM rides WHERE id=?");
$check->execute([$rideId]);
$ride = $check->fetch(PDO::FETCH_ASSOC);

if (!$ride || $ride["driver_id"] !== null || $ride["status"] !== "requested") {
    header("Location: notifications.php?taken=1");
    exit;
}

$assign = $pdo->prepare("
    UPDATE rides 
    SET driver_id=?, status='accepted'
    WHERE id=? AND driver_id IS NULL AND status='requested'
");
$assign->execute([$driverId, $rideId]);

if ($assign->rowCount() === 0) {
    header("Location: notifications.php?taken=1");
    exit;
}

header("Location: rides.php?accepted=1");
exit;
