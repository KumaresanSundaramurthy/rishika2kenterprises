<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Sync Required</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon {
            width: 64px; height: 64px;
            background: #fff7ed;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg { width: 32px; height: 32px; color: #f97316; }
        h2 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; }
        p  { font-size: .9rem; color: #64748b; line-height: 1.6; margin-bottom: 8px; }
        .code {
            display: inline-block;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: .78rem;
            color: #475569;
            font-family: monospace;
            margin: 12px 0 28px;
            word-break: break-all;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: #3b82f6; color: #fff;
            border: none; border-radius: 8px;
            padding: 12px 28px;
            font-size: .92rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background .15s;
        }
        .btn:hover { background: #2563eb; }
        .btn svg { width: 18px; height: 18px; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
    </div>

    <h2>Session Cache Sync Required</h2>
    <p>The in-memory session data for this module was not found in the cache layer.</p>
    <p>This usually resolves itself after a single page refresh.</p>

    <div class="code">ERR_SESSION_CACHE_MISS &nbsp;·&nbsp; <?php echo date('H:i:s'); ?></div>

    <a href="javascript:window.location.reload(true);" class="btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Refresh Page
    </a>
</div>
</body>
</html>
