<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Unavailable</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #040b18;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 1.5rem;
    color: #e2e8f0;
}
.box {
    background: rgba(8, 18, 38, 0.92);
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 16px;
    padding: 2.5rem 2rem;
    text-align: center;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
.icon {
    width: 60px; height: 60px;
    background: rgba(239, 68, 68, 0.1);
    border: 1.5px solid rgba(239, 68, 68, 0.3);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1.25rem;
    color: #ef4444;
}
.title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #f0f4f8;
    margin-bottom: 0.6rem;
}
.msg {
    font-size: 0.875rem;
    color: rgba(160, 190, 215, 0.65);
    line-height: 1.6;
}
</style>
</head>
<body>
<div class="box">
    <div class="icon">&#9888;</div>
    <div class="title">Payment Unavailable</div>
    <div class="msg"><?php echo htmlspecialchars($message ?? 'This payment link is invalid or has expired.', ENT_QUOTES, 'UTF-8'); ?></div>
</div>
</body>
</html>
