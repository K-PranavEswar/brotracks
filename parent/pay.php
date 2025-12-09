<?php
// FILE: BroTracks/parent/pay.php
session_start();

// 1. Capture the data from the URL links
$plan = isset($_GET['plan']) ? htmlspecialchars($_GET['plan']) : 'unknown';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

// 2. Mock User Data (Replace this with your actual database user check)
$user_name = "Parent User"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment - BroTracks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-card { max-width: 500px; margin: 50px auto; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-pay { width: 100%; padding: 12px; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="card payment-card bg-white">
        <div class="text-center mb-4">
            <h3>Complete Your Purchase</h3>
            <p class="text-muted">You are upgrading to the <strong class="text-uppercase"><?php echo $plan; ?></strong> plan.</p>
        </div>

        <hr>

        <div class="d-flex justify-content-between mb-3">
            <span>Plan Name:</span>
            <span class="fw-bold text-uppercase"><?php echo $plan; ?></span>
        </div>
        
        <div class="d-flex justify-content-between mb-4">
            <span>Total Amount:</span>
            <span class="fw-bold text-success fs-4">₹<?php echo $amount; ?></span>
        </div>

        <form action="" method="POST">
            <?php if($amount == 0): ?>
                <div class="alert alert-info">This is a free plan. No payment required.</div>
                <button type="button" class="btn btn-success btn-pay" onclick="alert('Free plan activated!'); window.location.href='dashboard.php';">Activate Now</button>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label">Card Holder Name</label>
                    <input type="text" class="form-control" value="<?php echo $user_name; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Card Number (Mock)</label>
                    <input type="text" class="form-control" placeholder="0000 0000 0000 0000">
                </div>
                
                <button type="button" class="btn btn-primary btn-pay" onclick="alert('Payment Successful!'); window.location.href='dashboard.php';">
                    Pay ₹<?php echo $amount; ?>
                </button>
            <?php endif; ?>
        </form>

        <div class="mt-3 text-center">
            <a href="plans.php" class="text-decoration-none text-secondary">Cancel and go back</a>
        </div>
    </div>
</div>

</body>
</html>