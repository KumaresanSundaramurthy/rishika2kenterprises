jQuery.fn.center = function () {
    this.css("position", "absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +
        $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +
        $(window).scrollLeft()) + "px");
    return this;
}

function showUIBlock() {

    $.blockUI({
        message: `
            <div class="d-flex flex-column justify-content-center align-items-center" style="height: 100vh;">
                <div class="spinner-border text-info" role="status" style="width: 4rem; height: 4rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mt-3 text-white animate__animated animate__infinite">Processing... Please wait</h4>
            </div>
        `,
        css: {
            border: 'none',
            padding: 0,
            backgroundColor: 'transparent',
            width: '100%',
            top: '0',
            left: '0',
            position: 'fixed',
            zIndex: 2000,
            textAlign: 'center'
        },
        overlayCSS: {
            backgroundColor: '#111',
            opacity: 0.7,
            backdropFilter: 'blur(4px)',  // Blurred effect
            zIndex: 1999,
            cursor: 'wait'
        }
    });

}

function hideUIBlock() {
    $.unblockUI();
}

function alertPopup(msg, delay = 8000, colour = 'yellow') {
    if ($("#alert").length) {
        $("#alert").remove();
    }
    $("body").append('<div onclick="gumHoJa();" id="alert" style="z-index:100;color:black; border-radius:3px; background-color:' + colour + '; padding:25px 50px; height:55px; line-height:10px; text-align:center; vertical-align:middle; border: 0 solid black; font-size: 12pt;"> <span id="spanText">' + msg + '</span></div>');
    // $("#alert").css({'z-index':100, 'position': 'absolute', 'left':15, 'bottom':15, 'box-shadow': '10px 10px 5px #888888' }); //#337ab7
    $("#alert").center();
    $("#alert").css({ 'z-index': 100, 'box-shadow': '10px 10px 5px #888888' }); //#337ab7
    $("#alert").fadeOut(delay);

    if (colour == "red") {
        // $("#alert").center();
        $("#alert").css({ 'box-shadow': '10px 10px 5px grey' });
    }

}

function gumHoJa() {
    $("#alert").remove();
}


jQuery.fn.center = function () {
    this.css("position", "absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +
        $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +
        $(window).scrollLeft()) + "px");
    return this;
}


function blankControls() {
    // to clear all text boxes
    $('input[type=text]').each(function () { $(this).val(''); });

    // to clear all number boxes
    $('input[type=number]').each(function () { $(this).val(''); });

    // To Set 1st element in all dropdowns
    $("select").prop('selectedIndex', 0);

    // For labels with class blank
    $("label.blank").text('');

    // For TextArea
    $("textarea").val('');

    // For images
    $('img').attr("src", "");

    // to clear all file inputs
    $('input[type=file]').each(function () { $(this).val(''); });
}

$(document).ready(function () {
    $("input[type=number]").click(function () {
        $(this).select();
    });

    ///////// Avoiding ', " and \ in text input
    $('input[type=text]').keypress(function (event) {
        if (event.which == 39 || event.which == 34 || event.which == 92) {
            event.preventDefault();
            $(this).val($(this).val() + '');
        }
    });

});




function myAlert(arg) {
    // alert("FFF");
    $("#dialog").text(arg);
    $("#dialog").dialog({
        title: "PJ",
        modal: true,
        dialogClass: "alert",
        buttons: [
            {
                text: "OK",
                click: function () {
                    $(this).dialog("close");
                }
            }
        ]
    });
}




// Function to Change the Default Date Format
function dateFormat(dt) {
    var mnth = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return (dt.getDate() <= 9 ? "0" + dt.getDate() : dt.getDate()) + "-" + mnth[dt.getMonth()] + "-" + dt.getFullYear();
}


/* Email Validation*/
function validateEmail(sEmail) {
    var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    if (filter.test(sEmail)) {
        return true;
    }
    else {
        return false;
    }
}
/* END - Email Validation*/




/*--------------- Date validation------------*/
function testDate(dt) {
    // alert(document.getElementById(dt).value);
    // alert(document.getElementById(dt).value);
    var result = isDate(document.getElementById(dt).value);
    return result;
    // console.log(document.getElementById('dateTest').value);
    // console.log(result);
    // $('#result').text(result );
}

//function isDate(txtDate) {
function isDate(currVal) {
    // alert(currVal);

    if (currVal == '') return false;

    //Declare Regex  
    var rxDatePattern = /^(\d{1,2})(\/|-)([a-zA-Z]{3})(\/|-)(\d{4})$/;

    var dtArray = currVal.match(rxDatePattern); // is format OK?
    // alert(dtArray);
    if (dtArray == null) return false;

    var dtDay = parseInt(dtArray[1]);
    var dtMonth = dtArray[3];
    var dtYear = parseInt(dtArray[4]);

    // alert(dtDay + " " + dtMonth + "  " + dtYear);
    // alert(dtDay);
    // need to change to lowerCase because switch is
    // case sensitive
    switch (dtMonth.toLowerCase()) {
        case 'jan':
            dtMonth = '01';
            break;
        case 'feb':
            dtMonth = '02';
            break;
        case 'mar':
            dtMonth = '03';
            break;
        case 'apr':
            dtMonth = '04';
            break;
        case 'may':
            dtMonth = '05';
            break;
        case 'jun':
            dtMonth = '06';
            break;
        case 'jul':
            dtMonth = '07';
            break;
        case 'aug':
            dtMonth = '08';
            break;
        case 'sep':
            dtMonth = '09';
            break;
        case 'oct':
            dtMonth = '10';
            break;
        case 'nov':
            dtMonth = '11';
            break;
        case 'dec':
            dtMonth = '12';
            break;
    }

    // // convert date to number
    // dtMonth = parseInt(dtMonth);

    // if (isNaN(dtMonth)) return false;
    // else if (dtMonth < 1 || dtMonth > 12) return false;
    // else if (dtDay < 1 || dtDay > 31) return false;
    // else if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) return false;
    // else if (dtMonth == 2) {
    //     var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
    //     if (dtDay > 29 || (dtDay == 29 && !isleap)) return false;
    // }
    // return true;
    // convert date to number
    dtMonth = parseInt(dtMonth);

    if (isNaN(dtMonth)) return false;
    else if (dtMonth < 1 || dtMonth > 12) return false;
    else if (dtDay < 1 || dtDay > 31) return false;
    else if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) return false;
    else if (dtMonth == 2) {
        // var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
        var isleap = false;
        if (dtYear % 4 == 0) {
            isleap = true;
        }
        // if (dtDay > 29 || (dtDay == 29 && !isleap)) return false;
        if (dtDay > 29 || (dtDay == 29 && isleap == true)) return false;
    }
    return true;
}
/*---------------END Date validation------------*/





// AJAX Indication Starts //
function ajaxindicatorstart(text) {

    if (jQuery('body').find('#resultLoading').attr('id') != 'resultLoading') {
        jQuery('body').append('<div id="resultLoading" style="display:none"><div><img src="/bootstrap/images/ajax-loader.gif"><div>' + text + '</div></div><div class="bg"></div></div>');
    }

    jQuery('#resultLoading').css({
        'width': '100%',
        'height': '100%',
        'position': 'fixed',
        'z-index': '10000000',
        'top': '0',
        'left': '0',
        'right': '0',
        'bottom': '0',
        'margin': 'auto'
    });

    jQuery('#resultLoading .bg').css({
        'background': '#000000',
        'opacity': '0.7',
        'width': '100%',
        'height': '100%',
        'position': 'absolute',
        'top': '0'
    });

    jQuery('#resultLoading>div:first').css({
        'width': '250px',
        'height': '75px',
        'text-align': 'center',
        'position': 'fixed',
        'top': '0',
        'left': '0',
        'right': '0',
        'bottom': '0',
        'margin': 'auto',
        'font-size': '16px',
        'z-index': '10',
        'color': '#ffffff'

    });

    jQuery('#resultLoading .bg').height('100%');
    jQuery('#resultLoading').fadeIn(300);
    jQuery('body').css('cursor', 'wait');
}

function ajaxindicatorstop() {
    jQuery('#resultLoading .bg').height('100%');
    jQuery('#resultLoading').fadeOut(300);
    jQuery('body').css('cursor', 'default');
}

jQuery(document).ajaxStart(function () {
    showUIBlock();
    // ajaxindicatorstart('loading... please wait...');
}).ajaxStop(function () {
    hideUIBlock()
    // ajaxindicatorstop();
});
// AJAX Indication Stops //

function validateMobileNumber(countryCode, mobileNumber) {
    
    mobileNumber = mobileNumber.replace(/[\s\-]/g, '');

    switch (countryCode.toUpperCase()) {
        case 'IN': // India
            return /^[6-9]\d{9}$/.test(mobileNumber); // 10 digits, starts with 6-9

        case 'US': // United States
            return /^\d{10}$/.test(mobileNumber); // 10-digit format

        case 'AE': // UAE
            return /^5[0-9]{8}$/.test(mobileNumber); // Mobile starts with 5, 9 digits

        case 'UK': // United Kingdom
            return /^\d{10}$/.test(mobileNumber); // 10-digit format
            
        case 'GB':
            return /^7\d{9}$/.test(mobileNumber); // UK mobiles typically start with 7, 10 digits

        case 'PK': // Pakistan
            return /^3\d{9}$/.test(mobileNumber); // Starts with 3, 10 digits

        case 'BD': // Bangladesh
            return /^1[3-9]\d{8}$/.test(mobileNumber); // 11 digits, starts with 13–19

        default:
            return /^[6-9]\d{9}$/.test(mobileNumber);
    }

}

function inlineMessageAlert(FieldName, Type, Message, IsLoading = false, ShowCloseBtn = false) {

    let HtmlData = '<div class="alert alert-'+Type+' alert-dismissible fade show" role="alert">';
    if(IsLoading) {
        HtmlData += '<div class="spinner-border spinner-border-sm me-3" role="status" aria-hidden="true"></div>';
    }
        HtmlData += '<strong>'+Message+'</strong>';
    if(ShowCloseBtn) {
        HtmlData += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    }
        HtmlData += '</div>';

    $(FieldName).html(HtmlData);

}

function changeHandler(val) {
    if (Number(val.value) > 100) {
        let str = val.value;
        let mark = str.slice(0, -1);
        val.value = mark;
    }
}

function resetUserPassword(formData) {

    $('#ResetPasswordSubBtn').prop('disabled', 'disabled');

    $.ajax({
		url: '/login/resetPassword',
		method: 'POST',
		data: formData,
		cache: false,
		success: function(response) {

            $('#ResetPasswordSubBtn').removeAttr('disabled');
			if(response.Error) {
                $('#ChangePasswordAlert').removeClass('d-none');
				inlineMessageAlert('#ChangePasswordAlert', 'danger', response.Message, false, false);
			} else {
                
                $('#ChangePasswordAlert').addClass('d-none');
                $('#ChangePasswordModal').modal('hide');
                window.location.replace('/login/logout');
                
			}

        }
	});

}

function fileSelect(id) {
    $('#image-error').addClass('d-none');

    var fileType = id.target.files[0].type;
    var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(fileType)) {
        $('#image-error').removeClass('d-none');
        $('#image-error').text('Please upload only image.');
    } else {
        var fSizeMB = id.target.files[0].size / Math.pow(1024, 2);
        if (fSizeMB <= 1) {
            var tmppath = URL.createObjectURL(id.target.files[0]);
            $('#uploadedAvatar').attr("src", tmppath);
            imageChange = 1;
        } else {
            $('#image-error').removeClass('d-none');
            $('#image-error').text('Upload Max of 1 MB');
        }
    }
}