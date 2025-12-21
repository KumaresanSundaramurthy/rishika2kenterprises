$(document).ready(function () {
    
    $('#addTransCustomer').click(function(e) {
        e.preventDefault();
        setTransAddrDefaultActions();
        $('#transCustomerModal').modal('show');
    });

    $('#addBillingAddress').click(function(e) {
        e.preventDefault();
        creationBilngAddrActions();
    });

    $('#addShippingAddress').click(function(e) {
        e.preventDefault();
        creationShipAddrActions();
    });

    $('#addrCopyToShipping').click(function(e) {
        e.preventDefault();
        $('#ShipAddrLine1').val($('#BillAddrLine1').val());
        $('#ShipAddrLine2').val($('#BillAddrLine2').val());
        $('#ShipAddrPincode').val($('#BillAddrPincode').val());
        $('#ShipAddrState').val($('#BillAddrState').find('option:selected').val()).trigger('change');
        $('#ShipAddrCity').val($('#BillAddrCity').find('option:selected').val()).trigger('change');
    });

    $('#deleteBillingAddress').click(function(e) {
        e.preventDefault();
        $(this).addClass('d-none');
        var addrUID = $('#BillAddressUID').val();
        if(addrUID > 0) {
            delAddrDetailFlag = 1;
            delAddrData.push(addrUID);
        }
        $('#addBillingAddress').removeClass('d-none');
        $('#appendBillingAddress').html('').addClass('d-none');
        $('#addrCopyToShipping').addClass('d-none');
    });

    $('#deleteShippingAddress').click(function(e) {
        e.preventDefault();
        $(this).addClass('d-none');
        var addrUID = $('#ShipAddressUID').val();
        if(addrUID > 0) {
            delAddrDetailFlag = 1;
            delAddrData.push(addrUID);
        }
        $('#addShippingAddress').removeClass('d-none');
        $('#appendShippingAddress').html('').addClass('d-none');
        $('#addrCopyToShipping').addClass('d-none');
    });

    $('#addEditCustomerForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#addEditCustomerForm')[0]);
        formData.append('CountryCode', defaultCCode);
        formData.append('CountryISO2', defaultIso2);

        formData.append('BankDetailsJSON', JSON.stringify([]));
        formData.append('BankDetailsCount', 0);

        var custUID = $('#addEditCustomerForm').find('#CustomerUID').val();
        
        var BillAddrLine1 = $('#addEditCustomerForm #BillAddrLine1').val();
        if (hasValue(BillAddrLine1)) {
            var city = $('#addEditCustomerForm #BillAddrCity').find('option:selected').val();
            if (hasValue(city) && $.isNumeric(city)) formData.append('BillAddrCityText', $('#addEditCustomerForm #BillAddrCity').find('option:selected').text());
            var state = $('#addEditCustomerForm #BillAddrState').find('option:selected').val();
            if (hasValue(state) && $.isNumeric(state)) formData.append('BillAddrStateText', $('#addEditCustomerForm #BillAddrState').find('option:selected').text());
        }

        var ShipAddrLine1 = $('#addEditCustomerForm #ShipAddrLine1').val();
        if (hasValue(ShipAddrLine1)) {
            var city = $('#addEditCustomerForm #ShipAddrCity').find('option:selected').val();
            if (hasValue(city) && $.isNumeric(city)) formData.append('ShipAddrCityText', $('#addEditCustomerForm #ShipAddrCity').find('option:selected').text());
            var state = $('#addEditCustomerForm #ShipAddrState').find('option:selected').val();
            if (hasValue(state) && $.isNumeric(state)) formData.append('ShipAddrStateText', $('#addEditCustomerForm #ShipAddrState').find('option:selected').text());
        }

        if(custUID == 0) {
            addCustomerData(formData);
        } else if(custUID > 0) {
            editCustomerData(formData);
        }

    });

});

function setTransAddrDefaultActions() {
    $('#addEditCustomerForm').trigger('reset');
    $('#addEditCustomerForm').find('#appendBillingAddress').html('').addClass('d-none');
    $('#addEditCustomerForm').find('#appendShippingAddress').html('').addClass('d-none');
    $('#addEditCustomerForm').find('#deleteBillingAddress,#deleteShippingAddress,#addrCopyToShipping').addClass('d-none');
    $('#addEditCustomerForm').find('#addBillingAddress,#addShippingAddress').removeClass('d-none');
}

function baseAddressCreation(AddressType, StateInfo, CityInfo) {

    if(AddressType == 1) {

        var finalReturnData = '<div class="mt-3">';
                finalReturnData += '<div class="row">';
                    finalReturnData += '<input type="hidden" name="BillAddressUID" id="BillAddressUID" value="0" />';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="BillAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>';
                        finalReturnData += '<input class="form-control" type="text" id="BillAddrLine1" name="BillAddrLine1" maxlength="100" placeholder="Address Line 1" required />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="BillAddrLine2" class="form-label">Address Line 2 </label>';
                        finalReturnData += '<input class="form-control" type="text" id="BillAddrLine2" name="BillAddrLine2" maxlength="100" placeholder="Address Line 2" />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="BillAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>';
                        finalReturnData += '<input class="form-control" type="text" id="BillAddrPincode" name="BillAddrPincode" maxlength="10" placeholder="Pincode" required />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-0 col-md-6">';
                        finalReturnData += '<label for="BillAddrState" class="form-label">State</label>';
                        finalReturnData += '<select class="select2 form-select" id="BillAddrState" name="BillAddrState">';
                            finalReturnData += '<option label="-- Select State --"></option>';
                            if (StateInfo.length > 0) {
                                StateInfo.forEach(StData => {
                                    finalReturnData += `<option value="${StData.id}" data-iso2="${StData.iso2}">${StData.name}</option>`;
                                });
                            }
                            finalReturnData += '</select>';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-0 col-md-6">';
                        finalReturnData += '<label for="BillAddrCity" class="form-label">City</label>';
                        finalReturnData += '<select class="select2 form-select" id="BillAddrCity" name="BillAddrCity">';
                        finalReturnData += '<option label="-- Select City --"></option>';
                        if (CityInfo.length > 0) {
                            CityInfo.forEach(CtyData => {
                                finalReturnData += `<option value="${CtyData.id}">${CtyData.name}</option>`;
                            });
                        }
                        finalReturnData += '</select>';
                    finalReturnData += '</div>';
                finalReturnData += '</div>';
            finalReturnData += '</div>';

        return finalReturnData;

    } else if(AddressType == 2) {
        
        var finalReturnData = '<div class="mt-3">';
                finalReturnData += '<div class="row">';
                    finalReturnData += '<input type="hidden" name="ShipAddressUID" id="ShipAddressUID" value="0" />';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="ShipAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>';
                        finalReturnData += '<input class="form-control" type="text" id="ShipAddrLine1" name="ShipAddrLine1" maxlength="100" placeholder="Address Line 1" required />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="ShipAddrLine2" class="form-label">Address Line 2 </label>';
                        finalReturnData += '<input class="form-control" type="text" id="ShipAddrLine2" name="ShipAddrLine2" maxlength="100" placeholder="Address Line 2" />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-12">';
                        finalReturnData += '<label for="ShipAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>';
                        finalReturnData += '<input class="form-control" type="text" id="ShipAddrPincode" name="ShipAddrPincode" maxlength="10" placeholder="Pincode" required />';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-6">';
                        finalReturnData += '<label for="ShipAddrState" class="form-label">State</label>';
                        finalReturnData += '<select class="select2 form-select" id="ShipAddrState" name="ShipAddrState">';
                            finalReturnData += '<option label="-- Select State --"></option>';
                            if (StateInfo.length > 0) {
                                StateInfo.forEach(StData => {
                                    finalReturnData += `<option value="${StData.id}" data-iso2="${StData.iso2}">${StData.name}</option>`;
                                });
                            }
                            finalReturnData += '</select>';
                    finalReturnData += '</div>';
                    finalReturnData += '<div class="mb-3 col-md-6">';
                        finalReturnData += '<label for="ShipAddrCity" class="form-label">City</label>';
                        finalReturnData += '<select class="select2 form-select" id="ShipAddrCity" name="ShipAddrCity">';
                        finalReturnData += '<option label="-- Select City --"></option>';
                        if (CityInfo.length > 0) {
                            CityInfo.forEach(CtyData => {
                                finalReturnData += `<option value="${CtyData.id}">${CtyData.name}</option>`;
                            });
                        }
                        finalReturnData += '</select>';
                    finalReturnData += '</div>';
                finalReturnData += '</div>';
            finalReturnData += '</div>';
            
        return finalReturnData;
        
    }

}

function creationBilngAddrActions() {
    $('#addBillingAddress').addClass('d-none');
    var DivId = $('#addBillingAddress').data('divid');
    $('#'+DivId).removeClass('d-none').html('');
    $('#'+DivId).html(baseAddressCreation(1, StateInfo, CityInfo, defaultIso2));
    $('#AddressDivider').removeClass('d-none');
    if($("#ShipAddressUID").length) {
        $('#addrCopyToShipping').removeClass('d-none');
    }
    $('#deleteBillingAddress').removeClass('d-none');
}

function creationShipAddrActions() {
    $('#addShippingAddress').addClass('d-none');
    var DivId = $('#addShippingAddress').data('divid');
    $('#'+DivId).removeClass('d-none').html('');
    $('#'+DivId).html(baseAddressCreation(2, StateInfo, CityInfo, defaultIso2));
    if($("#BillAddressUID").length) {
        $('#addrCopyToShipping').removeClass('d-none');
    }
    $('#deleteShippingAddress').removeClass('d-none');
    $('#AddressDivider').removeClass('d-none');
}

function addCustomerData(formdata) {
    $.ajax({
        url: '/transactions/addCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                Swal.fire({icon: "error", title: "OOPS!", text: response.Message});
            } else {
                $('#transCustomerModal').modal('hide');
                $('#customerSearch').append(`<option value="${response.Customer.id}">${response.Customer.text}</option>`);
                $('#customerSearch').val(response.Customer.id).trigger('change');
                if(hasValue(response.Customer.address)) {
                    var addrHtml = `
                        <div><strong>Shipping Address:</strong></div>
                        <div>${response.Customer.address.Line1 || ''}</div>
                        <div>${response.Customer.address.Line2 || ''}</div>
                        <div>${response.Customer.address.City || ''}, ${response.Customer.address.State || ''} - ${response.Customer.address.Pincode || ''}</div>
                    `;
                    $("#customerAddressBox").html(addrHtml).removeClass('d-none');
                } else {
                    $("#customerAddressBox").addClass('d-none').empty();
                }
                setTransAddrDefaultActions();
            }
        }
    });
}

function editCustomerData(formdata) {
    $.ajax({
        url: '/transactions/updateCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                Swal.fire({icon: "error", title: "OOPS!", text: response.Message});
            } else {
                $('#EditCustomerForm').trigger('reset');
                Swal.fire(response.Message, "", "success");
                setTimeout(function () {                    
                    window.history.back();
                }, 250);
            }
        }
    });
}