<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// 2. Handle Actions (Approve/Reject)
if (isset($_GET["approve"])) {
    $id = (int)$_GET["approve"];
    $stmt = $pdo->prepare("UPDATE drivers SET status='approved' WHERE id=?");
    $stmt->execute([$id]);
    header("Location: drivers.php"); // Redirect cleans URL
    exit;
}

if (isset($_GET["reject"])) {
    $id = (int)$_GET["reject"];
    $stmt = $pdo->prepare("UPDATE drivers SET status='rejected' WHERE id=?");
    $stmt->execute([$id]);
    header("Location: drivers.php");
    exit;
}

// 3. SEARCH LOGIC & DATA FETCHING
$search = $_GET['search'] ?? '';
$params = [];

// Base Query
$sql = "SELECT d.*, u.name, u.email, u.phone 
        FROM drivers d 
        JOIN users u ON d.user_id = u.id";

// Add Filter if Search is Active
if (!empty($search)) {
    $sql .= " WHERE u.name LIKE ? 
              OR u.email LIKE ? 
              OR u.phone LIKE ? 
              OR d.vehicle_no LIKE ? 
              OR d.license_no LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term];
}

$sql .= " ORDER BY d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for initials
$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - BroTracks Admin</title>
    
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

        /* Table Styling */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; padding: 1.25rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        
        .table th { font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; border-bottom-width: 1px; background-color: #f8fafc; padding: 1rem; }
        .table td { vertical-align: middle; padding: 1rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        /* Status Badges */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .bg-pending { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .bg-approved { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .bg-rejected { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

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
            <a href="drivers.php" class="list-group-item list-group-item-action active">
                <i class="fas fa-id-card"></i> Manage Drivers
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
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="fw-bold text-dark m-0">Driver Management</h3>
                
                <form method="GET" action="" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" 
                               placeholder="Search Name, Email, Phone..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               style="max-width: 250px;">
                        <button class="btn btn-dark" type="submit">Search</button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="drivers.php" class="btn btn-outline-secondary" title="Clear Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card card-table">
                <div class="card-header">
                    <span><i class="fas fa-list me-2"></i> All Registered Drivers</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Driver Name</th>
                                    <th>Contact Info</th>
                                    <th>Vehicle</th>
                                    <th>License No</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($drivers) > 0): ?>
                                    <?php foreach ($drivers as $d): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($d["name"]); ?></div>
                                                <div class="small text-muted">ID: #<?php echo $d["id"]; ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="fas fa-envelope text-muted me-2" style="width: 15px;"></i> 
                                                    <?php echo htmlspecialchars($d["email"]); ?>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-phone text-muted me-2" style="width: 15px;"></i> 
                                                    <?php echo htmlspecialchars($d["phone"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-car me-1"></i> <?php echo htmlspecialchars($d["vehicle_no"] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="small font-monospace text-secondary">
                                                <?php echo htmlspecialchars($d["license_no"] ?? 'N/A'); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $statusClass = 'bg-pending';
                                                    $icon = 'fa-clock';
                                                    if($d["status"] === 'approved') { $statusClass = 'bg-approved'; $icon = 'fa-check-circle'; }
                                                    if($d["status"] === 'rejected') { $statusClass = 'bg-rejected'; $icon = 'fa-times-circle'; }
                                                ?>
                                                <span class="badge-status <?php echo $statusClass; ?>">
                                                    <i class="fas <?php echo $icon; ?> me-1"></i> <?php echo ucfirst($d["status"]); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($d["status"] === "pending"): ?>
                                                    <a href="?approve=<?php echo $d["id"]; ?>" class="btn btn-sm btn-success me-1" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?reject=<?php echo $d["id"]; ?>" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Are you sure you want to reject this driver?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-search fa-3x mb-3 text-secondary opacity-25"></i>
                                            <p>No drivers found matching your search.</p>
                                            <?php if (!empty($search)): ?>
                                                <a href="drivers.php" class="btn btn-sm btn-primary">Clear Search</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white small text-muted">
                    Showing <?php echo count($drivers); ?> results
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