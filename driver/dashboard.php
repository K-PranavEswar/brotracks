<?php
session_start();

// 1. Security Check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Avatar Initials Logic
$nameParts = explode(" ", $_SESSION["name"]);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Cockpit | BroTracks</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        /* ========================================
           THEME VARIABLES (Driver Edition)
        ======================================== */
        :root {
            --primary: #10b981; /* Emerald Green */
            --accent: #3b82f6;
            --bg-dark: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
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
        .content-wrapper { position: relative; z-index: 10; padding-bottom: 50px; }

        /* ========================================
           NAVIGATION BAR
        ======================================== */
        .glass-nav {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
        }

        .brand {
            font-family: var(--font-display);
            font-size: 1.5rem; font-weight: 700;
            color: white; display: flex; align-items: center; gap: 10px;
        }

        .user-menu { display: flex; align-items: center; gap: 20px; }

        /* 🔔 Notification Button */
        .notif-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .notif-btn:hover {
            background: rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.5);
            box-shadow: 0 0 12px rgba(16,185,129,0.4);
            transform: translateY(-1px);
        }

        .notif-icon {
            animation: ring 1.8s infinite ease-in-out;
            display: inline-block;
        }

        @keyframes ring {
            0% { transform: rotate(0); }
            20% { transform: rotate(15deg); }
            40% { transform: rotate(-15deg); }
            60% { transform: rotate(8deg); }
            80% { transform: rotate(-8deg); }
            100% { transform: rotate(0); }
        }

        .user-info { display: flex; align-items: center; gap: 12px; }

        .avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem;
        }

        .user-text { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 0.95rem; }
        .user-role { font-size: 0.75rem; color: var(--primary); }

        .btn-logout {
            padding: 8px 16px;
            border: 1px solid rgba(239, 68, 68, 0.5);
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.2); }

        /* ========================================
           CONTENT + CARDS
        ======================================== */
        .container { max-width: 1000px; margin: 0 auto; padding: 3rem 2rem; }
        .hero-title { font-size: 2.2rem; font-family: var(--font-display); }
        .hero-subtitle { color: var(--text-muted); margin-bottom: 2rem; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 2rem;
            border-radius: 20px;
            text-decoration: none;
            color: white;
            transition: 0.3s;
            position: relative;
            height: 250px;
        }

        .action-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);
        }

        @media(max-width:768px){
            .notif-text { display:none; }
            .glass-nav { padding: 1rem; }
        }
    </style>
</head>
<body>

<canvas id="canvas-bg"></canvas>

<div class="content-wrapper">

    <nav class="glass-nav">
        <div class="brand">
            <svg width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
            BroTracks
        </div>

        <div class="user-menu">

            <a href="notifications.php" class="notif-btn">
                <span class="notif-icon">🔔</span>
                <span class="notif-text">Ride Requests</span>
            </a>

            <div class="user-info">
                <div class="user-text">
                    <span class="user-name"><?= htmlspecialchars($_SESSION["name"]) ?></span>
                    <span class="user-role">Driver</span>
                </div>
                <div class="avatar"><?= $initials ?></div>
            </div>

            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="hero-title">Driver Cockpit</h1>
        <p class="hero-subtitle">Manage assigned rides and broadcast your live location.</p>

        <div class="dashboard-grid">

            <a href="rides.php" class="action-card">
                <h2>Assigned Rides</h2>
                <p>View your accepted or ongoing rides.</p>
            </a>

            <a href="update_location.php" class="action-card">
                <h2>Update GPS</h2>
                <p>Broadcast your real-time location to parents.</p>
            </a>

        </div>
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
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.dx = (Math.random() * 0.2) - 0.1;
        this.dy = (Math.random() * 0.2) - 0.1;
        this.size = (Math.random() * 2) + 0.5;
        this.color = '#10b981';
    }
    update() {
        if (this.x > canvas.width || this.x < 0) this.dx = -this.dx;
        if (this.y > canvas.height || this.y < 0) this.dy = -this.dy;
        this.x += this.dx; this.y += this.dy;

        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = this.color;
        ctx.globalAlpha = 0.4;
        ctx.fill();
    }
}

function init() {
    particlesArray = [];
    let count = (canvas.height * canvas.width) / 15000;
    for (let i = 0; i < count; i++) particlesArray.push(new Particle());
}

function animate() {
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particlesArray.forEach(p => p.update());
}

init(); animate();
</script>

</body>
</html>