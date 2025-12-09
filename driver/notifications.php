<?php
session_start();
require_once "../config/db.php";

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Fetch Driver Profile
$stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id=?");
$stmt->execute([$_SESSION["user_id"]]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) { die("Driver profile missing!"); }

// 3. Fetch Unassigned Requests
// We join tables to get Child Name, Parent Name, and Parent Phone
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS child_name, u.name AS parent_name, u.phone AS parent_phone
    FROM rides r
    JOIN children c ON r.child_id = c.id
    JOIN parents p ON r.parent_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE r.driver_id IS NULL AND r.status='requested'
    ORDER BY r.ride_date ASC, r.ride_time ASC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Requests | BroTracks</title>
    
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
            --glass-bg: rgba(30, 41, 59, 0.75);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-main: 'Outfit', sans-serif;
            --font-display: 'Space Grotesk', sans-serif;
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
        .content-wrapper { position: relative; z-index: 10; padding: 2rem; max-width: 800px; margin: 0 auto; }

        /* Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;
        }
        h2 { font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; }
        .badge-count { 
            background: var(--primary); color: white; padding: 2px 8px; border-radius: 12px; 
            font-size: 0.9rem; margin-left: 10px; vertical-align: middle;
        }

        .btn-back {
            padding: 10px 16px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            border-radius: 8px; color: var(--text-muted); text-decoration: none; font-size: 0.9rem;
            transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.1); color: white; border-color: white; }

        /* ========================================
           REQUEST CARD (Glassmorphism)
        ======================================== */
        .requests-grid { display: grid; gap: 1.5rem; }

        .request-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.5);
            border-color: rgba(16, 185, 129, 0.3);
        }

        /* Card Header */
        .card-header {
            padding: 1.25rem;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--glass-border);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .child-info { display: flex; align-items: center; gap: 12px; }
        .child-avatar {
            width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent), #2563eb);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.2rem;
        }
        .child-name { font-size: 1.1rem; font-weight: 600; }
        .req-time { font-size: 0.85rem; color: var(--text-muted); }

        .new-badge {
            background: rgba(16, 185, 129, 0.15); color: #34d399; 
            padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; 
            font-weight: 700; text-transform: uppercase; border: 1px solid rgba(16, 185, 129, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

        /* Card Body (Timeline) */
        .card-body { padding: 1.5rem; }

        .route-timeline { position: relative; padding-left: 10px; margin-bottom: 1.5rem; }
        .timeline-line {
            position: absolute; left: 19px; top: 15px; bottom: 35px; width: 2px;
            background: repeating-linear-gradient(to bottom, var(--text-muted) 0, var(--text-muted) 4px, transparent 4px, transparent 8px);
            opacity: 0.3;
        }

        .stop-point { display: flex; gap: 15px; margin-bottom: 1.2rem; position: relative; z-index: 2; }
        .stop-point:last-child { margin-bottom: 0; }
        
        .point-icon {
            width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; margin-top: 2px;
            background: var(--bg-dark); border: 2px solid;
            display: flex; align-items: center; justify-content: center;
        }
        .icon-pickup { border-color: var(--accent); color: var(--accent); }
        .icon-drop { border-color: var(--primary); color: var(--primary); }

        .point-details h4 { font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; }
        .point-details p { font-size: 1rem; color: white; }

        .ride-meta {
            display: flex; gap: 20px; padding-top: 1rem; border-top: 1px dashed var(--glass-border);
        }
        .meta-item { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.9rem; }
        .meta-item svg { color: var(--primary); }

        /* Actions */
        .card-actions { padding: 1.25rem; display: flex; gap: 1rem; }
        
        .btn-accept {
            flex: 1; padding: 12px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, var(--primary) 0%, #059669 100%);
            color: white; font-weight: 600; font-size: 1rem; cursor: pointer;
            text-align: center; text-decoration: none;
            box-shadow: 0 4px 15px var(--primary-glow); transition: 0.2s;
        }
        .btn-accept:hover { transform: translateY(-2px); box-shadow: 0 10px 25px var(--primary-glow); }

        /* Empty State */
        .empty-state { text-align: center; padding: 4rem 1rem; opacity: 0.7; }

        /* Alerts */
        .alert-error {
            background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;
        }

    </style>
</head>
<body>

    <canvas id="canvas-bg"></canvas>

    <div class="content-wrapper">
        <div class="page-header">
            <h2>Requests <span class="badge-count"><?php echo count($requests); ?></span></h2>
            <a href="dashboard.php" class="btn-back">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                Cockpit
            </a>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'taken'): ?>
            <div class="alert-error">
                ⚠ This ride has already been accepted by another driver.
            </div>
        <?php endif; ?>

        <div class="requests-grid">
            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $r): 
                    $dateStr = date("D, M d", strtotime($r['ride_date']));
                    $timeStr = date("h:i A", strtotime($r['ride_time']));
                    $childInitial = strtoupper(substr($r['child_name'], 0, 1));
                ?>
                <div class="request-card">
                    <div class="card-header">
                        <div class="child-info">
                            <div class="child-avatar"><?php echo $childInitial; ?></div>
                            <div>
                                <div class="child-name"><?php echo htmlspecialchars($r['child_name']); ?></div>
                                <div class="req-time">Parent: <?php echo htmlspecialchars($r['parent_name']); ?></div>
                            </div>
                        </div>
                        <span class="new-badge">New Request</span>
                    </div>

                    <div class="card-body">
                        <div class="route-timeline">
                            <div class="timeline-line"></div>
                            
                            <div class="stop-point">
                                <div class="point-icon icon-pickup">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                </div>
                                <div class="point-details">
                                    <h4>Pickup Location</h4>
                                    <p><?php echo htmlspecialchars($r['pickup_location']); ?></p>
                                </div>
                            </div>

                            <div class="stop-point">
                                <div class="point-icon icon-drop">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20"/></svg>
                                </div>
                                <div class="point-details">
                                    <h4>Drop-off Location</h4>
                                    <p><?php echo htmlspecialchars($r['drop_location']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="ride-meta">
                            <div class="meta-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <?php echo $dateStr; ?>
                            </div>
                            <div class="meta-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <?php echo $timeStr; ?>
                            </div>
                            <div class="meta-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <?php echo htmlspecialchars($r['parent_phone']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="accept_ride.php?id=<?php echo $r['id']; ?>" class="btn-accept">Accept Assignment</a>
                    </div>
                </div>
                <?php endforeach; ?>
            
            <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--text-muted); margin-bottom: 1rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>All caught up</h3>
                    <p>There are no new ride requests at this moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('canvas-bg');
        const ctx = canvas.getContext('2d');
        let particlesArray = [];
        function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
        window.addEventListener('resize', resize); resize();

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height;
                this.dx = (Math.random() * 0.2) - 0.1; this.dy = (Math.random() * 0.2) - 0.1;
                this.size = (Math.random() * 2) + 0.5; this.color = '#10b981';
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