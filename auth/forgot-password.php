<?php
// FILE: BroTracks/auth/forgot-password.php
session_start();
require_once "../config/db.php";

$message = "";
$msg_type = ""; // success or error

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $msg_type = "error";
    } else {
        try {
            // Check if email exists in the 'user' table
            $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // In a real app, you would send an email here using PHPMailer.
                // For this demo, we will show a success message.
                $message = "A password reset link has been sent to " . htmlspecialchars($email);
                $msg_type = "success";
            } else {
                $message = "We could not find an account with that email.";
                $msg_type = "error";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BroTracks | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing exact styles from login.php for consistency */
        :root {
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --accent: #06b6d4;
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
            --font-main: 'Outfit', sans-serif;
            --font-display: 'Space Grotesk', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; outline: none; }
        body {
            font-family: var(--font-main);
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        #canvas-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .overlay-gradient {
            position: absolute; width: 100%; height: 100%;
            background: radial-gradient(circle at center, transparent 0%, var(--bg-dark) 90%);
            z-index: 2; pointer-events: none;
        }
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            width: 100%; max-width: 420px;
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative; z-index: 10;
        }
        .brand-section { text-align: center; margin-bottom: 2rem; }
        .logo-container {
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 0 30px var(--primary-glow);
        }
        h2 {
            font-family: var(--font-display);
            font-size: 1.75rem; font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        p.subtitle { color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; }
        
        .form-group { position: relative; margin-bottom: 1.5rem; }
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 16px 16px 16px 16px;
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.6);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }
        .btn-main {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%);
            border: none; border-radius: 12px;
            color: white; font-weight: 600; font-size: 1rem;
            cursor: pointer; transition: transform 0.2s;
        }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 10px 25px var(--primary-glow); }
        
        .alert-box {
            padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .back-link {
            display: block; text-align: center; margin-top: 1.5rem;
            color: var(--text-muted); text-decoration: none; font-size: 0.9rem;
            transition: color 0.3s;
        }
        .back-link:hover { color: var(--accent); }
    </style>
</head>
<body>
    <canvas id="canvas-bg"></canvas>
    <div class="overlay-gradient"></div>
    
    <div class="login-card">
        <div class="brand-section">
            <div class="logo-container">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:white;">
                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                </svg>
            </div>
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="alert-box <?php echo ($msg_type == 'error') ? 'alert-error' : 'alert-success'; ?>">
                <?php if($msg_type == 'error') { ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <?php } else { ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?php } ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn-main">
                Send Reset Link
            </button>
            
            <a href="login.php" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px; vertical-align: middle;">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Login
            </a>
        </form>
    </div>

    <script>
        // Simplified particle effect matching login.php
        const canvas = document.getElementById('canvas-bg');
        const ctx = canvas.getContext('2d');
        let particlesArray = [];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = (Math.random() * 2) + 1;
                this.speedX = (Math.random() * 0.5) - 0.25;
                this.speedY = (Math.random() * 0.5) - 0.25;
                this.color = Math.random() > 0.5 ? '#4f46e5' : '#06b6d4';
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
                if (this.y > canvas.height || this.y < 0) this.speedY = -this.speedY;
            }
            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function init() {
            for (let i = 0; i < 50; i++) particlesArray.push(new Particle());
        }
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                particlesArray[i].draw();
            }
            requestAnimationFrame(animate);
        }
        init();
        animate();
    </script>
</body>
</html>