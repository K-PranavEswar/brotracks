<?php
session_start();
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "parent") {
        header("Location: parent/dashboard.php");
        exit;
    }
    if ($_SESSION["role"] === "driver") {
        header("Location: driver/dashboard.php");
        exit;
    }
    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BroTracks</title>
    <link rel="stylesheet" href="public/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h1 class="mb-3">BroTracks</h1>
                    <p class="mb-4">Safe school ride scheduling and tracking for parents and drivers.</p>
                    <a href="auth/login.php" class="btn btn-primary me-2">Login</a>
                    <a href="auth/register.php" class="btn btn-outline-primary">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
