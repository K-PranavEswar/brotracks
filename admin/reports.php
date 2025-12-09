<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Default Filters (Current Month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$statusFilter = $_GET['status'] ?? 'all';

// 3. Build Query
$params = [$startDate, $endDate];
$sql = "SELECT r.*, c.name as child_name, p_u.name as parent_name, d_u.name as driver_name 
        FROM rides r
        LEFT JOIN children c ON r.child_id = c.id
        LEFT JOIN parents p ON r.parent_id = p.id
        LEFT JOIN users p_u ON p.user_id = p_u.id
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users d_u ON d.user_id = d_u.id
        WHERE r.ride_date BETWEEN ? AND ?";

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY r.ride_date DESC, r.ride_time DESC";

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Calculate Summary Stats for the selected period
$totalRides = count($rides);
$completedRides = 0;
$pendingRides = 0;

foreach ($rides as $r) {
    if ($r['status'] === 'completed') $completedRides++;
    if ($r['status'] === 'pending' || $r['status'] === 'requested') $pendingRides++;
}

// Helper for Initials
$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BroTracks Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-sidebar: #0f172a;
            --text-sidebar: #94a3b8;
            --accent-color: #f59e0b; /* Amber */
            --bg-main: #f1f5f9;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); overflow-x: hidden; }

        /* Sidebar & Layout */
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

        #page-content-wrapper { width: 100%; }
        .container-fluid { padding: 2rem; }

        /* Report Specific Styles */
        .report-card { background: white; border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .summary-box { padding: 20px; border-radius: 10px; color: white; position: relative; overflow: hidden; }
        .summary-box h3 { font-size: 2.5rem; font-weight: 700; margin: 0; }
        .summary-box span { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .summary-box i { position: absolute; right: 20px; bottom: 20px; font-size: 3rem; opacity: 0.2; }

        .bg-gradient-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-gradient-green { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

        .table thead th { background-color: #f8fafc; font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; }
        
        /* Print Styles */
        @media print {
            #sidebar-wrapper, .navbar, .no-print { display: none !important; }
            #page-content-wrapper { margin: 0; padding: 0; width: 100%; }
            .container-fluid { padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
            body { background-color: white; -webkit-print-color-adjust: exact; }
        }

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
            <a href="reports.php" class="list-group-item list-group-item-action active">
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark m-0">System Reports</h3>
                <button onclick="window.print()" class="btn btn-outline-dark no-print">
                    <i class="fas fa-print me-2"></i> Print Report
                </button>
            </div>

            <div class="card report-card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-muted fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="accepted" <?php echo $statusFilter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="summary-box bg-gradient-blue">
                        <span>Total Rides</span>
                        <h3><?php echo $totalRides; ?></h3>
                        <i class="fas fa-route"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-gradient-green">
                        <span>Completed</span>
                        <h3><?php echo $completedRides; ?></h3>
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-gradient-orange">
                        <span>Pending / Requests</span>
                        <h3><?php echo $pendingRides; ?></h3>
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="card report-card">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark">Ride Details (<?php echo $startDate; ?> to <?php echo $endDate; ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Child Name</th>
                                    <th>Parent</th>
                                    <th>Assigned Driver</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Locations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rides) > 0): ?>
                                    <?php foreach ($rides as $r): ?>
                                        <?php 
                                            // FIX: Handling missing column keys safely
                                            // Trying common names: pickup_location, pickup, source
                                            $pickup = $r['pickup_location'] ?? $r['pickup'] ?? 'N/A';
                                            $dropoff = $r['dropoff_location'] ?? $r['dropoff'] ?? 'N/A';
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap">
                                                <div class="fw-bold"><?php echo $r['ride_date']; ?></div>
                                                <div class="small text-muted"><?php echo $r['ride_time']; ?></div>
                                            </td>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($r['child_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($r['parent_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if($r['driver_name']): ?>
                                                    <span class="text-dark"><i class="fas fa-id-badge text-muted me-1"></i> <?php echo htmlspecialchars($r['driver_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $st = $r['status'];
                                                    $badge = 'secondary';
                                                    if($st == 'completed') $badge = 'success';
                                                    if($st == 'accepted') $badge = 'primary';
                                                    if($st == 'ongoing') $badge = 'info';
                                                    if($st == 'pending') $badge = 'warning';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($st); ?></span>
                                            </td>
                                            <td class="text-end pe-4 small">
                                                <div title="From: <?php echo htmlspecialchars($pickup); ?>">
                                                    <i class="fas fa-map-marker-alt text-success me-1"></i> <?php echo substr($pickup, 0, 15) . '...'; ?>
                                                </div>
                                                <div title="To: <?php echo htmlspecialchars($dropoff); ?>">
                                                    <i class="fas fa-flag-checkered text-danger me-1"></i> <?php echo substr($dropoff, 0, 15) . '...'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            No records found for this date range.
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