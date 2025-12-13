$(document).ready(function () {

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

});

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
                        finalReturnData += '<option label="-- Select City --">Select City</option>';
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
                        finalReturnData += '<option label="-- Select City --">Select City</option>';
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
    $('#'+DivId).html(baseAddressCreation(1, StateInfo, CityInfo, $('#CountryCode').find('option:selected').data('ccode')));
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
    $('#'+DivId).html(baseAddressCreation(2, StateInfo, CityInfo, $('#CountryCode').find('option:selected').data('ccode')));
    if($("#BillAddressUID").length) {
        $('#addrCopyToShipping').removeClass('d-none');
    }
    $('#deleteShippingAddress').removeClass('d-none');
    $('#AddressDivider').removeClass('d-none');
}