function updateProfileForm(formdata) {
    var isPasswordUpdate = formdata.get('IsPasswordUpdate');
    $.ajax({
        url: global_base_url + 'profile/updateProfileDetails',
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
                        window.location.href = global_base_url + 'logout';
                    } else {
                        window.location.reload();
                    }
                });
            }
        }
    });
}