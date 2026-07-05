/**
 * Additional Charges — Settings page
 * CRUD for Settings.AdditionalChargesTbl
 *
 * Globals injected by view.php:
 *   CsrfName, CsrfToken, acTaxOptions, acChargeLimit, acChargeCount, acNextSortOrder
 *
 * Modal open/save/reset logic lives in js/common/additional_charge_form.js
 * which fires $(document).trigger('acFormSaved', [resp]) on success.
 */
(function ($) {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param {string} s
     * @returns {string}
     */
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Sync Add button & count badge ─────────────────────────────────────────

    /**
     * @param {number} chargeCount
     * @param {number} chargeLimit
     * @param {number} nextSortOrder
     * @returns {void}
     */
    function syncState(chargeCount, chargeLimit, nextSortOrder) {
        acChargeCount   = chargeCount;
        acChargeLimit   = chargeLimit;
        acNextSortOrder = nextSortOrder;
        $('#acCountBadge').text(chargeCount + ' / ' + chargeLimit + ' charges used');
        var $btn = $('#btnAddAdditionalCharge');
        if (chargeCount >= chargeLimit) {
            $btn.prop('disabled', true).attr('title', 'Charge limit reached');
        } else {
            $btn.prop('disabled', false).removeAttr('title');
        }
    }

    // ── Open Add ──────────────────────────────────────────────────────────────

    $('#btnAddAdditionalCharge').on('click', function () {
        if (acChargeCount >= acChargeLimit) {
            showToastNotification('You have reached the maximum of ' + acChargeLimit + ' charges.', 'error');
            return;
        }
        $(document).trigger('acFormOpen', [{ taxOptions: acTaxOptions, sortOrder: acNextSortOrder }]);
    });

    // ── Open Edit ─────────────────────────────────────────────────────────────

    $(document).on('click', '.EditAdditionalCharge', function () {
        var charge = $(this).data('charge');
        if (!charge) return;
        $(document).trigger('acFormOpen', [{ taxOptions: acTaxOptions, chargeData: charge }]);
    });

    // ── React to save ─────────────────────────────────────────────────────────

    $(document).on('acFormSaved', function (_e, resp) {
        if (typeof resp.RecordHtmlData !== 'undefined') {
            $('#AdditionalChargesBody').html(resp.RecordHtmlData);
            syncState(resp.ChargeCount, resp.ChargeLimit, resp.NextSortOrder);
        }
    });

    // ── Delete ────────────────────────────────────────────────────────────────

    $(document).on('click', '.DeleteAdditionalCharge', function () {
        var uid  = $(this).data('uid');
        var name = $(this).data('name');

        Swal.fire({
            title             : 'Delete "' + escHtml(name) + '"?',
            text              : 'This cannot be undone.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonColor: '#d33',
            confirmButtonText : 'Yes, delete it!',
            cancelButtonColor : '#6c757d',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : '/settings/deleteAdditionalCharge',
                method: 'POST',
                data  : { acChargeUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    CsrfToken = resp.NewCsrfToken || CsrfToken;
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                        return;
                    }
                    $('#AdditionalChargesBody').html(resp.RecordHtmlData);
                    syncState(resp.ChargeCount, resp.ChargeLimit, resp.NextSortOrder);
                    showToastNotification(resp.Message, 'success');
                },
                error: function () { showToastNotification('Server error. Please try again.', 'error'); },
            });
        });
    });

}(jQuery));
