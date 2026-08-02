// ── GSTIN Fetch — shared across Customer, Vendor, and Transaction forms ──────

// Uppercase the actual input value as the user types, preserving cursor position
$(document).on('input', '[name="GSTIN"]', function () {
    var pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});

$(document).on('click', '#GSTIN_Fetch', function () {

    var $btn        = $(this);
    var $form       = $btn.closest('form');
    var $gstinInput = $form.find('[name="GSTIN"]');
    var gstin       = $.trim($gstinInput.val()).toUpperCase();

    if (!gstin) {
        showToastNotification(t('toast_gstin_enter', 'Please enter a GSTIN number first.'), 'error');
        return;
    }
    if (gstin.length !== 15) {
        showToastNotification(t('toast_gstin_15chars', 'GSTIN must be exactly 15 characters.'), 'error');
        return;
    }

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

    $.ajax({
        url   : '/globally/fetchGstinDetails',
        method: 'GET',
        data  : { gstin: gstin },
        success: function (resp) {

            $btn.prop('disabled', false).html('Fetch');

            if (resp.Error) {
                showToastNotification(resp.Message || t('toast_gstin_failed', 'GSTIN lookup failed. Please try again.'), 'error');
                return;
            }

            // ── Auto-fill fields ──────────────────────────────────────────────
            var $nameField    = $form.find('[name="Name"]');
            var $companyField = $form.find('[name="CompanyName"]');

            if (resp.TradeName && $companyField.length) $companyField.val(resp.TradeName);
            if (resp.LegalName && $nameField.length && !$.trim($nameField.val())) {
                $nameField.val(resp.LegalName);
            }

            // Billing address — open address modal and pre-fill from GSTIN data
            if (resp.AddressLine1 || resp.City || resp.Pincode) {
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

            var successMsg = t('toast_gstin_fetched', 'GSTIN details fetched successfully');
            if (resp.LegalName) successMsg += ' — ' + resp.LegalName;
            if (resp.Status)    successMsg += ' (' + resp.Status + ')';
            showToastNotification(successMsg, 'success');
        },
        error: function () {
            $btn.prop('disabled', false).html('Fetch');
            showToastNotification(t('swal_network_error', 'Network error. Please try again.'), 'error');
        }
    });

});
