$(document).ready(function () {
    'use strict'

    $('#transPrefixSelect').change(function(e) {
        e.preventDefault();
        var getVal = $(this).val();
        if(hasValue(getVal)) {
            $('#appendPrefixVal').text(getVal);
        }
    });

    $('#transPrefixName').on('input', function () {

        // Allow only A-Z, a-z, 0-9, /, \, |, -, _
        let clean = $(this).val().replace(/[^A-Za-z0-9\/\\|\-_]/g, '');
        $(this).val(clean.toUpperCase());

        // Preview the Information
        let prefix = $(this).val().trim();
        let fiscal = '25-26';
        let number = '1';

        if (prefix) {
            let preview = prefix.toUpperCase() + '/' + fiscal + '/' + number;
            $('#prefixPreview').val(preview);
        } else {
            $('#prefixPreview').val('');
        }
        
    });

    $('#addTransPrefixBtn').click(function(e) {
        e.preventDefault();
        $('#transPrefixModal').find('#addTransPrefixForm').trigger('reset');
        $('#transPrefixModal').modal('show');
    });

    $('#addTransPrefixForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $('#addTransPrefixForm').serializeArray();
        var getPreName = $('#addTransPrefixForm').find('#transPrefixName').val();
        $.ajax({
            url: '/transactions/addTransactionPrefix/',
            method: "POST",
            cache: false,
            data: formData,
            success: function (response) {
                if (response.Error) {
                    Swal.fire({icon: "error", title: '', text: response.Message});
                } else {
                    
                    $('#transPrefixSelect').append(`<option value="${getPreName}">${getPreName}</option>`);
                    $('#transPrefixModal').modal('hide');
                    $('#transPrefixModal').find('#addTransPrefixForm').trigger('reset');

                    $('#transPrefixSelect').val(getPreName).trigger('change');
                    
                }
            },
        });
    });

});