function updateOrgForm(formdata) {
    $('.OrgSubBtn').attr('disabled', 'disabled');
    $.ajax({
        url: '/organisation/updateOrgForm',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.OrgSubBtn').removeAttr('disabled');
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
            } else {

                Swal.fire(response.Message, "", "success");
                
                imageChange = 0;
                countryChange = 0;

                if ($('#BillOrgAddressUID').val() == 0) {
                    $('#BillOrgAddressUID').val(response.BillOrgAddressUID)
                }
                if ($('#ShipOrgAddressUID').val() == 0) {
                    $('#ShipOrgAddressUID').val(response.ShipOrgAddressUID)
                }
            }

        }
    });
}

function getStateCityOfCountry(CountryCode) {

    $('#updateFormAlert').addClass('d-none');

    var formData = new FormData();
    formData.append('CountryCode', CountryCode);
    formData.append('Type', 'Return');

    $.ajax({
        url: '/globally/getStateCityOfCountry',
        method: 'POST',
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {

            if (response.Error) {
                $('#updateFormAlert').removeClass('d-none');
                inlineMessageAlert('#updateFormAlert', 'danger', response.Message, false, false);
            } else {

                if(response.StateInfo) {

                    $('#BillAddrState,#ShipAddrState').empty().val(null).trigger('change');
                    $('#BillAddrState,#ShipAddrState').append('<option label="-- Select State --"></option>');
                    response.StateInfo.forEach(option => {
                        const newOption = $('<option>', {
                            value: option.id,
                            text: option.name,
                            'data-iso2': option.iso2
                        });
                        // const newOption = new Option(option.text, option.id, false, false);
                        $('#BillAddrState,#ShipAddrState').append(newOption);
                    });
                    $('#BillAddrState,#ShipAddrState').val(null).trigger('change');

                }

                if(response.CityInfo) {

                    $('#BillAddrCity,#ShipAddrCity').empty().val(null).trigger('change');
                    $('#BillAddrCity,#ShipAddrCity').append('<option label="-- Select City --"></option>');
                    response.CityInfo.forEach(option => {
                        const newOption = $('<option>', {
                            value: option.id,
                            text: option.name
                        });
                        // const newOption = new Option(option.text, option.id, false, false);
                        $('#BillAddrCity,#ShipAddrCity').append(newOption);
                    });
                    $('#BillAddrCity,#ShipAddrCity').val(null).trigger('change');

                }

            }

        }
    });

}