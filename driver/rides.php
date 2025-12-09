<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Fetch Driver Profile
$stmtDriver = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
$stmtDriver->execute([$_SESSION["user_id"]]);
$driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    die("Driver profile configuration error.");
}
$driverId = $driver["id"];

// 3. Handle Actions (Start/Complete)
if (isset($_GET["action"], $_GET["id"])) {
    $action = $_GET["action"];
    $rideId = (int)$_GET["id"];
    
    // Simulate network delay for UI realism if loading indicators were used
    // usleep(200000); 

    if ($action === "start") {
        $stmt = $pdo->prepare("UPDATE rides SET status='on_going' WHERE id=? AND driver_id=?");
        $stmt->execute([$rideId, $driverId]);
    }
    if ($action === "complete") {
        $stmt = $pdo->prepare("UPDATE rides SET status='completed' WHERE id=? AND driver_id=?");
        $stmt->execute([$rideId, $driverId]);
    }
    header("Location: rides.php");
    exit;
}

// 4. Fetch Assigned Rides
// Ordered by active status first, then date
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS child_name 
    FROM rides r 
    JOIN children c ON r.child_id = c.id 
    WHERE r.driver_id = ? 
    ORDER BY 
        CASE 
            WHEN r.status = 'on_going' THEN 1
            WHEN r.status = 'accepted' THEN 2
            ELSE 3 
        END,
        r.ride_date ASC, r.ride_time ASC
");
$stmt->execute([$driverId]);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Rides | BroTracks</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        /* ========================================
           THEME VARIABLES
        ======================================== */
        :root {
            --primary: #10b981; /* Emerald */
            --primary-glow: rgba(16, 185, 129, 0.4);
            --accent: #3b82f6;
            --bg-dark: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-main: 'Outfit', sans-serif;
            --font-display: 'Space Grotesk', sans-serif;
            
            /* Status Colors */
            --status-accepted: #f59e0b; /* Amber */
            --status-ongoing: #10b981;  /* Green */
            --status-completed: #64748b; /* Slate */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        #canvas-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .content-wrapper { position: relative; z-index: 10; padding: 1.5rem; max-width: 800px; margin: 0 auto; }

        /* Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;
        }
        h2 { font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: white; }
        
        .btn-back {
            padding: 10px 16px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            border-radius: 8px; color: var(--text-muted); text-decoration: none; font-size: 0.9rem;
            transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.1); color: white; border-color: white; }

        /* ========================================
           RIDE CARDS (Timeline Style)
        ======================================== */
        .rides-list { display: flex; flex-direction: column; gap: 1.5rem; }

        .ride-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 0; /* Padding handled internally */
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        /* Status Border indicator on left */
        .ride-card.accepted { border-left: 4px solid var(--status-accepted); }
        .ride-card.on_going { border-left: 4px solid var(--status-ongoing); box-shadow: 0 0 20px rgba(16, 185, 129, 0.15); }
        .ride-card.completed { border-left: 4px solid var(--status-completed); opacity: 0.8; }

        /* Card Header */
        .card-top {
            padding: 1.25rem;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--glass-border);
            display: flex; justify-content: space-between; align-items: center;
        }

        .passenger-info { display: flex; align-items: center; gap: 10px; }
        .p-icon { 
            width: 36px; height: 36px; background: rgba(255,255,255,0.05); 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
        }
        .p-name { font-size: 1.1rem; font-weight: 600; color: white; }
        
        .status-badge {
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
            padding: 4px 10px; border-radius: 20px;
        }
        .badge-accepted { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-on_going { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); animation: pulse-badge 2s infinite; }
        .badge-completed { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }

        @keyframes pulse-badge { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

        /* Route Timeline */
        .card-body { padding: 1.5rem; }

        .time-display {
            font-family: var(--font-display);
            font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 8px;
        }

        .route-container { position: relative; padding-left: 10px; }
        
        /* The connecting vertical line */
        .route-line {
            position: absolute; left: 19px; top: 15px; bottom: 35px;
            width: 2px; background: repeating-linear-gradient(to bottom, var(--text-muted) 0, var(--text-muted) 4px, transparent 4px, transparent 8px);
            opacity: 0.3; z-index: 0;
        }

        .stop-point {
            display: flex; gap: 15px; position: relative; z-index: 1;
            margin-bottom: 1.5rem;
        }
        .stop-point:last-child { margin-bottom: 0; }

        .stop-icon {
            width: 20px; height: 20px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-dark); border: 2px solid; flex-shrink: 0; margin-top: 2px;
        }
        .icon-pickup { border-color: var(--accent); color: var(--accent); }
        .icon-drop { border-color: var(--status-ongoing); color: var(--status-ongoing); }

        .address-box h4 { font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; }
        .address-box p { font-size: 1rem; color: white; line-height: 1.3; }

        /* Card Footer (Actions) */
        .card-footer { padding: 1.25rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: flex-end; }

        .btn-action {
            width: 100%; text-align: center; padding: 14px; border-radius: 12px;
            font-weight: 600; font-size: 1rem; text-decoration: none; border: none;
            cursor: pointer; transition: 0.2s;
        }

        .btn-start {
            background: linear-gradient(135deg, var(--status-ongoing) 0%, #059669 100%);
            color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-start:active { transform: scale(0.98); }

        .btn-complete {
            background: linear-gradient(135deg, var(--accent) 0%, #2563eb 100%);
            color: white; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .completed-msg {
            color: var(--status-completed); font-size: 0.9rem; font-style: italic; width: 100%; text-align: center;
        }

        /* Empty State */
        .empty-state { text-align: center; padding: 3rem; color: var(--text-muted); }

    </style>
</head>
<body>

    <canvas id="canvas-bg"></canvas>

    <div class="content-wrapper">
        <div class="page-header">
            <h2>Ride Manifest</h2>
            <a href="dashboard.php" class="btn-back">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                Cockpit
            </a>
        </div>

        <div class="rides-list">
            <?php if (count($rides) > 0): ?>
                <?php foreach ($rides as $r): 
                    // Formatting
                    $status = $r['status'];
                    $badgeClass = 'badge-' . $status;
                    $cardClass = $status;
                    $dateStr = date("M d • h:i A", strtotime($r['ride_date'] . ' ' . $r['ride_time']));
                ?>
                <div class="ride-card <?php echo $cardClass; ?>">
                    
                    <div class="card-top">
                        <div class="passenger-info">
                            <div class="p-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <span class="p-name"><?php echo htmlspecialchars($r["child_name"]); ?></span>
                        </div>
                        <span class="status-badge <?php echo $badgeClass; ?>">
                            <?php echo str_replace('_', ' ', $status); ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="time-display">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <?php echo $dateStr; ?>
                        </div>

                        <div class="route-container">
                            <div class="route-line"></div>
                            
                            <div class="stop-point">
                                <div class="stop-icon icon-pickup">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                </div>
                                <div class="address-box">
                                    <h4>Pickup</h4>
                                    <p><?php echo htmlspecialchars($r["pickup_location"]); ?></p>
                                </div>
                            </div>

                            <div class="stop-point">
                                <div class="stop-icon icon-drop">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20"/></svg>
                                </div>
                                <div class="address-box">
                                    <h4>Drop Off</h4>
                                    <p><?php echo htmlspecialchars($r["drop_location"]); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if ($status === "accepted"): ?>
                            <a href="?action=start&id=<?php echo $r["id"]; ?>" class="btn-action btn-start">
                                Start Trip
                            </a>
                        <?php elseif ($status === "on_going"): ?>
                            <a href="?action=complete&id=<?php echo $r["id"]; ?>" class="btn-action btn-complete">
                                Complete Trip
                            </a>
                        <?php elseif ($status === "completed"): ?>
                            <span class="completed-msg">Trip finalized successfully.</span>
                        <?php else: ?>
                            <span class="completed-msg">Status: <?php echo htmlspecialchars($status); ?></span>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            
            <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>No assignments yet</h3>
                    <p>You have no active or upcoming rides assigned to you.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('canvas-bg');
        const ctx = canvas.getContext('2d');
        let particlesArray = [];
        function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height;
                this.dx = (Math.random() * 0.2) - 0.1; this.dy = (Math.random() * 0.2) - 0.1;
                this.size = (Math.random() * 2) + 0.5; this.color = '#10b981'; // Green Theme
            }
            update() {
                if(this.x>canvas.width||this.x<0)this.dx=-this.dx; if(this.y>canvas.height||this.y<0)this.dy=-this.dy;
                this.x+=this.dx; this.y+=this.dy;
                ctx.beginPath(); ctx.arc(this.x,this.y,this.size,0,Math.PI*2); ctx.fillStyle=this.color; ctx.globalAlpha=0.4; ctx.fill();
            }
        }
        function init() { particlesArray = []; for(let i=0;i<(canvas.height*canvas.width)/15000;i++) particlesArray.push(new Particle()); }
        function animate() { requestAnimationFrame(animate); ctx.clearRect(0,0,canvas.width,canvas.height); particlesArray.forEach(p=>p.update()); }
        init(); animate();
    </script>
</body>
</html>