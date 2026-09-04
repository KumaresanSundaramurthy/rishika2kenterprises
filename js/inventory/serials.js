// ── Serial Numbers management page ────────────────────────────────────────────

(function ($) {
    'use strict';

    var _csrfName     = window._snCsrf    || '';
    var _csrfVal      = window._snCsrfVal || '';
    var _rowLimit     = window._snRowLimit || 10;
    var _activeStatus = '';
    var _searchTimer  = null;
    var _reqSeq       = 0;

    // ── URL state ─────────────────────────────────────────────────────────────

    /**
     * Push current status + search into the browser URL without reloading.
     * @param {string} status
     * @param {string} search
     * @returns {void}
     */
    function _pushUrlState(status, search) {
        var p = new URLSearchParams();
        if (status) p.set('status', status.toLowerCase()); // always lowercase in URL
        if (search)  p.set('search', search);
        var qs = p.toString();
        history.pushState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
    }

    // ── Filter ────────────────────────────────────────────────────────────────

    /**
     * @returns {Object}
     */
    function _buildFilter() {
        var f = {};
        if (_activeStatus) f.Status = _activeStatus;
        var q = $.trim($('#snSearchInput').val());
        if (q) f.search = q;
        return f;
    }

    // ── Tab helpers ───────────────────────────────────────────────────────────

    /**
     * Mark a tab active in the DOM and update internal state.
     * @param {string} status
     * @returns {void}
     */
    function _activateTab(status) {
        _activeStatus = status;
        $('.sn-tab').removeClass('active');
        $('.sn-tab[data-status="' + status + '"]').addClass('active');
    }

    /**
     * Show count badge only on the active tab; hide all others.
     * @param {number} count
     * @returns {void}
     */
    function _updateTabCount(count) {
        $('.sn-tab-count').addClass('d-none').text('');
        if (count > 0) {
            $('.sn-tab.active .sn-tab-count').text(count.toLocaleString()).removeClass('d-none');
        }
    }

    // ── Grid spinner ──────────────────────────────────────────────────────────

    /**
     * Replace table body with a spinner row and clear pagination.
     * @returns {void}
     */
    function _showGridSpinner() {
        $('#snTableBody').html(
            '<tr><td colspan="8" class="text-center py-4">' +
            '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
            '<span class="text-muted" style="font-size:.85rem;">Loading...</span>' +
            '</td></tr>'
        );
        $('#snPagination').empty();
    }

    // ── List reload ───────────────────────────────────────────────────────────

    /**
     * Load a page into the grid; always uses grid spinner — never a full-page overlay.
     * @param {number} pageNo
     * @returns {void}
     */
    function _loadPage(pageNo) {
        var seq = ++_reqSeq;
        ajaxLoading(0); // prevent full-page overlay — AjaxLoading defaults to 1 in trans_footer_script
        _showGridSpinner();
        $.post('/inventory/serials/getPageDetails/' + (pageNo || 1), {
            RowLimit   : _rowLimit,
            Filter     : _buildFilter(),
            [_csrfName]: _csrfVal,
        }, function (resp) {
            if (seq !== _reqSeq) return;
            if (!resp || resp.Error) {
                showToastNotification(resp ? resp.Message : 'Error loading data.', 'error');
                $('#snTableBody').html(
                    '<tr><td colspan="8" class="text-center text-danger py-3">Failed to load data.</td></tr>'
                );
                return;
            }
            $('#snTableBody').html(resp.RecordHtmlData);
            $('#snPagination').html(resp.Pagination);
            _updateTabCount(parseInt(resp.TotalCount, 10) || 0);
            if (resp.Stats) _updateStats(resp.Stats);
            if (typeof initTooltips === 'function') initTooltips();
        }, 'json').fail(function () {
            if (seq !== _reqSeq) return;
            showToastNotification('Request failed.', 'error');
            $('#snTableBody').html(
                '<tr><td colspan="8" class="text-center text-danger py-3">Request failed.</td></tr>'
            );
        });
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    /**
     * @param {Object} s
     * @returns {void}
     */
    function _updateStats(s) {
        $('#statAvailable').text(parseInt(s.Available || 0).toLocaleString());
        $('#statSold').text(parseInt(s.Sold || 0).toLocaleString());
        $('#statReturned').text(parseInt(s.Returned || 0).toLocaleString());
        $('#statDamaged').text(parseInt(s.Damaged || 0).toLocaleString());
    }

    // ── Tab switching ─────────────────────────────────────────────────────────

    $(document).on('click', '.sn-tab', function () {
        var status = $(this).data('status') || '';
        if (status === _activeStatus && $(this).hasClass('active')) return;
        _activateTab(status);
        _pushUrlState(status, $.trim($('#snSearchInput').val()));
        _loadPage(1);
    });

    // ── Search ────────────────────────────────────────────────────────────────

    $(document).on('input', '#snSearchInput', function () {
        clearTimeout(_searchTimer);
        var val = $.trim($(this).val());
        _searchTimer = setTimeout(function () {
            _pushUrlState(_activeStatus, val);
            _loadPage(1);
        }, 400);
    });

    // ── Pagination ────────────────────────────────────────────────────────────

    $(document).on('click', '#snPagination .apex-page-btn', function () {
        var pg = parseInt($(this).data('page'), 10);
        if (pg) _loadPage(pg);
    });

    // ── Refresh ───────────────────────────────────────────────────────────────

    $(document).on('click', '.pageRefresh', function () { _loadPage(1); });

    // ── Status update (mark Damaged / mark Available) ─────────────────────────

    $(document).on('click', '.sn-mark-damaged, .sn-mark-available', function () {
        var $btn      = $(this);
        var serialUID = parseInt($btn.data('uid'), 10);
        var newStatus = $btn.data('status');
        var label     = newStatus === 'Damaged' ? 'Mark as Damaged?' : 'Mark as Available?';

        if (!confirm(label)) return;

        ajaxLoading(1);
        $.post('/inventory/serials/updateStatus', {
            SerialUID   : serialUID,
            Status      : newStatus,
            [_csrfName] : _csrfVal,
        }, function (resp) {
            ajaxLoading(0);
            if (!resp || resp.Error) {
                showToastNotification(resp ? resp.Message : 'Update failed.', 'error');
                return;
            }
            showToastNotification('Status updated.', 'success');
            setTimeout(function () { _loadPage(1); }, 0);
        }, 'json').fail(function () {
            ajaxLoading(0);
            showToastNotification('Request failed.', 'error');
        });
    });

    // ── Add Serial Modal ──────────────────────────────────────────────────────

    var _serialProducts = null;

    /**
     * Load serial-tracked products: Upstash HGETALL first, server fallback if miss.
     * @param {function(Array)} callback
     * @returns {void}
     */
    function _loadSerialProducts(callback) {
        if (_serialProducts !== null) { callback(_serialProducts); return; }
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.hgetall(UpstashService.orgKey('products')).then(function (map) {
                var list = [];
                Object.keys(map).forEach(function (uid) {
                    var p = map[uid];
                    if (p && parseInt(p.IsSerialTracked, 10) === 1) {
                        list.push({ id: parseInt(uid, 10), text: p.ItemName || '' });
                    }
                });
                list.sort(function (a, b) { return a.text.localeCompare(b.text); });
                if (list.length > 0) {
                    _serialProducts = list;
                    callback(_serialProducts);
                    return;
                }
                _fetchSerialProductsFromServer(callback);
            }).catch(function () {
                _fetchSerialProductsFromServer(callback);
            });
        } else {
            _fetchSerialProductsFromServer(callback);
        }
    }

    /**
     * Server fallback: fetch all serial-tracked products in one shot.
     * @param {function(Array)} callback
     * @returns {void}
     */
    function _fetchSerialProductsFromServer(callback) {
        $.post('/inventory/serials/searchProducts', { [_csrfName]: _csrfVal }, function (resp) {
            var list = [];
            if (resp && !resp.Error && Array.isArray(resp.Data)) {
                resp.Data.forEach(function (p) {
                    list.push({ id: parseInt(p.ProductUID, 10), text: p.ItemName || '' });
                });
            }
            _serialProducts = list;
            callback(_serialProducts);
        }, 'json').fail(function () {
            _serialProducts = [];
            callback(_serialProducts);
        });
    }

    /**
     * Init (or re-init) the product Select2 with a static data array.
     * @param {Array} products
     * @returns {void}
     */
    function _initProductSelect2(products) {
        var $sel = $('#snProductSelect');
        if ($sel.hasClass('select2-hidden-accessible')) { $sel.select2('destroy'); }
        $sel.html('<option value=""></option>');
        $sel.select2({
            placeholder   : '— Select Product —',
            allowClear    : true,
            dropdownParent: $('#addSerialModal'),
            data          : products,
        });
        $sel.val(null).trigger('change');
    }

    $('#btnAddSerial').on('click', function () {
        $('#addSerialError').addClass('d-none').text('');
        $('#snSerialInput').val('');
        $('#snNotesInput').val('');
        _loadSerialProducts(function (products) {
            _initProductSelect2(products);
            var modal = new bootstrap.Modal(document.getElementById('addSerialModal'));
            modal.show();
            setTimeout(function () { $('#snSerialInput').focus(); }, 400);
        });
    });

    $('#btnSaveSerial').on('click', function () {
        var productUID   = parseInt($('#snProductSelect').val(), 10) || 0;
        var serialNumber = $.trim($('#snSerialInput').val());
        var notes        = $.trim($('#snNotesInput').val());
        var $err         = $('#addSerialError');

        $err.addClass('d-none').text('');
        if (!productUID)   { $err.removeClass('d-none').text('Please select a product.');   return; }
        if (!serialNumber) { $err.removeClass('d-none').text('Serial number is required.'); return; }

        ajaxLoading(1);
        $.post('/inventory/serials/add', {
            ProductUID  : productUID,
            SerialNumber: serialNumber,
            Notes       : notes,
            [_csrfName] : _csrfVal,
        }, function (resp) {
            ajaxLoading(0);
            if (!resp || resp.Error) {
                $err.removeClass('d-none').text(resp ? resp.Message : 'Save failed.');
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('addSerialModal')).hide();
            showToastNotification('Serial number added.', 'success');
            setTimeout(function () { _loadPage(1); }, 0);
        }, 'json').fail(function () {
            ajaxLoading(0);
            $err.removeClass('d-none').text('Request failed.');
        });
    });

    $(document).on('keydown', '#snSerialInput', function (e) {
        if (e.key === 'Enter') $('#btnSaveSerial').trigger('click');
    });

    // ── Init from URL state ───────────────────────────────────────────────────

    (function _init() {
        // URL uses lowercase; map back to capitalised internal value used by DB + tab data-status
        var _statusMap = { available: 'Available', sold: 'Sold', returned: 'Returned', damaged: 'Damaged' };
        var p          = new URLSearchParams(window.location.search);
        var raw        = (p.get('status') || '').toLowerCase();
        var status     = _statusMap[raw] || '';

        // Sync JS state — PHP already rendered the correct tab + data server-side
        _activeStatus = status;

        // Sync count from what PHP rendered into the active tab badge
        var $activeBadge = $('.sn-tab.active .sn-tab-count');
        var initCount    = parseInt(($activeBadge.text() || '').replace(/,/g, ''), 10) || 0;
        _updateTabCount(initCount);
    }());

}(jQuery));
