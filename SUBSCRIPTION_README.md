# Subscription & License Management System

## Overview
Complete subscription management system with expiry checks, grace periods, and multi-tier plans for worldwide deployment.

## Features
- ✅ Subscription-based access control
- ✅ Multiple subscription plans (Trial, Basic, Pro, Enterprise)
- ✅ Grace period support (7 days default)
- ✅ Automatic expiry checks
- ✅ Login blocking for expired subscriptions
- ✅ Email notifications (7, 3, 1 days before expiry)
- ✅ Subscription history tracking
- ✅ Login attempt logging
- ✅ UTC timezone support (worldwide compatible)
- ✅ Admin subscription extension
- ✅ Payment integration ready

## Installation Steps

### 1. Database Setup
```bash
# Run the SQL file to create tables and insert default plans
mysql -u your_username -p your_database < database/subscription_system.sql

# Enable MySQL event scheduler (for automatic expiry checks)
mysql -u your_username -p -e "SET GLOBAL event_scheduler = ON;"
```

### 2. Enable Hook (Optional - for automatic checks on every request)
Edit `application/config/config.php`:
```php
$config['enable_hooks'] = TRUE;
```

Edit `application/config/hooks.php`:
```php
$hook['post_controller_constructor'] = array(
    'class'    => 'SubscriptionCheck',
    'function' => 'checkSubscription',
    'filename' => 'SubscriptionCheck.php',
    'filepath' => 'hooks'
);
```

### 3. Files Created
```
application/
├── libraries/
│   └── Subscription.php              # Core subscription logic
├── hooks/
│   └── SubscriptionCheck.php         # Auto-check on every request
├── controllers/
│   ├── Subscription.php              # Subscription management
│   └── Login.php (modified)          # Added subscription check
└── views/
    └── subscription/
        └── expired.php               # Subscription expired page

database/
└── subscription_system.sql           # Database schema
```

## Usage

### Check Subscription Status
```php
$this->load->library('subscription');
$result = $this->subscription->checkSubscription($userUID);

if ($result->isValid) {
    echo "Subscription active. {$result->daysRemaining} days remaining.";
} else {
    echo "Subscription expired: {$result->message}";
}
```

### Extend Subscription (Admin)
```php
$this->load->library('subscription');
$result = $this->subscription->extendSubscription($userUID, 30, 'BASIC_MONTHLY');
```

### Activate Subscription with Plan
```php
$this->load->library('subscription');
$paymentData = [
    'status' => 'Paid',
    'method' => 'Credit Card',
    'transactionId' => 'TXN123456'
];
$result = $this->subscription->activateSubscription($userUID, 'PRO_MONTHLY', $paymentData);
```

### Get Available Plans
```php
$this->load->library('subscription');
$plans = $this->subscription->getSubscriptionPlans();
```

## Default Subscription Plans

| Plan | Code | Duration | Price | Users | Invoices |
|------|------|----------|-------|-------|----------|
| Free Trial | FREE_TRIAL | 14 days | Free | 1 | 50 |
| Basic Monthly | BASIC_MONTHLY | 30 days | ₹499 | 3 | 500 |
| Pro Monthly | PRO_MONTHLY | 30 days | ₹999 | 10 | Unlimited |
| Enterprise | ENTERPRISE_MONTHLY | 30 days | ₹2499 | Unlimited | Unlimited |
| Basic Yearly | BASIC_YEARLY | 365 days | ₹4990 | 3 | 500 |
| Pro Yearly | PRO_YEARLY | 365 days | ₹9990 | 10 | Unlimited |

## How It Works

### 1. Login Flow
```
User Login → Password Check → Subscription Check → Block if Expired → Allow if Valid
```

### 2. Request Flow (with Hook)
```
Every Request → Hook Triggered → Check Subscription → Redirect if Expired → Continue if Valid
```

### 3. Expiry Notifications
```
Daily Cron → Check Subscriptions → Send Notifications (7, 3, 1 days before) → Update Status
```

### 4. Grace Period
```
Subscription Ends → Grace Period Starts (7 days) → Still Accessible → After Grace → Blocked
```

## API Endpoints

### Get Subscription Status
```
GET/POST /subscription/getStatus
Response: {
    "Error": false,
    "Status": "Active",
    "IsValid": true,
    "DaysRemaining": 15,
    "Message": "Subscription active. 15 days remaining.",
    "InGracePeriod": false,
    "Plan": "Pro Monthly"
}
```

### Extend Subscription (Admin)
```
POST /subscription/extend
Data: {
    "UserUID": 123,
    "Days": 30,
    "PlanCode": "PRO_MONTHLY"
}
```

### Activate Subscription
```
POST /subscription/activate
Data: {
    "PlanCode": "PRO_MONTHLY",
    "PaymentMethod": "Credit Card",
    "TransactionID": "TXN123456"
}
```

## Database Tables

### 1. UserTbl (Modified)
- SubscriptionStatus: Active, Expired, Suspended, Trial, Cancelled
- SubscriptionStartDate: When subscription started
- SubscriptionEndDate: When subscription ends
- TrialEndsOn: Trial expiry date
- GracePeriodDays: Grace period (default 7)
- SubscriptionPlan: Current plan name

### 2. SubscriptionPlanTbl
- Stores all available subscription plans
- Configurable features, pricing, duration

### 3. SubscriptionHistoryTbl
- Tracks all subscription changes
- Renewal history, payment status

### 4. LoginAttemptLogTbl
- Logs all login attempts
- Tracks blocked logins due to expiry

### 5. SubscriptionNotificationTbl
- Tracks sent notifications
- Prevents duplicate notifications

## Customization

### Change Grace Period
```sql
UPDATE Users.UserTbl SET GracePeriodDays = 14 WHERE UserUID = 123;
```

### Add Custom Plan
```sql
INSERT INTO Users.SubscriptionPlanTbl (PlanName, PlanCode, DurationDays, Price, Features) 
VALUES ('Custom Plan', 'CUSTOM', 90, 1999.00, '["Feature 1", "Feature 2"]');
```

### Disable Hook (Manual Check Only)
Comment out the hook in `application/config/hooks.php`

## Payment Gateway Integration

### Razorpay Example
```php
// In Subscription controller activate() method
$api = new Razorpay\Api\Api($keyId, $keySecret);
$payment = $api->payment->fetch($paymentId);

if ($payment->status === 'captured') {
    $paymentData = [
        'status' => 'Paid',
        'method' => $payment->method,
        'transactionId' => $payment->id
    ];
    $this->subscription->activateSubscription($userUID, $planCode, $paymentData);
}
```

## Timezone Handling
All dates stored in UTC. Convert to user timezone for display:
```php
$endDate = new DateTime($user->SubscriptionEndDate, new DateTimeZone('UTC'));
$endDate->setTimezone(new DateTimeZone('Asia/Kolkata'));
echo $endDate->format('d-m-Y H:i:s');
```

## Security Features
- Server-side validation (cannot be bypassed by changing system date)
- Login attempt logging
- Subscription status checked on every request (with hook)
- Grace period to prevent accidental lockouts
- Admin-only subscription extension

## Monitoring

### Check Expired Subscriptions
```sql
SELECT UserUID, UserName, SubscriptionStatus, SubscriptionEndDate 
FROM Users.UserTbl 
WHERE SubscriptionStatus = 'Expired';
```

### View Login Attempts
```sql
SELECT * FROM Users.LoginAttemptLogTbl 
WHERE AttemptStatus = 'Blocked_Expired' 
ORDER BY AttemptTime DESC LIMIT 50;
```

### Subscription Revenue
```sql
SELECT SUM(Amount) as TotalRevenue, COUNT(*) as TotalSubscriptions
FROM Users.SubscriptionHistoryTbl 
WHERE PaymentStatus = 'Paid' 
AND MONTH(CreatedOn) = MONTH(NOW());
```

## Troubleshooting

### Users Can't Login After Implementation
```sql
-- Give all users 30 days trial
UPDATE Users.UserTbl 
SET SubscriptionStatus = 'Trial',
    SubscriptionStartDate = NOW(),
    SubscriptionEndDate = DATE_ADD(NOW(), INTERVAL 30 DAY);
```

### Event Scheduler Not Running
```sql
-- Check if enabled
SHOW VARIABLES LIKE 'event_scheduler';

-- Enable it
SET GLOBAL event_scheduler = ON;

-- Add to my.cnf for permanent
[mysqld]
event_scheduler = ON
```

### Hook Not Working
- Check `config['enable_hooks'] = TRUE` in config.php
- Verify hook file exists in `application/hooks/`
- Check PHP error logs

## Production Checklist
- [ ] Run database migration
- [ ] Enable event scheduler
- [ ] Configure payment gateway
- [ ] Set up email notifications
- [ ] Test subscription expiry flow
- [ ] Test grace period
- [ ] Test admin extension
- [ ] Configure timezone settings
- [ ] Set up monitoring alerts
- [ ] Update support email in views

## Support
For issues or questions, contact your development team.

## License
Proprietary - All rights reserved
