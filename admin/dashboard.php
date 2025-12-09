<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// 2. Fetch Dashboard Statistics
try {
    // A. Fetch TOTAL Registered Drivers (From 'users' table where role='driver')
    $stmtDriver = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'driver'");
    $stmtDriver->execute();
    $totalDrivers = $stmtDriver->fetchColumn();

    // B. Fetch TOTAL Registered Parents (From 'users' table where role='parent')
    $stmtParent = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'parent'");
    $stmtParent->execute();
    $totalParents = $stmtParent->fetchColumn();

    // C. Fetch Pending Drivers (From 'drivers' table where status='pending')
    // We check the drivers profile table for status, as 'users' table contains auth info only
    $stmtPending = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'pending'");
    $pendingDrivers = $stmtPending->fetchColumn();

    // D. Fetch Active Rides
    $stmtRides = $pdo->query("SELECT COUNT(*) FROM rides WHERE status = 'ongoing' OR status = 'requested'");
    $countActiveRides = $stmtRides->fetchColumn();

    // E. Fetch Recent Rides
    $stmtRecent = $pdo->query("
        SELECT r.id, r.pickup_location, r.dropoff_location, r.status, c.name as child_name 
        FROM rides r
        LEFT JOIN children c ON r.child_id = c.id
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $recentRides = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fallback data
    $totalDrivers = 0; $pendingDrivers = 0; $totalParents = 0; $countActiveRides = 0; $recentRides = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BroTracks</title>
    
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

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            border-left: 4px solid transparent;
        }
        .stat-card:hover { transform: translateY(-5px); }
        
        .stat-card.blue { border-left-color: #3b82f6; }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card.amber { border-left-color: #f59e0b; }
        .stat-card.red { border-left-color: #ef4444; }

        .stat-title { font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #1e293b; margin: 5px 0; }
        .stat-icon { font-size: 1.5rem; opacity: 0.2; position: absolute; top: 20px; right: 20px; }

        /* Recent Table */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: none; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; padding: 1.25rem; font-weight: 600; }
        .table th { font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; border-bottom-width: 1px; }
        .table td { vertical-align: middle; padding: 1rem 0.5rem; color: #334155; }
        
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .bg-ongoing { background-color: #dbeafe; color: #1e40af; }
        .bg-completed { background-color: #d1fae5; color: #065f46; }
        .bg-pending { background-color: #fef3c7; color: #92400e; }

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
            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="drivers.php" class="list-group-item list-group-item-action">
                <i class="fas fa-id-card"></i> Manage Drivers
                <?php if($pendingDrivers > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $pendingDrivers; ?></span>
                <?php endif; ?>
            </a>
            <a href="parents.php" class="list-group-item list-group-item-action">
                <i class="fas fa-users"></i> View Parents
            </a>
            <a href="rides.php" class="list-group-item list-group-item-action">
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
                        <div class="fw-bold small text-dark"><?php echo htmlspecialchars($_SESSION["name"] ?? 'Admin'); ?></div>
                        <div class="small text-muted" style="font-size: 0.75rem;">Super Admin</div>
                    </div>
                    <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">
                        <?php echo strtoupper(substr($_SESSION["name"] ?? 'A', 0, 1)); ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            
            <h3 class="fw-bold text-dark mb-4">Overview</h3>

            <div class="row g-4 mb-5">
                
                <div class="col-md-3">
                    <div class="stat-card blue position-relative">
                        <div class="stat-title">Registered Drivers</div>
                        <div class="stat-value"><?php echo $totalDrivers; ?></div>
                        <div class="text-muted small">Total Accounts</div>
                        <i class="fas fa-car stat-icon text-primary"></i>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card green position-relative">
                        <div class="stat-title">Registered Parents</div>
                        <div class="stat-value"><?php echo $totalParents; ?></div>
                        <div class="text-muted small">Total Accounts</div>
                        <i class="fas fa-users stat-icon text-success"></i>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card amber position-relative">
                        <div class="stat-title">Live Rides</div>
                        <div class="stat-value"><?php echo $countActiveRides; ?></div>
                        <div class="text-warning small">In Progress</div>
                        <i class="fas fa-map-marker-alt stat-icon text-warning"></i>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card red position-relative">
                        <div class="stat-title">Pending Approvals</div>
                        <div class="stat-value text-danger"><?php echo $pendingDrivers; ?></div>
                        <div class="text-danger small">Action Required</div>
                        <i class="fas fa-exclamation-circle stat-icon text-danger"></i>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-table">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Recent Ride Activity</span>
                            <a href="rides.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Child</th>
                                            <th>Route</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recentRides) > 0): ?>
                                            <?php foreach ($recentRides as $ride): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($ride['child_name'] ?? 'Unknown'); ?></td>
                                                <td>
                                                    <div class="small text-muted">From: <?php echo htmlspecialchars($ride['pickup_location']); ?></div>
                                                    <div class="small text-muted">To: <?php echo htmlspecialchars($ride['dropoff_location']); ?></div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'bg-pending';
                                                        if($ride['status'] == 'ongoing') $statusClass = 'bg-ongoing';
                                                        if($ride['status'] == 'completed') $statusClass = 'bg-completed';
                                                    ?>
                                                    <span class="badge-status <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($ride['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="ride_details.php?id=<?php echo $ride['id']; ?>" class="btn btn-sm btn-light border"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No recent rides found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-table h-100">
                        <div class="card-header">Quick Actions</div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="drivers.php" class="btn btn-outline-primary text-start p-3">
                                    <i class="fas fa-user-check me-2"></i> Review Pending Drivers
                                </a>
                                <a href="add_notification.php" class="btn btn-outline-dark text-start p-3">
                                    <i class="fas fa-bell me-2"></i> Send System Alert
                                </a>
                                <a href="reports.php" class="btn btn-outline-secondary text-start p-3">
                                    <i class="fas fa-file-download me-2"></i> Export Monthly Report
                                </a>
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
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");
    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };
</script>

</body>
</html>