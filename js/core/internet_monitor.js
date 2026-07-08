/**
 * Internet Connectivity Monitor
 * Detects offline, slow internet, and connection restoration.
 *
 * Slow internet is determined ONLY by the dedicated /ping/check fetch.
 * The ping is skipped while any user $.ajax request is in-flight so that
 * a legitimate long-running server action never triggers a false warning.
 */

class InternetMonitor {
    constructor() {
        this.isOnline        = navigator.onLine;
        this.isSlow          = false;
        this.checkInterval   = null;
        this.slowThreshold   = 8000;  // ping must respond within 8 s
        this.checkFrequency  = 30000; // check every 30 s
        this.overlayShown    = false;
        this.consecutiveSlowCount = 0;
        this.slowCountThreshold   = 2; // 2 consecutive slow pings → show warning
        this.activeRequests  = 0;     // in-flight $.ajax request count
        this._slowBannerTimer = null; // auto-hide timer for slow banner

        this.init();
    }

    init() {
        window.addEventListener('online',  () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Track in-flight jQuery AJAX requests.
        // ajaxSend / ajaxComplete fire for every $.ajax call made by the app,
        // but NOT for the fetch()-based ping below — so the counter is clean.
        $(document).on('ajaxSend',     () => { this.activeRequests++; });
        $(document).on('ajaxComplete', () => { this.activeRequests = Math.max(0, this.activeRequests - 1); });

        if (!navigator.onLine) {
            this.handleOffline();
        } else {
            // Wait 5 s after page load before starting checks
            setTimeout(() => this.startPeriodicCheck(), 5000);
        }
    }

    startPeriodicCheck() {
        if (this.checkInterval) clearInterval(this.checkInterval);
        this.checkInterval = setInterval(() => this.checkConnection(), this.checkFrequency);
    }

    checkConnection() {
        // Skip the ping entirely while the user has AJAX requests running.
        // A slow server response to a user action is NOT a slow internet connection.
        if (this.activeRequests > 0) return;

        const startTime = Date.now();
        let timedOut = false;

        const timeoutId = setTimeout(() => {
            timedOut = true;
            this.onSlowPing();
        }, this.slowThreshold);

        fetch(global_base_url + 'ping/check', {
            method: 'GET',
            cache:  'no-cache',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (timedOut) return; // already handled above

            if (response.ok) {
                if (!this.isOnline) this.handleOnline();
                const duration = Date.now() - startTime;
                if (duration < this.slowThreshold) {
                    this.onFastPing();
                } else {
                    this.onSlowPing();
                }
            } else {
                this.handleOffline();
            }
        })
        .catch(() => {
            clearTimeout(timeoutId);
            if (!timedOut && this.isOnline) this.handleOffline();
        });
    }

    onFastPing() {
        this.consecutiveSlowCount = 0;
        if (this.isSlow) this.hideSlowInternetWarning();
    }

    onSlowPing() {
        this.consecutiveSlowCount++;
        if (this.consecutiveSlowCount >= this.slowCountThreshold && !this.isSlow) {
            this.isSlow = true;
            this.showSlowInternetWarning();
        }
    }

    handleOffline() {
        this.isOnline = false;
        this.showOfflineOverlay();
        if (this.checkInterval) clearInterval(this.checkInterval);
        // Retry every 5 s when offline
        this.checkInterval = setInterval(() => this.checkConnection(), 5000);
    }

    handleOnline() {
        this.isOnline = true;
        this.consecutiveSlowCount = 0;
        this.hideOfflineOverlay();
        this.hideSlowInternetWarning();
        this.startPeriodicCheck();
    }

    showOfflineOverlay() {
        if (this.overlayShown) return;

        const overlay = `
            <div id="internet-offline-overlay">
            <style>
            #internet-offline-overlay{
                position:fixed;top:0;left:0;width:100%;height:100%;
                background:rgba(8,8,20,.97);
                z-index:999999;display:flex;align-items:center;justify-content:center;
                animation:ioFadeIn .3s ease;
            }
            @keyframes ioFadeIn{from{opacity:0}to{opacity:1}}

            .io-card{
                background:rgba(255,255,255,.04);
                border:1px solid rgba(255,255,255,.09);
                border-radius:24px;
                padding:48px 52px 36px;
                text-align:center;
                max-width:380px;width:90%;
                box-shadow:0 40px 100px rgba(0,0,0,.7);
                animation:ioSlideUp .4s cubic-bezier(.22,.68,0,1.2);
                backdrop-filter:blur(20px);
                position:relative;overflow:hidden;
            }
            @keyframes ioSlideUp{
                from{transform:translateY(30px);opacity:0}
                to{transform:translateY(0);opacity:1}
            }

            .io-card::before{
                content:'';position:absolute;top:0;left:0;right:0;height:2px;
                background:linear-gradient(90deg,transparent,#ff4757 50%,transparent);
                animation:ioScanLine 2.8s linear infinite;
            }
            @keyframes ioScanLine{
                0%  {transform:translateX(-100%);opacity:.8}
                100%{transform:translateX(100%);opacity:.8}
            }

            .io-icon-wrap{
                position:relative;width:88px;height:88px;
                margin:0 auto 20px;
                display:flex;align-items:center;justify-content:center;
            }
            .io-ripple{
                position:absolute;width:88px;height:88px;border-radius:50%;
                border:2px solid rgba(255,71,87,.45);
                animation:ioRipple 2.2s ease-out infinite;
            }
            .io-ripple-2{animation-delay:1.1s;}
            @keyframes ioRipple{
                0%  {transform:scale(1);opacity:.8}
                100%{transform:scale(2.4);opacity:0}
            }
            .io-icon-bg{
                width:70px;height:70px;border-radius:50%;
                background:rgba(255,71,87,.1);
                border:1.5px solid rgba(255,71,87,.3);
                display:flex;align-items:center;justify-content:center;
                position:relative;z-index:1;
            }
            .io-icon-bg i{font-size:2.1rem;color:#ff4757;}

            .io-badge{
                display:inline-flex;align-items:center;gap:6px;
                background:rgba(255,71,87,.12);
                border:1px solid rgba(255,71,87,.28);
                border-radius:100px;padding:4px 14px;
                font-size:.67rem;font-weight:700;letter-spacing:1.3px;
                color:#ff6b7a;margin-bottom:16px;
            }
            .io-blink-dot{
                width:6px;height:6px;border-radius:50%;
                background:#ff4757;display:inline-block;
                animation:ioBlink 1.1s ease infinite;
            }
            @keyframes ioBlink{0%,100%{opacity:1}50%{opacity:.15}}

            .io-title{
                color:#fff;font-size:1.38rem;font-weight:700;
                margin:0 0 10px;letter-spacing:-.3px;
            }
            .io-desc{
                color:rgba(255,255,255,.42);font-size:.83rem;
                line-height:1.6;margin:0 0 26px;
            }

            .io-dots{
                display:flex;align-items:center;justify-content:center;
                gap:6px;margin-bottom:8px;
            }
            .io-dots span{
                width:7px;height:7px;border-radius:50%;
                background:rgba(255,255,255,.25);
                animation:ioBounce 1.5s ease infinite;
            }
            .io-dots span:nth-child(2){animation-delay:.2s}
            .io-dots span:nth-child(3){animation-delay:.4s}
            @keyframes ioBounce{
                0%,80%,100%{transform:scale(.8);background:rgba(255,255,255,.2)}
                40%        {transform:scale(1.3);background:rgba(255,255,255,.8)}
            }
            .io-reconn-label{
                font-size:.71rem;color:rgba(255,255,255,.24);
                letter-spacing:.4px;margin:0 0 0;
            }

            .io-signal-row{
                display:flex;align-items:flex-end;justify-content:center;
                gap:5px;margin-top:26px;padding-top:20px;
                border-top:1px solid rgba(255,255,255,.06);
            }
            .io-signal-row .sb{
                width:9px;border-radius:2px;
                background:rgba(255,255,255,.1);
            }
            .io-signal-row .sb:nth-child(1){height:10px}
            .io-signal-row .sb:nth-child(2){height:16px}
            .io-signal-row .sb:nth-child(3){height:22px}
            .io-signal-row .sb:nth-child(4){height:28px}
            .io-signal-row .sblabel{
                font-size:.68rem;color:rgba(255,255,255,.18);
                margin-left:8px;align-self:center;letter-spacing:.3px;
            }
            </style>

            <div class="io-card">
                <div class="io-icon-wrap">
                    <div class="io-ripple"></div>
                    <div class="io-ripple io-ripple-2"></div>
                    <div class="io-icon-bg"><i class="bx bx-wifi-off"></i></div>
                </div>

                <div class="io-badge">
                    <span class="io-blink-dot"></span>OFFLINE
                </div>

                <h3 class="io-title">Connection Lost</h3>
                <p class="io-desc">Your network connection was interrupted.<br>We'll reconnect you automatically.</p>

                <div class="io-dots"><span></span><span></span><span></span></div>
                <p class="io-reconn-label">Attempting to reconnect</p>

                <div class="io-signal-row">
                    <div class="sb"></div>
                    <div class="sb"></div>
                    <div class="sb"></div>
                    <div class="sb"></div>
                    <span class="sblabel">No Signal</span>
                </div>
            </div>
            </div>`;

        $('body').append(overlay);
        this.overlayShown = true;
    }

    hideOfflineOverlay() {
        $('#internet-offline-overlay').fadeOut(300, function() { $(this).remove(); });
        this.overlayShown = false;
    }

    showSlowInternetWarning() {
        if ($('#slow-internet-banner').length > 0) return;

        const banner = `
            <div id="slow-internet-banner" style="position:fixed;top:0;left:0;width:100%;
                background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);
                color:white;padding:12px 20px;z-index:99999;box-shadow:0 2px 10px rgba(0,0,0,0.2);
                display:flex;align-items:center;justify-content:space-between;animation:slideDown 0.3s ease;">
                <div style="display:flex;align-items:center;gap:15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:24px;"></i>
                    <div>
                        <strong style="font-size:16px;">Slow Internet Connection</strong>
                        <p style="margin:0;font-size:13px;opacity:0.9;">
                            Your connection is slow. Some features may take longer to load.
                        </p>
                    </div>
                </div>
                <button onclick="internetMonitor.hideSlowInternetWarning()"
                    style="background:rgba(255,255,255,0.2);border:none;color:white;
                    padding:8px 15px;border-radius:5px;cursor:pointer;font-size:14px;">
                    Dismiss
                </button>
            </div>
            <style>
                @keyframes slideDown { from { transform:translateY(-100%); } to { transform:translateY(0); } }
            </style>`;

        $('body').prepend(banner);

        // Auto-hide after 6 seconds
        this._slowBannerTimer = setTimeout(() => this.hideSlowInternetWarning(), 6000);
    }

    hideSlowInternetWarning() {
        if (this._slowBannerTimer) {
            clearTimeout(this._slowBannerTimer);
            this._slowBannerTimer = null;
        }
        $('#slow-internet-banner').fadeOut(300, function() { $(this).remove(); });
        this.isSlow = false;
    }
}

// Initialize on document ready
let internetMonitor;
$(document).ready(function() {
    internetMonitor = new InternetMonitor();
});
