<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document Not Available</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.box{background:#fff;border-radius:14px;padding:44px 36px;text-align:center;max-width:400px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.09);}
.icon{font-size:52px;margin-bottom:18px;}
.title{font-size:18px;font-weight:700;color:#1e293b;margin-bottom:10px;}
.msg{font-size:14px;color:#64748b;line-height:1.6;}
.brand{margin-top:28px;padding-top:18px;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8;letter-spacing:.5px;}
.brand span{font-weight:700;color:#f59e0b;}
</style>
</head>
<body>
<div class="box">
  <div class="icon">📄</div>
  <div class="title">Document Not Available</div>
  <div class="msg"><?php echo htmlspecialchars($message ?? 'This document link is invalid or has been removed.', ENT_QUOTES, 'UTF-8'); ?></div>
  <div class="brand">Powered by <span>Rishika 2K</span> Billing</div>
</div>
</body>
</html>
