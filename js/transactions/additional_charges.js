/**
 * Transaction Additional Charges — shared across all transaction form pages.
 *
 * Globals injected by the transaction form view:
 *   _transAdditionalCharges  array   All active charges for the org (from Upstash)
 *   _transAdditionalTaxOpts  array   Tax options [{TaxDetailsUID, TaxName, Percentage}]
 *   billManager              object  BillingManager instance from transactions.js
 */

(function ($) {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param {string} s
     * @returns {string}
     */
    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Decimal formatter for money fields — returns '0' for zero, otherwise
     * smartDecimal with trailing-zero trimming (e.g. 50.00 → "50", 50.50 → "50.5").
     * @param {number} v
     * @returns {string}
     */
    function fmt(v) {
        var dp  = (typeof genSettings !== 'undefined' && genSettings.DecimalPoints) ? genSettings.DecimalPoints : 2;
        var num = parseFloat(v) || 0;
        return num === 0 ? '0' : smartDecimal(num, dp, false);
    }

    /**
     * Decimal formatter for the IN(%) field — preserves up to 8 significant decimal
     * places and strips trailing zeros, matching the precision needed for derived
     * percentages (e.g. 50 / 254.24 × 100 = 19.66666667, not the rounded 19.67).
     * @param {number} v
     * @returns {string}
     */
    function fmtPct(v) {
        var num = parseFloat(v) || 0;
        return num === 0 ? '0' : parseFloat(num.toFixed(8)).toString();
    }

    // ── Tax dropdown helpers ──────────────────────────────────────────────────

    /**
     * Build options HTML for a tax <select>.
     * @param {number} selectedUID
     * @returns {string}
     */
    function buildTaxOptions(selectedUID) {
        var html = '<option value="0" data-pct="0">0</option>';
        $.each(_transAdditionalTaxOpts || [], function (_, t) {
            var pct = parseFloat(t.Percentage) || 0;
            if (pct === 0) return; // 0 already covered by the hardcoded sentinel above
            var sel = parseInt(t.TaxDetailsUID, 10) === parseInt(selectedUID, 10) ? ' selected' : '';
            html += '<option value="' + t.TaxDetailsUID + '" data-pct="' + pct + '"' + sel + '>' + pct + '</option>';
        });
        return html;
    }

    /**
     * Populate the tax <select> for a single charge row.
     * @param {jQuery} $row
     * @param {number} selectedUID
     * @returns {void}
     */
    function populateRowTax($row, selectedUID) {
        $row.find('.ac-tax-select').html(buildTaxOptions(selectedUID || 0));
    }

    // ── Row calculation ───────────────────────────────────────────────────────

    /**
     * Triangular recalc — whichever field changed drives the other two.
     * @param {jQuery} $row
     * @param {string} [source]  'pct' | 'wot' | 'wt' | 'tax' — omit on init/billManagerReady
     * @returns {void}
     */
    function recalcRow($row, source) {
        _inChargeRecalc = true;
        var chargeUID = parseInt($row.data('charge-uid'), 10);
        var subtotal  = billManager ? (billManager.summary.items.taxableAmount || 0) : 0;
        var taxPct    = parseFloat($row.find('.ac-tax-select option:selected').data('pct')) || 0;
        var taxUID    = parseInt($row.find('.ac-tax-select').val(), 10) || 0;
        var taxMult   = 1 + taxPct / 100;

        var pctVal = parseFloat($row.find('.ac-pct-input').val()) || 0;
        var wotVal = parseFloat($row.find('.ac-wot-input').val()) || 0;
        var wtVal  = parseFloat($row.find('.ac-wt-input').val())  || 0;

        var netAmt = 0, grossAmt = 0, calcPct = 0;

        if (source === 'pct') {
            // IN% → derive Without Tax and With Tax
            netAmt   = subtotal > 0 ? (subtotal * pctVal / 100) : 0;
            grossAmt = netAmt * taxMult;
            $row.find('.ac-wot-input').val(fmt(netAmt));
            $row.find('.ac-wt-input').val(fmt(grossAmt));

        } else if (source === 'wot') {
            // Without Tax → derive IN% and With Tax
            netAmt   = wotVal;
            grossAmt = netAmt * taxMult;
            calcPct  = subtotal > 0 ? (netAmt * 100 / subtotal) : 0;
            $row.find('.ac-pct-input').val(fmtPct(calcPct));
            $row.find('.ac-wt-input').val(fmt(grossAmt));

        } else if (source === 'wt') {
            // With Tax → derive Without Tax and IN%
            netAmt   = wtVal / taxMult;
            grossAmt = wtVal;
            calcPct  = subtotal > 0 ? (netAmt * 100 / subtotal) : 0;
            $row.find('.ac-pct-input').val(fmtPct(calcPct));
            $row.find('.ac-wot-input').val(fmt(netAmt));

        } else {
            // Tax changed or init: drive from the highest-priority non-zero field
            if (pctVal > 0) {
                netAmt = subtotal > 0 ? (subtotal * pctVal / 100) : 0;
            } else if (wotVal > 0) {
                netAmt = wotVal;
            } else {
                netAmt = wtVal / taxMult;
            }
            grossAmt = netAmt * taxMult;
            $row.find('.ac-wot-input').val(fmt(netAmt));
            $row.find('.ac-wt-input').val(fmt(grossAmt));
        }

        // Re-read final values after all fields are updated
        var finalPct = parseFloat($row.find('.ac-pct-input').val()) || 0;
        var finalWot = parseFloat($row.find('.ac-wot-input').val()) || 0;
        var finalWt  = parseFloat($row.find('.ac-wt-input').val())  || 0;
        var taxAmt   = finalWot * taxPct / 100;

        if (billManager) {
            billManager.setAdditionalChargeDynamic(chargeUID, {
                inPercent        : finalPct,
                withoutTaxAmount : finalWot,
                taxUID           : taxUID,
                taxPercent       : taxPct,
                taxAmount        : taxAmt,
                withTaxAmount    : finalWt,
            });
        }
        _updateAppliedFlag();
        _inChargeRecalc = false;
    }

    // ── Build a new charge row HTML ───────────────────────────────────────────

    /**
     * @param {object} charge  Entry from _transAdditionalCharges
     * @param {number} [defaultTaxUID]
     * @returns {string}
     */
    function buildChargeRow(charge, defaultTaxUID) {
        var uid  = parseInt(charge.ChargeUID, 10);
        var name = esc(charge.Name        || '');
        var disp = esc(charge.DisplayName || '');
        var sort = parseInt(charge.SortOrder, 10) || 0;
        defaultTaxUID = defaultTaxUID || (charge.DefaultTaxUID || 0);
        return '<tr class="ac-charge-row"' +
            ' data-charge-uid="' + uid + '"' +
            ' data-name="'       + name + '"' +
            ' data-display-name="' + disp + '"' +
            ' data-tax-uid="'    + defaultTaxUID + '"' +
            ' data-sort-order="' + sort + '"' +
            ' data-driver="pct">' +
            '<td style="overflow:hidden;">' +
              '<span class="fw-semibold" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + disp + ' (+)</span>' +
            '</td>' +
            '<td class="text-center">' +
              '<select class="form-select form-select-sm ac-tax-select"' +
              ' data-charge-uid="' + uid + '">' +
              buildTaxOptions(defaultTaxUID) +
              '</select>' +
            '</td>' +
            '<td class="text-end">' +
              '<input type="number" class="form-control form-control-sm text-end ac-pct-input"' +
              ' min="0" max="100" step="any" value="0"' +
              ' data-charge-uid="' + uid + '">' +
            '</td>' +
            '<td class="text-end">' +
              '<div class="input-group input-group-sm">' +
                '<span class="input-group-text" style="border-right:0;">' + (genSettings && genSettings.CurrenySymbol ? genSettings.CurrenySymbol : '₹') + '</span>' +
                '<input type="number" class="form-control form-control-sm text-end ac-wot-input"' +
                ' min="0" step="0.01" value="0"' +
                ' data-charge-uid="' + uid + '">' +
              '</div>' +
            '</td>' +
            '<td class="text-end">' +
              '<div class="input-group input-group-sm">' +
                '<span class="input-group-text" style="border-right:0;">' + (genSettings && genSettings.CurrenySymbol ? genSettings.CurrenySymbol : '₹') + '</span>' +
                '<input type="number" class="form-control form-control-sm text-end ac-wt-input"' +
                ' min="0" step="0.01" value="0"' +
                ' data-charge-uid="' + uid + '">' +
              '</div>' +
            '</td>' +
            '<td class="text-center">' +
              '<button type="button" class="btn btn-icon btn-sm text-danger ac-remove-btn p-0"' +
              ' title="Remove charge" data-charge-uid="' + uid + '">' +
                '<i class="bx bx-trash fs-5"></i>' +
              '</button>' +
            '</td>' +
          '</tr>';
    }

    // ── Check if UID already on page ─────────────────────────────────────────

    /**
     * @param {number} chargeUID
     * @returns {boolean}
     */
    function chargeIsOnPage(chargeUID) {
        return $('#additionalChargesBody .ac-charge-row[data-charge-uid="' + chargeUID + '"]').length > 0;
    }

    // ── Show / hide table wrapper ─────────────────────────────────────────────

    function syncTableVisibility() {
        var hasRows = $('#additionalChargesBody .ac-charge-row').length > 0;
        $('#additionalChargesTable').toggleClass('d-none', !hasRows);
        $('#acEmptyState').toggleClass('d-none', hasRows);
        $('#acSeparator').toggleClass('d-none', !hasRows);
    }

    // ── Applied charges flag ──────────────────────────────────────────────────

    var acHasAppliedCharges = false;
    var _recalcingPct       = false; // re-entrance guard for _recalcPctRows
    var _inChargeRecalc     = false; // true while recalcRow is running (blocks _recalcPctRows)
    var _allChargesCache    = null;  // full org charges list loaded from Upstash on first View All click

    /**
     * Update acHasAppliedCharges — true if any charge row has a non-zero value.
     * @returns {void}
     */
    function _updateAppliedFlag() {
        acHasAppliedCharges = false;
        $('#additionalChargesBody .ac-charge-row').each(function () {
            var $r = $(this);
            if ((parseFloat($r.find('.ac-pct-input').val()) || 0) > 0 ||
                (parseFloat($r.find('.ac-wot-input').val()) || 0) > 0 ||
                (parseFloat($r.find('.ac-wt-input').val())  || 0) > 0) {
                acHasAppliedCharges = true;
                return false; // break early
            }
        });
    }

    /**
     * Zero all applied charge fields and collapse the section.
     * @returns {void}
     */
    function _clearAppliedCharges() {
        $('#additionalChargesBody .ac-charge-row').each(function () {
            $(this).find('.ac-pct-input, .ac-wot-input, .ac-wt-input').val('0');
        });
        acHasAppliedCharges = false;
        var $section = $('#additionalChargesSection');
        if ($section.is(':visible')) {
            $section.slideUp(200, function () {
                $('#acToggleChevron').removeClass('bx-chevron-up').addClass('bx-chevron-down');
            });
        }
    }

    /**
     * Sync every charge row when the cart taxable total changes.
     *
     * Driver = 'pct'  → IN% is fixed; recalc wot/wt from the new total.
     * Driver = 'wot'  → rupee amount (without tax) is fixed; recalc IN% only.
     * Driver = 'wt'   → rupee amount (with tax) is fixed; recalc IN% only.
     *
     * Re-entrance guards:
     *   _recalcingPct   — blocks the inner updateSummary call looping back here.
     *   _inChargeRecalc — blocks calls that originate from recalcRow itself
     *                     (user is typing in a charge field, not a cart item).
     * @returns {void}
     */
    function _recalcPctRows() {
        if (_recalcingPct || _inChargeRecalc) { return; }
        _recalcingPct = true;
        var subtotal = billManager ? (billManager.summary.items.taxableAmount || 0) : 0;
        $('#additionalChargesBody .ac-charge-row').each(function () {
            var $row      = $(this);
            var driver    = $row.data('driver') || 'pct';
            var chargeUID = parseInt($row.data('charge-uid'), 10);
            var taxPct    = parseFloat($row.find('.ac-tax-select option:selected').data('pct')) || 0;
            var taxUID    = parseInt($row.find('.ac-tax-select').val(), 10) || 0;
            var taxMult   = 1 + taxPct / 100;

            if (driver === 'pct') {
                // IN% drives → keep pct, update wot/wt to match the new total
                if ((parseFloat($row.find('.ac-pct-input').val()) || 0) > 0) {
                    recalcRow($row, 'pct');
                }
            } else {
                // Amount drives → keep wot/wt fixed, update IN% to reflect new total
                var wotVal  = parseFloat($row.find('.ac-wot-input').val()) || 0;
                var wtVal   = parseFloat($row.find('.ac-wt-input').val())  || 0;
                var netAmt  = driver === 'wt' ? wtVal / taxMult : wotVal;
                var calcPct = subtotal > 0 ? (netAmt * 100 / subtotal) : 0;
                $row.find('.ac-pct-input').val(fmtPct(calcPct));
                if (billManager && chargeUID) {
                    billManager.setAdditionalChargeDynamic(chargeUID, {
                        inPercent        : calcPct,
                        withoutTaxAmount : netAmt,
                        taxUID           : taxUID,
                        taxPercent       : taxPct,
                        taxAmount        : netAmt * taxPct / 100,
                        withTaxAmount    : netAmt * taxMult,
                    });
                }
            }
        });
        _recalcingPct = false;
    }

    // ── Watch cart for empty state (handles btnClearCart + individual deletes) ─

    $(function () {
        var billBodyEl = document.getElementById('billTableBody');
        if (billBodyEl) {
            new MutationObserver(function () {
                if (!$('#billTableBody tr[data-id]').length && acHasAppliedCharges) {
                    _clearAppliedCharges();
                }
            }).observe(billBodyEl, { childList: true });
        }
    });

    // ── Populate tax dropdowns for existing (PHP-rendered) rows ─────────────

    /**
     * On page load, fill tax selects for all rows already rendered by PHP.
     * @returns {void}
     */
    function initExistingRowTaxDropdowns() {
        $('#additionalChargesBody .ac-charge-row').each(function () {
            var $row      = $(this);
            var defTaxUID = parseInt($row.data('tax-uid'), 10) || 0;
            populateRowTax($row, defTaxUID);
        });
    }

    // ── Toggle section ───────────────────────────────────────────────────────
    $(document).on('click', '#btnToggleCharges', function () {
        var $section = $('#additionalChargesSection');
        // Block expansion when cart has no items
        if (!$section.is(':visible') && !$('#billTableBody tr[data-id]').length) {
            showToastNotification('Please add items to the cart before applying additional charges.', 'error');
            return;
        }
        $section.slideToggle(200, function () {
            var open = $section.is(':visible');
            $('#acToggleChevron')
                .toggleClass('bx-chevron-up', open)
                .toggleClass('bx-chevron-down', !open);
        });
    });

    // ── Add Charge button — opens shared settings form modal ─────────────────
    $('#btnAddCharge').on('click', function () {
        $(document).trigger('acFormOpen', [{
            taxOptions : _transAdditionalTaxOpts || [],
            sortOrder  : (typeof _transAcNextSortOrder !== 'undefined') ? _transAcNextSortOrder : 1,
        }]);
    });

    // ── React to charge saved (fired by js/common/additional_charge_form.js) ─
    $(document).on('acFormSaved', function (_e, resp) {
        if (!resp || resp.Error) { return; }
        // Keep next sort order in sync with server
        if (resp.NextSortOrder != null) {
            _transAcNextSortOrder = resp.NextSortOrder;
        }
        if (resp.AllCharges) {
            _allChargesCache = resp.AllCharges;
        }
        if (resp.NewCharge) {
            _appendChargeRow(resp.NewCharge);
        }
    });

    /**
     * Append a charge row to the table and initialise it.
     * @param {object} charge
     * @param {number} [defaultTaxUID]
     * @returns {void}
     */
    window._appendChargeRow = function (charge, defaultTaxUID) {
        if (chargeIsOnPage(parseInt(charge.ChargeUID, 10))) return;
        var html = buildChargeRow(charge, defaultTaxUID || charge.DefaultTaxUID || 0);
        $('#additionalChargesBody').append(html);
        syncTableVisibility();
        if (!$('#additionalChargesSection').is(':visible')) {
            $('#additionalChargesSection').show();
            $('#acToggleChevron').removeClass('bx-chevron-down').addClass('bx-chevron-up');
        }
        var $newRow = $('#additionalChargesBody .ac-charge-row[data-charge-uid="' + charge.ChargeUID + '"]');
        // Tax options already built in buildChargeRow; trigger recalc to prime BillingManager
        recalcRow($newRow);
    };

    // ── Delete charge row ─────────────────────────────────────────────────────

    $(document).on('click', '.ac-remove-btn', function () {
        var chargeUID = parseInt($(this).data('charge-uid'), 10);
        $(this).closest('.ac-charge-row').remove();
        syncTableVisibility();
        if (billManager) {
            billManager.removeAdditionalChargeDynamic(chargeUID);
        }
        // Refresh View All modal if it's open
        if ($('#viewAllChargesModal').hasClass('show')) {
            _renderViewAllModal();
        }
    });

    // ── Focus / blur behaviour (mirrors pay-amount-inp pattern) ─────────────

    $(document).on('focus', '.ac-pct-input, .ac-wot-input, .ac-wt-input', function () {
        var v = parseFloat($(this).val()) || 0;
        if (v === 0) {
            $(this).val(''); // clear zero so user types fresh
        } else {
            this.select();   // non-zero → select all so user can overtype
        }
    });

    $(document).on('blur', '.ac-pct-input, .ac-wot-input, .ac-wt-input', function () {
        var raw = $.trim($(this).val());
        if (raw === '' || isNaN(parseFloat(raw))) {
            $(this).val('0');
            var source = $(this).hasClass('ac-pct-input') ? 'pct'
                       : $(this).hasClass('ac-wot-input') ? 'wot'
                       : 'wt';
            recalcRow($(this).closest('.ac-charge-row'), source);
        }
    });

    // ── Live recalc on input changes ─────────────────────────────────────────

    $(document).on('change input', '.ac-pct-input, .ac-wot-input, .ac-wt-input', function () {
        var source = $(this).hasClass('ac-pct-input') ? 'pct'
                   : $(this).hasClass('ac-wot-input') ? 'wot'
                   : 'wt';
        var $row = $(this).closest('.ac-charge-row');
        $row.data('driver', source); // remember which field the user last drove manually
        recalcRow($row, source);
    });

    $(document).on('change', '.ac-tax-select', function () {
        recalcRow($(this).closest('.ac-charge-row'), 'tax');
    });

    // ── View All modal ────────────────────────────────────────────────────────

    /**
     * Render rows in the View All modal (uses _transAdditionalCharges global).
     * @returns {void}
     */
    function _renderViewAllModal() {
        var $body   = $('#viewAllChargesBody');
        var charges = _allChargesCache || _transAdditionalCharges || [];
        $body.empty();

        if (!charges.length) {
            $body.html('<tr><td colspan="4" class="text-center py-4 text-muted">No charges defined.</td></tr>');
            return;
        }

        $.each(charges, function (_, c) {
            var uid        = parseInt(c.ChargeUID, 10);
            var onPage     = chargeIsOnPage(uid);
            var isSystem   = parseInt(c.IsSystem, 10) === 1;
            var taxLabel   = c.TaxPercentage ? (parseFloat(c.TaxPercentage) + '%') : '—';
            var defLabel   = parseInt(c.ShowByDefault, 10) === 1
                ? '<span class="badge bg-success">Yes</span>'
                : '<span class="badge bg-label-secondary">No</span>';

            var rowClass   = onPage ? 'table-primary' : '';
            var actionHtml = onPage
                ? '<span class="badge bg-primary">In transaction</span>'
                : '<button type="button" class="btn btn-sm btn-outline-success ac-modal-add-btn"' +
                  ' data-charge-uid="' + uid + '">Add</button>';

            $body.append(
                '<tr class="' + rowClass + '">' +
                '<td>' +
                  '<div class="fw-semibold" style="font-size:.82rem;">' + esc(c.DisplayName || '') + '</div>' +
                  (isSystem ? '<span class="badge bg-label-secondary mt-1" style="font-size:.65rem;">System</span>' : '') +
                '</td>' +
                '<td style="font-size:.8rem;">' + taxLabel + '</td>' +
                '<td class="text-center">' + defLabel + '</td>' +
                '<td class="text-center">' + actionHtml + '</td>' +
              '</tr>'
            );
        });
    }

    $('#btnViewAllCharges').on('click', function () {
        if (_allChargesCache) {
            _renderViewAllModal();
            $('#viewAllChargesModal').modal('show');
            return;
        }

        var upstashUrl   = (typeof _upstashUrl       !== 'undefined') ? _upstashUrl       : '';
        var upstashToken = (typeof _upstashReadToken  !== 'undefined') ? _upstashReadToken  : '';
        var custKey      = (typeof _custCacheKey      !== 'undefined') ? _custCacheKey      : '';
        var chargesKey   = custKey.replace(/:customers$/, ':additional-charges');

        if (upstashUrl && chargesKey) {
            $.ajax({
                url    : upstashUrl + '/get/' + encodeURIComponent(chargesKey),
                method : 'GET',
                headers: { Authorization: 'Bearer ' + upstashToken },
                success: function (resp) {
                    var raw = resp && resp.result ? resp.result : null;
                    if (raw) {
                        _allChargesCache = typeof raw === 'string' ? JSON.parse(raw) : raw;
                        _renderViewAllModal();
                        $('#viewAllChargesModal').modal('show');
                    } else {
                        // Cache miss — fetch from DB via PHP fallback
                        _fetchChargesFromServer();
                    }
                },
                error: function () {
                    _fetchChargesFromServer();
                }
            });
        } else {
            _fetchChargesFromServer();
        }
    });

    /**
     * Fallback: fetch all org charges from server (DB) when Upstash has no data.
     * @returns {void}
     */
    function _fetchChargesFromServer() {
        $.ajax({
            url     : '/settings/getAdditionalChargesCache',
            method  : 'GET',
            success : function (resp) {
                if (resp && !resp.Error && resp.Data) {
                    _allChargesCache = resp.Data;
                }
                _renderViewAllModal();
                $('#viewAllChargesModal').modal('show');
            },
            error: function () {
                _renderViewAllModal();
                $('#viewAllChargesModal').modal('show');
            }
        });
    }

    // Push charge from modal to transaction
    $(document).on('click', '.ac-modal-add-btn', function () {
        var uid    = parseInt($(this).data('charge-uid'), 10);
        var charge = (_allChargesCache || _transAdditionalCharges || []).find(function (c) {
            return parseInt(c.ChargeUID, 10) === uid;
        });
        if (!charge) return;
        _appendChargeRow(charge);
        _renderViewAllModal(); // refresh highlight
    });

    // ── Collect charges for form submit ───────────────────────────────────────

    /**
     * Build the JSON array to be submitted with the form.
     * Called by each transaction form's submit handler.
     * @returns {Array<object>}
     */
    window.collectAdditionalCharges = function () {
        var charges = [];
        $('#additionalChargesBody .ac-charge-row').each(function () {
            var $row      = $(this);
            var chargeUID = parseInt($row.data('charge-uid'), 10);
            if (!chargeUID) return;
            var pctVal  = parseFloat($row.find('.ac-pct-input').val())  || 0;
            var wotVal  = parseFloat($row.find('.ac-wot-input').val())  || 0;
            var taxUID  = parseInt($row.find('.ac-tax-select').val(), 10) || 0;
            var taxPct  = parseFloat($row.find('.ac-tax-select option:selected').data('pct')) || 0;
            var taxAmt  = wotVal * taxPct / 100;
            var wtVal   = parseFloat($row.find('.ac-wt-input').val())   || 0;

            charges.push({
                chargeUID         : chargeUID,
                chargeName        : $row.data('name')         || '',
                chargeDisplayName : $row.data('display-name') || '',
                inPercent         : pctVal,
                withoutTaxAmount  : wotVal,
                taxUID            : taxUID,
                taxPercent        : taxPct,
                taxAmount         : taxAmt,
                withTaxAmount     : wtVal,
                sortOrder         : parseInt($row.data('sort-order'), 10) || 0,
            });
        });
        return charges;
    };

    // ── Init on DOM ready ─────────────────────────────────────────────────────

    initExistingRowTaxDropdowns();
    syncTableVisibility();

    // Pre-populate saved charges for edit mode (_transTransactionCharges injected by view)
    if (typeof _transTransactionCharges !== 'undefined' && _transTransactionCharges.length > 0) {
        $.each(_transTransactionCharges, function (_, saved) {
            var uid = parseInt(saved.ChargeUID, 10);
            if (chargeIsOnPage(uid)) return;
            var chargeDef = {
                ChargeUID   : saved.ChargeUID,
                Name        : saved.ChargeName        || '',
                DisplayName : saved.ChargeDisplayName || '',
                SortOrder   : parseInt(saved.SortOrder, 10) || 0,
            };
            var html = buildChargeRow(chargeDef, parseInt(saved.TaxUID, 10) || 0);
            $('#additionalChargesBody').append(html);
            var $row = $('#additionalChargesBody .ac-charge-row[data-charge-uid="' + uid + '"]');
            if ($row.length) {
                if (parseFloat(saved.InPercent) > 0) {
                    $row.find('.ac-pct-input').val(saved.InPercent);
                } else {
                    $row.find('.ac-wot-input').val(saved.WithoutTaxAmount);
                }
                $row.find('.ac-wt-input').val(saved.WithTaxAmount);
            }
        });
        syncTableVisibility();
        if (!$('#additionalChargesSection').is(':visible')) {
            $('#additionalChargesSection').show();
            $('#acToggleChevron').removeClass('bx-chevron-down').addClass('bx-chevron-up');
        }
    }

    // Recalc all existing rows once subtotal is available, then keep them in
    // sync whenever the cart changes by wrapping billManager.updateSummary.
    // (transactions.js fires 'billManagerReady' after BillingManager init)
    $(document).on('billManagerReady', function () {
        // Initial recalc for edit-mode rows
        $('#additionalChargesBody .ac-charge-row').each(function () {
            recalcRow($(this));
        });

        // Wrap updateSummary so cart changes automatically recalculate IN% rows.
        // _recalcingPct guard prevents the infinite loop:
        //   recalcRow → setAdditionalChargeDynamic → updateSummary → _recalcPctRows → recalcRow …
        if (billManager && typeof billManager.updateSummary === 'function') {
            var _origUpdateSummary = billManager.updateSummary.bind(billManager);
            billManager.updateSummary = function () {
                var result = _origUpdateSummary();
                _recalcPctRows();
                return result;
            };
        }
    });

}(jQuery));
