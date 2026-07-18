/**
 * sticky_paginate.js
 * Auto-initialises for every .apex-pag-sticky element on the page.
 *
 * Places a zero-height sentinel immediately after each element. An
 * IntersectionObserver watches the sentinel — when it scrolls out of the
 * viewport the pagination is in "stuck" mode, so .is-stuck is added (CSS
 * then shows the background/border). When the sentinel re-enters the viewport
 * the element is back in normal flow and .is-stuck is removed.
 *
 * HTML usage:
 *   <div class="... apex-pag-sticky" id="myPagination"> ... </div>
 *
 * No data attributes or manual hooks needed — works automatically across
 * tab switches, AJAX reloads, searches, filters, resizes, and empty states.
 */
(function () {
    'use strict';

    /**
     * Wires up the stuck-detection observer for one pagination element.
     * @param {Element} el - The .apex-pag-sticky element to observe.
     * @returns {void}
     */
    function initStickyPag(el) {
        var sentinel = document.createElement('div');
        el.parentNode.insertBefore(sentinel, el.nextSibling);

        new IntersectionObserver(function (entries) {
            el.classList.toggle('is-stuck', !entries[0].isIntersecting);
        }).observe(sentinel);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.apex-pag-sticky').forEach(initStickyPag);
    });
})();
