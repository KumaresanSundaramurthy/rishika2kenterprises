<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php if (ENVIRONMENT === 'development'): ?>
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">
<h4>A PHP Error was encountered</h4>
<p>Severity: <?php echo $severity; ?></p>
<p>Message:  <?php echo $message; ?></p>
<p>Filename: <?php echo $filepath; ?></p>
<p>Line Number: <?php echo $line; ?></p>
<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>
    <p>Backtrace:</p>
    <?php foreach (debug_backtrace() as $error): ?>
        <?php if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>
            <p style="margin-left:10px">
            File: <?php echo $error['file'] ?><br />
            Line: <?php echo $error['line'] ?><br />
            Function: <?php echo $error['function'] ?>
            </p>
        <?php endif ?>
    <?php endforeach ?>
<?php endif ?>
</div>
<?php else: ?>
<?php
while (ob_get_level() > 0) { ob_end_clean(); }
if (!headers_sent()) { header('HTTP/1.1 503 Service Unavailable'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Something Went Wrong</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f4f6f9;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2d3748;
}
.err-card {
    background: #fff;
    border-radius: 12px;
    padding: 48px 40px;
    max-width: 460px;
    width: 90%;
    text-align: center;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
}
.err-icon { font-size: 52px; margin-bottom: 20px; display: block; }
.err-title { font-size: 22px; font-weight: 700; margin-bottom: 12px; color: #1a202c; }
.err-msg { font-size: 15px; line-height: 1.7; color: #5a6475; margin-bottom: 28px; }
.err-btn {
    display: inline-block;
    padding: 11px 28px;
    background: #4f63d2;
    color: #fff;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}
.err-btn:hover { background: #3d50c3; }
</style>
</head>
<body>
    <div class="err-card">
        <span class="err-icon">&#x26A0;&#xFE0F;</span>
        <h1 class="err-title">Something Went Wrong</h1>
        <p class="err-msg">We're experiencing a temporary issue on our end.<br>Please try again in a few minutes.</p>
        <button class="err-btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>
<?php exit(1); ?>
<?php endif ?>
