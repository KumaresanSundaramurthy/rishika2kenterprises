<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$state = $state ?? 'invalid';
$user  = $user  ?? null;
$token = $token ?? '';
$error = $error ?? '';
$siteName = function_exists('getSiteConfiguration') ? getSiteConfiguration()->ShortName : 'R2K Enterprises';
$loginUrl = base_url('login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set Password — <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="/assets/vendor/css/core.css">
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css">
    <link rel="stylesheet" href="/assets/css/demo.css">
    <style>
        body { background: #f4f5fb; }
        .sp-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .sp-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px 36px;
            width: 100%;
            max-width: 440px;
        }
        .sp-logo {
            display: block;
            width: 80px;
            height: auto;
            margin: 0 auto 20px;
        }
        .sp-title   { font-size: 1.3rem; font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 6px; }
        .sp-subtitle{ font-size: .875rem; color: #64748b; text-align: center; margin-bottom: 28px; }
        .form-label { font-size: .83rem; font-weight: 500; }
        .btn-primary { background: #7c3aed; border-color: #7c3aed; }
        .btn-primary:hover { background: #6d28d9; border-color: #6d28d9; }
        .signin-link { text-align: center; margin-top: 20px; font-size: .875rem; color: #64748b; }
        .signin-link a { color: #7c3aed; font-weight: 500; text-decoration: none; }
    </style>
</head>
<body>
<div class="sp-wrapper">
    <div class="sp-card">

        <img src="/images/logo/R2kE_Logo_square.png" alt="<?php echo htmlspecialchars($siteName); ?>" class="sp-logo">

        <?php if ($state === 'form'): ?>

            <div class="sp-title">Set Your Password</div>
            <div class="sp-subtitle">
                Hi <?php echo htmlspecialchars($user->FirstName); ?>, create a password to activate your account.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;">
                    <i class="bx bx-error-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo site_url('setpassword/submit'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>"
                       value="<?php echo $this->security->get_csrf_hash(); ?>">

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="Password" id="spPwd"
                               placeholder="Min. 6 characters" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" id="spTogglePwd" tabindex="-1">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="ConfirmPassword" id="spCpwd"
                           placeholder="Re-enter password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bx bx-check me-1"></i>Set Password & Activate Account
                </button>
            </form>

        <?php elseif ($state === 'success'): ?>

            <div class="sp-title">Password Set Successfully!</div>
            <div class="sp-subtitle">
                Your account is now active, <?php echo htmlspecialchars($user->FirstName); ?>.
                You can sign in to continue.
            </div>
            <div class="text-center mb-3">
                <i class="bx bx-check-circle" style="font-size:3rem;color:#22c55e;"></i>
            </div>
            <a href="<?php echo $loginUrl; ?>" class="btn btn-primary w-100">
                <i class="bx bx-log-in me-1"></i>Sign In Now
            </a>

        <?php elseif ($state === 'already_set'): ?>

            <div class="sp-title">Password Already Set</div>
            <div class="sp-subtitle">
                Hi <?php echo htmlspecialchars($user->FirstName); ?>, your password has already been configured.
                Please sign in to your account.
            </div>
            <div class="text-center mb-3">
                <i class="bx bx-info-circle" style="font-size:3rem;color:#f59e0b;"></i>
            </div>
            <a href="<?php echo $loginUrl; ?>" class="btn btn-primary w-100">
                <i class="bx bx-log-in me-1"></i>Go to Sign In
            </a>

        <?php else: ?>

            <div class="sp-title">Invalid Link</div>
            <div class="sp-subtitle">
                This password setup link is invalid or has already been used.
                Please contact your administrator if you need assistance.
            </div>
            <div class="text-center mb-3">
                <i class="bx bx-x-circle" style="font-size:3rem;color:#ef4444;"></i>
            </div>
            <a href="<?php echo $loginUrl; ?>" class="btn btn-outline-secondary w-100">
                <i class="bx bx-arrow-back me-1"></i>Back to Sign In
            </a>

        <?php endif; ?>

        <div class="signin-link">
            <?php echo htmlspecialchars($siteName); ?>
        </div>

    </div>
</div>

<?php if ($state === 'form'): ?>
<script>
document.getElementById('spTogglePwd').addEventListener('click', function () {
    var p = document.getElementById('spPwd');
    var i = this.querySelector('i');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.classList.toggle('bx-hide');
    i.classList.toggle('bx-show');
});
</script>
<?php endif; ?>
</body>
</html>
