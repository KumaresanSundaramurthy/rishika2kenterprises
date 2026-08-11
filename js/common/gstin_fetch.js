// ── GSTIN Fetch — shared across Customer, Vendor, and Transaction forms ──────
//
// Pages that want confirmation-overlay + validated-text behaviour register:
//   window.gstinFetchConfig = {
//     skipAddress  : true,                          // skip openAddressModal auto-fill
//     confirmRows  : function(resp) { return html; },
//     onConfirm    : function(resp) { ... },        // fills page-specific fields; _doAutoFill NOT called
//     onValidated  : function()     { ... },
//   };
//
// Without gstinFetchConfig, _doAutoFill runs immediately (legacy behaviour for
// customer / vendor / transaction forms).

var _gstinPendingResp = null;
var _gstinPendingForm = null;

// ── Uppercase GSTIN & PAN inputs as user types ───────────────────────────────
$(document).on('input', '[name="GSTIN"], [name="PANNumber"]', function () {
    var pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});

// ── Fetch button ─────────────────────────────────────────────────────────────
$(document).on('click', '#GSTIN_Fetch', function () {

    var $btn        = $(this);
    var $form       = $btn.closest('form');
    var $gstinInput = $form.find('[name="GSTIN"]');
    var gstin       = $.trim($gstinInput.val()).toUpperCase();

    if (!gstin) {
        showToastNotification(t('toast_gstin_enter', 'Please enter a GSTIN number first.'), 'error');
        return;
    }
    var _gstinCheck = validateGSTIN(gstin);
    if (!_gstinCheck.isValid) {
        showToastNotification(_gstinCheck.message, 'error');
        return;
    }

    /**
     * @returns {void}
     */
    function _doFetch() {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

        $.ajax({
            url   : '/globally/fetchGstinDetails',
            method: 'GET',
            data  : { gstin: gstin },
            success: function (resp) {

                $btn.prop('disabled', false).html('Fetch');

                if (resp.Error) {
                    if (resp.NoCreditPoints) {
                        showPersistentToast(resp.Message, 'error');
                    } else {
                        showToastNotification(resp.Message || t('toast_gstin_failed', 'GSTIN lookup failed. Please try again.'), 'error');
                    }
                    return;
                }

            var successMsg = t('toast_gstin_fetched', 'GSTIN details fetched successfully');
            if (resp.LegalName) successMsg += ' — ' + resp.LegalName;
            if (resp.Status)    successMsg += ' (' + resp.Status + ')';
            showToastNotification(successMsg, 'success');

            var cfg = (typeof window.gstinFetchConfig === 'object') ? window.gstinFetchConfig : null;

            // Notify page that GSTIN was validated (always on success)
            if (cfg && typeof cfg.onValidated === 'function') {
                cfg.onValidated();
            }

            // Show confirmation overlay if configured; otherwise auto-fill immediately
            if (cfg && typeof cfg.confirmRows === 'function') {
                _gstinPendingResp = resp;
                _gstinPendingForm = $form;
                $('#gstin-confirm-rows').html(cfg.confirmRows(resp));
                // Move to <body> to escape any parent stacking context, then show
                $('#gstinConfirmOverlay').appendTo('body').addClass('active');
                return;
            }

            // No config — auto-fill directly (customer / vendor / transaction forms)
            _doAutoFill($form, resp, false);
        },
        error: function () {
            $btn.prop('disabled', false).html('Fetch');
            showToastNotification(t('swal_network_error', 'Network error. Please try again.'), 'error');
        }
        });
    }

    // Check gstin_points from cache before making the API call.
    // If points are 0, show a persistent toast and abort — no AJAX call needed.
    if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
        UpstashService.get(UpstashService.orgKey('credit-settings')).then(function (data) {
            if (data && typeof data.gstin_points !== 'undefined' && parseInt(data.gstin_points, 10) <= 0) {
                showPersistentToast(
                    t('toast_no_gstin_credits', 'You don\'t have credit points to fetch GSTIN details. Please purchase more credits to get the information.'),
                    'error'
                );
                return;
            }
            _doFetch();
        }).catch(function () {
            _doFetch();
        });
    } else {
        _doFetch();
    }

});

// ── Confirm overlay — Accept ──────────────────────────────────────────────────
$(document).on('click', '#gstin-confirm-accept', function () {
    var resp  = _gstinPendingResp;
    _hideGstinConfirm();

    var cfg = (typeof window.gstinFetchConfig === 'object') ? window.gstinFetchConfig : null;
    if (cfg) {
        // Config path: only call page-specific handler — _doAutoFill is NOT called
        if (typeof cfg.onConfirm === 'function') cfg.onConfirm(resp || {});
    } else {
        // Legacy path: auto-fill shared fields (customer / vendor / transaction forms)
        if (resp && _gstinPendingForm) _doAutoFill(_gstinPendingForm, resp, false);
    }
});

// ── Confirm overlay — Cancel ──────────────────────────────────────────────────
$(document).on('click', '#gstin-confirm-cancel', function () {
    _hideGstinConfirm();
});

/**
 * @returns {void}
 */
function _hideGstinConfirm() {
    $('[id="gstinConfirmOverlay"]').removeClass('active');
    _gstinPendingResp = null;
    _gstinPendingForm = null;
}

// ── Auto-fill shared fields (Name, CompanyName, PAN, Address) ────────────────
/**
 * @param {jQuery} $form
 * @param {Object} resp
 * @param {boolean} skipAddress
 * @returns {void}
 */
function _doAutoFill($form, resp, skipAddress) {
    var $nameField    = $form.find('[name="Name"]');
    var $companyField = $form.find('[name="CompanyName"]');
    var $panField     = $form.find('[name="PANNumber"]');

    if (resp.TradeName && $companyField.length) $companyField.val(resp.TradeName);
    if (resp.LegalName && $nameField.length && !$.trim($nameField.val())) {
        $nameField.val(resp.LegalName);
    }
    if (resp.PAN && $panField.length && !$.trim($panField.val())) {
        $panField.val(resp.PAN);
    }

    if (skipAddress) return;

    // Billing address — open address modal and pre-fill from GSTIN data
    if ((resp.AddressLine1 || resp.City || resp.Pincode) && typeof openAddressModal === 'function') {
        openAddressModal(1);
        setTimeout(function () {
            if (resp.AddressLine1) $('#ModalAddrLine1').val(resp.AddressLine1);
            if (resp.AddressLine2) $('#ModalAddrLine2').val(resp.AddressLine2);
            if (resp.Pincode)      $('#ModalAddrPincode').val(resp.Pincode);
            if (resp.StateName) {
                var $stateOpt = $('#ModalAddrState option').filter(function () {
                    return $(this).text().trim().toLowerCase() === resp.StateName.trim().toLowerCase();
                });
                if ($stateOpt.length) $('#ModalAddrState').val($stateOpt.val()).trigger('change');
            }
            if (resp.City) {
                setTimeout(function () {
                    var cityLower = resp.City.trim().toLowerCase();
                    var $cityOpt  = $('#ModalAddrCity option').filter(function () {
                        return $(this).text().trim().toLowerCase() === cityLower
                            || $(this).text().trim().toLowerCase().indexOf(cityLower) === 0;
                    });
                    if ($cityOpt.length) {
                        $('#ModalAddrCity').val($cityOpt.first().val());
                        if ($('#ModalAddrCity').hasClass('select2')) $('#ModalAddrCity').trigger('change');
                    }
                }, 600);
            }
        }, 400);
    }
}

// ── GSTIN Prefill hint — focus GSTIN field when "Enter GSTIN" is clicked ─────
/**
 * @returns {void}
 */
$(document).on('click', '#gstinPrefillBtn', function () {
    var $gstin = $('[name="GSTIN"]:visible').first();
    if (!$gstin.length) return;
    $gstin[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(function () { $gstin.trigger('focus').trigger('select'); }, 150);
});
