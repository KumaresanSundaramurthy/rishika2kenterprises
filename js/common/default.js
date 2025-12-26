jQuery.fn.center = function () {
    this.css("position", "absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +
        $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +
        $(window).scrollLeft()) + "px");
    return this;
}

function inputDelay(callback, ms) {
    var timer = 0;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
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

    const BLUR_ID = 'modal-blur-layer';

    $('[data-toggle="tooltip"]').tooltip();

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

    $('#AutoGeneratePartNoBtn').click(function (e) {
        e.preventDefault();
        $('#' + $(this).data('field')).val(generateTimestampRandomNumber(8));
    });

    $('#exportPagesModal').on('hidden.bs.modal', function () {
        exportModule = '';
        expActionType = '';
    });

    $('#btnPageSettings').click(function (e) {
        e.preventDefault();
        $('#pageSettingsModal').modal('show');
    });

    $('#UpdatePageSettingsForm').submit(function (e) {
        e.preventDefault();
        let checkResp = checkPageSettingsSortOrder();
        if (checkResp) {
            return false;
        }
        var formData = $('#UpdatePageSettingsForm').serializeArray();
        updatePageSettings(formData);
    });

    $('#SettingsMenuBarBtn').on('click', function(e) {
        e.preventDefault();
        // $('#ModulesMenuBar').addClass('d-none');
        // $('#SettingsMenuBar').removeClass('d-none');
        let currentUrl = window.location.href;
        if(currentUrl.indexOf("/settings") === -1) {
            localStorage.setItem("previousUrl", currentUrl);
        }
        window.location.href = '/settings/profile';
    });

    $('#SettingsBackMenuBarBtn').on('click', function(e) {
        e.preventDefault();
        let previousUrl = localStorage.getItem("previousUrl");
        console.log(previousUrl)
        window.location.href = previousUrl ? previousUrl : '/dashboard';
        // $('#SettingsMenuBar').addClass('d-none');
        // $('#ModulesMenuBar').removeClass('d-none');
    });

    document.addEventListener('show.bs.modal', function (event) {
        const openedModal = event.target;
        const visibleModals = document.querySelectorAll('.modal.show');
        if (visibleModals.length > 0) {
            const lastModal = visibleModals[visibleModals.length - 1];
            lastModal.querySelector('.modal-content')?.classList.add('modal-blur');
        }
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        const closedModal = event.target;
        const allModals = document.querySelectorAll('.modal');
        allModals.forEach(modal => {
            modal.querySelector('.modal-content')?.classList.remove('modal-blur');
        });
        const stillOpen = document.querySelectorAll('.modal.show');
        if (stillOpen.length > 1) {
            const secondTop = stillOpen[stillOpen.length - 2];
            secondTop.querySelector('.modal-content')?.classList.add('modal-blur');
        }
    });

    $(document).on('click', function(e) {
        var $filterBoxes = $('.mp-filterbox');
        var $toggleIcons  = $('.filter-toggle, .bx-filter-alt');
        if (!$filterBoxes.is(e.target) && $filterBoxes.has(e.target).length === 0 &&
            !$toggleIcons.is(e.target) && $toggleIcons.has(e.target).length === 0) {
            $filterBoxes.hide();
        }
    });

    $(document).on('click', '.filter-toggle', function(e) {
        e.stopPropagation();
        var target = $(this).data('target');
        if (!target) return;
        var $target = $(target);
        $('.mp-filterbox').not($target).hide();
        $target.toggle();
    });

    $(document).on('click', '.mp-filterbox', function(e) {
        e.stopPropagation();
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape") {
            $('.mp-filterbox').hide();
        }
    });

});

function exportURLDynamic(Url) {
    console.log(Url)
    if (Url.length > 7000) {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Too many items selected. Please select fewer items.",
        });
    } else {
        window.location.href = Url;
    }
}

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

    if (currVal == '') return false;

    //Declare Regex  
    var rxDatePattern = /^(\d{1,2})(\/|-)([a-zA-Z]{3})(\/|-)(\d{4})$/;

    var dtArray = currVal.match(rxDatePattern); // is format OK?

    if (dtArray == null) return false;

    var dtDay = parseInt(dtArray[1]);
    var dtMonth = dtArray[3];
    var dtYear = parseInt(dtArray[4]);

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

jQuery(document).ajaxStart(function () {
    if (AjaxLoading == 1) {
        showUIBlock();
    }
}).ajaxStop(function () {
    hideUIBlock();
});

function showOneDropzoneImgDetails(dropzoneInstance, imageUrl, fileName, fileSize) {

    // Create a mock file
    const mockFile = {
        name: fileName,
        size: fileSize,
        type: 'image/jpeg',
        accepted: true,
        isStored: true
    };

    // Add the mock file to the dropzone preview area
    dropzoneInstance.emit("addedfile", mockFile);
    dropzoneInstance.emit("thumbnail", mockFile, imageUrl);
    dropzoneInstance.emit("complete", mockFile);

    // Add the mock file to Dropzone's internal files array
    dropzoneInstance.files.push(mockFile);

}

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

    let HtmlData = '<div class="alert alert-' + Type + ' alert-dismissible fade show" role="alert">';
    if (IsLoading) {
        HtmlData += '<div class="spinner-border spinner-border-sm me-3" role="status" aria-hidden="true"></div>';
    }
    HtmlData += '<strong>' + Message + '</strong>';
    if (ShowCloseBtn) {
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

function handleOnlyNumbers(input) {
    // Remove any character that's not 0-9
    input.value = input.value.replace(/[^0-9]/g, '');
}

function pasteOnlyNumbers(event) {
    const pastedData = (event.clipboardData || window.clipboardData).getData('text');

    // If the pasted data contains non-digit characters, block the paste
    if (!/^\d+$/.test(pastedData)) {
        event.preventDefault();
    }
}

function dropOnlyNumbers(event) {
    const data = event.dataTransfer.getData('text');
    if (!/^\d+$/.test(data)) {
        event.preventDefault();
    }
}

function handleDotOnly(event) {
    const key = event.key;
    const value = event.target.value;

    // Allow control/navigation keys
    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(key) || event.ctrlKey || event.metaKey) {
        return true;
    }

    // Allow digits
    if (/^[0-9]$/.test(key)) return true;

    // Allow dot if not already present
    if (key === '.' && !value.includes('.')) return true;

    return false; // Block everything else
}

function handlePricePaste(event, maxLength, decimalPlaces) {
    const data = (event.clipboardData || window.clipboardData).getData('text');
    if (!/^\d*\.?\d*$/.test(data)) {
        event.preventDefault();
        return;
    }

    // Temporarily create an input to validate the pasted value
    const temp = document.createElement('input');
    temp.value = data;
    validatePriceInput(temp, maxLength, decimalPlaces);

    if (temp.value !== data) {
        event.preventDefault();
    }
}

function handlePriceDrop(event, maxLength, decimalPlaces) {
    const data = event.dataTransfer.getData('text');
    if (!/^\d*\.?\d*$/.test(data)) {
        event.preventDefault();
        return;
    }

    const temp = document.createElement('input');
    temp.value = data;
    validatePriceInput(temp, maxLength, decimalPlaces);

    if (temp.value !== data) {
        event.preventDefault();
    }
}

function validatePriceInput(input, maxLength, decimalLength) {
    let val = input.value;

    // Remove all characters except digits and one dot
    val = val.replace(/[^0-9.]/g, '');

    // Remove multiple dots
    const firstDot = val.indexOf('.');
    if (firstDot !== -1 && val.lastIndexOf('.') !== firstDot) {
        const beforeDot = val.slice(0, firstDot);
        const afterDot = val.slice(firstDot + 1).replace(/\./g, '');
        val = beforeDot + '.' + afterDot;
    }

    // Split into integer and decimal parts
    const parts = val.split('.');
    let integerPart = parts[0];
    let decimalPart = parts[1] || '';

    // Remove leading zeros unless it's just '0'
    if (integerPart.length > 1) {
        integerPart = integerPart.replace(/^0+/, '');
        if (integerPart === '') integerPart = '0';
    }

    // Limit integer part to allowed length
    const maxIntLen = maxLength - decimalLength - 1;
    integerPart = integerPart.slice(0, maxIntLen);

    // Limit decimal part
    decimalPart = decimalPart.slice(0, decimalLength);

    // Compose final value
    if (val.endsWith('.') && decimalPart === '') {
        input.value = integerPart + '.';
    } else if (decimalPart) {
        input.value = `${integerPart}.${decimalPart}`;
    } else {
        input.value = integerPart;
    }

}

function validateDiscountInput(input, maxLength, decimalLength, forcedValue = null) {

    let val = forcedValue !== null ? forcedValue : input.value;

    // Remove all characters except digits and one dot
    val = val.replace(/[^0-9.]/g, '');

    // Remove multiple dots
    const firstDot = val.indexOf('.');
    if (firstDot !== -1 && val.lastIndexOf('.') !== firstDot) {
        const beforeDot = val.slice(0, firstDot);
        const afterDot = val.slice(firstDot + 1).replace(/\./g, '');
        val = beforeDot + '.' + afterDot;
    }

    const type = $('#DiscountOption').find('option:selected').val();
    if (type == 1) {
        if (Number(val) > 100) {
            let str = val;
            let mark = str.slice(0, -1);
            val = mark;
        }
    }

    // Split into integer and decimal parts
    const parts = val.split('.');
    let integerPart = parts[0];
    let decimalPart = parts[1] || '';

    // Remove leading zeros unless it's just '0'
    if (integerPart.length > 1) {
        integerPart = integerPart.replace(/^0+/, '');
        if (integerPart === '') integerPart = '0';
    }

    // Limit integer part to allowed length
    const maxIntLen = maxLength - decimalLength - 1;
    integerPart = integerPart.slice(0, maxIntLen);

    // Limit decimal part
    decimalPart = decimalPart.slice(0, decimalLength);

    // Compose final value
    if (val.endsWith('.') && decimalPart === '') {
        input.value = integerPart + '.';
    } else if (decimalPart) {
        input.value = `${integerPart}.${decimalPart}`;
    } else {
        input.value = integerPart;
    }

}

function handleDiscountPaste(event, maxLength, decimalLength) {
    event.preventDefault();
    const pastedData = (event.clipboardData || window.clipboardData).getData('text');
    validateDiscountInput(event.target, maxLength, decimalLength, pastedData);
}

function handleDiscountDrop(event, maxLength, decimalLength) {
    event.preventDefault();
    const droppedData = event.dataTransfer.getData('text');
    validateDiscountInput(event.target, maxLength, decimalLength, droppedData);
}

function generateTimestampRandomNumber(length) {
    return Date.now().toString().slice(-length) + Math.floor(1000 + Math.random() * 9000);
}

function QuillEditor(EditorName, PlaceHolder) {
    quill = new Quill(EditorName, {
        placeholder: PlaceHolder,
        modules: {
            toolbar: [
                [{ 'size': ['small', false, 'large', 'huge'] }],  // font sizes
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

                ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                // ['blockquote', 'code-block'],

                [{ 'header': 1 }, { 'header': 2 }],               // custom button values
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
                [{ 'indent': '-1' }, { 'indent': '+1' }],          // outdent/indent
                // [{ 'direction': 'rtl' }],                         // text direction

                // [{ 'color': [] }, { 'background': [] }],          // text and background color
                // [{ 'font': [] }],
                // [{ 'align': [] }],

                // ['clean'],                                        // remove formatting

                // ['link', 'image', 'video'],                       // insert media
                // ['formula']                                       // insert formula (KaTeX)
            ],
            // clipboard: true,
            // keyboard: true,
            // history: {
            //     delay: 1000,
            //     maxStack: 50,
            //     userOnly: true
            // }
        },
        theme: 'snow'
    });
}

function appendToQuill(content, isHtml = false) {
    const index = quill.getLength() - 1;
    if (isHtml) {
        quill.clipboard.dangerouslyPasteHTML(index, content);
    } else {
        quill.insertText(index, content);
    }
}

function resetUserPassword(formData) {

    $('#ResetPasswordSubBtn').prop('disabled', 'disabled');

    $.ajax({
        url: '/login/resetPassword',
        method: 'POST',
        data: formData,
        cache: false,
        success: function (response) {

            $('#ResetPasswordSubBtn').removeAttr('disabled');
            if (response.Error) {
                $('#ChangePasswordAlert').removeClass('d-none');
                inlineMessageAlert('#ChangePasswordAlert', 'danger', response.Message, false, false);
            } else {

                $('#ChangePasswordAlert').addClass('d-none');
                $('#ChangePasswordModal').modal('hide');
                window.location.replace('/logout');

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

function loadSelect2Field(FieldName, Placeholder, IsModal = '') {
    if(IsModal) {
        $(FieldName).select2({
            placeholder: Placeholder,
            allowClear: true,
            dropdownParent: $(IsModal)
        });
    } else {
        $(FieldName).select2({
            placeholder: Placeholder,
            allowClear: true,
        });
    }  
}

function initializeSelect2Tags(FieldName, Placeholder, IsModal = '') {
    if(IsModal) {
        $(FieldName).select2({
            tags: "true",
            placeholder: Placeholder,
            allowClear: true,
            dropdownParent: $(IsModal)
        });
    } else {
        $(FieldName).select2({
            tags: "true",
            placeholder: Placeholder,
            allowClear: true,
        });
    }
}

function initializeFlatPickr(FieldName, IsModal = '') {
    if(IsModal) {
        flatpickr(FieldName, {
            dateFormat: "Y-m-d",
            appendTo: document.querySelector(IsModal+' .modal-body'),
            clickOpens: true,                
        });
    } else {
        flatpickr(FieldName, {
            dateFormat: "Y-m-d",
            clickOpens: true,                
        });
    }
}

function updatePageSettings(formdata) {
    $('#updatePageSettingsBtn').removeAttr('disabled');
    $.ajax({
        url: '/globally/updatePageSettings',
        method: 'POST',
        data: formdata,
        cache: false,
        success: function (response) {
            $('#updatePageSettingsBtn').removeAttr('disabled');
            if (response.Error) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
            } else {
                Swal.fire(response.Message, "", "success");
                $('#pageSettingsModal').modal('hide');
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            }
        }
    });

}

function CopyAllDatatoSelectItems(PageItemIds) {
    SelectedUIDs = [...PageItemIds];
}

function removeAllDatatoSelectItems() {
    SelectedUIDs = [];
}

function validatePageSettingsMinValue(input) {
    const min = parseInt(input.min) || 1000;
    const value = parseInt(input.value);
    if ((isNaN(value) || value < min)) {
        input.value = min;
    }
}

function checkPageSettingsSortOrder() {
    let hasDuplicates = false;
    let message = '';

    const sections = [
        { checkboxName: 'MainPageFld', inputName: 'MainPageFldSort' },
        { checkboxName: 'PrintPageFld', inputName: 'PrintPageFldSort' },
        { checkboxName: 'ExpCsvFld', inputName: 'ExpCsvFldSort' },
        { checkboxName: 'ExpXlFld', inputName: 'ExpXlFldSort' },
        { checkboxName: 'ExpPdfFld', inputName: 'ExpPdfFldSort' }
    ];

    sections.forEach(function (section) {
        const usedSortValues = new Set();
        const duplicateValues = [];

        // Select all checkboxes in this section
        $(`input[name^="${section.checkboxName}"]`).each(function () {
            const $checkbox = $(this);
            const nameMatch = $checkbox.attr('name').match(/\[(\d+)\]/); // Extract UID

            if ($checkbox.is(':checked') && nameMatch) {
                const uid = nameMatch[1];
                const $sortInput = $(`input[name="${section.inputName}[${uid}]"]`);

                if ($sortInput.length) {
                    const val = $sortInput.val().trim();

                    if (usedSortValues.has(val)) {
                        duplicateValues.push(val);
                        hasDuplicates = true;
                    } else {
                        usedSortValues.add(val);
                    }
                }
            }
        });

        if (duplicateValues.length > 0) {
            message += `Duplicate sort values in ${section.checkboxName.replace(/Fld/, '')}: ${duplicateValues.join(', ')}\n`;
        }
    });

    if (hasDuplicates) {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: 'Form submission blocked due to duplicate sort values:\n\n' + message,
        });
    }
    return hasDuplicates;
}

function commonSetDropzoneImageOne(ImageUrl) {
    myOneDropzone.removeAllFiles(true);
    fetch(ImageUrl)
        .then(res => {
            const contentLength = res.headers.get('Content-Length');
            return res.blob().then(blob => {
                const fileName = decodeURIComponent(ImageUrl.substring(ImageUrl.lastIndexOf('/') + 1));
                const file = new File([blob], fileName, {
                    type: blob.type,
                    lastModified: new Date()
                });

                // Manually patch the size if Dropzone doesn’t read it right
                file.size = contentLength ? parseInt(contentLength) : blob.size;
                file.isStored = true;

                // Add to Dropzone
                myOneDropzone.emit("addedfile", file);
                myOneDropzone.emit("thumbnail", file, ImageUrl);
                myOneDropzone.emit("complete", file);
                myOneDropzone.files.push(file);
            });
        });

}

function commonSetDropzoneImageTwo(ImageUrl) {
    myTwoDropzone.removeAllFiles(true);
    fetch(ImageUrl)
        .then(res => {
            const contentLength = res.headers.get('Content-Length');
            return res.blob().then(blob => {
                const fileName = decodeURIComponent(ImageUrl.substring(ImageUrl.lastIndexOf('/') + 1));
                const file = new File([blob], fileName, {
                    type: blob.type,
                    lastModified: new Date()
                });

                // Manually patch the size if Dropzone doesn’t read it right
                file.size = contentLength ? parseInt(contentLength) : blob.size;
                file.isStored = true;

                // Add to Dropzone
                myTwoDropzone.emit("addedfile", file);
                myTwoDropzone.emit("thumbnail", file, ImageUrl);
                myTwoDropzone.emit("complete", file);
                myTwoDropzone.files.push(file);
            });
        });

}

function baseSelectFunctionality(PageSelcType) {
    if (PageSelcType == 'AllPage') {
        CopyAllDatatoSelectItems(ModuleUIDs);
    }
    selectTableRecords(ModuleTable, ModuleRow);
    headerCheckboxTrueFalse(ModuleUIDs, ModuleHeader);
    $('#selectPagesModal').modal('hide');
}

function baseUnSelectFunctionality(PageSelcType) {
    if (PageSelcType == 'AllPage') {
        removeAllDatatoSelectItems(ModuleUIDs);
    }
    unSelectTableRecords(ModuleTable, ModuleRow);
    headerCheckboxTrueFalse(ModuleUIDs, ModuleHeader);
    $('#unSelectPagesModal').modal('hide');
}

function smartDecimal(number, maxDecimals = 6, digReq = false) {
    number = parseFloat(number);

    // Format to max 6 decimal places
    let formatted = number.toFixed(maxDecimals);

    if (digReq) {
        // Force showing all decimals (e.g. 250.00)
        return formatted;
    }

    // Remove unnecessary trailing zeros and decimal point if not needed
    formatted = formatted.replace(/\.?0+$/, '');
    return formatted;
}

function hasValue(val) {
    return val !== null && val !== '' && val !== undefined;
}

function getScrollableOnSubmitForm($this) {
    
    var form = $this;

    // If form is invalid, reveal hidden containers, scroll to first invalid field and show native message
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {

        // find first invalid element (fallback to required-empty if :invalid not present)
        var $firstInvalid = $(form).find(':invalid').first();
        if (!$firstInvalid.length) {
            $firstInvalid = $(form).find('[required]').filter(function(){
                var val = $(this).val();
                if ($(this).is(':checkbox') || $(this).is(':radio')) {
                    return !$(form).find('[name="'+$(this).attr('name')+'"]:checked').length;
                }
                return (val === null || val === undefined || (typeof val === 'string' && val.trim() === ''));
            }).first();
        }

        if ($firstInvalid.length) {
            // open Bootstrap collapses and tabs that may hide the field
            $firstInvalid.parents().each(function(){
                var $p = $(this);

                if ($p.hasClass('collapse') && !$p.hasClass('show')) {
                    try { $p.collapse('show'); } catch(e) { $p.addClass('show'); }
                }

                if ($p.hasClass('tab-pane')) {
                    var id = $p.attr('id');
                    if (id) {
                        var $tabTrigger = $('a[data-toggle="tab"][href="#'+id+'"], a[data-bs-toggle="tab"][href="#'+id+'"]');
                        if ($tabTrigger.length) {
                            try { $tabTrigger.first().tab('show'); } catch (e) { $tabTrigger.first().trigger('click'); }
                        }
                    }
                }
            });

            // allow UI to open, then scroll & focus & show native validity message
            setTimeout(function(){
                var el = $firstInvalid[0];
                try {
                    el.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});
                } catch(_) {
                    $('html,body').animate({scrollTop: $firstInvalid.offset().top - 100}, 300);
                }
                el.focus();
                if (typeof el.reportValidity === 'function') {
                    el.reportValidity(); // shows "Please fill out this field"
                } else if (typeof form.reportValidity === 'function') {
                    form.reportValidity();
                }
            }, 250);
        }

        return false;
    }

}