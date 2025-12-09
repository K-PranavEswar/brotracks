<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// 2. Handle Profile Update (POST Request)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_parent'])) {
    $parent_id = $_POST['parent_id'];
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    try {
        $pdo->beginTransaction();

        // Update User Info (Auth)
        $stmtU = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmtU->execute([$name, $email, $phone, $user_id]);

        // Update Parent Info (Profile)
        $stmtP = $pdo->prepare("UPDATE parents SET address = ? WHERE id = ?");
        $stmtP->execute([$address, $parent_id]);

        $pdo->commit();
        $success_msg = "Parent profile updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error updating profile: " . $e->getMessage();
    }
}

// 3. SEARCH LOGIC & DATA FETCHING
$search = $_GET['search'] ?? '';
$params = [];

// Base Query: Get Parent + User Info + Count/Names of Children
// FIX: Removed 'c.grade' to prevent SQL error. Now just fetching names.
$sql = "SELECT p.*, u.id as u_id, u.name, u.email, u.phone,
               GROUP_CONCAT(c.name SEPARATOR '||') as child_names
        FROM parents p 
        JOIN users u ON p.user_id = u.id
        LEFT JOIN children c ON p.id = c.parent_id";

// Add Filter if Search is Active
if (!empty($search)) {
    $sql .= " WHERE u.name LIKE ? 
              OR u.email LIKE ? 
              OR u.phone LIKE ? 
              OR p.address LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= " GROUP BY p.id ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for initials
$adminName = $_SESSION["name"] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Parents - BroTracks Admin</title>
    
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
        
        /* Avatars */
        .user-avatar { width: 35px; height: 35px; border-radius: 50%; background-color: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; font-size: 0.9rem; }

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
            <a href="parents.php" class="list-group-item list-group-item-action active">
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
            
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="fw-bold text-dark m-0">Parent Directory</h3>
                
                <form method="GET" action="" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" 
                               placeholder="Search Name, Email..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               style="max-width: 250px;">
                        <button class="btn btn-dark" type="submit">Search</button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="parents.php" class="btn btn-outline-secondary" title="Clear Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card card-table">
                <div class="card-header">
                    <span><i class="fas fa-user-friends me-2"></i> Registered Parents</span>
                    <span class="badge bg-light text-dark border"><?php echo count($parents); ?> Total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Name</th>
                                    <th>Contact Information</th>
                                    <th>Address</th>
                                    <th>Children</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($parents) > 0): ?>
                                    <?php foreach ($parents as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php echo strtoupper(substr($p["name"], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($p["name"]); ?></div>
                                                        <div class="small text-muted">ID: #<?php echo $p["id"]; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="fas fa-envelope text-muted me-2" style="width: 15px;"></i> 
                                                    <a href="mailto:<?php echo htmlspecialchars($p["email"]); ?>" class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($p["email"]); ?>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-phone text-muted me-2" style="width: 15px;"></i> 
                                                    <?php echo htmlspecialchars($p["phone"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-map-marker-alt text-muted me-2 mt-1" style="width: 15px;"></i>
                                                    <span class="text-secondary" style="max-width: 250px; display: inline-block;">
                                                        <?php echo !empty($p["address"]) ? htmlspecialchars($p["address"]) : '<span class="text-muted fst-italic">No address provided</span>'; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($p['child_names'])): ?>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo count(explode('||', $p['child_names'])); ?> Child(ren)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-light border me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewChildModal<?php echo $p['id']; ?>" 
                                                        title="View Children">
                                                    <i class="fas fa-child text-primary"></i>
                                                </button>

                                                <button class="btn btn-sm btn-light border" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editParentModal<?php echo $p['id']; ?>"
                                                        title="Edit Profile">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="viewChildModal<?php echo $p['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Children of <?php echo htmlspecialchars($p['name']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if(!empty($p['child_names'])): ?>
                                                            <ul class="list-group">
                                                                <?php 
                                                                    $kids = explode('||', $p['child_names']);
                                                                    foreach($kids as $kid) {
                                                                        echo "<li class='list-group-item'><i class='fas fa-user-graduate me-2 text-success'></i>" . htmlspecialchars($kid) . "</li>";
                                                                    }
                                                                ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <p class="text-muted text-center">No children linked to this parent yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="editParentModal<?php echo $p['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Parent Profile</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="parent_id" value="<?php echo $p['id']; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $p['u_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Full Name</label>
                                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($p['email']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Phone</label>
                                                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($p['phone']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Address</label>
                                                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($p['address']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_parent" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-25"></i>
                                            <p>No parents found matching your criteria.</p>
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