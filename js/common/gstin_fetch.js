// ── GSTIN Fetch — shared across Customer & Vendor add/edit forms ──────────

$(document).on('click', '#GSTIN_Fetch', function () {

    var gstin = $.trim($('#GSTIN').val()).toUpperCase();

    if (!gstin) {
        Swal.fire({ icon: 'warning', text: 'Please enter a GSTIN number first.' });
        return;
    }
    if (gstin.length !== 15) {
        Swal.fire({ icon: 'warning', text: 'GSTIN must be exactly 15 characters.' });
        return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

    $.ajax({
        url   : '/globally/fetchGstinDetails',
        method: 'GET',
        data  : { gstin: gstin },
        success: function (resp) {
            $btn.prop('disabled', false).html('Fetch');

            if (resp.Error) {
                Swal.fire({ icon: 'error', title: 'GSTIN Lookup Failed', text: resp.Message });
                return;
            }

            // ── Auto-fill fields ──────────────────────────────────────────

            // Company Name ← Trade Name
            if (resp.TradeName) $('#CompanyName').val(resp.TradeName);

            // Name ← Legal Name (fill only if empty)
            if (resp.LegalName && !$.trim($('#Name').val())) {
                $('#Name').val(resp.LegalName);
            }

            // GSTIN status badge
            var statusText = resp.Status ? ' (' + resp.Status + ')' : '';

            // Billing address — trigger billing address section if not open
            if (resp.AddressLine1 || resp.City || resp.Pincode) {

                // Open billing address section if it exists and is hidden
                if ($('#appendBillingAddress').hasClass('d-none')) {
                    $('#addBillingAddress').trigger('click');
                }

                // Wait a tick for the address form to render
                setTimeout(function () {
                    if (resp.AddressLine1) $('#BillAddrLine1').val(resp.AddressLine1);
                    if (resp.AddressLine2) $('#BillAddrLine2').val(resp.AddressLine2);
                    if (resp.Pincode)      $('#BillAddrPincode').val(resp.Pincode);

                    // State — match by name
                    if (resp.StateName) {
                        var $stateOpt = $('#BillAddrState option').filter(function () {
                            return $(this).text().trim().toLowerCase() === resp.StateName.trim().toLowerCase();
                        });
                        if ($stateOpt.length) {
                            $('#BillAddrState').val($stateOpt.val()).trigger('change');
                        }
                    }

                    // City — match by name after state loads
                    if (resp.City) {
                        setTimeout(function () {
                            var cityLower = resp.City.trim().toLowerCase();
                            var $cityOpt = $('#BillAddrCity option').filter(function () {
                                return $(this).text().trim().toLowerCase() === cityLower
                                    || $(this).text().trim().toLowerCase().indexOf(cityLower) === 0;
                            });
                            if ($cityOpt.length) {
                                $('#BillAddrCity').val($cityOpt.first().val()).trigger('change');
                            }
                        }, 600);
                    }
                }, 300);
            }

            // Success toast
            Swal.fire({
                icon : 'success',
                title: 'GSTIN Details Fetched',
                html : '<b>' + (resp.LegalName || '') + '</b>' +
                       (resp.TradeName ? '<br><small class="text-muted">Trade Name: ' + resp.TradeName + '</small>' : '') +
                       (resp.Status ? '<br><small class="text-muted">Status: ' + resp.Status + '</small>' : ''),
                timer: 2500,
                showConfirmButton: false,
            });
        },
        error: function () {
            $btn.prop('disabled', false).html('Fetch');
            Swal.fire({ icon: 'error', text: 'Network error. Please try again.' });
        }
    });

});
