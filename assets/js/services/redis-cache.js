/**
 * RedisCacheService — thin frontend wrapper around the /cache/* API endpoints.
 *
 * Usage:
 *   const settings = await RedisCacheService.get('settings');
 *   await RedisCacheService.refresh();
 */
const RedisCacheService = (() => {

    const BASE = '/cache';

    async function _request(method, path, body = null) {
        const opts = {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        };
        if (body) {
            const form = new FormData();
            Object.entries(body).forEach(([k, v]) =>
                form.append(k, typeof v === 'object' ? JSON.stringify(v) : v)
            );
            // Append CSRF token if available
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) form.append(csrf.getAttribute('name') || 'csrf_token', csrf.getAttribute('content'));
            opts.body = form;
        }
        const res = await fetch(BASE + path, opts);
        return res.json();
    }

    /**
     * Read a user-scoped cache entry.
     * @param {string} type  One of: menus, submenus, modules, permissions, settings, userinfo
     * @returns {Promise<any>}  The cached value, or null on miss.
     */
    async function get(type) {
        const data = await _request('GET', `/get?type=${encodeURIComponent(type)}`);
        return data.Error ? null : data.Value;
    }

    /**
     * Write a user-scoped cache entry.
     * @param {string} type
     * @param {any}    value
     * @param {number} [ttl=0]  Seconds; 0 uses the server default (LOGIN_EXPIRE_SECS).
     * @returns {Promise<object>}
     */
    async function set(type, value, ttl = 0) {
        return _request('POST', '/set', { type, value, ttl });
    }

    /**
     * Delete one or all user-scoped cache entries.
     * @param {string} type  Cache type name, or 'all' to clear everything.
     * @returns {Promise<object>}
     */
    async function remove(type) {
        return _request('DELETE', `/delete?type=${encodeURIComponent(type)}`);
    }

    /**
     * Rebuild the user's cache from the database.
     * Call after saving settings/roles/permissions so the UI reflects changes immediately.
     * @returns {Promise<object>}
     */
    async function refresh() {
        return _request('POST', '/refresh');
    }

    return { get, set, remove, refresh };

})();
