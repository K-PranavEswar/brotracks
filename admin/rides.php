<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Handle Driver Assignment (POST)
$success_msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ride_id = $_POST["ride_id"];
    $driver_id = $_POST["driver_id"];
    
    if ($ride_id && $driver_id) {
        $stmt = $pdo->prepare("UPDATE rides SET driver_id=?, status='accepted' WHERE id=?");
        if($stmt->execute([$driver_id, $ride_id])) {
            $success_msg = "Driver assigned successfully!";
        }
    }
}

// 3. Fetch Data
// Get Approved Drivers for Dropdown
$driversStmt = $pdo->query("SELECT d.id, u.name FROM drivers d JOIN users u ON d.user_id=u.id WHERE d.status='approved'");
$drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);

// Get All Rides with Details
$stmt = $pdo->query("
    SELECT r.*, 
           c.name AS child_name, 
           pu.name AS parent_name, 
           du.name AS driver_name 
    FROM rides r 
    JOIN children c ON r.child_id = c.id 
    JOIN parents p ON r.parent_id = p.id 
    JOIN users pu ON p.user_id = pu.id 
    LEFT JOIN drivers d ON r.driver_id = d.id 
    LEFT JOIN users du ON d.user_id = du.id 
    ORDER BY r.created_at DESC
");
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for Admin Initials
$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rides - BroTracks Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-sidebar: #0f172a;
            --text-sidebar: #94a3b8;
            --accent-color: #f59e0b; /* Amber for Admin */
            --bg-main: #f1f5f9;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); overflow-x: hidden; }

        /* Sidebar Styles */
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper {
            min-height: 100vh;
            width: 260px;
            background-color: var(--bg-sidebar);
            color: white;
            transition: margin .25s ease-out;
        }
        
        .sidebar-heading {
            padding: 1.5rem;
            font-size: 1.25rem;
            font-weight: bold;
            color: white;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .list-group-item {
            background-color: transparent;
            color: var(--text-sidebar);
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .list-group-item:hover { color: #fff; background-color: rgba(255,255,255,0.05); padding-left: 30px; }
        .list-group-item.active { color: var(--accent-color); background-color: rgba(245, 158, 11, 0.1); border-right: 3px solid var(--accent-color); }
        .list-group-item i { width: 25px; }

        /* Main Content */
        #page-content-wrapper { width: 100%; }
        .container-fluid { padding: 2rem; }

        /* Table Styling */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; padding: 1.25rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        
        .table th { font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; border-bottom-width: 1px; background-color: #f8fafc; padding: 1rem; }
        .table td { vertical-align: middle; padding: 1rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        /* Status Badges */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .bg-requested { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .bg-accepted { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .bg-ongoing { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .bg-completed { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

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
            <a href="rides.php" class="list-group-item list-group-item-action active">
                <i class="fas fa-route"></i> Live Rides
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
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark m-0">Ride Management</h3>
                <div class="text-muted small">Total Rides: <?php echo count($rides); ?></div>
            </div>

            <div class="card card-table">
                <div class="card-header">
                    <span><i class="fas fa-car-side me-2"></i> All Ride Requests</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Details (Child / Parent)</th>
                                    <th>Schedule</th>
                                    <th>Current Status</th>
                                    <th>Assigned Driver</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rides) > 0): ?>
                                    <?php foreach ($rides as $r): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><i class="fas fa-child text-info me-1"></i> <?php echo htmlspecialchars($r["child_name"]); ?></div>
                                                <div class="small text-muted">Parent: <?php echo htmlspecialchars($r["parent_name"]); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($r["ride_date"]); ?></div>
                                                <div class="small text-muted"><i class="far fa-clock me-1"></i> <?php echo htmlspecialchars($r["ride_time"]); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status = $r["status"];
                                                    $badgeClass = 'bg-requested';
                                                    if ($status == 'accepted') $badgeClass = 'bg-accepted';
                                                    if ($status == 'ongoing') $badgeClass = 'bg-ongoing';
                                                    if ($status == 'completed') $badgeClass = 'bg-completed';
                                                ?>
                                                <span class="badge-status <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($r['driver_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center me-2" style="width:25px;height:25px;font-size:0.7rem;">
                                                            <i class="fas fa-user-tie text-secondary"></i>
                                                        </div>
                                                        <span class="text-dark fw-bold"><?php echo htmlspecialchars($r["driver_name"]); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic small">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4" style="min-width: 250px;">
                                                <form method="post" class="d-flex justify-content-end align-items-center gap-2">
                                                    <input type="hidden" name="ride_id" value="<?php echo $r["id"]; ?>">
                                                    
                                                    <select name="driver_id" class="form-select form-select-sm" style="width: 160px;" required>
                                                        <option value="" disabled selected>Select Driver</option>
                                                        <?php foreach ($drivers as $d) { ?>
                                                            <option value="<?php echo $d["id"]; ?>" <?php if ($r["driver_id"] == $d["id"]) echo "selected"; ?>>
                                                                <?php echo htmlspecialchars($d["name"]); ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Save Assignment">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-route fa-3x mb-3 text-secondary opacity-25"></i>
                                            <p>No rides found in the system.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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