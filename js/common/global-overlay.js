/* ── Global Processing Overlay — standalone, no jQuery required ─────────────
 * Defines showUIBlock(text) and hideUIBlock() for pages that do not load
 * default.js (e.g. signup, onboarding — pre-auth standalone pages).
 * Injects its own CSS so core.css is not required.
 * Safe to include on any page; a later default.js load will overwrite these
 * globals on authenticated pages (identical behaviour, no conflict).
 * ─────────────────────────────────────────────────────────────────────────── */
(function () {

    /* Inject overlay CSS once — skipped if already present (e.g. core.css loaded) */
    if (!document.getElementById('gpo-global-styles')) {
        var s = document.createElement('style');
        s.id = 'gpo-global-styles';
        s.textContent =
            '#globalProcOverlay{'
            + 'display:none;position:fixed;inset:0;z-index:999999;'
            + 'background:rgba(10,10,20,.82);'
            + 'align-items:center;justify-content:center;}'
            + '#globalProcOverlay.proc-on{'
            + 'display:flex;animation:gpoFade .18s ease;}'
            + '@keyframes gpoFade{from{opacity:0}to{opacity:1}}'
            + '.gpo-wrap{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;}'
            + '.gpo-ring-outer{'
            + 'width:90px;height:90px;border-radius:50%;'
            + 'background:conic-gradient(from 0deg,#696cff,#a78bfa,#38bdf8,#4ade80,#696cff);'
            + 'padding:4px;animation:gpoSpin 1.4s linear infinite;'
            + 'display:flex;align-items:center;justify-content:center;flex-shrink:0;}'
            + '.gpo-ring-inner{'
            + 'width:100%;height:100%;border-radius:50%;background:#13111a;'
            + 'display:flex;align-items:center;justify-content:center;overflow:hidden;'
            + 'animation:gpoCounterSpin 1.4s linear infinite;}'
            + '@keyframes gpoCounterSpin{to{transform:rotate(-360deg)}}'
            + '@keyframes gpoSpin{to{transform:rotate(360deg)}}'
            + '.gpo-logo{width:56px;height:56px;border-radius:50%;object-fit:cover;}'
            + '.gpo-wait-text{'
            + 'display:none;font-size:13px;font-weight:500;color:#e0e0ff;letter-spacing:.03em;}'
            + '#globalProcOverlay.gpo-wait-only .gpo-wait-text{display:block;}'
            + '.gpo-dots{display:flex;gap:7px;}'
            + '.gpo-dots span{width:7px;height:7px;border-radius:50%;background:#696cff;animation:gpoBounce .9s ease-in-out infinite;}'
            + '.gpo-dots span:nth-child(2){animation-delay:.18s;background:#a78bfa;}'
            + '.gpo-dots span:nth-child(3){animation-delay:.36s;background:#38bdf8;}'
            + '@keyframes gpoBounce{0%,100%{transform:translateY(0);opacity:.5}50%{transform:translateY(-7px);opacity:1}}';
        document.head.appendChild(s);
    }

    var _gpoTimer = null;
    var _logoSrc  = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';

    /**
     * Show the global processing overlay.
     * @param {string} [text] - Optional message shown below the ring
     * @returns {void}
     */
    window.showUIBlock = function (text) {
        clearTimeout(_gpoTimer);
        if (!document.getElementById('globalProcOverlay')) {
            var d = document.createElement('div');
            d.id        = 'globalProcOverlay';
            d.className = 'gpo-wait-only';
            d.innerHTML =
                '<div class="gpo-wrap">'
                + '<div class="gpo-ring-outer"><div class="gpo-ring-inner">'
                + '<img class="gpo-logo" src="' + _logoSrc + '">'
                + '</div></div>'
                + '<div class="gpo-wait-text" id="gpoWaitText">Processing… Please wait…</div>'
                + '<div class="gpo-dots"><span></span><span></span><span></span></div>'
                + '</div>';
            document.body.appendChild(d);
        }
        if (text) {
            var el = document.getElementById('gpoWaitText');
            if (el) el.textContent = text;
        }
        document.getElementById('globalProcOverlay').classList.add('proc-on');
    };

    /**
     * Hide the global processing overlay.
     * @returns {void}
     */
    window.hideUIBlock = function () {
        clearTimeout(_gpoTimer);
        _gpoTimer = setTimeout(function () {
            var ov = document.getElementById('globalProcOverlay');
            if (ov) ov.classList.remove('proc-on');
        }, 300);
    };

}());
