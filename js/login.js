function validateUserForm(formdata) {
    $.ajax({
		url: '/login/doLoginForm',
		method: 'POST',
		data: formdata,
		cache: false,
		success: function(response) {
			if(response.Error) {
                
			}
        },
        error: function(xhr, status, error) {
            console.error('Error:', xhr.responseText);
        }
	});
}