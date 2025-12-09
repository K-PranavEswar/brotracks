<?php
// FILE: BroTracks/admin/add_notification.php
session_start();
require_once "../config/db.php";

// 1. SECURITY: STRICTLY ENFORCE 'admin' ROLE
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
$message = "";
$msg_type = "";

// 2. FORM HANDLING
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $notif_msg = trim($_POST['message']);
    $target_role = $_POST['target_role'];

    if (empty($title) || empty($notif_msg)) {
        $message = "Title and Message are required.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, target_role) VALUES (?, ?, ?)");
            if ($stmt->execute([$title, $notif_msg, $target_role])) {
                $message = "Notification sent successfully!";
                $msg_type = "success";
            } else {
                $message = "Failed to send notification.";
                $msg_type = "danger";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Notification - BroTracks Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-sidebar: #0f172a;
            --text-sidebar: #94a3b8;
            --accent-color: #f59e0b;
            --bg-main: #f1f5f9;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); overflow-x: hidden; }

        /* Sidebar & Layout (Same as Settings) */
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper {
            min-height: 100vh;
            width: 260px;
            background-color: var(--bg-sidebar);
            color: white;
            transition: margin .25s ease-out;
        }
        .sidebar-heading { padding: 1.5rem; font-size: 1.25rem; font-weight: bold; color: white; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .list-group-item { background-color: transparent; color: var(--text-sidebar); border: none; padding: 15px 25px; font-weight: 500; transition: all 0.2s; }
        .list-group-item:hover { color: #fff; background-color: rgba(255,255,255,0.05); padding-left: 30px; }
        .list-group-item.active { color: var(--accent-color); background-color: rgba(245, 158, 11, 0.1); border-right: 3px solid var(--accent-color); }
        .list-group-item i { width: 25px; }
        #page-content-wrapper { width: 100%; }
        .container-fluid { padding: 2rem; }

        /* Form Card Style */
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; color: #334155; }
        .form-control, .form-select { padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; }
        .form-control:focus, .form-select:focus { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); }

        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -260px; }
            .toggled #sidebar-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="fas fa-shield-alt text-warning"></i> BroTracks Admin
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="drivers.php" class="list-group-item list-group-item-action">
                <i class="fas fa-id-card"></i> Manage Drivers
            </a>
            <a href="parents.php" class="list-group-item list-group-item-action">
                <i class="fas fa-users"></i> View Parents
            </a>
            <a href="rides.php" class="list-group-item list-group-item-action">
                <i class="fas fa-route"></i> Live Rides
            </a>
            <a href="add_notification.php" class="list-group-item list-group-item-action active">
                <i class="fas fa-bell"></i> Send Notification
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action">
                <i class="fas fa-cogs"></i> Settings
            </a>
            <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger mt-5">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-sm btn-outline-secondary" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="fw-bold small text-dark"><?php echo htmlspecialchars($adminName); ?></div>
                        <div class="small text-muted" style="font-size: 0.75rem;">Super Admin</div>
                    </div>
                    <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">
                        <?php echo $initials; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="fw-bold text-dark">Send Notification</h3>
                    <p class="text-muted">Broadcast messages to parents, drivers, or all users.</p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Notification Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. School Bus Delay" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select name="target_role" class="form-select">
                                    <option value="all">All Users</option>
                                    <option value="parent">Parents Only</option>
                                    <option value="driver">Drivers Only</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-warning text-white px-4"><i class="fas fa-paper-plane me-2"></i> Send Notification</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-4 bg-light">
                        <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i> How it works</h5>
                        <p class="small text-muted">
                            Sending a notification here will store it in the database. 
                            Users (Parents/Drivers) will see this in their app dashboard when they log in.
                        </p>
                        <hr>
                        <h6 class="fw-bold">Tips:</h6>
                        <ul class="small text-muted ps-3">
                            <li>Keep titles short and clear.</li>
                            <li>Use "Parents Only" for fee reminders.</li>
                            <li>Use "Drivers Only" for route updates.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");
    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };
</script>

</body>
</html>