<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = 6;
$message = "";
$msg_type = "";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $message = "Name and Email are required.";
        $msg_type = "danger";
    } else {
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $user_id]);

        if ($email !== $currentUser['email'] && $checkEmail->rowCount() > 0) {
            $message = "Email already exists. Try another email.";
            $msg_type = "danger";
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $updateStmt->execute([$name, $email, $user_id]);

            $_SESSION["name"] = $name;
            $currentUser['name'] = $name;
            $currentUser['email'] = $email;

            $message = "Profile updated successfully.";
            $msg_type = "success";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All password fields are required.";
        $msg_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match.";
        $msg_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $msg_type = "danger";
    } else {
        if (password_verify($current_password, $currentUser['password'])) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $passStmt->execute([$new_hashed, $user_id]);

            $message = "Password changed successfully.";
            $msg_type = "success";
        } else {
            $message = "Incorrect current password.";
            $msg_type = "danger";
        }
    }
}

$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - BroTracks Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-sidebar: #0f172a;
            --text-sidebar: #94a3b8;
            --accent-color: #f59e0b;
            --bg-main: #f1f5f9;
            --card-border: #e2e8f0;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); overflow-x: hidden; }
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper {
            min-height: 100vh; width: 260px; background-color: var(--bg-sidebar); color: white;
        }
        .sidebar-heading {
            padding: 1.5rem; font-size: 1.25rem; font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; gap: 10px;
        }
        .list-group-item {
            background: transparent; color: var(--text-sidebar); border: none;
            padding: 15px 25px; font-weight: 500;
        }
        .list-group-item:hover {
            color: #fff; background-color: rgba(255,255,255,0.05); padding-left: 30px;
        }
        .list-group-item.active {
            color: var(--accent-color);
            background-color: rgba(245,158,11,0.1);
            border-right: 3px solid var(--accent-color);
        }
        #page-content-wrapper { width: 100%; }
        .container-fluid { padding: 2rem; }
        .settings-card {
            border-radius: 12px; background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-control {
            border-radius: 8px; padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(245,158,11,0.2);
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="fas fa-shield-alt text-warning"></i> BroTracks Admin
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="drivers.php" class="list-group-item"><i class="fas fa-id-card"></i> Manage Drivers</a>
            <a href="parents.php" class="list-group-item"><i class="fas fa-users"></i> View Parents</a>
            <a href="rides.php" class="list-group-item"><i class="fas fa-route"></i> Live Rides</a>
            <a href="reports.php" class="list-group-item"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="settings.php" class="list-group-item active"><i class="fas fa-cogs"></i> Settings</a>
            <a href="../auth/logout.php" class="list-group-item text-danger mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div id="page-content-wrapper">

        <nav class="navbar navbar-expand-lg bg-white border-bottom px-4 py-3">
            <div class="d-flex w-100 justify-content-between">
                <button class="btn btn-sm btn-outline-secondary" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="fw-bold small"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="small text-muted">Super Admin</div>
                    </div>
                    <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center"
                         style="width:40px;height:40px;font-weight:bold;">
                        <?php echo $initials; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">

            <h3 class="fw-bold">Settings</h3>
            <p class="text-muted">Manage your profile and security preferences.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">

                <div class="col-md-4 mb-4">
                    <div class="card settings-card p-4 text-center">
                        <div class="mx-auto mb-3 rounded-circle"
                             style="width:80px;height:80px;background:#f59e0b;color:white;
                             display:flex;align-items:center;justify-content:center;font-size:2rem;">
                            <?php echo $initials; ?>
                        </div>
                        <h4 class="fw-bold"><?php echo htmlspecialchars($currentUser['name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Administrator</span>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card settings-card p-4">

                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#profile">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#security">
                                    <i class="fas fa-lock me-2"></i>Security
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <div class="tab-pane fade show active" id="profile">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">

                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control mb-3"
                                           value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>

                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control mb-3"
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>

                                    <button class="btn btn-primary px-4">Save Changes</button>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="security">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">

                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control mb-3" required>

                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" minlength="6" class="form-control mb-3" required>

                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" minlength="6" class="form-control mb-3" required>

                                    <button class="btn btn-warning text-white px-4">Update Password</button>
                                </form>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("menu-toggle").onclick = () =>
    document.getElementById("wrapper").classList.toggle("toggled");
</script>

</body>
</html>
