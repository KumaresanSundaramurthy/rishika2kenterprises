/**
 * Additional Charge Form — shared modal logic.
 *
 * Open the modal by triggering:
 *   $(document).trigger('acFormOpen', [opts])
 *
 * opts = {
 *   taxOptions : Array   Tax options [{TaxDetailsUID, Percentage}] to populate the select
 *   sortOrder  : number  Default sort order for add mode
 *   chargeData : object  If present, enters edit mode (populates all fields)
 * }
 *
 * On successful save the event below fires with the full server response:
 *   $(document).trigger('acFormSaved', [resp])
 *
 * Globals required at click time (always available via trans_footer_script / settings view):
 *   CsrfName, CsrfToken
 */
(function ($) {
    'use strict';

    var _nameManuallyEdited = false;
    var _acIsDirty          = false;
    var _acIsCreateMode     = false;

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param {string} displayName
     * @returns {string}
     */
    function toSlug(displayName) {
        return displayName.trim()
            .replace(/[^A-Za-z0-9]+/g, '_')
            .replace(/^_+|_+$/, '');
    }

    /**
     * Populate #acDefaultTaxUID from a tax-options array.
     * @param {Array<object>} taxOptions
     * @returns {void}
     */
    function populateTaxSelect(taxOptions) {
        var html = '<option value="">— No default tax —</option>';
        $.each(taxOptions || [], function (_, t) {
            var pct = parseFloat(t.Percentage) || 0;
            html += '<option value="' + t.TaxDetailsUID + '">' + pct + '%</option>';
        });
        $('#acDefaultTaxUID').html(html);
    }

    /**
     * Reset #additionalChargeModal fields to blank / add state.
     * @returns {void}
     */
    function resetModal() {
        _acIsCreateMode = false;
        _acIsDirty      = false;
        $('#acChargeUID').val('0');
        $('#acIsSystem').val('0');
        $('#acName').val('').prop('disabled', false);
        $('#acDisplayName').val('').prop('disabled', false);
        $('#acDefaultTaxUID').val('').prop('disabled', false);
        $('#acDescription').val('').prop('disabled', false);
        $('#acShowByDefault').prop('checked', false);
        $('#acSortOrder').val('0');
        $('#acUserFieldsRow').removeClass('d-none');
        $('#acSystemNotice').addClass('d-none');
        $('#acModalTitle').text('Add Additional Charge');
        $('#acModalSubtitle').text('Define a charge type for use in transactions');
        $('#saveAdditionalChargeBtn')
            .prop('disabled', false)
            .html('<i class="bx bx-save me-1"></i>Save Charge');
    }

    // ── Auto-fill Name from DisplayName ──────────────────────────────────────

    $(document).on('input', '#acName', function () {
        _nameManuallyEdited = true;
    });

    $(document).on('input', '#acDisplayName', function () {
        if (!_nameManuallyEdited) {
            $('#acName').val(toSlug($(this).val()));
        }
    });

    // ── Open modal via event ─────────────────────────────────────────────────

    /**
     * @param {jQuery.Event} _e
     * @param {object} opts
     * @returns {void}
     */
    $(document).on('acFormOpen', function (_e, opts) {
        opts = opts || {};
        resetModal();
        populateTaxSelect(opts.taxOptions || []);
        _nameManuallyEdited = false;

        var charge = opts.chargeData || null;
        if (charge) {
            // Edit mode — fill all fields
            _nameManuallyEdited = true;
            $('#acChargeUID').val(charge.ChargeUID  || 0);
            $('#acIsSystem').val(charge.IsSystem    || 0);
            $('#acName').val(charge.Name            || '');
            $('#acDisplayName').val(charge.DisplayName || '');
            $('#acDefaultTaxUID').val(charge.DefaultTaxUID || '');
            $('#acDescription').val(charge.Description || '');
            $('#acShowByDefault').prop('checked', parseInt(charge.ShowByDefault, 10) === 1);
            $('#acSortOrder').val(charge.SortOrder  || 0);

            if (parseInt(charge.IsSystem, 10) === 1) {
                $('#acName').prop('disabled', true);
                $('#acDisplayName').prop('disabled', true);
                $('#acUserFieldsRow').addClass('d-none');
                $('#acSystemNotice').removeClass('d-none');
                $('#acModalTitle').text('Edit System Charge');
            } else {
                $('#acModalTitle').text('Edit Charge');
            }
            $('#acModalSubtitle').text(
                String(charge.DisplayName || '')
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            );
        } else {
            // Add mode — apply provided sort order
            $('#acSortOrder').val(opts.sortOrder != null ? opts.sortOrder : 0);
        }

        $('#additionalChargeModal').modal('show');
        if (!charge) {
            _acIsCreateMode = true;
            _acIsDirty      = false;
        }
    });

    // ── Save ─────────────────────────────────────────────────────────────────

    $(document).on('click', '#saveAdditionalChargeBtn', function () {
        var $btn      = $(this);
        var chargeUID = parseInt($('#acChargeUID').val(), 10) || 0;
        var isSystem  = parseInt($('#acIsSystem').val(), 10) === 1;

        var name        = $.trim($('#acName').val());
        var displayName = $.trim($('#acDisplayName').val());
        var taxUID      = $('#acDefaultTaxUID').val() || '';
        var description = $.trim($('#acDescription').val());
        var showDefault = $('#acShowByDefault').is(':checked') ? '1' : '';
        var sortOrder   = parseInt($('#acSortOrder').val(), 10) || 0;

        if (!isSystem) {
            if (!displayName) {
                showToastNotification(t('toast_display_name_req', 'Display name is required.'), 'error');
                $('#acDisplayName').focus();
                return;
            }
            if (!name) {
                name = toSlug(displayName);
            }
            if (!name) {
                showToastNotification(t('toast_name_required', 'Name is required.'), 'error');
                $('#acName').focus();
                return;
            }
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url   : '/settings/saveAdditionalCharge',
            method: 'POST',
            data  : {
                acChargeUID     : chargeUID,
                acName          : name,
                acDisplayName   : displayName,
                acDefaultTaxUID : taxUID,
                acDescription   : description,
                acShowByDefault : showDefault,
                acSortOrder     : sortOrder,
                [CsrfName]      : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Charge');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                _acIsDirty      = false;
                _acIsCreateMode = false;
                $('#additionalChargeModal').modal('hide');
                showToastNotification(resp.Message, 'success');
                $(document).trigger('acFormSaved', [resp]);
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Charge');
                showToastNotification(t('swal_server_error', 'Server error. Please try again.'), 'error');
            },
        });
    });

    // ── Reset on modal close ─────────────────────────────────────────────────

    $(document).on('hidden.bs.modal', '#additionalChargeModal', function () {
        resetModal();
        _nameManuallyEdited = false;
    });

    // ── Dirty-tracking listener ───────────────────────────────────────────────
    $(document).on('input change', '#acName, #acDisplayName, #acDefaultTaxUID, #acDescription, #acShowByDefault, #acSortOrder', function () {
        if (_acIsCreateMode) _acIsDirty = true;
    });

    // ── Unsaved-changes guard ─────────────────────────────────────────────────
    $(document).on('hide.bs.modal', '#additionalChargeModal', function (e) {
        if (!_acIsDirty || !_acIsCreateMode) return;
        e.preventDefault();
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _acIsDirty      = false;
                _acIsCreateMode = false;
                $('#additionalChargeModal').modal('hide');
            }
        });
    });

}(jQuery));
