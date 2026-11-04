/**
 * @param {FormData} formdata
 * @returns {void}
 */
function updateProfileForm(formdata) {
    var isPasswordUpdate = formdata.get('IsPasswordUpdate');
    formdata.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    formdata.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({
        url         : global_services_url + 'api/profile/updateProfileDetails',
        method      : 'POST',
        data        : formdata,
        cache       : false,
        processData : false,
        contentType : false,
        headers     : { Authorization: 'Bearer ' + JwtToken },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, '', 'error');
            } else {
                Swal.fire(response.Message, '', 'success').then(function () {
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