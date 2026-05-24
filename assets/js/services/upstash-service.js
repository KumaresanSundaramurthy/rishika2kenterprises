/**
 * UpstashService — browser-side Upstash REST client.
 *
 * Mirrors the PHP Upstashservice library so the same cache keys and
 * patterns work on both sides without a PHP round-trip.
 *
 * Config is read from meta tags injected by header.php:
 *   <meta name="upstash-url"    content="https://xxxx.upstash.io">
 *   <meta name="upstash-token"  content="Bearer token">
 *   <meta name="app-org-prefix" content="{shortcode}:{orgtoken}:{env}">
 *
 * Usage:
 *   const data = await UpstashService.get(UpstashService.orgKey('loc-states'));
 *   await UpstashService.set(UpstashService.orgKey('loc-states'), map, 0); // 0 = no expiry
 */
const UpstashService = (() => {

    const _url    = document.querySelector('meta[name="upstash-url"]')?.content    || '';
    const _token  = document.querySelector('meta[name="upstash-token"]')?.content  || '';
    const _prefix = document.querySelector('meta[name="app-org-prefix"]')?.content || '';
    const _on     = !!((_url && _token));

    // ── Internal: execute one Redis command via Upstash REST API ─────────────

    async function _cmd(command) {
        if (!_on) return null;
        try {
            const res = await fetch(_url, {
                method:  'POST',
                headers: {
                    'Authorization': 'Bearer ' + _token,
                    'Content-Type':  'application/json',
                },
                body: JSON.stringify(command),
            });
            if (!res.ok) return null;
            const json = await res.json();
            return json.result ?? null;
        } catch {
            return null;
        }
    }

    // ── Key helpers ───────────────────────────────────────────────────────────

    /**
     * Build an org-scoped key — same logic as PHP Redisservice::orgKey().
     * Format: {shortcode}:{orgtoken}:{env}:{type}  (all lowercase)
     * Falls back to {type} when the org prefix is unavailable.
     */
    function orgKey(type) {
        return _prefix ? `${_prefix}:${type}` : type;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * GET a cached value.
     * Returns the decoded JS value on HIT, null on MISS or error.
     */
    async function get(key) {
        try {
            const raw = await _cmd(['GET', key]);
            if (raw === null) return null;
            try   { return JSON.parse(raw); }
            catch { return raw; }
        } catch {
            return null;
        }
    }

    /**
     * SET a value with optional TTL.
     * ttl = 0 (default) → no expiry (permanent key).
     * Returns true on success.
     */
    async function set(key, value, ttl = 0) {
        try {
            const payload = typeof value === 'string'
                ? value
                : JSON.stringify(value);
            const cmd = ['SET', key, payload];
            if (ttl > 0) { cmd.push('EX', String(ttl)); }
            return (await _cmd(cmd)) === 'OK';
        } catch {
            return false;
        }
    }

    /**
     * DELETE one or more keys (variadic).
     * Returns true if at least one key was deleted.
     */
    async function del(...keys) {
        try {
            return (await _cmd(['DEL', ...keys])) > 0;
        } catch {
            return false;
        }
    }

    /** True when Upstash URL + token are configured. */
    function isEnabled() { return _on; }

    return { get, set, del, orgKey, isEnabled };

})();
