<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verification</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, sans-serif;
    background: #0a1628;
    color: #e2e8f0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.card {
    background: #0f1f3a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 3rem 2.5rem;
    max-width: 460px;
    width: 100%;
    text-align: center;
}
.icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1.5rem;
}
.icon-success  { background: rgba(52,211,153,0.15); color: #34d399; }
.icon-invalid  { background: rgba(248,113,113,0.15); color: #f87171; }
.icon-expired  { background: rgba(251,191,36,0.15);  color: #fbbf24; }
.icon-verified { background: rgba(96,165,250,0.15);  color: #60a5fa; }
.title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}
.title-success  { color: #34d399; }
.title-invalid  { color: #f87171; }
.title-expired  { color: #fbbf24; }
.title-verified { color: #60a5fa; }
.msg {
    font-size: 0.9rem;
    color: rgba(180,210,230,0.7);
    line-height: 1.6;
    margin-bottom: 1.75rem;
}
.btn-login {
    display: inline-block;
    padding: 0.75rem 2rem;
    background: #f59e0b;
    color: #0a1628;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s;
    cursor: pointer;
    border: none;
}
.btn-login:hover { background: #d97706; }

/* Resend form (expired state only) */
.resend-form { margin-top: 0.25rem; }
.resend-label {
    display: block;
    font-size: 0.82rem;
    color: rgba(180,210,230,0.6);
    margin-bottom: 0.6rem;
}
.resend-input {
    width: 100%;
    padding: 0.65rem 1rem;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.05);
    color: #e2e8f0;
    font-size: 0.9rem;
    margin-bottom: 0.85rem;
    outline: none;
    transition: border-color 0.2s;
}
.resend-input:focus { border-color: #fbbf24; }
.btn-resend {
    width: 100%;
    padding: 0.7rem 1rem;
    background: #fbbf24;
    color: #0a1628;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-resend:hover:not(:disabled) { background: #d97706; }
.btn-resend:disabled { opacity: 0.55; cursor: not-allowed; }
.resend-status {
    margin-top: 0.85rem;
    font-size: 0.85rem;
    line-height: 1.5;
    min-height: 1.2rem;
}
.resend-status.ok  { color: #34d399; }
.resend-status.err { color: #f87171; }
.divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.07);
    margin: 1.5rem 0;
}
</style>
</head>
<body>
<div class="card">

    <?php if ($status === 'success'): ?>
        <div class="icon icon-success">✓</div>
        <div class="title title-success">Email Verified!</div>
        <p class="msg"><?php echo htmlspecialchars($message); ?></p>
        <a href="/portal" class="btn-login">Go to Login</a>

    <?php elseif ($status === 'already_verified'): ?>
        <div class="icon icon-verified">✓</div>
        <div class="title title-verified">Already Verified</div>
        <p class="msg"><?php echo htmlspecialchars($message); ?></p>
        <a href="/portal" class="btn-login">Go to Login</a>

    <?php elseif ($status === 'expired'): ?>
        <div class="icon icon-expired">⏱</div>
        <div class="title title-expired">Link Expired</div>
        <p class="msg"><?php echo htmlspecialchars($message); ?></p>
        <div class="resend-form">
            <label class="resend-label">Enter your organisation email to receive a new link:</label>
            <input type="email" id="resendEmail" class="resend-input" placeholder="organisation@example.com" autocomplete="email">
            <button id="btnResend" class="btn-resend" onclick="doResend()">Resend Verification Email</button>
            <div id="resendStatus" class="resend-status"></div>
        </div>
        <hr class="divider">
        <a href="/portal" class="btn-login">Go to Login</a>

    <?php else: ?>
        <div class="icon icon-invalid">✕</div>
        <div class="title title-invalid">Invalid Link</div>
        <p class="msg"><?php echo htmlspecialchars($message); ?></p>
        <a href="/portal" class="btn-login">Go to Login</a>

    <?php endif; ?>

</div>

<?php if ($status === 'expired'): ?>
<script>
function doResend() {
    var email  = document.getElementById('resendEmail').value.trim();
    var btn    = document.getElementById('btnResend');
    var status = document.getElementById('resendStatus');

    status.textContent = '';
    status.className   = 'resend-status';

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        status.textContent = 'Please enter a valid email address.';
        status.className   = 'resend-status err';
        return;
    }

    btn.disabled       = true;
    btn.textContent    = 'Sending…';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/resend-verification', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function () {
        btn.disabled    = false;
        btn.textContent = 'Resend Verification Email';
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.Error) {
                status.textContent = res.Message;
                status.className   = 'resend-status err';
            } else {
                status.textContent = res.Message;
                status.className   = 'resend-status ok';
                btn.disabled       = true;
            }
        } catch (e) {
            status.textContent = 'Something went wrong. Please try again.';
            status.className   = 'resend-status err';
        }
    };
    xhr.onerror = function () {
        btn.disabled    = false;
        btn.textContent = 'Resend Verification Email';
        status.textContent = 'Network error. Please try again.';
        status.className   = 'resend-status err';
    };
    xhr.send('email=' + encodeURIComponent(email));
}

document.getElementById('resendEmail').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') doResend();
});
</script>
<?php endif; ?>
</body>
</html>
