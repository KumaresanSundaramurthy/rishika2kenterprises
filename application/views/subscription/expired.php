<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .subscription-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            overflow: hidden;
        }
        .subscription-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .subscription-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .subscription-body {
            padding: 40px;
        }
        .plan-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .plan-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            transform: translateY(-5px);
        }
        .plan-card.recommended {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .plan-price {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .plan-features li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .plan-features li:last-child {
            border-bottom: none;
        }
        .btn-renew {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-renew:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
        }
        .status-expired {
            background: #fee;
            color: #c00;
        }
        .status-grace {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="subscription-card">
            <div class="subscription-header">
                <i class="bx bx-error-circle subscription-icon"></i>
                <h1>Subscription <?php echo $SubscriptionInfo->status === 'Expired' ? 'Expired' : 'Required'; ?></h1>
                <p class="mb-0"><?php echo htmlspecialchars($SubscriptionInfo->message); ?></p>
                <?php if ($SubscriptionInfo->inGracePeriod): ?>
                    <span class="status-badge status-grace">
                        <i class="bx bx-time-five me-1"></i>Grace Period Active
                    </span>
                <?php else: ?>
                    <span class="status-badge status-expired">
                        <i class="bx bx-x-circle me-1"></i>Access Blocked
                    </span>
                <?php endif; ?>
            </div>

            <div class="subscription-body">
                <h3 class="mb-4 text-center">Choose a Plan to Continue</h3>
                
                <div class="row">
                    <?php foreach ($Plans as $plan): ?>
                        <?php if ($plan->PlanCode === 'FREE_TRIAL') continue; ?>
                        <div class="col-md-6">
                            <div class="plan-card <?php echo strpos($plan->PlanCode, 'PRO') !== false ? 'recommended' : ''; ?>">
                                <?php if (strpos($plan->PlanCode, 'PRO') !== false): ?>
                                    <div class="badge bg-primary mb-2">Recommended</div>
                                <?php endif; ?>
                                
                                <h4><?php echo htmlspecialchars($plan->PlanName); ?></h4>
                                <div class="plan-price">
                                    <?php echo $plan->Currency; ?> <?php echo number_format($plan->Price, 2); ?>
                                    <small class="text-muted" style="font-size: 16px;">/ <?php echo $plan->DurationDays; ?> days</small>
                                </div>
                                
                                <p class="text-muted"><?php echo htmlspecialchars($plan->Description); ?></p>
                                
                                <ul class="plan-features">
                                    <?php 
                                    $features = json_decode($plan->Features, true);
                                    if ($features):
                                        foreach ($features as $feature):
                                    ?>
                                        <li><i class="bx bx-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>
                                
                                <button class="btn btn-renew w-100" onclick="selectPlan('<?php echo $plan->PlanCode; ?>', '<?php echo $plan->PlanName; ?>', <?php echo $plan->Price; ?>)">
                                    <i class="bx bx-cart me-2"></i>Select Plan
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted">Need help? <a href="mailto:support@yourcompany.com">Contact Support</a></p>
                    <a href="/logout" class="btn btn-link text-danger">
                        <i class="bx bx-log-out me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function selectPlan(planCode, planName, price) {
            if (confirm(`Activate ${planName} for ${price}?\n\nNote: Integrate with payment gateway for production.`)) {
                $.ajax({
                    url: '/subscription/activate',
                    method: 'POST',
                    data: {
                        PlanCode: planCode,
                        PaymentMethod: 'Demo',
                        TransactionID: 'DEMO_' + Date.now()
                    },
                    success: function(response) {
                        if (!response.Error) {
                            alert('Subscription activated successfully!');
                            window.location.href = response.RedirectUrl || '/dashboard';
                        } else {
                            alert('Error: ' + response.Message);
                        }
                    },
                    error: function() {
                        alert('Failed to activate subscription. Please try again.');
                    }
                });
            }
        }
    </script>
</body>
</html>
