<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
html, body { margin: 0; padding: 0; background: #040b18; min-height: 100%; }
.srex-root {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    padding: 2rem;
}
.srex-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 16px;
    padding: 3rem 2.5rem;
    text-align: center;
    max-width: 420px;
    width: 100%;
}
.srex-icon { font-size: 3rem; margin-bottom: 1rem; color: #f87171; }
.srex-title { font-size: 1.3rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.6rem; }
.srex-sub { font-size: 0.9rem; color: rgba(148,163,184,0.8); line-height: 1.7; margin-bottom: 2rem; }
.srex-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    background: rgba(245,158,11,0.1);
    border: 1px solid rgba(245,158,11,0.4);
    border-radius: 8px;
    color: #fbbf24;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.srex-btn:hover { background: rgba(245,158,11,0.18); border-color: rgba(245,158,11,0.7); color: #fde68a; text-decoration: none; }
</style>

<div class="srex-root">
    <div class="srex-card">
        <div class="srex-icon"><i class="bx bx-link-external"></i></div>
        <p class="srex-title">This link has expired</p>
        <p class="srex-sub">
            Renewal links are valid for 30 minutes only.<br>
            Please go back to the login page and try again to get a fresh link.
        </p>
        <a href="<?php echo base_url('login'); ?>" class="srex-btn">
            <i class="bx bx-arrow-back"></i> Back to Login
        </a>
    </div>
</div>
