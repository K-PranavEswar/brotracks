<?php
// FILE: BroTracks/parent/plans.php
session_start();

// 1. Security Check (Matches your other files)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "parent") {
    // Redirect to login if not logged in (adjust path as needed)
    // header("Location: ../auth/login.php");
    // exit;
    
    // For demo purposes, we will just set mock data if session is empty
    if(!isset($_SESSION["name"])) $_SESSION["name"] = "Parent User";
}

// 2. Sidebar Helper: Get User Initials
$nameParts = explode(" ", $_SESSION["name"] ?? 'User');
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
    <title>Subscription Plans | BroTracks</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- COPY OF YOUR EXISTING CSS --- */
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
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }

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
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* --- PRICING SPECIFIC STYLES --- */
        .pricing-header {
            text-align: center;
            margin-bottom: 3rem;
            max-width: 600px;
        }
        .pricing-header h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .pricing-header p { color: var(--text-muted); }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            width: 100%;
            max-width: 1100px;
        }

        .plan-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        /* Highlight the Term Plan */
        .plan-card.popular {
            border: 2px solid var(--primary);
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, var(--bg-card) 100%);
        }

        .badge-popular {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .plan-name { font-size: 1.25rem; font-weight: 600; color: var(--text-main); margin-bottom: 1rem; }
        
        .plan-price { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: var(--text-main); 
            margin-bottom: 0.5rem;
            display: flex;
            align-items: baseline;
        }
        .plan-price span { font-size: 1rem; color: var(--text-muted); font-weight: 400; margin-left: 5px; }

        .feature-list {
            list-style: none;
            margin: 2rem 0;
            flex: 1; /* Pushes button to bottom */
        }

        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .check-icon {
            color: var(--success);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Buttons */
        .btn-plan {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
        }
        .btn-outline:hover {
            border-color: var(--text-main);
            background-color: rgba(255,255,255,0.05);
        }

        .btn-filled {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
            box-shadow: 0 4px 12px var(--primary-glow);
        }
        .btn-filled:hover {
            background-color: var(--primary-hover);
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; border: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .pricing-grid { grid-template-columns: 1fr; }
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
                <a href="plans.php" class="active" style="color: #facc15;">
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
        
        <div class="pricing-header">
            <h2>Choose Your Plan</h2>
            <p>Select the tracking plan that fits your family's needs perfectly. Upgrade or downgrade at any time.</p>
        </div>

        <div class="pricing-grid">
            
            <div class="plan-card">
                <div class="plan-name">Basic</div>
                <div class="plan-price">₹0 <span>/mo</span></div>
                
                <ul class="feature-list">
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        1 Child Profile
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Basic GPS Tracking
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Email Support
                    </li>
                </ul>
                
                <a href="pay.php?plan=basic&amount=0" class="btn-plan btn-outline">Sign up Free</a>
            </div>

            <div class="plan-card">
                <div class="plan-name">Monthly</div>
                <div class="plan-price">₹250 <span>/mo</span></div>
                
                <ul class="feature-list">
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Up to 3 Children
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Real-time Tracking
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Geofencing Alerts
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Priority Support
                    </li>
                </ul>
                
                <a href="pay.php?plan=monthly&amount=250" class="btn-plan btn-filled">Get Monthly</a>
            </div>

            <div class="plan-card popular">
                <span class="badge-popular">Best Value</span>
                <div class="plan-name" style="color: var(--primary);">Annual Term</div>
                <div class="plan-price">₹650 <span>/yr</span></div>
                
                <ul class="feature-list">
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Unlimited Children
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        1 Year History Reports
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Instant SMS Alerts
                    </li>
                    <li>
                        <svg class="check-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        24/7 Phone Support
                    </li>
                </ul>
                
                <a href="pay.php?plan=term&amount=650" class="btn-plan btn-filled">Get Annual Plan</a>
            </div>

        </div>

    </main>

</body>
</html>