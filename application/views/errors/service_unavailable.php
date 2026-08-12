<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable</title>
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
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 48px 40px;
            max-width: 460px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon {
            font-size: 52px;
            margin-bottom: 20px;
            display: block;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a202c;
        }
        p {
            font-size: 15px;
            line-height: 1.7;
            color: #5a6475;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-block;
            padding: 11px 28px;
            background: #4f63d2;
            color: #fff;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn:hover { background: #3d50c3; }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">&#x26A0;&#xFE0F;</span>
        <h1>Something went wrong</h1>
        <p>We're experiencing a temporary issue on our end.<br>Please try again in a few minutes.</p>
        <button class="btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>
