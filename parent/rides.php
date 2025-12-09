<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "parent") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Sidebar Helper: Get User Initials
$nameParts = explode(" ", $_SESSION["name"] ?? 'User');
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// 3. Get Parent ID
$stmtParent = $pdo->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmtParent->execute([$_SESSION["user_id"]]);
$parent = $stmtParent->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    die("Parent profile configuration error.");
}

$parentId = $parent["id"];

// 4. Fetch Rides
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        c.name AS child_name, 
        u.name AS driver_name
    FROM rides r
    JOIN children c ON r.child_id = c.id
    LEFT JOIN drivers d ON r.driver_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE r.parent_id = ?
    ORDER BY r.ride_date DESC, r.ride_time DESC
");
$stmt->execute([$parentId]);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride History | BroTracks</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* DARK THEME PALETTE */
            --bg-body: #0f172a;       
            --bg-sidebar: #1e293b;    
            --bg-card: #1e293b;
            --bg-input: #334155;
            
            --text-main: #f8fafc;     
            --text-muted: #94a3b8;    
            
            --primary: #6366f1;       
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.3);
            
            --border: #334155;
            --sidebar-width: 260px;

            /* Status Colors */
            --status-pending-bg: rgba(245, 158, 11, 0.1);
            --status-pending-text: #fbbf24;
            
            --status-active-bg: rgba(6, 182, 212, 0.1);
            --status-active-text: #22d3ee;
            
            --status-completed-bg: rgba(16, 185, 129, 0.1);
            --status-completed-text: #34d399;
            
            --status-cancelled-bg: rgba(239, 68, 68, 0.1);
            --status-cancelled-text: #f87171;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            padding: 1.5rem;
            z-index: 100;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2.5rem;
            text-decoration: none;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-item a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .nav-item a.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .user-profile {
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), #4338ca);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.9rem; color: white;
        }

        .user-info div { font-size: 0.9rem; font-weight: 600; }
        .user-info span { font-size: 0.75rem; color: var(--text-muted); }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2.5rem;
        }

        .header {
            margin-bottom: 2.5rem;
        }
        .header h2 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { color: var(--text-muted); }

        /* --- RIDES GRID --- */
        .rides-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .ride-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .ride-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .date-box {
            display: flex;
            flex-direction: column;
        }
        .date-day { font-size: 1.2rem; font-weight: 700; color: var(--text-main); }
        .date-year { font-size: 0.85rem; color: var(--text-muted); }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-requested { background: var(--status-pending-bg); color: var(--status-pending-text); border: 1px solid rgba(251, 191, 36, 0.2); }
        .status-on_going { background: var(--status-active-bg); color: var(--status-active-text); border: 1px solid rgba(34, 211, 238, 0.2); }
        .status-completed { background: var(--status-completed-bg); color: var(--status-completed-text); border: 1px solid rgba(52, 211, 153, 0.2); }
        .status-cancelled { background: var(--status-cancelled-bg); color: var(--status-cancelled-text); border: 1px solid rgba(248, 113, 113, 0.2); }

        /* Info Rows */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 0.8rem;
        }
        .info-row:last-child { margin-bottom: 0; }

        .info-icon {
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .info-content {
            flex: 1;
        }
        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 0.95rem;
            color: var(--text-main);
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px dashed var(--border);
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; border: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="dashboard.php" class="brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path>
                <circle cx="7" cy="17" r="2"></circle>
                <circle cx="17" cy="17" r="2"></circle>
            </svg>
            BroTracks
        </a>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="add_child.php">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    Add Child
                </a>
            </li>
            <li class="nav-item">
                <a href="book_ride.php">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    Book Ride
                </a>
            </li>
            <li class="nav-item">
                <a href="rides.php" class="active">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Ride History
                </a>
            </li>
            <li class="nav-item">
                <a href="recurring_ride.php">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                    Recurring Rides
                </a>
            </li>
            <li class="nav-item" style="margin-top: 1rem;">
                <a href="plans.php" style="color: #facc15;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 18V6"></path></svg>
                    Plans & Pricing
                </a>
            </li>
        </ul>

        <div class="user-profile">
            <div class="avatar"><?php echo $initials; ?></div>
            <div class="user-info">
                <div><?php echo htmlspecialchars(explode(' ', $_SESSION["name"])[0]); ?></div>
                <span>Parent Account</span>
            </div>
            <a href="../auth/logout.php" style="margin-left:auto; color: #ef4444;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <div class="header">
            <h2>Ride History</h2>
            <p>View past trips, upcoming schedules, and driver assignments.</p>
        </div>

        <div class="rides-grid">

            <?php if (count($rides) > 0): ?>
                <?php foreach ($rides as $r): 
                    $date = date("M d", strtotime($r["ride_date"]));
                    $year = date("Y", strtotime($r["ride_date"]));
                    $time = date("g:i A", strtotime($r["ride_time"]));
                    
                    // Determine CSS class based on status
                    $status = strtolower($r["status"]); // requested, on_going, completed, cancelled
                    $statusLabel = str_replace('_', ' ', $r["status"]);
                ?>
                
                <div class="ride-card">
                    <div class="card-header">
                        <div class="date-box">
                            <span class="date-day"><?= $date ?></span>
                            <span class="date-year"><?= $year ?></span>
                        </div>
                        <div class="status-badge status-<?= $status ?>">
                            <?= $statusLabel ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Time</div>
                            <div class="info-value"><?= $time ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Child</div>
                            <div class="info-value"><?= htmlspecialchars($r["child_name"]) ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Driver</div>
                            <div class="info-value">
                                <?php if ($r["driver_name"]): ?>
                                    <?= htmlspecialchars($r["driver_name"]) ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Pending assignment</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($r["pickup_location"]) && !empty($r["drop_location"])): ?>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border);">
                        <div style="font-size: 0.8rem; color: var(--text-muted); display:flex; gap:6px; margin-bottom:4px;">
                            <span style="color:#10b981;">●</span> <?= htmlspecialchars($r["pickup_location"]) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); display:flex; gap:6px;">
                            <span style="color:#ef4444;">●</span> <?= htmlspecialchars($r["drop_location"]) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    <h3>No rides found</h3>
                    <p style="margin-bottom: 1.5rem;">You haven't booked any rides yet.</p>
                    <a href="book_ride.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Book a ride now &rarr;</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>