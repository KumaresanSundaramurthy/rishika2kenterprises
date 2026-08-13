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
        this.activeRequests       = 0;    // in-flight $.ajax request count
        this._slowBannerTimer     = null; // auto-hide timer for slow banner
        this._offlineSince        = null; // timestamp when offline started
        this._offlineTimerInterval = null; // interval updating duration counter

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
        this._offlineSince = Date.now();
        this.showOfflineOverlay();
        if (this.checkInterval) clearInterval(this.checkInterval);
        // Retry every 5 s when offline
        this.checkInterval = setInterval(() => this.checkConnection(), 5000);
    }

    handleOnline() {
        this.isOnline = true;
        this.consecutiveSlowCount = 0;
        this._offlineSince = null;
        if (this._offlineTimerInterval) {
            clearInterval(this._offlineTimerInterval);
            this._offlineTimerInterval = null;
        }
        this.hideOfflineOverlay();
        this.hideSlowInternetWarning();
        this.startPeriodicCheck();
    }

    retryNow() {
        const $btn = $('#io-retry-btn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Checking…');
        this.checkConnection();
        setTimeout(() => {
            $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Try Again');
        }, 4000);
    }

    _formatOfflineDuration(ms) {
        const s = Math.floor(ms / 1000);
        if (s < 60)  return s + 's';
        const m = Math.floor(s / 60), rs = s % 60;
        if (m < 60)  return m + 'm ' + rs + 's';
        const h = Math.floor(m / 60), rm = m % 60;
        return h + 'h ' + rm + 'm';
    }

    showOfflineOverlay() {
        if (this.overlayShown) return;

        const overlay = `
            <div id="internet-offline-overlay">
            <style>
            #internet-offline-overlay{
                position:fixed;top:0;left:0;width:100%;height:100%;
                background:rgba(6,6,16,.96);
                z-index:999999;display:flex;align-items:center;justify-content:center;
                animation:ioFadeIn .3s ease;
            }
            @keyframes ioFadeIn{from{opacity:0}to{opacity:1}}

            .io-card{
                background:rgba(20,20,36,.98);
                border:1px solid rgba(255,71,87,.22);
                border-radius:20px;
                padding:40px 44px 32px;
                text-align:center;
                max-width:360px;width:90%;
                box-shadow:0 32px 80px rgba(0,0,0,.75),0 0 0 1px rgba(255,71,87,.08);
                animation:ioSlideUp .4s cubic-bezier(.22,.68,0,1.2);
                position:relative;overflow:hidden;
            }
            @keyframes ioSlideUp{
                from{transform:translateY(28px);opacity:0}
                to{transform:translateY(0);opacity:1}
            }
            .io-card::before{
                content:'';position:absolute;top:0;left:0;right:0;height:2px;
                background:linear-gradient(90deg,transparent,#ff4757 50%,transparent);
                animation:ioScanLine 2.8s linear infinite;
            }
            @keyframes ioScanLine{
                0%  {transform:translateX(-100%)}
                100%{transform:translateX(100%)}
            }

            .io-icon-wrap{
                position:relative;width:72px;height:72px;
                margin:0 auto 18px;
                display:flex;align-items:center;justify-content:center;
            }
            .io-ripple{
                position:absolute;width:72px;height:72px;border-radius:50%;
                border:2px solid rgba(255,71,87,.4);
                animation:ioRipple 2.2s ease-out infinite;
            }
            .io-ripple-2{animation-delay:1.1s;}
            @keyframes ioRipple{
                0%  {transform:scale(1);opacity:.7}
                100%{transform:scale(2.2);opacity:0}
            }
            .io-icon-bg{
                width:56px;height:56px;border-radius:50%;
                background:rgba(255,71,87,.1);
                border:1.5px solid rgba(255,71,87,.3);
                display:flex;align-items:center;justify-content:center;
                position:relative;z-index:1;
            }
            .io-icon-bg i{font-size:1.7rem;color:#ff4757;}

            .io-badge{
                display:inline-flex;align-items:center;gap:6px;
                background:rgba(255,71,87,.1);
                border:1px solid rgba(255,71,87,.25);
                border-radius:100px;padding:3px 12px;
                font-size:.65rem;font-weight:700;letter-spacing:1.4px;
                color:#ff6b7a;margin-bottom:14px;
            }
            .io-blink-dot{
                width:5px;height:5px;border-radius:50%;
                background:#ff4757;display:inline-block;
                animation:ioBlink 1.1s ease infinite;
            }
            @keyframes ioBlink{0%,100%{opacity:1}50%{opacity:.15}}

            .io-title{
                color:#fff;font-size:1.28rem;font-weight:700;
                margin:0 0 8px;letter-spacing:-.3px;
            }
            .io-desc{
                color:rgba(255,255,255,.4);font-size:.81rem;
                line-height:1.65;margin:0 0 22px;
            }

            .io-dots{
                display:flex;align-items:center;justify-content:center;
                gap:6px;margin-bottom:6px;
            }
            .io-dots span{
                width:6px;height:6px;border-radius:50%;
                animation:ioBounce 1.5s ease infinite;
            }
            .io-dots span:nth-child(2){animation-delay:.2s}
            .io-dots span:nth-child(3){animation-delay:.4s}
            @keyframes ioBounce{
                0%,80%,100%{transform:scale(.8);background:rgba(255,255,255,.18)}
                40%        {transform:scale(1.3);background:rgba(255,255,255,.75)}
            }
            .io-reconn-label{
                font-size:.7rem;color:rgba(255,255,255,.22);
                letter-spacing:.4px;margin:0 0 20px;
            }

            .io-footer{
                display:flex;align-items:center;justify-content:space-between;
                padding-top:18px;
                border-top:1px solid rgba(255,255,255,.07);
                gap:12px;
            }
            .io-duration{
                font-size:.72rem;color:rgba(255,255,255,.28);
                letter-spacing:.2px;text-align:left;
            }
            .io-duration strong{color:rgba(255,255,255,.5);}
            #io-retry-btn{
                display:inline-flex;align-items:center;gap:5px;
                background:rgba(255,71,87,.14);
                border:1px solid rgba(255,71,87,.3);
                border-radius:8px;
                color:#ff7b87;font-size:.75rem;font-weight:600;
                padding:6px 14px;cursor:pointer;
                transition:background .2s,border-color .2s;
                white-space:nowrap;
            }
            #io-retry-btn:hover:not(:disabled){
                background:rgba(255,71,87,.24);border-color:rgba(255,71,87,.5);
            }
            #io-retry-btn:disabled{opacity:.5;cursor:default;}
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

                <div class="io-footer">
                    <div class="io-duration">
                        Offline for <strong id="io-duration-val">0s</strong>
                    </div>
                    <button id="io-retry-btn" onclick="internetMonitor.retryNow()">
                        <i class="bx bx-refresh"></i> Try Again
                    </button>
                </div>
            </div>
            </div>`;

        $('body').append(overlay);
        this.overlayShown = true;

        // Live duration counter
        this._offlineTimerInterval = setInterval(() => {
            if (!this._offlineSince) return;
            const el = document.getElementById('io-duration-val');
            if (el) el.textContent = this._formatOfflineDuration(Date.now() - this._offlineSince);
        }, 1000);
    }

    hideOfflineOverlay() {
        if (this._offlineTimerInterval) {
            clearInterval(this._offlineTimerInterval);
            this._offlineTimerInterval = null;
        }
        $('#internet-offline-overlay').fadeOut(300, function() { $(this).remove(); });
        this.overlayShown = false;
    }

    showSlowInternetWarning() {
        if ($('#slow-internet-banner').length > 0) return;

        const banner = `
            <div id="slow-internet-banner">
            <style>
            #slow-internet-banner{
                position:fixed;top:0;left:0;width:100%;
                background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);
                color:#fff;padding:10px 20px;z-index:99999;
                box-shadow:0 2px 12px rgba(0,0,0,.25);
                display:flex;align-items:center;justify-content:space-between;
                animation:siBannerDown .3s ease;
                gap:12px;
            }
            @keyframes siBannerDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
            .si-left{display:flex;align-items:center;gap:12px;}
            .si-left i{font-size:1.4rem;flex-shrink:0;}
            .si-text strong{font-size:.9rem;}
            .si-text p{margin:0;font-size:.78rem;opacity:.88;}
            #si-dismiss-btn{
                background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);
                color:#fff;padding:5px 14px;border-radius:6px;
                cursor:pointer;font-size:.78rem;font-weight:600;flex-shrink:0;
                transition:background .2s;
            }
            #si-dismiss-btn:hover{background:rgba(255,255,255,.32);}
            </style>
                <div class="si-left">
                    <i class="bx bx-error-circle"></i>
                    <div class="si-text">
                        <strong>Slow Internet Connection</strong>
                        <p>Your connection is slow. Some features may take longer to load.</p>
                    </div>
                </div>
                <button id="si-dismiss-btn" onclick="internetMonitor.hideSlowInternetWarning()">Dismiss</button>
            </div>`;

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
