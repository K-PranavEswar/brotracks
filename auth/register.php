<?php
session_start();

// Debugging (Disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../config/db.php";

$error = "";
// Initialize variables to keep input data on error
$name_val = "";
$email_val = "";
$phone_val = "";
$role_val = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and Capture Input
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $passwordRaw = $_POST["password"];
    $role = $_POST["role"];

    // Preserve values for display
    $name_val = $name;
    $email_val = $email;
    $phone_val = $phone;
    $role_val = $role;

    // Artificial delay to discourage bot spam
    usleep(150000);

    if ($name && $email && $passwordRaw && $role) {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "This email address is already registered.";
            } else {
                // Transaction ensures both User and Role-Specific tables update, or neither does
                $pdo->beginTransaction();

                // 1. Create Base User
                $password = password_hash($passwordRaw, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $password, $role, $phone]);
                
                $userId = $pdo->lastInsertId();

                // 2. Create Role Specific Entry
                if ($role === "parent") {
                    $stmt2 = $pdo->prepare("INSERT INTO parents (user_id, address) VALUES (?, ?)");
                    $stmt2->execute([$userId, ""]); // Default empty address
                } elseif ($role === "driver") {
                    $stmt2 = $pdo->prepare("INSERT INTO drivers (user_id, license_no, vehicle_no, status) VALUES (?, ?, ?, 'pending')");
                    $stmt2->execute([$userId, "", ""]); // Default empty license/vehicle
                }

                $pdo->commit();

                $_SESSION["success"] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "System error: " . $e->getMessage();
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BroTracks | Join the Fleet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        /* ========================================
           SHARED VISUAL CORE (Matches Login)
        ======================================== */
        :root {
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --accent: #06b6d4;
            --accent-glow: rgba(6, 182, 212, 0.4);
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.65);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --font-main: 'Outfit', sans-serif;
            --font-display: 'Space Grotesk', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            width: 100vw;
            overflow: hidden; /* Hide scrollbars for full immersion */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Canvas & Background */
        #canvas-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .overlay-gradient {
            position: absolute; width: 100%; height: 100%;
            background: radial-gradient(circle at center, transparent 0%, var(--bg-dark) 90%);
            z-index: 2; pointer-events: none;
        }

        /* 3D Wrapper */
        .tilt-wrapper { perspective: 1000px; z-index: 10; width: 100%; max-width: 500px; padding: 1rem;}

        /* Card Style */
        .register-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            width: 100%;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            transform-style: preserve-3d;
            position: relative;
            overflow: hidden;
        }

        /* Scanline Animation */
        .register-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            animation: scanline 4s linear infinite;
        }
        @keyframes scanline { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        /* Headers */
        .header-section { text-align: center; margin-bottom: 2rem; transform: translateZ(20px); }
        h2 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        p.subtitle { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }

        /* ========================================
           FORM GRID & INPUTS
        ======================================== */
        form { display: grid; gap: 1.25rem; transform: translateZ(10px); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .form-group { position: relative; }

        .form-control, .form-select {
            width: 100%;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 14px 14px 14px 44px;
            color: var(--text-main);
            font-size: 0.95rem;
            font-family: var(--font-main);
            transition: all 0.3s var(--ease-out);
        }

        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.6);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        /* Icons inside inputs */
        .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); pointer-events: none; transition: 0.3s;
        }
        .form-control:focus + .input-icon, .form-select:focus + .input-icon { color: var(--accent); }

        /* Custom Select Styling */
        .form-select {
            appearance: none;
            cursor: pointer;
        }
        .select-arrow {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); pointer-events: none;
        }

        /* Floating Labels */
        .floating-label {
            position: absolute; left: 44px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.95rem; transition: 0.3s var(--ease-out);
            pointer-events: none; padding: 0 4px;
        }
        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label,
        .form-select:focus ~ .floating-label,
        .form-select:valid ~ .floating-label {
            top: 0; transform: translateY(-50%) scale(0.85); left: 10px;
            color: var(--primary); background-color: var(--bg-dark); border-radius: 4px;
        }

        /* Submit Button */
        .btn-main {
            width: 100%; padding: 14px; margin-top: 0.5rem;
            background: linear-gradient(135deg, var(--accent) 0%, #0891b2 100%);
            border: none; border-radius: 12px; color: white; font-weight: 600;
            cursor: pointer; position: relative; overflow: hidden;
            transition: transform 0.2s;
        }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 10px 20px var(--accent-glow); }
        
        .footer-link { text-align: center; font-size: 0.9rem; margin-top: 1rem; }
        .footer-link a { color: var(--text-muted); text-decoration: none; transition: 0.3s; }
        .footer-link a:hover { color: var(--primary); }

        /* Alert */
        .alert-box {
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 0.85rem;
            display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem;
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; gap: 1.25rem;}
            .register-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <canvas id="canvas-bg"></canvas>
    <div class="overlay-gradient"></div>

    <div class="tilt-wrapper" id="tiltCard">
        <div class="register-card">
            
            <div class="header-section">
                <h2>Create Account</h2>
                <p class="subtitle">Join BroTracks Fleet Management</p>
            </div>

            <?php if (!empty($error)) { ?>
                <div class="alert-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="post" autocomplete="off">
                
                <div class="form-group">
                    <input type="text" id="name" name="name" class="form-control" placeholder=" " value="<?php echo htmlspecialchars($name_val); ?>" required>
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <label for="name" class="floating-label">Full Name</label>
                </div>

                <div class="form-group">
                    <input type="email" id="email" name="email" class="form-control" placeholder=" " value="<?php echo htmlspecialchars($email_val); ?>" required>
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <label for="email" class="floating-label">Email Address</label>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <input 
    type="tel" 
    id="phone" 
    name="phone" 
    class="form-control" 
    placeholder=" " 
    value="<?php echo htmlspecialchars($phone_val); ?>" 
    required
    maxlength="10"
    pattern="[0-9]{10}"
    inputmode="numeric"
>

                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <label for="phone" class="floating-label">Phone</label>
                    </div>

                    <div class="form-group">
                        <select name="role" id="role" class="form-select" required>
                            <option value="" disabled <?php echo empty($role_val) ? 'selected' : ''; ?>></option>
                            <option value="parent" <?php echo $role_val === 'parent' ? 'selected' : ''; ?>>Parent</option>
                            <option value="driver" <?php echo $role_val === 'driver' ? 'selected' : ''; ?>>Driver</option>
                        </select>
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <div class="select-arrow">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                        <label for="role" class="floating-label">Register As</label>
                    </div>
                </div>

                <div class="form-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <label for="password" class="floating-label">Create Password</label>
                </div>

                <button type="submit" class="btn-main">Complete Registration</button>

                <div class="footer-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>

            </form>
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
                this.dx = (Math.random() * 0.6) - 0.3;
                this.dy = (Math.random() * 0.6) - 0.3;
                this.size = (Math.random() * 2) + 1;
                this.color = Math.random() > 0.5 ? '#4f46e5' : '#06b6d4';
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.dx = -this.dx;
                if (this.y > canvas.height || this.y < 0) this.dy = -this.dy;
                this.x += this.dx; this.y += this.dy;
                
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        function init() {
            particlesArray = [];
            let count = (canvas.height * canvas.width) / 9000;
            for (let i = 0; i < count; i++) particlesArray.push(new Particle());
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                for (let j = i; j < particlesArray.length; j++) {
                    let dx = particlesArray[i].x - particlesArray[j].x;
                    let dy = particlesArray[i].y - particlesArray[j].y;
                    let distance = dx * dx + dy * dy;
                    if (distance < (canvas.width/7) * (canvas.height/7)) {
                        ctx.strokeStyle = `rgba(79, 70, 229, ${1 - distance/20000})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particlesArray[i].x, particlesArray[i].y);
                        ctx.lineTo(particlesArray[j].x, particlesArray[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        init(); animate();

        // 3D Tilt Logic
        const cardInner = document.querySelector('.register-card');
        document.addEventListener('mousemove', (e) => {
            if (window.innerWidth < 768) return;
            const xAxis = (window.innerWidth / 2 - e.pageX) / 35;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 35;
            cardInner.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            cardInner.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });
    </script>
</body>
</html>