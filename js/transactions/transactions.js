$(document).ready(function () {
    'use strict'

    $('#toggleChargesBtn').on('click', function (e) {
        e.preventDefault();
        const box = $('#additionalChargesBox');
        const icon = $(this).find('i');

        box.toggleClass('d-none');

        if (box.hasClass('d-none')) {
            icon.removeClass('bx-minus-circle').addClass('bx-plus-circle');
            $(this).text(' Additional Charges').prepend(icon);
        } else {
            icon.removeClass('bx-plus-circle').addClass('bx-minus-circle');
            $(this).text(' Hide Charges').prepend(icon);
        }
    });

});

function searchCustomers(key) {
    $("#"+key).select2({
        placeholder: "Search Customer by Name, Email, Mobile, GSTIN, Company, Contact Person.",
        minimumInputLength: 0,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        ajax: {
            url: '/transactions/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, type: 'public' };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        var data = e.params.data;
        if (data.address) {
            var addrHtml = `
                <div><strong>Shipping Address:</strong></div>
                <div>${data.address.Line1 || ''}</div>
                <div>${data.address.Line2 || ''}</div>
                <div>${data.address.City || ''}, ${data.address.State || ''} - ${data.address.Pincode || ''}</div>
            `;
            $("#customerAddressBox").html(addrHtml).removeClass('d-none');
        } else {
            $("#customerAddressBox").addClass('d-none').empty();
        }
    }).on('select2:clear', function () {
        $("#customerAddressBox").addClass('d-none').empty();
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}

function transDatePickr(
    FieldName,
    IsModal = '',
    dateFormat = 'Y-m-d',
    restrictPastDate = false,
    restrictFutureDate = false,
    setTodaysDate = false,
    useAltInput = true,
    altFormat = 'd-m-Y',
    minDateField = '',
) {

    const el = document.querySelector(FieldName);
    if (!el) return;

    const existingVal = el.value?.trim() || '';

    const options = {
        dateFormat,
        altInput: useAltInput,
        altFormat,
        allowInput: false,
        clickOpens: true
    };
    if (IsModal) {
        const container = document.querySelector(IsModal + ' .modal-body');
        if (container) options.appendTo = container;
    }
    if (restrictPastDate) options.minDate = 'today';
    if (restrictFutureDate) options.maxDate = 'today';

    if (existingVal) {
        options.defaultDate = existingVal;
    } else if (setTodaysDate) {
        const today = new Date();
        const pad = n => String(n).padStart(2, '0');
        const yyyy = today.getFullYear();
        const mm = pad(today.getMonth() + 1);
        const dd = pad(today.getDate());
        options.defaultDate =
        dateFormat === 'Y-m-d' ? `${yyyy}-${mm}-${dd}` :
        dateFormat === 'd-m-Y' ? `${dd}-${mm}-${yyyy}` :
        dateFormat === 'm/d/Y' ? `${mm}/${dd}/${yyyy}` :
        today;
    }
    if (minDateField) {
        const refEl = document.querySelector(minDateField);
        const refVal = refEl?.value?.trim();
        if (refVal) options.minDate = refVal;
    }
    if (el._flatpickr) {
        el._flatpickr.destroy();
    }
    flatpickr(FieldName, options);
}

function setupTransactionValidity(quotationSel, validityDaysSel, validityDateSel) {

    const quotationEl    = document.querySelector(quotationSel);
    const validityDaysEl = document.querySelector(validityDaysSel);
    const validityDateEl = document.querySelector(validityDateSel);

    if (!quotationEl || !validityDaysEl || !validityDateEl) return;
    if (!quotationEl._flatpickr || !validityDateEl._flatpickr) return;

    const qPicker = quotationEl._flatpickr;
    const vPicker = validityDateEl._flatpickr;

    // Ensure validityDate can't be before quotationDate
    function enforceMinDate() {
        const qDate = qPicker.selectedDates[0];
        if (qDate) vPicker.set('minDate', qDate);
    }

    // Compute validity date = quotation date + days
    function updateValidityDateFromDays() {
        const qDate = qPicker.selectedDates[0];
        const days = parseInt(validityDaysEl.value, 10) || 0;
        if (!qDate) return;

        const newDate = new Date(qDate);
        newDate.setDate(newDate.getDate() + days);

        enforceMinDate();
        vPicker.setDate(newDate, true);
    }

    // Compute validity days from validity date (positive only)
    function updateDaysFromValidityDate(selectedDates) {
        const vDate = selectedDates[0] || vPicker.selectedDates[0];
        const qDate = qPicker.selectedDates[0];
        if (!vDate || !qDate) return;

        const diff = Math.round((vDate - qDate) / (1000 * 60 * 60 * 24));

        // Positive-only rule: clamp at 0
        validityDaysEl.value = Math.max(diff, 0);

        // If user picked a date before quotation date, snap back to minDate
        if (diff < 0) {
        vPicker.setDate(qDate, true);
        }
    }

    // Events
    qPicker.set('onChange', updateValidityDateFromDays);
    vPicker.set('onChange', updateDaysFromValidityDate);
    validityDaysEl.addEventListener('input', updateValidityDateFromDays);

    // Initial sync
    enforceMinDate();
    updateValidityDateFromDays();

}

function searchProductInfo() {
    $('#searchProductInfo').select2({
        width: '100%',
        dropdownParent: $('#searchProductGroup')
    });

}