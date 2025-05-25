function updateOrgForm(formdata) {

    $('#OrgSubBtn').prop('disabled', 'disabled');

    $.ajax({
        url: '/organisation/updateOrgForm',
        method: 'POST',
        data: formdata,
        cache: false,
        success: function (response) {
            $('#OrgSubBtn').removeAttr('disabled');
            $('#updateFormAlert').removeClass('d-none');
            if (response.Error) {
                inlineMessageAlert('#updateFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('#updateFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#updateFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);
            }
        }
    });

}