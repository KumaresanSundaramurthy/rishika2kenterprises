<script>
$(function() {
	'use strict'

    var JwtToken = '<?php echo $JwtToken; ?>';
    var JwtData = JSON.parse('<?php echo json_encode($JwtData); ?>');
    // var CsrfName = '<?php //echo $this->security->get_csrf_token_name(); ?>';
    // var CsrfToken = '<?php //echo $this->security->get_csrf_hash(); ?>';
    var RowLimit = 10;
    var PageNo = 0;
    var Filter = {};
    var global_base_url = '<?php echo base_url(); ?>';

    var tm = new Date();
    tm = '<h3>Loading... Pls. wait...<h3><span style="color:yellow;">' + tm.getHours() + ":" + tm.getMinutes()+ ":" + tm.getSeconds() + '</span>'  ;
    $.blockUI({
        message: tm, 
        css: { 
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff',
        }
    });

    $.unblockUI();

    $('#ChangePasswordBtn').click(function(e) {
        e.preventDefault();
        $('#ChangePasswordModal').modal('show');
        $('#ChangePasswordModal #ResetPasswordForm').trigger('reset');
        $('#ResetPasswordSubBtn').removeAttr('disabled');
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');
    });

    $('#ResetPasswordForm').submit(function(e) {
        e.preventDefault();
        
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');

        var newPassword = $('#NewPassword').val();        
        var confirmPassword = $('#ConfirmPassword').val();
        if (newPassword !== confirmPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Passwords do not match!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }

        var oldPassword = $('#OldPassword').val();
        if(oldPassword === newPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Old & New Passwords are same!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }
        
        resetUserPassword($('#ResetPasswordForm').serializeArray());

    });

});
</script>