// ── Serial Numbers management page ────────────────────────────────────────────

(function ($) {
    'use strict';

    var _rowLimit     = window._snRowLimit || 10;
    var _activeStatus = '';
    var _searchTimer  = null;
    var _reqSeq       = 0;

    /* escHtml may not be loaded on this page — define a local fallback */
    var _esc = typeof escHtml === 'function' ? escHtml : function (s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // ── Service params helper ─────────────────────────────────────────────────

    /**
     * @param {Object} extra
     * @returns {Object}
     */
    function _srlSvcParams(extra) {
        return $.extend({
            orgId   : parseInt(JwtData.Org.OrgUID,    10),
            userId  : parseInt(JwtData.User.UserUID,  10),
            branchId: parseInt(JwtData.Org.BranchUID, 10) || 1,
        }, extra || {});
    }

    // ── Pagination builder ────────────────────────────────────────────────────

    /**
     * @param {number} total
     * @param {number} pageNo
     * @param {number} limit
     * @returns {string}
     */
    // ── Date formatter (local — inventory.js not loaded on this page) ────────

    /**
     * @param {string} raw
     * @returns {string}
     */
    function _snFmtDate(raw) {
        if (!raw) return '—';
        var d = new Date(raw);
        if (isNaN(d)) return raw;
        var fmt     = (typeof _transListDateFormat !== 'undefined') ? _transListDateFormat : 'd M Y';
        var months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day     = d.getDate();
        var mon     = months[d.getMonth()];
        var yr      = d.getFullYear();
        var mm      = String(d.getMonth() + 1).padStart(2, '0');
        var dd      = String(day).padStart(2, '0');
        if (fmt === 'd M Y' || fmt === 'j M Y') return day + ' ' + mon + ' ' + yr;
        if (fmt === 'd/m/Y')                    return dd + '/' + mm + '/' + yr;
        if (fmt === 'm/d/Y')                    return mm + '/' + dd + '/' + yr;
        if (fmt === 'Y-m-d')                    return yr + '-' + mm + '-' + dd;
        return day + ' ' + mon + ' ' + yr;
    }

    // ── Row generator ─────────────────────────────────────────────────────────

    var SN_STATUS_CONFIG = {
        Available: { label: 'Available', cls: 'bg-label-success', icon: 'bx-check-circle' },
        Sold     : { label: 'Sold',      cls: 'bg-label-primary', icon: 'bx-shopping-bag' },
        Returned : { label: 'Returned',  cls: 'bg-label-info',    icon: 'bx-undo'         },
        Damaged  : { label: 'Damaged',   cls: 'bg-label-danger',  icon: 'bx-error-circle' },
    };

    /**
     * @param {Array}  rows
     * @param {number} serialStart
     * @returns {string}
     */
    function _generateSerialsHtml(rows, serialStart) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="8"><div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">'
                + '<i class="bx bx-barcode" style="font-size:2.5rem;opacity:.3;margin-bottom:8px;"></i>'
                + '<div style="font-size:var(--r2k-text-85);font-weight:600;color:#64748b;">No serial numbers found</div>'
                + '<div style="font-size:var(--r2k-text-75);margin-top:4px;">Add a serial number using the + Add Serial button above.</div>'
                + '</div></td></tr>';
        }

        var showSno  = parseInt(((JwtData.GenSettings || {}).SerialNoDisplay), 10) === 1;
        var snoClass = showSno ? 'table-serialno' : 'd-none table-serialno';
        var sno      = parseInt(serialStart, 10) || 0;
        var html     = '';

        rows.forEach(function (row) {
            sno++;
            var uid     = parseInt(row.SerialUID, 10);
            var status  = row.Status || 'Available';
            var cfg     = SN_STATUS_CONFIG[status] || { label: status, cls: 'bg-label-secondary', icon: 'bx-circle' };
            var isManual = row.SourceType === 'Manual';
            var canEdit  = status === 'Available' || status === 'Damaged';

            // Date / time
            var createdDate = '—', createdTime = '';
            if (row.CreatedAt) {
                var cd = new Date(row.CreatedAt);
                if (!isNaN(cd)) {
                    createdDate = _snFmtDate(row.CreatedAt);
                    var h = cd.getHours(), m = cd.getMinutes();
                    var ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12 || 12;
                    createdTime = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ampm;
                }
            }

            var snCell = '<div class="fw-semibold" style="font-size:var(--r2k-text-88);font-family:monospace;letter-spacing:.03em;">' + _esc(row.SerialNumber) + '</div>';
            if (row.Notes) snCell += '<div class="text-muted" style="font-size:var(--r2k-text-sm);margin-top:2px;"><i class="bx bx-note me-1"></i>' + _esc(row.Notes) + '</div>';

            var prodCell = '<div class="fw-medium" style="font-size:.83rem;">' + _esc(row.ItemName) + '</div>';
            if (row.PartNumber) prodCell += '<div class="text-muted" style="font-size:var(--r2k-text-70);">Part# ' + _esc(row.PartNumber) + '</div>';

            var statusCell = '<span class="badge ' + cfg.cls + '" style="font-size:var(--r2k-text-70);"><i class="bx ' + cfg.icon + ' me-1"></i>' + cfg.label + '</span>';

            var sourceCell;
            if (isManual) {
                sourceCell = '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-70);"><i class="bx bx-pencil me-1"></i>Manual</span>';
            } else {
                sourceCell = '<span class="badge bg-label-success" style="font-size:var(--r2k-text-70);"><i class="bx bx-shopping-bag me-1"></i>Purchase</span>';
                if (row.PurchaseTransNo) sourceCell += '<div class="text-muted" style="font-size:var(--r2k-text-70);margin-top:2px;">' + _esc(row.PurchaseTransNo) + '</div>';
            }

            var txCell;
            if (row.SaleTransNo) {
                txCell = '<span class="text-danger" style="font-size:var(--r2k-text-78);font-weight:600;"><i class="bx bx-receipt me-1"></i>' + _esc(row.SaleTransNo) + '</span>';
            } else if (row.ReturnTransNo) {
                txCell = '<span class="text-info" style="font-size:var(--r2k-text-78);font-weight:600;"><i class="bx bx-undo me-1"></i>' + _esc(row.ReturnTransNo) + '</span>';
            } else {
                txCell = '<span class="text-muted">—</span>';
            }

            var dateCell = '<div style="font-size:var(--r2k-text-78);">' + createdDate + '</div>';
            if (createdTime) dateCell += '<div class="text-muted" style="font-size:var(--r2k-text-sm);margin-top:1px;"><i class="bx bx-time-five me-1" style="font-size:var(--r2k-text-70);"></i>' + createdTime + '</div>';

            var actCell;
            if (canEdit) {
                actCell = '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
                    + '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';
                if (status === 'Available') actCell += '<li><button class="dropdown-item sn-mark-damaged" data-uid="' + uid + '" data-status="Damaged"><i class="bx bx-error-circle me-2 text-danger"></i>Mark Damaged</button></li>';
                if (status === 'Damaged')   actCell += '<li><button class="dropdown-item sn-mark-available" data-uid="' + uid + '" data-status="Available"><i class="bx bx-check-circle me-2 text-success"></i>Mark Available</button></li>';
                actCell += '</ul></div>';
            } else {
                actCell = '<span class="text-muted" style="font-size:var(--r2k-text-75);">—</span>';
            }

            html += '<tr id="sn-row-' + uid + '">'
                + '<td class="' + snoClass + '" style="width:44px;"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>'
                + '<td>' + snCell + '</td>'
                + '<td>' + prodCell + '</td>'
                + '<td>' + statusCell + '</td>'
                + '<td>' + sourceCell + '</td>'
                + '<td>' + txCell + '</td>'
                + '<td>' + dateCell + '</td>'
                + '<td>' + actCell + '</td>'
                + '</tr>';
        });
        return html;
    }

    // ── URL state ─────────────────────────────────────────────────────────────

    /**
     * @param {string} status
     * @param {string} search
     * @returns {void}
     */
    function _pushUrlState(status, search) {
        var p = new URLSearchParams();
        if (status) p.set('status', status.toLowerCase());
        if (search) p.set('search', search);
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
     * @param {string} status
     * @returns {void}
     */
    function _activateTab(status) {
        _activeStatus = status;
        $('.sn-tab').removeClass('active');
        $('.sn-tab[data-status="' + status + '"]').addClass('active');
    }

    /**
     * @param {number} count
     * @returns {void}
     */
    function _updateTabCount(count) {
        $('.sn-tab-count').addClass('d-none').text('');
        if (count > 0) {
            $('.sn-tab.active .sn-tab-count').text(count.toLocaleString()).removeClass('d-none');
        }
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    /**
     * @param {Object} s
     * @returns {void}
     */
    function _updateStats(s) {
        $('#statAvailable').text(parseInt(s.Available || 0).toLocaleString());
        $('#statSold').text(parseInt(s.Sold        || 0).toLocaleString());
        $('#statReturned').text(parseInt(s.Returned || 0).toLocaleString());
        $('#statDamaged').text(parseInt(s.Damaged   || 0).toLocaleString());
    }

    // ── Grid spinner ──────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _showGridSpinner() {
        $('#snTableBody').html(
            '<tr><td colspan="8" class="text-center py-4">'
            + '<span class="spinner-border spinner-border-sm text-primary me-2"></span>'
            + '<span class="text-muted" style="font-size:var(--r2k-text-85);">Loading...</span>'
            + '</td></tr>'
        );
        $('#snPagination').empty().addClass('d-none');
    }

    // ── List reload ───────────────────────────────────────────────────────────

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _loadPage(pageNo) {
        var seq = ++_reqSeq;
        ajaxLoading(0);
        _showGridSpinner();

        $.ajax({
            url    : global_services_url + 'api/inventory/serials/getPageDetails',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _srlSvcParams({
                RowLimit: _rowLimit,
                PageNo  : pageNo || 1,
                Filter  : JSON.stringify(_buildFilter()),
            }),
            success: function (resp) {
                if (seq !== _reqSeq) return;
                if (resp.Error) {
                    showToastNotification(resp.Message || 'Error loading data.', 'error');
                    $('#snTableBody').html('<tr><td colspan="8" class="text-center text-danger py-3">Failed to load data.</td></tr>');
                    return;
                }
                var serialStart = (parseInt(resp.PageNo, 10) - 1) * parseInt(resp.RowLimit, 10);
                $('#snTableBody').html(_generateSerialsHtml(resp.Data, serialStart));
                var pagHtml = generatePagination(resp.TotalCount, parseInt(resp.PageNo, 10), parseInt(resp.RowLimit, 10));
                if (pagHtml) {
                    $('#snPagination').html(pagHtml).removeClass('d-none');
                } else {
                    $('#snPagination').empty().addClass('d-none');
                }
                _updateTabCount(parseInt(resp.TotalCount, 10) || 0);
                if (typeof initTooltips === 'function') initTooltips();
            },
            error: function () {
                if (seq !== _reqSeq) return;
                showToastNotification('Request failed.', 'error');
                $('#snTableBody').html('<tr><td colspan="8" class="text-center text-danger py-3">Request failed.</td></tr>');
            }
        });
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

    $(document).on('click', '#snPagination .r2k-pag-btn', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (pg && !$(this).hasClass('disabled')) _loadPage(pg);
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
        $.ajax({
            url        : global_services_url + 'api/inventory/serials/updateStatus',
            method     : 'POST',
            headers    : { Authorization: 'Bearer ' + JwtToken },
            contentType: 'application/json',
            data       : JSON.stringify(_srlSvcParams({
                SerialUID: serialUID,
                Status   : newStatus,
            })),
            success: function (resp) {
                ajaxLoading(0);
                if (!resp || resp.Error) {
                    showToastNotification(resp ? resp.Message : 'Update failed.', 'error');
                    return;
                }
                showToastNotification('Status updated.', 'success');
                _loadPage(1);
            },
            error: function () {
                ajaxLoading(0);
                showToastNotification('Request failed.', 'error');
            }
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
        $.ajax({
            url    : global_services_url + 'api/inventory/serials/searchProducts',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _srlSvcParams({ search: '' }),
            success: function (resp) {
                var list = [];
                if (resp && !resp.Error && Array.isArray(resp.Data)) {
                    resp.Data.forEach(function (p) {
                        list.push({ id: parseInt(p.ProductUID, 10), text: p.ItemName || '' });
                    });
                }
                _serialProducts = list;
                callback(_serialProducts);
            },
            error: function () {
                _serialProducts = [];
                callback(_serialProducts);
            }
        });
    }

    /**
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
        $.ajax({
            url        : global_services_url + 'api/inventory/serials/add',
            method     : 'POST',
            headers    : { Authorization: 'Bearer ' + JwtToken },
            contentType: 'application/json',
            data       : JSON.stringify(_srlSvcParams({
                ProductUID  : productUID,
                SerialNumber: serialNumber,
                Notes       : notes || null,
            })),
            success: function (resp) {
                ajaxLoading(0);
                if (!resp || resp.Error) {
                    $err.removeClass('d-none').text(resp ? resp.Message : 'Save failed.');
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('addSerialModal')).hide();
                showToastNotification('Serial number added.', 'success');
                _loadPage(1);
            },
            error: function () {
                ajaxLoading(0);
                $err.removeClass('d-none').text('Request failed.');
            }
        });
    });

    $(document).on('keydown', '#snSerialInput', function (e) {
        if (e.key === 'Enter') $('#btnSaveSerial').trigger('click');
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    (function _init() {
        var _statusMap = { available: 'Available', sold: 'Sold', returned: 'Returned', damaged: 'Damaged' };
        var p      = new URLSearchParams(window.location.search);
        var raw    = (p.get('status') || '').toLowerCase();
        var status = _statusMap[raw] || '';
        _activateTab(status);

        // Fetch stats in parallel with the first page load
        $.ajax({
            url    : global_services_url + 'api/inventory/serials/getPageData',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _srlSvcParams({}),
            success: function (resp) {
                if (resp && !resp.Error) {
                    if (resp.Stats)    _updateStats(resp.Stats);
                    if (resp.RowLimit) _rowLimit = parseInt(resp.RowLimit, 10) || _rowLimit;
                }
            }
        });

        _loadPage(1);
    }());

}(jQuery));
