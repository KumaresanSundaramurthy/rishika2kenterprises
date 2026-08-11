// ── Auto-Draft: silently saves a draft on every user action once
//    party + date + ≥1 item are all present. No overlay; button spinner only.

(function (window) {
    'use strict';

    var _cfg      = {};
    var _draftUid = 0;
    var _timer    = null;
    var _busy     = false;

    /**
     * @returns {boolean}
     */
    function _meetsThreshold() {
        var partyEl  = document.querySelector(_cfg.partyField);
        var partyUID = parseInt(partyEl ? partyEl.value : 0, 10) || 0;
        if (partyUID <= 0) return false;

        var dateEl = document.querySelector('[name="transDate"]');
        if (!dateEl || !String(dateEl.value || '').trim()) return false;

        if (typeof billManager === 'undefined') return false;
        return billManager.getAllItems().length > 0;
    }

    /**
     * @returns {Object}
     */
    function _buildPayload() {
        var partyEl      = document.querySelector(_cfg.partyField);
        var partyPostKey = (_cfg.partyField || '').replace(/^#/, '');
        var s            = (typeof billManager !== 'undefined' && billManager.summary) ? billManager.summary : {};
        var totals       = s.totals    || {};
        var taxTotals    = s.taxTotals || {};
        var itemSummary  = s.items     || {};
        var extra        = s.extra     || {};
        var addCharges   = s.additionalCharges || {};

        var payload = {};
        if (typeof CsrfName !== 'undefined') payload[CsrfName] = CsrfToken;

        payload[partyPostKey]         = parseInt(partyEl ? partyEl.value : 0, 10);
        payload.transDate             = String($('[name="transDate"]').val()   || '').trim();
        payload.transPrefixSelect     = parseInt($('#transPrefixSelect').val(), 10) || 0;
        payload.transNumber           = String($('#transNumber').val()         || '').trim();
        payload.dueDate               = String($('[name="dueDate"]').val()     || '').trim();
        payload.transNotes            = String($('#transNotes').val()          || '').trim();
        payload.transTermsCond        = String($('#transTermsCond').val()      || '').trim();
        payload.referenceDetails      = String($('#referenceDetails').val()    || '').trim();
        payload.GlobalDiscPercent     = parseFloat($('#globalDiscPercent').val()          || 0);
        payload.extraDiscount         = parseFloat($('[name="extraDiscount"]').val()      || 0);
        payload.extDiscountType       = String($('[name="extDiscountType"]').val()        || '').trim();
        payload.SubTotal              = totals.subtotal         || 0;
        payload.DiscountAmount        = itemSummary.discountTotal || 0;
        payload.TaxAmount             = taxTotals.totalTax      || 0;
        payload.CgstAmount            = taxTotals.cgstTotal     || 0;
        payload.SgstAmount            = taxTotals.sgstTotal     || 0;
        payload.IgstAmount            = taxTotals.igstTotal     || 0;
        payload.AdditionalChargesTotal = addCharges.total       || 0;
        payload.AdditionalCharges     = JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []);
        payload.RoundOff              = extra.roundOff          || 0;
        payload.NetAmount             = totals.grandTotal       || 0;
        payload.Items                 = JSON.stringify(typeof billManager !== 'undefined' ? billManager.getAllItems() : []);
        payload.action                = 'draft';

        if (_draftUid > 0) payload.TransUID = _draftUid;

        return payload;
    }

    /**
     * @returns {void}
     */
    function _doSave() {
        if (_busy) return;
        if (!_meetsThreshold()) return;
        _busy = true;

        var formSel = '#' + _cfg.formId;
        var url     = _draftUid > 0
            ? '/' + (_cfg.updateUrl || '')
            : '/' + (_cfg.addUrl    || '');

        ajaxLoading(0);
        setFormLoading(formSel, true, 'draft');

        $.ajax({
            url      : url,
            method   : 'POST',
            data     : _buildPayload(),
            cache    : false,
            success  : function (res) {
                if (res && !res.Error && res.TransUID) {
                    _draftUid = parseInt(res.TransUID, 10) || _draftUid;
                }
            },
            complete : function () {
                _busy = false;
                setFormLoading(formSel, false, 'draft');
            }
        });
    }

    /**
     * @returns {void}
     */
    function _onChanged() {
        if (!_meetsThreshold()) return;
        clearTimeout(_timer);
        _timer = setTimeout(_doSave, _cfg.debounceMs || 3000);
    }

    /**
     * @returns {void}
     */
    function _bindListeners() {
        var $form = $('#' + _cfg.formId);
        if (!$form.length) return;

        $form.on('input change', 'input:not([type="hidden"]), textarea, select', _onChanged);
        $(_cfg.partyField).on('change', _onChanged);

        var bodyEl = document.getElementById('billTableBody');
        if (bodyEl) {
            new MutationObserver(_onChanged).observe(bodyEl, { childList: true });
        }
    }

    /**
     * Initialise autoDraft for a transaction form.
     * @param {Object} cfg
     * @param {string} cfg.formId       — form element ID without #
     * @param {string} cfg.addUrl       — POST URL to create first draft (no leading /)
     * @param {string} cfg.updateUrl    — POST URL to update existing draft (no leading /)
     * @param {string} cfg.partyField   — CSS selector for party Select2 field
     * @param {number} [cfg.debounceMs] — debounce delay ms (default 3000)
     * @returns {{ getDraftUid:function():number, trigger:function():void, reset:function():void }}
     */
    function init(cfg) {
        _cfg            = cfg || {};
        _cfg.debounceMs = _cfg.debounceMs || 3000;

        $(function () { _bindListeners(); });

        return {
            /** @returns {number} */
            getDraftUid : function () { return _draftUid; },
            /** @returns {void} */
            trigger     : function () { _doSave(); },
            /** @returns {void} */
            reset       : function () { _draftUid = 0; }
        };
    }

    /**
     * Shorthand: read URLs + formId from a _transFormData object.
     * @param {Object} transFormData   — window._transFormData (or _cfg)
     * @param {string} partyField      — CSS selector for party Select2 field
     * @param {string} [fallbackId]    — fallback formId if transFormData.formId is missing
     * @param {number} [debounceMs]
     * @returns {{ getDraftUid:function():number, trigger:function():void, reset:function():void }}
     */
    function initFromCfg(transFormData, partyField, fallbackId, debounceMs) {
        var d = transFormData || {};
        return init({
            formId     : d.formId       || fallbackId || '',
            addUrl     : d.formAction   || '',
            updateUrl  : d.updateAction || '',
            partyField : partyField,
            debounceMs : debounceMs
        });
    }

    window.AutoDraft = { init: init, initFromCfg: initFromCfg, getDraftUid: function () { return _draftUid; } };

}(window));
