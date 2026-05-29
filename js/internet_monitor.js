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
            <div id="internet-offline-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;
                background:rgba(0,0,0,0.95);z-index:999999;display:flex;align-items:center;justify-content:center;">
                <div style="text-align:center;color:white;padding:40px;">
                    <div style="font-size:80px;margin-bottom:20px;">
                        <i class="fas fa-wifi-slash"></i>
                    </div>
                    <h2 style="font-size:32px;margin-bottom:15px;font-weight:600;">No Internet Connection</h2>
                    <p style="font-size:18px;color:#ccc;margin-bottom:25px;">
                        Please check your internet connection and try again.
                    </p>
                    <div class="spinner-border text-light" role="status" style="width:3rem;height:3rem;">
                        <span class="sr-only">Reconnecting...</span>
                    </div>
                    <p style="font-size:14px;color:#999;margin-top:15px;">Attempting to reconnect...</p>
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
