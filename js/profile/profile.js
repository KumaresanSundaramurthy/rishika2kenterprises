function updateProfileForm(formdata) {
    var isPasswordUpdate = formdata.get('IsPasswordUpdate');
    $.ajax({
        url: '/profile/updateProfileDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
            } else {
                Swal.fire(response.Message, "", "success").then(() => {
                    if (isPasswordUpdate == 1) {
                        window.location.href = '/logout';
                    } else {
                        window.location.reload();
                    }
                });
            }
        }
    });
}