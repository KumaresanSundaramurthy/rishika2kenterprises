$(document).ready(function () {

    // ── Add Customer — now handled by CustomerForm.open() in customer_form.js ─
    // #addTransCustomer button is kept in HTML for layout; click opens common modal
    $(document).on('click', '#addTransCustomer', function (e) {
        e.preventDefault();
        CustomerForm.open('add', null, {
            onSaveSuccess: function (response) {
                var c = response.Customer;
                if (!c || !c.id) return;
                var $select = $('#customerSearch');
                if ($select.find('option[value="' + c.id + '"]').length === 0) {
                    $select.append(new Option(c.text, c.id, true, true));
                } else {
                    $select.val(c.id);
                }
                $select.trigger('change');
                $select.trigger({ type: 'select2:select', params: { data: { id: c.id, text: c.text } } });
                if (c.address) {
                    var a = c.address;
                    var addrHtml = '<div><strong>Shipping Address:</strong></div>'
                        + '<div>' + (a.Line1 || '') + '</div>'
                        + '<div>' + (a.Line2 || '') + '</div>'
                        + '<div>' + [a.City, a.State].filter(Boolean).join(', ') + (a.Pincode ? ' - ' + a.Pincode : '') + '</div>';
                    $('#customerAddressBox').html(addrHtml).removeClass('d-none');
                } else {
                    $('#customerAddressBox').addClass('d-none').empty();
                }
            }
        });
    });

    // ── Add Vendor (Purchase forms) ──────────────────────────────────────────
    $(document).on('click', '#addTransVendor', function(e) {
        e.preventDefault();
        $('#transVendorModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5"><span class="spinner-border text-primary"></span></div>'
        );
        $('#transVendorModal').modal('show');
        $.ajax({
            url     : '/vendors/modal/add',
            method  : 'GET',
            success : function(resp) {
                if (resp.Error) {
                    $('#transVendorModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                    return;
                }
                $('#transVendorModalBody').html(resp.Html);
                // Initialise flatpickr for DOB field inside modal
                if (typeof flatpickr !== 'undefined' && document.querySelector('#VM_CPDateOfBirth')) {
                    flatpickr('#VM_CPDateOfBirth', { dateFormat: 'Y-m-d', altInput: true, altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y' });
                }
                // Initialise Select2 fields inside modal
                $('#transVendorModalBody .select2').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ dropdownParent: $('#transVendorModal'), width: '100%' });
                    }
                });
            },
            error: function() {
                $('#transVendorModalBody').html('<div class="alert alert-danger m-3">Failed to load vendor form.</div>');
            }
        });
    });

    // ── Save Vendor button inside modal ──────────────────────────────────────
    $(document).on('click', '#saveTransVendorBtn', function() {
        var $form = $('#VendorModalForm');
        if (!$form.length) return;

        // Basic HTML5 validation
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        var formData = new FormData($form[0]);
        formData.append('BankDetailsJSON', JSON.stringify([]));
        formData.append('BankDetailsCount', 0);

        // Address city/state text
        var billLine1 = $form.find('#BillAddrLine1').val();
        if (hasValue(billLine1)) {
            var bCity  = $form.find('#BillAddrCity option:selected');
            var bState = $form.find('#BillAddrState option:selected');
            if (bCity.val()  && $.isNumeric(bCity.val()))  formData.append('BillAddrCityText',  bCity.text());
            if (bState.val() && $.isNumeric(bState.val())) formData.append('BillAddrStateText', bState.text());
        }
        var shipLine1 = $form.find('#ShipAddrLine1').val();
        if (hasValue(shipLine1)) {
            var sCity  = $form.find('#ShipAddrCity option:selected');
            var sState = $form.find('#ShipAddrState option:selected');
            if (sCity.val()  && $.isNumeric(sCity.val()))  formData.append('ShipAddrCityText',  sCity.text());
            if (sState.val() && $.isNumeric(sState.val())) formData.append('ShipAddrStateText', sState.text());
        }

        var $btn = $('#saveTransVendorBtn').prop('disabled', true).text('Saving...');

        $.ajax({
            url         : '/vendors/addVendorData',
            method      : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            success: function(resp) {
                $btn.prop('disabled', false).text('Save');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                    return;
                }
                $('#transVendorModal').modal('hide');
                // Build display text and inject directly into vendorSearch Select2
                var vendorArea  = resp.VendorArea || '';
                var displayText = vendorArea
                    ? resp.VendorName + ' (' + vendorArea + ')'
                    : resp.VendorName;
                var newOpt = new Option(displayText, resp.VendorUID, true, true);
                $('#vendorSearch').append(newOpt).trigger('change');
                showToastNotification('Vendor created and selected.', 'success');
            },
            error: function() {
                $btn.prop('disabled', false).text('Save');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Server error. Please try again.' });
            }
        });
    });

    // Reset vendor modal body when closed
    $('#transVendorModal').on('hidden.bs.modal', function() {
        $('#transVendorModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5"><span class="spinner-border text-primary"></span></div>'
        );
    });


});

