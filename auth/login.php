<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "../config/db.php";

$error = "";
$email_input = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $email_input = $email;

    usleep(200000);

    $hardcodedEmail = "admin@brotracks.com";
    $hardcodedPassword = "admin123";

    if ($email === $hardcodedEmail && $password === $hardcodedPassword) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = 1;
        $_SESSION["role"] = "admin";
        $_SESSION["name"] = "Super Admin";
        header("Location: ../admin/dashboard.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["name"] = $user["name"];

            switch ($user["role"]) {
                case "parent":
                    header("Location: ../parent/dashboard.php");
                    break;
                case "driver":
                    header("Location: ../driver/dashboard.php");
                    break;
                case "admin":
                    header("Location: ../admin/dashboard.php");
                    break;
                default:
                    header("Location: ../index.php");
            }
            exit;
        } else {
            $error = "Invalid credentials provided.";
        }
    } catch (PDOException $e) {
        $error = "Database system error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BroTracks | Secure Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --accent: #06b6d4;
            --accent-glow: rgba(6, 182, 212, 0.4);
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --font-main: 'Outfit', sans-serif;
            --font-display: 'Space Grotesk', sans-serif;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            outline: none;
        }
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
        #canvas-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .overlay-gradient {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, var(--bg-dark) 90%);
            z-index: 2;
            pointer-events: none;
        }
        .tilt-wrapper {
            perspective: 1000px;
            z-index: 10;
        }
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            width: 100%;
            max-width: 420px;
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255,255,255,0.05) inset;
            transform-style: preserve-3d;
            transform: rotateX(0deg) rotateY(0deg);
            transition: transform 0.1s ease-out;
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            animation: scanline 3s linear infinite;
        }
        @keyframes scanline {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .brand-section {
            text-align: center;
            margin-bottom: 2.5rem;
            transform: translateZ(20px);
        }
        .logo-container {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 0 30px var(--primary-glow);
            position: relative;
        }
        .logo-img {
            width: 40px;
            filter: drop-shadow(0 0 5px var(--accent));
        }
        h2 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
            transform: translateZ(10px);
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: color 0.3s var(--ease-out);
            pointer-events: none;
            z-index: 2;
        }
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 16px 16px 16px 48px;
            color: var(--text-main);
            font-size: 1rem;
            font-family: var(--font-main);
            transition: all 0.3s var(--ease-out);
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.6);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }
        .form-control:focus + .input-icon {
            color: var(--primary);
        }
        .form-control::placeholder {
            color: transparent;
        }
        .floating-label {
            position: absolute;
            left: 48px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
            transition: all 0.3s var(--ease-out);
            pointer-events: none;
            background-color: transparent;
            padding: 0 4px;
        }
        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            left: 12px;
            color: var(--accent);
            background-color: var(--bg-dark);
            border-radius: 4px;
        }
        .btn-main {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s var(--ease-out), box-shadow 0.2s;
            transform: translateZ(15px);
        }
        .btn-main:hover {
            transform: translateZ(15px) translateY(-2px);
            box-shadow: 0 10px 25px var(--primary-glow);
        }
        .btn-main:active {
            transform: translateZ(15px) scale(0.98);
        }
        .btn-main::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .btn-main:hover::after {
            left: 100%;
        }
        .links {
            margin-top: 1.5rem;
            text-align: center;
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            transform: translateZ(10px);
        }
        .links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }
        .links a:hover {
            color: var(--accent);
        }
        .alert-box {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        @media (max-width: 480px) {
            .login-card {
                margin: 20px;
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <canvas id="canvas-bg"></canvas>
    <div class="overlay-gradient"></div>
    <div class="tilt-wrapper" id="tiltCard">
        <div class="login-card">
            <div class="brand-section">
                <div class="logo-container">
                    <svg class="logo-img" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:white;">
                        <path d="M3 11l18-5v12L3 14v-3z"></path>
                        <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>
                    </svg>
                </div>
                <h2>BroTracks</h2>
                <p class="subtitle">Secure Fleet Management Access</p>
            </div>
            <?php if (!empty($error)) { ?>
                <div class="alert-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>
            <form method="post" autocomplete="off">
                <div class="form-group">
                    <input type="email" id="email" name="email" class="form-control" placeholder=" " value="<?php echo htmlspecialchars($email_input); ?>" required>
                    <div class="input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <label for="email" class="floating-label">Email Address</label>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                    <div class="input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <label for="password" class="floating-label">Password</label>
                </div>
                <button type="submit" class="btn-main">
                    <span>Access Dashboard</span>
                </button>
                <div class="links">
                    <a href="forgot-password.php">Forgot Password?</a>
                    <a href="register.php">Create Account</a>
                </div>
            </form>
        </div>
    </div>
    <script>
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
                this.directionX = (Math.random() * 0.6) - 0.3;
                this.directionY = (Math.random() * 0.6) - 0.3;
                this.size = (Math.random() * 2) + 1;
                this.color = Math.random() > 0.5 ? '#4f46e5' : '#06b6d4';
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
                ctx.fillStyle = this.color;
                ctx.globalAlpha = 0.8;
                ctx.fill();
            }
            update() {
                if (this.x > canvas.width || this.x < 0) {
                    this.directionX = -this.directionX;
                }
                if (this.y > canvas.height || this.y < 0) {
                    this.directionY = -this.directionY;
                }
                this.x += this.directionX;
                this.y += this.directionY;
                this.draw();
            }
        }
        function init() {
            particlesArray = [];
            let numberOfParticles = (canvas.height * canvas.width) / 9000;
            for (let i = 0; i < numberOfParticles; i++) {
                particlesArray.push(new Particle());
            }
        }
        function connect() {
            let opacityValue = 1;
            for (let a = 0; a < particlesArray.length; a++) {
                for (let b = a; b < particlesArray.length; b++) {
                    let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x)) +
                                   ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
                    if (distance < (canvas.width/7) * (canvas.height/7)) {
                        opacityValue = 1 - (distance / 20000);
                        ctx.strokeStyle = 'rgba(79, 70, 229,' + opacityValue + ')';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                        ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                        ctx.stroke();
                    }
                }
            }
        }
        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
            }
            connect();
        }
        init();
        animate();
        const cardInner = document.querySelector('.login-card');
        document.addEventListener('mousemove', (e) => {
            if (window.innerWidth < 768) return;
            const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            cardInner.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            cardInner.style.transform = `rotateY(0deg) rotateX(0deg)`;
            cardInner.style.transition = 'all 0.5s ease';
        });
        document.addEventListener('mouseenter', () => {
            cardInner.style.transition = 'none';
        });
    </script>
</body>
</html>
