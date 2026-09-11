<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f5fb;
            color: #333;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            text-align: center;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff3cd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.2rem;
        }
        h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 12px; color: #1a1a2e; }
        p  { font-size: .95rem; color: #6c757d; line-height: 1.6; margin-bottom: 28px; }
        .btn {
            display: inline-block;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            margin: 4px;
        }
        .btn-primary   { background: #696cff; color: #fff; }
        .btn-outline   { background: transparent; border: 1.5px solid #696cff; color: #696cff; }
        .contact-line  { margin-top: 24px; font-size: .85rem; color: #aaa; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">⏰</div>
        <h2>Subscription Expired</h2>
        <p>
            Your subscription has expired or is inactive.<br>
            Please renew your plan to continue using the application.
        </p>
        <div>
            <a href="<?= base_url('subscription/dashboard') ?>" class="btn btn-primary">View Plans</a>
            <a href="<?= base_url('auth/logout') ?>" class="btn btn-outline">Logout</a>
        </div>
        <p class="contact-line">Need help? Contact your administrator or support team.</p>
    </div>
</body>
</html>
