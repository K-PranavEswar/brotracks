<?php
session_start();
require_once "../config/db.php";

// 1. Security & Role Check
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

// 3. Fetch Parent Profile
$stmtParent = $pdo->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmtParent->execute([$_SESSION["user_id"]]);
$parent = $stmtParent->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    die("Parent profile configuration error. Please contact support.");
}
$parentId = $parent["id"];

// 4. Fetch Children for Dropdown
$childrenStmt = $pdo->prepare("SELECT id, name FROM children WHERE parent_id = ?");
$childrenStmt->execute([$parentId]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Handle Form Submission
$error = "";
$success = "";

// Initialize value holders
$pickup_val = "";
$drop_val = "";
$date_val = "";
$time_val = "";
$child_val = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $child_id = $_POST["child_id"] ?? '';
    $pickup = trim($_POST["pickup"]);
    $drop = trim($_POST["drop"]);
    $date = $_POST["ride_date"];
    $time = $_POST["ride_time"];

    // Persist input for UI
    $child_val = $child_id;
    $pickup_val = $pickup;
    $drop_val = $drop;
    $date_val = $date;
    $time_val = $time;

    // Simulate network delay (from your original code)
    usleep(100000);

    if ($child_id && $pickup && $drop && $date && $time) {
        try {
            $stmt = $pdo->prepare("INSERT INTO rides (child_id, parent_id, driver_id, pickup_location, drop_location, ride_date, ride_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'requested', NOW())");
            
            // Passing null for driver_id as per original logic
            $stmt->execute([$child_id, $parentId, null, $pickup, $drop, $date, $time]);
            
            $success = "Ride request transmitted successfully.";
            
            // Clear form on success
            $pickup_val = ""; $drop_val = ""; $date_val = ""; $time_val = ""; $child_val = "";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please complete all ride details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ride | BroTracks</title>
    
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR (Standardized) --- */
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* --- FORM CONTAINER --- */
        .form-container {
            width: 100%;
            max-width: 600px;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .form-header { margin-bottom: 2rem; }
        .form-header h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .form-header p { color: var(--text-muted); font-size: 0.95rem; }

        .form-group { margin-bottom: 1.5rem; }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--bg-body);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.2s;
            /* Ensures date/time icons are visible in dark mode */
            color-scheme: dark; 
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        /* 2-column layout for date/time */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            flex: 2;
        }
        .btn-primary:hover { background-color: var(--primary-hover); }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            flex: 1;
        }
        .btn-secondary:hover {
            color: var(--text-main);
            border-color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; border: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
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
                <a href="book_ride.php" class="active">
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
        
        <div class="form-container">
            <div class="form-header">
                <h2>Book a Ride</h2>
                <p>Schedule a one-time pickup or drop-off for your child.</p>
            </div>

            <?php if (!empty($success)) { ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php } ?>

            <?php if (!empty($error)) { ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="post" autocomplete="off">
                
                <div class="form-group">
                    <label class="form-label" for="child_id">Select Child</label>
                    <select name="child_id" id="child_id" class="form-select" required>
                        <option value="" disabled <?php echo empty($child_val) ? 'selected' : ''; ?>>-- Choose Student --</option>
                        <?php foreach ($children as $c) { ?>
                            <option value="<?php echo $c["id"]; ?>" <?php echo $child_val == $c["id"] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c["name"]); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pickup">Pickup Location</label>
                    <input type="text" id="pickup" name="pickup" class="form-control" placeholder="e.g. Home Address" value="<?php echo htmlspecialchars($pickup_val); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="drop">Drop-off Location</label>
                    <input type="text" id="drop" name="drop" class="form-control" placeholder="e.g. Springfield High School" value="<?php echo htmlspecialchars($drop_val); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="ride_date">Date</label>
                        <input type="date" id="ride_date" name="ride_date" class="form-control" value="<?php echo htmlspecialchars($date_val); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ride_time">Time</label>
                        <input type="time" id="ride_time" name="ride_time" class="form-control" value="<?php echo htmlspecialchars($time_val); ?>" required>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Request Ride</button>
                </div>

            </form>
        </div>

    </main>

</body>
</html>