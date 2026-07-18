/**
 * Stores a pending toast in sessionStorage for a specific page key.
 * @param {string} key - Page-specific key e.g. '_quotPendingToast'
 * @param {string} msg - Toast message
 * @param {string} type - 'success' | 'error' | 'warning'
 * @returns {void}
 */
function _setPendingToast(key, msg, type) {
    sessionStorage.setItem(key, JSON.stringify({ msg: msg, type: type || 'success' }));
}

/**
 * Reads, clears and shows a pending toast from sessionStorage for the given key.
 * @param {string} key - Page-specific key e.g. '_quotPendingToast'
 * @returns {void}
 */
function _checkPendingToast(key) {
    var _pt = sessionStorage.getItem(key);
    if (!_pt) return;
    sessionStorage.removeItem(key);
    try {
        var _toast = JSON.parse(_pt);
        showToastNotification(_toast.msg, _toast.type || 'success');
    } catch (e) {}
}
