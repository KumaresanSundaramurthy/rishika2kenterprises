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
                    var _lines = [a.Line1, a.Line2].filter(Boolean).join(', ');
                    var _loc   = [a.City, a.State].filter(Boolean).join(', ');
                    if (a.Pincode) _loc += ' – ' + a.Pincode;
                    var _addrText = [_lines, _loc].filter(Boolean).join(' · ');
                    $('#customerAddressBox').find('span').text(_addrText);
                    $('#customerAddressBox').removeClass('d-none');
                } else {
                    $('#customerAddressBox').addClass('d-none').find('span').text('');
                }
            }
        });
    });

    // ── Add Vendor (Purchase forms) — opens shared VendorForm modal ─────────
    $(document).on('click', '#addTransVendor', function(e) {
        e.preventDefault();
        if (typeof VendorForm === 'undefined') return;
        VendorForm.open('add', null, {
            onSaveSuccess: _selectNewVendorInForm
        });
    });

    /**
     * @param {Object} response
     * @returns {void}
     */
    function _selectNewVendorInForm(response) {
        var v    = response.Vendor || {};
        var uid  = parseInt(v.VendorUID || response.VendorUID || 0, 10);
        var name = v.Name || response.VendorName || '';
        var area = v.Area || response.VendorArea || '';
        if (!uid) return;

        var displayText = area ? name + ' (' + area + ')' : name;
        var $select = $('#vendorSearch');
        if ($select.find('option[value="' + uid + '"]').length === 0) {
            $select.append(new Option(displayText, uid, true, true));
        } else {
            $select.val(uid);
        }
        $select.trigger('change');
        $select.closest('.vendor-search-group').addClass('party-has-selection');

        // Address strip
        var addrArr = v.Address || [];
        var billing = null;
        addrArr.forEach(function (a) { if (a.AddressType === 'Billing') billing = a; });
        if (billing && billing.Line1) {
            if (typeof _buildVendAddrLine === 'function') {
                $('#vendorAddressBox').find('span').text(_buildVendAddrLine({
                    Line1:   billing.Line1     || '',
                    Line2:   billing.Line2     || '',
                    City:    billing.CityText  || '',
                    State:   billing.StateText || '',
                    Pincode: billing.Pincode   || '',
                }));
            }
            $('#vendorAddressBox').removeClass('d-none');
            $('#vendorNoAddressBox').addClass('d-none');
        } else {
            $('#vendorAddressBox').addClass('d-none').find('span').text('');
            $('#vendorNoAddressBox').removeClass('d-none');
        }
    }

});

