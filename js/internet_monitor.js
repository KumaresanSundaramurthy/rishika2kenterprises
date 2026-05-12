/**
 * Internet Connectivity Monitor
 * Detects offline, slow internet, and connection restoration
 */

class InternetMonitor {
    constructor() {
        this.isOnline = navigator.onLine;
        this.isSlow = false;
        this.checkInterval = null;
        this.slowThreshold = 10000; // 10 seconds timeout for slow connection
        this.checkFrequency = 30000; // Check every 30 seconds
        this.overlayShown = false;
        this.consecutiveSlowCount = 0; // Track consecutive slow responses
        this.slowCountThreshold = 2; // Require 2 consecutive slow responses
        this.warningDelayTimer = null; // Timer for delayed warning
        this.warningDelay = 5000; // Wait 5 seconds before showing warning
        
        this.init();
    }

    init() {
        // Listen to browser online/offline events
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Initial check - wait 5 seconds after page load
        if (!navigator.onLine) {
            this.handleOffline();
        } else {
            setTimeout(() => {
                this.startPeriodicCheck();
            }, 5000);
        }

        // Intercept AJAX requests to detect slow/failed requests
        this.interceptAjax();
    }

    startPeriodicCheck() {
        if (this.checkInterval) clearInterval(this.checkInterval);
        
        this.checkInterval = setInterval(() => {
            this.checkConnection();
        }, this.checkFrequency);
    }

    checkConnection() {
        const startTime = Date.now();
        const timeout = this.slowThreshold;
        let timedOut = false;

        const timeoutId = setTimeout(() => {
            timedOut = true;
            this.consecutiveSlowCount++;
            
            if (this.consecutiveSlowCount >= this.slowCountThreshold && !this.isSlow) {
                this.isSlow = true;
                this.showSlowInternetWarning();
            }
        }, timeout);

        // Ping server to check connectivity
        fetch(global_base_url + 'ping/check', {
            method: 'GET',
            cache: 'no-cache',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            clearTimeout(timeoutId);
            const duration = Date.now() - startTime;
            
            if (response.ok) {
                // Connection restored!
                if (!this.isOnline) {
                    this.handleOnline();
                    return;
                }
                
                if (!timedOut && duration < timeout) {
                    // Connection is good
                    this.consecutiveSlowCount = 0;
                    if (this.isSlow) {
                        this.isSlow = false;
                        this.hideSlowInternetWarning();
                    }
                } else if (duration >= timeout) {
                    this.consecutiveSlowCount++;
                    if (this.consecutiveSlowCount >= this.slowCountThreshold && !this.isSlow) {
                        this.isSlow = true;
                        this.showSlowInternetWarning();
                    }
                }
            } else {
                this.handleOffline();
            }
        })
        .catch(() => {
            clearTimeout(timeoutId);
            // Still offline, keep trying
            if (this.isOnline) {
                this.handleOffline();
            }
        });
    }

    handleOffline() {
        this.isOnline = false;
        this.showOfflineOverlay();
        if (this.checkInterval) clearInterval(this.checkInterval);
        
        // Try to reconnect every 5 seconds
        this.checkInterval = setInterval(() => {
            this.checkConnection();
        }, 5000);
    }

    handleOnline() {
        this.isOnline = true;
        this.consecutiveSlowCount = 0;
        if (this.warningDelayTimer) {
            clearTimeout(this.warningDelayTimer);
            this.warningDelayTimer = null;
        }
        this.hideOfflineOverlay();
        this.hideSlowInternetWarning();
        this.startPeriodicCheck();
    }

    showOfflineOverlay() {
        if (this.overlayShown) return;
        
        const overlay = `
            <div id="internet-offline-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.95); z-index: 999999; display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center; color: white; padding: 40px;">
                    <div style="font-size: 80px; margin-bottom: 20px;">
                        <i class="fas fa-wifi-slash"></i>
                    </div>
                    <h2 style="font-size: 32px; margin-bottom: 15px; font-weight: 600;">No Internet Connection</h2>
                    <p style="font-size: 18px; color: #ccc; margin-bottom: 25px;">
                        Please check your internet connection and try again.
                    </p>
                    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Reconnecting...</span>
                    </div>
                    <p style="font-size: 14px; color: #999; margin-top: 15px;">Attempting to reconnect...</p>
                </div>
            </div>
        `;
        
        $('body').append(overlay);
        this.overlayShown = true;
    }

    hideOfflineOverlay() {
        $('#internet-offline-overlay').fadeOut(300, function() {
            $(this).remove();
        });
        this.overlayShown = false;
    }

    showSlowInternetWarning() {
        if ($('#slow-internet-banner').length > 0) return;
        
        const banner = `
            <div id="slow-internet-banner" style="position: fixed; top: 0; left: 0; width: 100%; 
                background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); 
                color: white; padding: 12px 20px; z-index: 99999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                display: flex; align-items: center; justify-content: space-between; animation: slideDown 0.3s ease;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                    <div>
                        <strong style="font-size: 16px;">Slow Internet Connection</strong>
                        <p style="margin: 0; font-size: 13px; opacity: 0.9;">
                            Your connection is slow. Some features may take longer to load.
                        </p>
                    </div>
                </div>
                <button onclick="internetMonitor.hideSlowInternetWarning()" 
                    style="background: rgba(255,255,255,0.2); border: none; color: white; 
                    padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                    Dismiss
                </button>
            </div>
            <style>
                @keyframes slideDown {
                    from { transform: translateY(-100%); }
                    to { transform: translateY(0); }
                }
            </style>
        `;
        
        $('body').prepend(banner);
    }

    hideSlowInternetWarning() {
        // Clear any pending warning
        if (this.warningDelayTimer) {
            clearTimeout(this.warningDelayTimer);
            this.warningDelayTimer = null;
        }
        
        $('#slow-internet-banner').fadeOut(300, function() {
            $(this).remove();
        });
        this.isSlow = false;
    }

    interceptAjax() {
        const self = this;
        
        // Store original jQuery ajax
        const originalAjax = $.ajax;
        
        $.ajax = function(options) {
            const startTime = Date.now();
            
            // Add timeout if not specified (use longer timeout)
            if (!options.timeout) {
                options.timeout = 30000; // 30 seconds default
            }
            
            const originalError = options.error;
            const originalSuccess = options.success;
            
            options.error = function(xhr, status, error) {
                if (status === 'timeout') {
                    self.consecutiveSlowCount++;
                    
                    // Wait 5 seconds before showing warning
                    if (self.consecutiveSlowCount >= self.slowCountThreshold && !self.isSlow) {
                        if (self.warningDelayTimer) clearTimeout(self.warningDelayTimer);
                        
                        self.warningDelayTimer = setTimeout(() => {
                            // Double-check count is still high after delay
                            if (self.consecutiveSlowCount >= self.slowCountThreshold && !self.isSlow) {
                                self.isSlow = true;
                                self.showSlowInternetWarning();
                            }
                        }, self.warningDelay);
                    }
                } else if (status === 'error' && xhr.status === 0) {
                    self.handleOffline();
                }
                
                if (originalError) {
                    originalError.apply(this, arguments);
                }
            };
            
            options.success = function(data, status, xhr) {
                const duration = Date.now() - startTime;
                
                // Only flag as slow if significantly over threshold
                if (duration > self.slowThreshold * 1.5) {
                    self.consecutiveSlowCount++;
                    
                    // Wait 5 seconds before showing warning
                    if (self.consecutiveSlowCount >= self.slowCountThreshold && !self.isSlow) {
                        if (self.warningDelayTimer) clearTimeout(self.warningDelayTimer);
                        
                        self.warningDelayTimer = setTimeout(() => {
                            // Double-check count is still high after delay
                            if (self.consecutiveSlowCount >= self.slowCountThreshold && !self.isSlow) {
                                self.isSlow = true;
                                self.showSlowInternetWarning();
                            }
                        }, self.warningDelay);
                    }
                } else {
                    // Reset counter on successful fast request
                    self.consecutiveSlowCount = 0;
                    
                    // Cancel any pending warning
                    if (self.warningDelayTimer) {
                        clearTimeout(self.warningDelayTimer);
                        self.warningDelayTimer = null;
                    }
                    
                    // Hide warning if shown
                    if (self.isSlow) {
                        self.hideSlowInternetWarning();
                    }
                }
                
                if (originalSuccess) {
                    originalSuccess.apply(this, arguments);
                }
            };
            
            return originalAjax.call(this, options);
        };
    }
}

// Initialize on document ready
let internetMonitor;
$(document).ready(function() {
    internetMonitor = new InternetMonitor();
});
