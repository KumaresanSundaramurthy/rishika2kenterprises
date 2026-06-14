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

function ajaxLoading(state) {
    if(state) {
        AjaxLoading = 1;
    } else{
        AjaxLoading = 0;
    }
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

function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
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
    // Bootstrap 5 tooltips — container:'body' prevents stuck tooltips inside overflow containers
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { container: 'body', trigger: 'hover' });
    });

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
        $('#ModulesMenuBar').addClass('d-none');
        $('#SettingsMenuBar').removeClass('d-none');
    });

    $('#SettingsBackMenuBarBtn').on('click', function(e) {
        e.preventDefault();
        $('#SettingsMenuBar').addClass('d-none');
        $('#ModulesMenuBar').removeClass('d-none');
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
        if ($target.is(':visible')) { $target.hide(); return; }
        var rect = this.getBoundingClientRect();
        $target.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px' }).show();
    });

    $(document).on('click', '.mp-filterbox', function(e) {
        e.stopPropagation();
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape") {
            $('.mp-filterbox').hide();
        }
    });

    ApexHeader.init();

    $(document).on('click', '.preview-image', function() {
        var imageSrc = $(this).data('src');
        if (imageSrc) {
            $('#imagePreviewTarget').attr('src', imageSrc);
            $('#imagePreviewModal').modal('show');
        }
    });

    $('#imagePreviewModal').on('hidden.bs.modal', function () {
        $('#imagePreviewTarget').attr('src', '');
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

    if (!document.getElementById('globalProcOverlay')) {

        var d = document.createElement('div');

        d.id = 'globalProcOverlay';

        d.innerHTML = ''
            + '<div class="gpo-wrap">'
                + '<div class="gpo-spinner">'
                    + '<img class="gpo-logo" src="/images/logo/favicon_io/android-chrome-512x512-1.png">'
                + '</div>'
            + '</div>';

        document.body.appendChild(d);
    }

    // var lbl = document.getElementById('globalProcLabel');

    // if (lbl) {
    //     lbl.textContent = label || 'PROCESSING';
    // }

    document.getElementById('globalProcOverlay')
        .classList.add('proc-on');
}


/* HIDE FUNCTION */
function hideUIBlock() {

    var overlay = document.getElementById('globalProcOverlay');

    if (overlay) {
        overlay.classList.remove('proc-on');
    }

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

function _resolveDropdownParent($el, modalSelector) {
    // Explicit selector wins
    if (modalSelector) return $(modalSelector);
    // Auto-detect: use .modal-content (NOT .modal) as dropdownParent.
    // .modal-content is position:relative so Select2 positions correctly.
    // Using .modal caused Bootstrap._adjustDialog() to fire on each dropdown
    // open/close (recalculating scrollbar padding), producing the zoom in/out.
    var $content = $el.closest('.modal-content');
    return $content.length ? $content : null;
}

function loadSelect2Field(fieldSelector, placeholder, modalSelector = null) {

    const $el = $(fieldSelector);
    if (!$el.length) return;

    const options = {
        placeholder: placeholder,
        allowClear: true,
        width: '100%'
    };
    var parent = _resolveDropdownParent($el, modalSelector);
    if (parent) options.dropdownParent = parent;

    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
    $el.select2(options);

}

function loadCountrySelect2Field(fieldSelector, placeholder, modalSelector = null) {

    const $el = $(fieldSelector);
    if (!$el.length) return;

    const options = {
        placeholder: placeholder,
        width: '100%'
    };
    var parent = _resolveDropdownParent($el, modalSelector);
    if (parent) options.dropdownParent = parent;

    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
    $el.select2(options);

}

function initializeSelect2Tags(fieldSelector, placeholder, modalSelector = null) {

    const $el = $(fieldSelector);
    if (!$el.length) return;

    const options = {
        tags: "true",
        placeholder: placeholder,
        width: '100%'
    };
    var parent = _resolveDropdownParent($el, modalSelector);
    if (parent) options.dropdownParent = parent;

    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
    $el.select2(options);

}

/* ── Select2: copy placeholder + auto-focus search field on open ── */
$(document).on('select2:open', function(e) {
    var opts        = $(e.target).data('select2').options.options;
    var placeholder = (opts && opts.placeholder) ? opts.placeholder : '';
    if (typeof placeholder === 'object') placeholder = placeholder.text || '';
    setTimeout(function() {
        var $search = $('.select2-container--open .select2-search--dropdown .select2-search__field');
        if (placeholder) $search.attr('placeholder', placeholder);
        // Use native focus() — avoids triggering jQuery's event chain which
        // Bootstrap 5's FocusTrap intercepts, causing the focus in/out flicker
        // inside modals when trigger('focus') was used.
        if ($search.length) $search[0].focus({ preventScroll: true });
    }, 0);
});

function initializeFlatPickr(FieldName, IsModal) {
    var altFmt = (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y';
    var opts = { dateFormat: "Y-m-d", altInput: true, altFormat: altFmt, clickOpens: true };
    if (IsModal) { opts.appendTo = document.querySelector(IsModal + " .modal-body"); }
    flatpickr(FieldName, opts);
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

function reinitDropzoneOne(selector) {
    var el = document.querySelector(selector || '#DropzoneOneBasic');
    if (!el) return;
    if (myOneDropzone) {
        try { myOneDropzone.destroy(); } catch (e) {}
        myOneDropzone = null;
    }
    Dropzone.instances = Dropzone.instances.filter(function (d) { return d.element !== el; });
    el.classList.remove('dz-started', 'dropzone');
    myOneDropzone = new Dropzone(el, {
        url: '#',
        autoProcessQueue: false,
        previewTemplate: `
            <div class="dz-preview dz-file-preview" style="display:inline-flex;flex-direction:column;align-items:center;width:150px;padding:10px;border:1px solid #e4e4e7;border-radius:8px;background:#fff;margin:8px auto;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <div class="dz-image" style="width:120px;height:90px;border-radius:6px;overflow:hidden;margin-bottom:6px;border:1px solid #f0f0f0;flex-shrink:0;">
                    <img data-dz-thumbnail style="width:100%;height:100%;object-fit:cover;display:block;" />
                </div>
                <div class="dz-filename" style="font-size:.72rem;font-weight:600;color:#374151;width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:2px;text-align:center;">
                    <span data-dz-name></span>
                </div>
                <div class="dz-size" style="font-size:.70rem;color:#9ca3af;margin-bottom:6px;text-align:center;">
                    <span data-dz-size></span>
                </div>
                <div class="dz-error-message" style="font-size:.68rem;color:#ef4444;text-align:center;margin-bottom:4px;"><span data-dz-errormessage></span></div>
                <a class="dz-remove" href="javascript:void(0);" data-dz-remove style="font-size:.72rem;color:#6b7280;text-decoration:underline;cursor:pointer;">Remove file</a>
            </div>`,
        parallelUploads: 1,
        maxFilesize: 1,
        acceptedFiles: '.jpg,.jpeg,.png',
        addRemoveLinks: true,
        maxFiles: 1,
        init: function () {
            this.on('addedfile', function (file) {
                if (this.files.length > 1) this.removeFile(this.files[1]);
            });
            this.on('error', function (file) {
                if (file.size > this.options.maxFilesize * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File too large', text: 'Maximum allowed size is 1 MB.' });
                    this.removeFile(file);
                }
            });
            this.on('removedfile', function (file) {
                if (file.isStored) hasRemovedStoredImage = true;
            });
            this.on('maxfilesexceeded', function (file) {
                this.removeFile(file);
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Only one image is allowed.' });
            });
        }
    });
    el.addEventListener('click', function (e) {
        if (myOneDropzone && myOneDropzone.files.length >= 1) {
            e.preventDefault();
            e.stopPropagation();
            Swal.fire({ icon: 'error', title: 'Oops...', text: 'Only one image is allowed.' });
        }
    });
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

function showAlertMessageSwal(icon, title, textMsg, okButton = true, timer = 0) {
    const swalOptions = {
        icon: icon,
        title: title,
        text: textMsg,
    };
    if (timer) {
        swalOptions.timer = typeof timer === 'number' ? timer : 1500;
    }
    if(okButton) {
        swalOptions.showConfirmButton = true;
    }
    Swal.fire(swalOptions);
}

// Add this function in your customers.js or common validation file
function validateMobileNumber(mobileValue, countryCode = '91') {
    if (!mobileValue || mobileValue.trim() === '') {
        return { isValid: true, message: '' };
    }
    
    // Remove all non-digit characters
    const cleanMobile = mobileValue.replace(/\D/g, '');
    
    // Remove leading zeros
    const normalizedMobile = cleanMobile.replace(/^0+/, '');
    
    // Country specific validation rules
    const countryRules = {
        '91': { // India
            regex: /^[6-9]\d{9}$/,
            message: 'Invalid Indian mobile number. Must be 10 digits starting with 6-9.'
        },
        '1': { // USA/Canada
            regex: /^\d{10}$/,
            message: 'Invalid US/Canada mobile number. Must be 10 digits.'
        },
        '44': { // UK
            regex: /^\d{10,11}$/,
            message: 'Invalid UK mobile number. Must be 10-11 digits.'
        },
        '61': { // Australia
            regex: /^\d{9}$/,
            message: 'Invalid Australian mobile number. Must be 9 digits.'
        },
        '971': { // UAE
            regex: /^\d{9}$/,
            message: 'Invalid UAE mobile number. Must be 9 digits.'
        }
    };
    
    // Get rule for country or use default
    const rule = countryRules[countryCode] || { 
        regex: /^\d{5,15}$/,
        message: 'Invalid mobile number for selected country. Must be 5-15 digits.'
    };
    
    const isValid = rule.regex.test(normalizedMobile);
    
    return {
        isValid: isValid,
        message: isValid ? '' : rule.message
    };
}

function validatePANNumber(panValue) {
    if (!panValue || panValue.trim() === '') {
        return { isValid: true, message: '' };
    }
    
    const cleanPAN = panValue.trim().toUpperCase();
    
    // PAN format: ABCDE1234F (5 letters, 4 digits, 1 letter)
    const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
    const isValid = panRegex.test(cleanPAN);
    
    return {
        isValid: isValid,
        message: isValid ? '' : 'Invalid PAN number. Format must be: ABCDE1234F (5 letters, 4 digits, 1 letter).'
    };
}

function validateGSTIN(gstinValue) {
    if (!gstinValue || gstinValue.trim() === '') {
        return { isValid: true, message: '' };
    }
    
    const cleanGSTIN = gstinValue.trim().toUpperCase();
    
    // Check length
    if (cleanGSTIN.length !== 15) {
        return {
            isValid: false,
            message: 'GSTIN must be exactly 15 characters long.'
        };
    }
    
    // GSTIN format: 22AAAAA0000A1Z5
    const gstinRegex = /^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
    const isValid = gstinRegex.test(cleanGSTIN);
    
    if (!isValid) {
        return {
            isValid: false,
            message: 'Invalid GSTIN format. Must be: 22AAAAA0000A1Z5 (15 characters with valid state code and PAN).'
        };
    }
    
    // Validate state code (01-37)
    const stateCode = cleanGSTIN.substring(0, 2);
    const stateCodeInt = parseInt(stateCode, 10);
    if (stateCodeInt < 1 || stateCodeInt > 37) {
        return {
            isValid: false,
            message: 'Invalid state code in GSTIN. State code must be between 01 and 37.'
        };
    }
    
    // Validate PAN part (first 10 chars after state code)
    const panPart = cleanGSTIN.substring(2, 12);
    const panValidation = validatePANNumber(panPart);
    if (!panValidation.isValid) {
        return {
            isValid: false,
            message: 'Invalid PAN number in GSTIN. ' + panValidation.message
        };
    }
    
    return {
        isValid: true,
        message: ''
    };
}

// ── Toast Notification ───────────────────────────────────────────────────
// Usage: showToastNotification('Your message here', 'success' | 'error' | 'info')
function showToastNotification(message, type) {
    var _id    = 'r2k-toast-' + Date.now();
    var _color = type === 'success' ? '#198754' : (type === 'error' ? '#dc3545' : '#0d6efd');
    var _icon  = type === 'success' ? 'bx-check-circle' : (type === 'error' ? 'bx-x-circle' : 'bx-info-circle');
    var _html  =
        '<div id="' + _id + '" class="r2k-toast-notify" style="border-left-color:' + _color + ';">' +
            '<i class="bx ' + _icon + ' r2k-toast-icon" style="color:' + _color + ';"></i>' +
            '<span class="r2k-toast-msg">' + message + '</span>' +
            '<button class="r2k-toast-close" onclick="$(this).closest(\'.r2k-toast-notify\').remove()">&times;</button>' +
            '<div class="r2k-toast-bar" style="background:' + _color + ';"></div>' +
        '</div>';
    if (!$('#r2k-toast-wrap').length) {
        $('body').append('<div id="r2k-toast-wrap"></div>');
    }
    $('#r2k-toast-wrap').append(_html);
    var $el = $('#' + _id);
    setTimeout(function() { $el.addClass('r2k-toast-show'); }, 10);
    setTimeout(function() {
        $el.removeClass('r2k-toast-show');
        setTimeout(function() { $el.remove(); }, 350);
    }, 2500);
}

// ── Copy Mobile Number ────────────────────────────────────────────────────
// Usage: add class="copy-mobile" and data-mobile="number" to any element.
// Tooltip text "Click to copy mobile number" is set via data-bs-title.
// Works globally across all pages.

function copyMobileNumber(mobile) {
    if (!mobile) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(mobile).then(function () {
            showToastNotification('Mobile number copied!', 'success');
        });
    } else {
        var $tmp = $('<input>').val(mobile).appendTo('body').select();
        document.execCommand('copy');
        $tmp.remove();
        showToastNotification('Mobile number copied!', 'success');
    }
}

// Click handler — works on dynamically rendered rows (AJAX pagination)
$(document).on('click', '.copy-mobile', function () {
    copyMobileNumber($(this).data('mobile'));
});

// Tooltip init for dynamically rendered .copy-mobile elements
$(document).on('mouseenter', '.copy-mobile', function () {
    if (!bootstrap.Tooltip.getInstance(this)) {
        new bootstrap.Tooltip(this, { trigger: 'hover' });
        $(this).tooltip('show');
    }
});

function toastSuccess(msg) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 2500, timerProgressBar: true });
}
function toastError(msg) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: msg, showConfirmButton: false, timer: 3000 });
}

/* ══════════════════════════════════════════════════════════════════
   ApexHeader — User dropdown + Quick Access modal (Ctrl+K)
   Encapsulates all apex page-header behaviour; loaded once via default.js.
   ══════════════════════════════════════════════════════════════════ */
var ApexHeader = (function ($) {
    'use strict';

    // Per-section color palette (cycles if there are more sections than entries)
    var _pal = [
        { c: '#696cff', bg: 'rgba(105,108,255,.10)' },
        { c: '#3b82f6', bg: 'rgba(59,130,246,.10)'  },
        { c: '#10b981', bg: 'rgba(16,185,129,.10)'  },
        { c: '#f97316', bg: 'rgba(249,115,22,.10)'  },
        { c: '#8b5cf6', bg: 'rgba(139,92,246,.10)'  },
        { c: '#f59e0b', bg: 'rgba(245,158,11,.10)'  },
        { c: '#14b8a6', bg: 'rgba(20,184,166,.10)'  },
        { c: '#ef4444', bg: 'rgba(239,68,68,.10)'   },
        { c: '#ec4899', bg: 'rgba(236,72,153,.10)'  },
        { c: '#0ea5e9', bg: 'rgba(14,165,233,.10)'  },
    ];

    function _esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function _setUserDropdown(open) {
        $('#apexUserDropdown').toggleClass('open', open);
        $('#apexUserBtn .apex-user-caret')
            .toggleClass('bx-chevron-up',   open)
            .toggleClass('bx-chevron-down', !open);
    }

    function _buildQAContent() {
        var data = (typeof window._APEX_QA_DATA !== 'undefined') ? window._APEX_QA_DATA : [];
        var $body = $('#apexQABody');
        if (!$body.length) return;
        if (!data || !data.length) {
            $body.html('<div class="r2k-qs-empty"><i class="bx bx-grid-alt"></i><p>No modules available.</p></div>');
            return;
        }
        var html = '';
        for (var i = 0; i < data.length; i++) {
            var sec  = data[i];
            var pal  = _pal[i % _pal.length];
            var mods = sec.modules || [];
            if (!mods.length) continue;
            var secIcon = sec.icon || 'bx bx-grid-alt';
            html += '<div class="r2k-qs-section" data-section-idx="' + i + '" style="--qs-clr:' + pal.c + ';--qs-clr-bg:' + pal.bg + ';">';
            html +=   '<div class="r2k-qs-section-head">';
            html +=     '<div class="r2k-qs-section-icon"><i class="' + _esc(secIcon) + '"></i></div>';
            html +=     '<div class="r2k-qs-section-name">' + _esc(sec.name) + '</div>';
            html +=     '<div class="r2k-qs-section-badge">' + mods.length + '</div>';
            html +=   '</div>';
            html +=   '<div class="r2k-qs-grid">';
            for (var j = 0; j < mods.length; j++) {
                var mod  = mods[j];
                var icon = mod.icon || 'bx bx-file-blank';
                html += '<a href="' + _esc(mod.url) + '" class="r2k-qs-item" data-name="' + _esc(mod.name.toLowerCase()) + '">';
                html +=   '<div class="r2k-qs-item-icon"><i class="' + _esc(icon) + '"></i></div>';
                html +=   '<div class="r2k-qs-item-name">' + _esc(mod.name) + '</div>';
                html += '</a>';
            }
            html +=   '</div>';
            html += '</div>';
        }
        $body.html(html || '<div class="r2k-qs-empty"><i class="bx bx-search-alt"></i><p>No modules found.</p></div>');
    }

    function _filterQA(q) {
        var term = (q || '').toLowerCase().trim();
        var $body = $('#apexQABody');
        if (!$body.length) return;

        if (!term) {
            $body.find('.r2k-qs-section').show();
            $body.find('.r2k-qs-item').show();
            $body.find('.r2k-qs-empty-search').remove();
            return;
        }

        var anyVisible = false;
        $body.find('.r2k-qs-section').each(function () {
            var $sec       = $(this);
            var secName    = $sec.find('.r2k-qs-section-name').text().toLowerCase();
            var secMatch   = secName.indexOf(term) !== -1;
            var anyItemVis = false;

            $sec.find('.r2k-qs-item').each(function () {
                var $item     = $(this);
                var itemMatch = $item.data('name').indexOf(term) !== -1;
                $item.toggle(secMatch || itemMatch);
                if (secMatch || itemMatch) anyItemVis = true;
            });

            $sec.toggle(anyItemVis);
            if (anyItemVis) anyVisible = true;
        });

        $body.find('.r2k-qs-empty-search').remove();
        if (!anyVisible) {
            $body.append('<div class="r2k-qs-empty r2k-qs-empty-search"><i class="bx bx-search-alt"></i><p>No results for &ldquo;' + _esc(q) + '&rdquo;</p></div>');
        }
    }

    function _openQA() {
        $('#apexQuickAccessModal').addClass('open');
        setTimeout(function () {
            var el = document.getElementById('apexQuickSearchInput');
            if (el) el.focus();
        }, 60);
    }

    function _closeQA() {
        $('#apexQuickAccessModal').removeClass('open');
        var $inp = $('#apexQuickSearchInput');
        $inp.val('');
        _filterQA('');
    }

    function init() {
        if (!$('#apexUserBtn').length && !$('#apexHeaderSearch').length) return;

        // Build Quick Access content from pre-loaded PHP data
        _buildQAContent();

        // ── User dropdown ────────────────────────────────────────────────
        $(document).on('click', '#apexUserBtn', function (e) {
            e.stopPropagation();
            _setUserDropdown(!$('#apexUserDropdown').hasClass('open'));
        });
        $(document).on('click.apexUserClose', function (e) {
            if (!$(e.target).closest('#apexUserWrap').length) {
                _setUserDropdown(false);
            }
        });

        // ── Quick Access: open ───────────────────────────────────────────
        $(document).on('click', '#apexHeaderSearch', _openQA);
        $(document).on('keydown.apexQA', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                _openQA();
            }
            if (e.key === 'Escape' && $('#apexQuickAccessModal').hasClass('open')) {
                _closeQA();
            }
        });

        // ── Quick Access: real-time search ──────────────────────────────
        $(document).on('input', '#apexQuickSearchInput', function () {
            _filterQA($(this).val());
        });

        // ── Quick Access: close ──────────────────────────────────────────
        $(document).on('click', '#apexQuickAccessClose', _closeQA);
        $(document).on('click', '#apexQuickAccessModal', function (e) {
            if ($(e.target).is('#apexQuickAccessModal')) _closeQA();
        });
    }

    return {
        init            : init,
        openQuickAccess : _openQA,
        closeQuickAccess: _closeQA
    };

}(jQuery));

// ── Auto-dropup for 3-dot actions menus ──────────────────────────────────
// When the dropdown would overflow the bottom of the viewport, flip it upward.
$(document).on('show.bs.dropdown', '.dropdown', function () {
    var $toggle = $(this).find('.trans-actions-btn');
    if (!$toggle.length) return;
    var $menu   = $(this).find('.dropdown-menu');
    var rect    = $toggle[0].getBoundingClientRect();
    var menuH   = $menu.outerHeight(true) || 260;
    var spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < menuH) {
        $(this).addClass('dropup');
    } else {
        $(this).removeClass('dropup');
    }
});

// ── r2k search bar — universal expand + clear icon ───────────────────────
// Expand on focus
$(document).on('focus', '.r2k-search-wrap input', function () {
    $(this).closest('.r2k-search-wrap').addClass('is-expanded');
});

// Shrink when blurred with empty value; keep expanded if text is present
$(document).on('blur', '.r2k-search-wrap input', function () {
    if (!$.trim($(this).val())) {
        $(this).closest('.r2k-search-wrap').removeClass('is-expanded');
    }
});

// Show/hide clear icon as user types; expand and highlight while typing
$(document).on('input', '.r2k-search-wrap input', function () {
    var hasVal = !!$.trim($(this).val());
    var $wrap  = $(this).closest('.r2k-search-wrap');
    $wrap.find('.r2k-clear').toggleClass('d-none', !hasVal);
    $wrap.toggleClass('is-expanded r2k-search-active', hasVal);
    if (!hasVal) $wrap.removeClass('r2k-search-active');
});

// Clear icon click:
//   - Pages that already have their own clear handler (icon has an id): only do
//     housekeeping (wipe value, hide icon, collapse) — the page's own click handler
//     already fires first and handles the data reload.
//   - Transaction list pages (icon has no id): no dedicated handler exists, so we
//     trigger 'input' here which fires each page's debounced search handler.
$(document).on('click', '.r2k-clear', function () {
    var $wrap    = $(this).closest('.r2k-search-wrap');
    var $input   = $wrap.find('input');
    var hasOwnId = !!$(this).attr('id'); // id means the page wires its own reload

    $input.val('');
    $wrap.find('.r2k-clear').addClass('d-none');
    $wrap.removeClass('is-expanded r2k-search-active');

    if (!hasOwnId) {
        // No page-specific clear handler — fire input to trigger the search reload
        $input.trigger('input');
    }
});

// ── Stats strip toggle ────────────────────────────────────────────────
$(function () {
    var $strip = $('.apex-stats-strip');
    if (!$strip.length) return;

    var isOpen = (typeof R2K_STATS_DEFAULT_OPEN !== 'undefined') ? !!R2K_STATS_DEFAULT_OPEN : true;

    // Two-level wrap:
    //   .apex-stats-wrap  → position:relative anchor for the toggle buttons
    //     .apex-stats-outer → plain block div; THIS gets animated by jQuery
    //       .apex-stats-strip → always display:flex; never touched by jQuery
    // Animating a block div avoids the jQuery slideDown bug where it restores
    // display:flex elements as display:block, breaking the horizontal flex layout.
    $strip.wrap('<div class="apex-stats-outer"></div>');
    var $outer = $strip.parent('.apex-stats-outer');
    $outer.wrap('<div class="apex-stats-wrap"></div>');
    var $wrap = $outer.parent('.apex-stats-wrap');

    var $openBtn  = $('<button type="button" class="apex-stats-toggle" id="statsOpenToggle"  title="Show stats"><i class="bx bx-chevron-down"></i></button>');
    var $closeBtn = $('<button type="button" class="apex-stats-toggle" id="statsCloseToggle" title="Hide stats"><i class="bx bx-chevron-up"></i></button>');

    $('.apex-page-header').append($openBtn);
    $wrap.append($closeBtn);

    if (isOpen) {
        // Strip visible from server render — just wire the buttons
        $openBtn.hide();
        $closeBtn.show();
    } else {
        // Server-side CSS already hides the strip (no flash).
        // Restore strip to flex inline so it renders correctly inside the outer when later shown,
        // then hide the outer — both run synchronously before first paint.
        $strip.css('display', 'flex');
        $outer.hide();
        $openBtn.show();
        $closeBtn.hide();
    }

    // Animate the outer block wrapper — strip's display:flex is never changed
    $openBtn.on('click', function () {
        $openBtn.hide();
        $outer.slideDown(240, function () { $closeBtn.show(); });
    });

    $closeBtn.on('click', function () {
        $closeBtn.hide();
        $outer.slideUp(240, function () { $openBtn.show(); });
    });
});
