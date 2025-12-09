<?php
session_start();
require_once "../config/db.php";

// Security Check
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get User Initials for Avatar
$nameParts = explode(" ", $_SESSION["name"] ?? 'User');
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// Fetch Parent ID
$parentQuery = $pdo->prepare("SELECT id FROM parents WHERE user_id=?");
$parentQuery->execute([$_SESSION["user_id"]]);
$parentId = $parentQuery->fetchColumn();

// Fetch ongoing ride
$ongoingRide = null;
if ($parentId) {
    $ongoingQuery = $pdo->prepare("SELECT id FROM rides WHERE parent_id=? AND status='on_going' ORDER BY id DESC LIMIT 1");
    $ongoingQuery->execute([$parentId]);
    $ongoingRide = $ongoingQuery->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard | BroTracks</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* DARK THEME PALETTE */
            --bg-body: #0f172a;       /* Deep Slate */
            --bg-sidebar: #1e293b;    /* Slate 800 */
            --bg-card: #1e293b;
            --bg-card-hover: #334155; /* Slate 700 */
            
            --text-main: #f8fafc;     /* Slate 50 */
            --text-muted: #94a3b8;    /* Slate 400 */
            
            --primary: #6366f1;       /* Indigo 500 */
            --primary-glow: rgba(99, 102, 241, 0.3);
            --accent-green: #10b981;
            --accent-purple: #a855f7;
            --accent-orange: #f59e0b;
            --accent-cyan: #06b6d4;

            --border: #334155;
            --sidebar-width: 260px;
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
            transition: transform 0.3s ease;
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
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .header h1 { font-size: 1.8rem; font-weight: 700; }
        .header p { color: var(--text-muted); margin-top: 4px; }

        .date-badge {
            background: var(--bg-card);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* --- GRID SYSTEM --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        /* --- CARDS --- */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
        }

        .card:hover {
            transform: translateY(-5px);
            background-color: var(--bg-card-hover);
            border-color: var(--primary);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .icon-box {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            background: rgba(255,255,255,0.05);
        }

        /* Card Text */
        .card h3 { font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem; }
        .card p { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }

        /* Color Variants for Icons */
        .c-purple .icon-box { color: var(--accent-purple); background: rgba(168, 85, 247, 0.1); }
        .c-green .icon-box { color: var(--accent-green); background: rgba(16, 185, 129, 0.1); }
        .c-orange .icon-box { color: var(--accent-orange); background: rgba(245, 158, 11, 0.1); }
        .c-blue .icon-box { color: var(--primary); background: rgba(99, 102, 241, 0.1); }
        .c-gold .icon-box { color: #facc15; background: rgba(250, 204, 21, 0.1); border: 1px solid rgba(250, 204, 21, 0.2); }

        /* Live Tracking Card Special */
        .live-card {
            grid-column: 1 / -1; /* Spans full width */
            background: linear-gradient(to right, rgba(6, 182, 212, 0.1), transparent);
            border-color: var(--accent-cyan);
            flex-direction: row;
            align-items: center;
            height: auto;
            min-height: 100px;
        }
        
        .pulse-dot {
            width: 10px; height: 10px; background-color: var(--accent-cyan);
            border-radius: 50%; box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.7);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(6, 182, 212, 0); }
            100% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; border: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            /* You would typically add a hamburger menu toggle here with JS */
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="#" class="brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path>
                <circle cx="7" cy="17" r="2"></circle>
                <circle cx="17" cy="17" r="2"></circle>
            </svg>
            BroTracks
        </a>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="active">
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
                <a href="rides.php">
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
        
        <header class="header">
            <div>
                <h1>Good Afternoon, <?php echo htmlspecialchars(explode(' ', $_SESSION["name"])[0]); ?></h1>
                <p>Welcome to your control center. Manage rides and payments.</p>
            </div>
            <div class="date-badge">
                <?php echo date("l, d M Y"); ?>
            </div>
        </header>

        <div class="dashboard-grid">

            <?php if ($ongoingRide): ?>
            <a href="live_tracking.php?ride_id=<?php echo $ongoingRide; ?>" class="card live-card">
                <div style="display:flex; gap:1.5rem; align-items:center;">
                    <div style="width:50px; height:50px; background:rgba(6,182,212,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--accent-cyan);">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                    </div>
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <h3 style="margin:0; font-size:1.1rem;">Ongoing Ride Detected</h3>
                            <span class="pulse-dot"></span>
                        </div>
                        <p style="margin:0;">Vehicle is currently moving. Click to track live.</p>
                    </div>
                </div>
                <div style="padding-right:1rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </a>
            <?php endif; ?>

            <a href="add_child.php" class="card c-purple">
                <div class="icon-box">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                </div>
                <div>
                    <h3>Add Child</h3>
                    <p>Register a new student profile for transport services.</p>
                </div>
            </a>

            <a href="book_ride.php" class="card c-green">
                <div class="icon-box">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                </div>
                <div>
                    <h3>Book Ride</h3>
                    <p>Schedule a new pickup or drop-off for your child.</p>
                </div>
            </a>

            <a href="rides.php" class="card c-blue">
                <div class="icon-box">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div>
                    <h3>Ride History</h3>
                    <p>View previous trips and upcoming scheduled rides.</p>
                </div>
            </a>

            <a href="recurring_ride.php" class="card c-orange">
                <div class="icon-box">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div>
                    <h3>Recurring Rides</h3>
                    <p>Set up automated daily or weekly transport schedules.</p>
                </div>
            </a>

            <a href="plans.php" class="card c-gold">
                <div class="icon-box">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 18V6"></path></svg>
                </div>
                <div>
                    <h3>Subscription Plans</h3>
                    <p>Manage monthly subscriptions and billing history.</p>
                </div>
            </a>

        </div>
    </main>

</body>
</html>