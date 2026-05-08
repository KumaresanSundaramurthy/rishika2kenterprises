<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt Not Found</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; background:#f0f2f5; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.box { background:#fff; border-radius:12px; padding:40px 32px; text-align:center; max-width:380px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.icon { font-size:48px; margin-bottom:16px; }
.title { font-size:18px; font-weight:700; color:#222; margin-bottom:8px; }
.msg { font-size:14px; color:#666; }
</style>
</head>
<body>
<div class="box">
    <div class="icon">🔒</div>
    <div class="title">Receipt Not Available</div>
    <div class="msg"><?php echo htmlspecialchars($message ?? 'This receipt link is invalid or has expired.'); ?></div>
</div>
</body>
</html>
