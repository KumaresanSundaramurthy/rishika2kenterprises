'use strict';

// ── Flatpickr instances ───────────────────────────────────────────────────────
var _rntFpStart  = null;
var _rntFpReturn = null;
var _rntFpRtnDT  = null;

// ── Machine row counter ───────────────────────────────────────────────────────
var _rntRowIdx = 0;

// ── In-memory cart of machine rows {idx: {ProductUID, ItemName, Qty, RentalType, rates...}} ──
var _rntMachines = {};

// ── Pagination ────────────────────────────────────────────────────────────────
function rntLoadPage(pageNo) {
    var $body = $('#rntTableBody');
    $body.html('<tr><td colspan="11" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');

    $.ajax({
        url:    '/rental/getPageDetails/' + (pageNo || 1),
        method: 'POST',
        data: {
            RowLimit:   RowLimit || 10,
            Filter:     JSON.stringify(RntFilter),
            [CsrfName]: CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
                $body.html('<tr><td colspan="11" class="text-center text-danger py-3">Failed to load data.</td></tr>');
                return;
            }
            $body.html(r.RecordHtmlData);
            $('.rntPagination').html(r.Pagination);
            $('.rnt-status-tab.active .rnt-tab-count').text(r.TotalCount > 0 ? r.TotalCount : '').removeClass('d-none');
            if (r.Stats) rntUpdateStats(r.Stats);
        },
        error: function () {
            $body.html('<tr><td colspan="11" class="text-center text-danger py-3">Request failed.</td></tr>');
        }
    });
}

// ── Update stat cards ─────────────────────────────────────────────────────────
function rntUpdateStats(s) {
    if (!s) return;
    var fmt = function (v) {
        return RntCurrency + ' ' + Number(v || 0).toLocaleString('en-IN', {
            minimumFractionDigits: RntDecimals,
            maximumFractionDigits: RntDecimals,
        });
    };
    $('#statTotalCount').text(Number(s.totalCount || 0).toLocaleString());
    $('#statTotalRevenue').text(fmt(s.totalRevenue));
    $('#statActiveCount').text(Number(s.activeCount || 0).toLocaleString());
    var ovd = parseInt(s.overdueCount || 0);
    $('#statOverdue').text(ovd > 0 ? ovd + ' overdue' : 'None overdue')
                     .css('color', ovd > 0 ? '#dc2626' : 'inherit');
    $('#statClosedCount').text(Number(s.closedCount || 0).toLocaleString());
    $('#statDeposit').text(fmt(s.totalDeposit) + ' deposit');
    $('#statBalance').text(fmt(s.totalBalance));
}

// ── Open Create modal ─────────────────────────────────────────────────────────
function rntOpenCreate() {
    // Reset state
    _rntMachines = {};
    _rntRowIdx   = 0;

    $('#rntCustomerUID').val(null).trigger('change');
    $('#rntNotes').val('');
    $('#rntDepositCollected').val('0');
    $('#rntDepositPayWrap').addClass('d-none');
    $('#rntDepositPayType').val('');
    $('#rntDepositBankUID').val('');
    $('#rntMachineRows').html(
        '<tr id="rntNoMachinesRow"><td colspan="6" class="text-center text-muted py-4" style="font-size:.85rem;">' +
        '<i class="bx bx-cycling fs-4 d-block mb-2"></i>Click "Add Machine" to add rentable equipment</td></tr>'
    );
    rntCalcSummary();

    // Init flatpickr
    var now = new Date();
    var startStr = flatpickr.formatDate(now, 'Y-m-d H:i');
    var dueStr   = flatpickr.formatDate(new Date(now.getTime() + 8 * 3600 * 1000), 'Y-m-d H:i');

    if (_rntFpStart) {
        _rntFpStart.setDate(startStr, true);
    } else {
        _rntFpStart = flatpickr('#rntStartDateTime', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            altInput:   true,
            altFormat:  'd M Y, h:i K',
            defaultDate: startStr,
            time_24hr:  false,
            disableMobile: true,
        });
    }

    if (_rntFpReturn) {
        _rntFpReturn.setDate(dueStr, true);
    } else {
        _rntFpReturn = flatpickr('#rntReturnDueDateTime', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            altInput:   true,
            altFormat:  'd M Y, h:i K',
            defaultDate: dueStr,
            time_24hr:  false,
            disableMobile: true,
        });
    }

    // Init customer Select2
    if (!$('#rntCustomerUID').hasClass('select2-hidden-accessible')) {
        $('#rntCustomerUID').select2({
            placeholder: 'Search customer by name or mobile...',
            minimumInputLength: 0,
            allowClear: true,
            dropdownParent: $('#rntCreateModal'),
            escapeMarkup: function (m) { return m; },
            templateResult: function (d) {
                if (d.loading || !d.name) return d.text;
                return '<div><strong>' + _rntEsc(d.name) + '</strong>' +
                    (d.mobile ? '<small class="text-muted ms-2">' + _rntEsc(d.mobile) + '</small>' : '') + '</div>';
            },
            templateSelection: function (d) {
                return d.name ? _rntEsc(d.name) : d.text;
            },
            ajax: {
                url:      '/transactions/searchCustomers',
                dataType: 'json',
                delay:    250,
                data:     function (p) { return { term: p.term, type: 'public', [CsrfName]: CsrfToken }; },
                processResults: function (data) {
                    return {
                        results: (data.Lists || []).map(function (c) {
                            return { id: c.id, text: c.name, name: c.name, mobile: c.mobile };
                        })
                    };
                },
                cache: true,
            },
        });
    }

    // Populate deposit payment type dropdown
    var $dpType = $('#rntDepositPayType').empty().append('<option value="">— Select method —</option>');
    var $dpBank = $('#rntDepositBankUID').empty().append('<option value="">— Select bank —</option>');
    if (typeof _rntPayTypes !== 'undefined') {
        _rntPayTypes.forEach(function (t) {
            $dpType.append('<option value="' + t.PaymentTypeUID + '" data-is-cash="' + t.IsCash + '">' + _rntEsc(t.Name) + '</option>');
        });
    }
    if (typeof _rntBankAccts !== 'undefined') {
        _rntBankAccts.forEach(function (b) {
            $dpBank.append('<option value="' + b.BankAccountUID + '">' + _rntEsc(b.BankName) + ' — ' + _rntEsc(b.AccountName) + '</option>');
        });
    }

    new bootstrap.Modal(document.getElementById('rntCreateModal')).show();
}

// ── Add machine row ───────────────────────────────────────────────────────────
function rntAddMachineRow(product) {
    $('#rntNoMachinesRow').remove();

    var idx = ++_rntRowIdx;
    var rate = _rntGetRate(product, 'Hourly');

    _rntMachines[idx] = {
        ProductUID:              product.ProductUID,
        ItemName:                product.ItemName,
        Qty:                     1,
        RentalType:              'Hourly',
        SecurityDeposit:         parseFloat(product.SecurityDeposit  || 0),
        HourlyRate:              parseFloat(product.HourlyRate        || 0),
        HalfDayRate:             parseFloat(product.HalfDayRate       || 0),
        FullDayRate:             parseFloat(product.FullDayRate        || 0),
        FixedPackageRate:        parseFloat(product.FixedPackageRate  || 0),
        ExtraHourRate:           parseFloat(product.ExtraHourRate     || 0),
        LateReturnChargePerHour: parseFloat(product.LateReturnChargePerHour || 0),
        BaseRentalCharge:        rate,
    };

    var html = '<tr id="rntRow_' + idx + '" data-idx="' + idx + '">'
        + '<td style="padding:.4rem .75rem;">'
        +   '<div style="font-weight:500;font-size:.82rem;">' + _rntEsc(product.ItemName) + '</div>'
        +   (product.PartNumber ? '<div class="text-muted" style="font-size:.7rem;">P/N: ' + _rntEsc(product.PartNumber) + '</div>' : '')
        + '</td>'
        + '<td style="padding:.4rem .5rem;">'
        +   '<input type="number" class="form-control form-control-sm rntMachineQty" data-idx="' + idx + '" value="1" min="1" step="1" style="width:60px;">'
        + '</td>'
        + '<td style="padding:.4rem .5rem;">'
        +   '<select class="form-select form-select-sm rntMachineType" data-idx="' + idx + '" style="min-width:110px;">'
        +   '<option value="Hourly"' + (rate === parseFloat(product.HourlyRate || 0) ? ' selected' : '') + '>Hourly</option>'
        +   '<option value="HalfDay">Half Day</option>'
        +   '<option value="FullDay">Full Day</option>'
        +   '<option value="Fixed">Fixed</option>'
        +   '</select>'
        + '</td>'
        + '<td style="padding:.4rem .5rem;text-align:right;">'
        +   '<span class="rntMachineRate" data-idx="' + idx + '" style="font-size:.82rem;font-weight:500;">'
        +   RntCurrency + ' ' + rate.toFixed(RntDecimals)
        +   '</span>'
        + '</td>'
        + '<td style="padding:.4rem .5rem;text-align:right;">'
        +   '<span class="rntMachineCharge" data-idx="' + idx + '" style="font-size:.82rem;font-weight:600;color:#7c3aed;">'
        +   RntCurrency + ' ' + rate.toFixed(RntDecimals)
        +   '</span>'
        + '</td>'
        + '<td style="padding:.4rem .5rem;text-align:center;">'
        +   '<button type="button" class="btn btn-icon btn-sm text-danger rntRemoveMachine" data-idx="' + idx + '" title="Remove"><i class="bx bx-x fs-5"></i></button>'
        + '</td>'
        + '</tr>';

    $('#rntMachineRows').append(html);
    rntCalcSummary();
}

// ── Get rate for selected rental type ─────────────────────────────────────────
function _rntGetRate(m, type) {
    var map = {
        Hourly:  parseFloat(m.HourlyRate       || m.hourlyRate       || 0),
        HalfDay: parseFloat(m.HalfDayRate      || m.halfDayRate      || 0),
        FullDay: parseFloat(m.FullDayRate       || m.fullDayRate       || 0),
        Fixed:   parseFloat(m.FixedPackageRate  || m.fixedPackageRate  || 0),
    };
    return map[type] || 0;
}

// ── Recalculate summary ───────────────────────────────────────────────────────
function rntCalcSummary() {
    var totalCharge  = 0;
    var totalDeposit = 0;

    Object.keys(_rntMachines).forEach(function (idx) {
        var m = _rntMachines[idx];
        totalCharge  += m.BaseRentalCharge * m.Qty;
        totalDeposit += m.SecurityDeposit  * m.Qty;
    });

    var deposit = parseFloat($('#rntDepositCollected').val()) || 0;
    var balance = totalCharge - deposit;

    var fmt = function (v) {
        return RntCurrency + ' ' + v.toLocaleString('en-IN', {
            minimumFractionDigits: RntDecimals,
            maximumFractionDigits: RntDecimals,
        });
    };

    $('#rntSummaryRentalCharge').text(fmt(totalCharge));
    $('#rntSummaryGrandTotal').text(fmt(totalCharge));
    $('#rntSummaryBalance').text(fmt(balance));

    if (totalDeposit > 0) {
        $('#rntDepositSuggestion').text('Suggested deposit: ' + fmt(totalDeposit));
    } else {
        $('#rntDepositSuggestion').text('');
    }
}

// ── Submit create ─────────────────────────────────────────────────────────────
function rntSubmitCreate() {
    var customerUID = $('#rntCustomerUID').val();
    var startDT     = _rntFpStart  ? _rntFpStart.input.value  : $('#rntStartDateTime').val();
    var returnDT    = _rntFpReturn ? _rntFpReturn.input.value : $('#rntReturnDueDateTime').val();

    if (!customerUID) {
        Swal.fire({ icon: 'warning', text: 'Please select a customer.' });
        return;
    }
    if (!startDT) {
        Swal.fire({ icon: 'warning', text: 'Please select the rental start date/time.' });
        return;
    }
    if (!returnDT) {
        Swal.fire({ icon: 'warning', text: 'Please select the expected return date/time.' });
        return;
    }
    if (Object.keys(_rntMachines).length === 0) {
        Swal.fire({ icon: 'warning', text: 'Please add at least one machine.' });
        return;
    }

    var items = Object.keys(_rntMachines).map(function (idx) {
        return _rntMachines[idx];
    });

    var $btn = $('#rntCreateSaveBtn').prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    $.ajax({
        url:    '/rental/createRental',
        method: 'POST',
        data: {
            CustomerUID:             customerUID,
            RentalStartDateTime:     startDT,
            ReturnDueDateTime:       returnDT,
            DepositCollected:        $('#rntDepositCollected').val() || 0,
            DepositPaymentTypeUID:   $('#rntDepositPayType').val() || '',
            DepositBankAccountUID:   $('#rntDepositBankUID').val() || '',
            Notes:                   $('#rntNotes').val(),
            Items:                   JSON.stringify(items),
            [CsrfName]:              CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                bootstrap.Modal.getInstance(document.getElementById('rntCreateModal')).hide();
                Swal.fire({ icon: 'success', title: 'Rental Created', text: r.Message, timer: 2000, showConfirmButton: false });
                if (r.RecordHtmlData) { $('#rntTableBody').html(r.RecordHtmlData); $('.rntPagination').html(r.Pagination); }
                if (r.Stats) rntUpdateStats(r.Stats);
            }
        },
        error: function () {
            Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Rental');
        }
    });
}

// ── Open Return modal ─────────────────────────────────────────────────────────
function rntOpenReturn(rentalUID, rentalNum, itemUID, itemName, itemStatus) {
    if (itemStatus === 'Returned') {
        Swal.fire({ icon: 'info', text: itemName + ' has already been returned.' });
        return;
    }

    $('#rtnRentalUID').val(rentalUID);
    $('#rtnRentalItemUID').val(itemUID);
    $('#rtnItemLabel').text(itemName);
    $('#rtnRentalNum').text(rentalNum || '—');
    $('#rtnRentalType').text('—');
    $('#rtnStartDate').text('—');
    $('#rtnDueDate').text('—');
    $('#rtnReturnedQty').val(1);
    $('#rtnDamagedQty').val(0);
    $('#rtnExtraHourCharge').val(0);
    $('#rtnLateCharge').val(0);
    $('#rtnDamageCharge').val(0);
    $('#rtnReturnNotes').val('');
    rntCalcReturnTotal();

    // Fetch rental detail to populate banner
    $.post('/rental/getRentalDetail', { RentalUID: rentalUID, [CsrfName]: CsrfToken }, function (r) {
        if (!r.Error && r.Data) {
            var d = r.Data;
            if (d.RentalStartDateTime) $('#rtnStartDate').text(new Date(d.RentalStartDateTime).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}));
            if (d.ReturnDueDateTime)   $('#rtnDueDate').text(new Date(d.ReturnDueDateTime).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}));
            // Find the item
            if (d.Items) {
                d.Items.forEach(function (item) {
                    if (parseInt(item.RentalItemUID) === parseInt(itemUID)) {
                        $('#rtnRentalType').text(item.RentalType);
                    }
                });
            }
        }
    });

    // Init actual return datetime picker
    var nowStr = flatpickr.formatDate(new Date(), 'Y-m-d H:i');
    if (_rntFpRtnDT) {
        _rntFpRtnDT.setDate(nowStr, true);
    } else {
        _rntFpRtnDT = flatpickr('#rtnActualReturnDateTime', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            altInput:   true,
            altFormat:  'd M Y, h:i K',
            defaultDate: nowStr,
            time_24hr:  false,
            disableMobile: true,
            onChange: function (selectedDates) {
                _rntAutoCalcHours(selectedDates[0]);
            },
        });
    }

    new bootstrap.Modal(document.getElementById('rntReturnModal')).show();
}

// Auto-calculate hours when return time is set
function _rntAutoCalcHours(returnDate) {
    var rentalUID = $('#rtnRentalUID').val();
    if (!rentalUID || !returnDate) return;
    $.post('/rental/getRentalDetail', { RentalUID: rentalUID, [CsrfName]: CsrfToken }, function (r) {
        if (!r.Error && r.Data && r.Data.RentalStartDateTime) {
            var start  = new Date(r.Data.RentalStartDateTime);
            var hours  = (returnDate - start) / 3600000;
            $('#rtnActualHours').val(Math.max(0, hours).toFixed(1));
        }
    });
}

// ── Calculate return total ────────────────────────────────────────────────────
function rntCalcReturnTotal() {
    var extra  = parseFloat($('#rtnExtraHourCharge').val()) || 0;
    var late   = parseFloat($('#rtnLateCharge').val())      || 0;
    var damage = parseFloat($('#rtnDamageCharge').val())    || 0;
    var total  = extra + late + damage;
    $('#rtnTotalExtraCharges').text(
        RntCurrency + ' ' + total.toLocaleString('en-IN', {
            minimumFractionDigits: RntDecimals,
            maximumFractionDigits: RntDecimals,
        })
    );
}

// ── Submit return ─────────────────────────────────────────────────────────────
function rntSubmitReturn() {
    var rentalUID     = $('#rtnRentalUID').val();
    var rentalItemUID = $('#rtnRentalItemUID').val();
    var returnedQty   = parseInt($('#rtnReturnedQty').val()) || 0;
    var returnDT      = _rntFpRtnDT ? _rntFpRtnDT.input.value : $('#rtnActualReturnDateTime').val();

    if (!rentalUID || !rentalItemUID) {
        Swal.fire({ icon: 'warning', text: 'Invalid return request.' });
        return;
    }
    if (returnedQty <= 0) {
        Swal.fire({ icon: 'warning', text: 'Returned quantity must be at least 1.' });
        return;
    }
    if (!returnDT) {
        Swal.fire({ icon: 'warning', text: 'Please select the actual return date/time.' });
        return;
    }

    var $btn = $('#rtnSubmitBtn').prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    $.ajax({
        url:    '/rental/processReturn',
        method: 'POST',
        data: {
            RentalUID:            rentalUID,
            RentalItemUID:        rentalItemUID,
            ReturnedQty:          returnedQty,
            DamagedQty:           parseInt($('#rtnDamagedQty').val()) || 0,
            ActualReturnDateTime: returnDT,
            ActualHours:          parseFloat($('#rtnActualHours').val()) || 0,
            ExtraHourCharge:      parseFloat($('#rtnExtraHourCharge').val()) || 0,
            LateReturnCharge:     parseFloat($('#rtnLateCharge').val()) || 0,
            DamageCharge:         parseFloat($('#rtnDamageCharge').val()) || 0,
            ReturnNotes:          $('#rtnReturnNotes').val(),
            [CsrfName]:           CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                bootstrap.Modal.getInstance(document.getElementById('rntReturnModal')).hide();
                Swal.fire({ icon: 'success', title: 'Return Recorded', text: r.Message, timer: 2000, showConfirmButton: false });
                if (r.RecordHtmlData) { $('#rntTableBody').html(r.RecordHtmlData); $('.rntPagination').html(r.Pagination); }
                if (r.Stats) rntUpdateStats(r.Stats);
            }
        },
        error: function () {
            Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-undo me-1"></i>Record Return');
        }
    });
}

// ── HTML escape ───────────────────────────────────────────────────────────────
function _rntEsc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── DOM Ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Add Machine button — open product search popup
    $(document).on('click', '#rntAddMachineBtn', function () {
        rntShowProductSearch();
    });

    // Remove machine row
    $(document).on('click', '.rntRemoveMachine', function () {
        var idx = $(this).data('idx');
        delete _rntMachines[idx];
        $('#rntRow_' + idx).remove();
        if ($('#rntMachineRows tr').length === 0) {
            $('#rntMachineRows').html(
                '<tr id="rntNoMachinesRow"><td colspan="6" class="text-center text-muted py-4" style="font-size:.85rem;">' +
                '<i class="bx bx-cycling fs-4 d-block mb-2"></i>Click "Add Machine" to add rentable equipment</td></tr>'
            );
        }
        rntCalcSummary();
    });

    // Qty change
    $(document).on('change input', '.rntMachineQty', function () {
        var idx = $(this).data('idx');
        var qty = Math.max(1, parseInt($(this).val()) || 1);
        $(this).val(qty);
        if (_rntMachines[idx]) {
            _rntMachines[idx].Qty = qty;
            var charge = _rntMachines[idx].BaseRentalCharge * qty;
            $('.rntMachineCharge[data-idx="' + idx + '"]').text(RntCurrency + ' ' + charge.toFixed(RntDecimals));
        }
        rntCalcSummary();
    });

    // Rental type change
    $(document).on('change', '.rntMachineType', function () {
        var idx  = $(this).data('idx');
        var type = $(this).val();
        if (_rntMachines[idx]) {
            var rate = _rntGetRate(_rntMachines[idx], type);
            _rntMachines[idx].RentalType        = type;
            _rntMachines[idx].BaseRentalCharge  = rate;
            var qty    = _rntMachines[idx].Qty;
            var charge = rate * qty;
            $('.rntMachineRate[data-idx="'   + idx + '"]').text(RntCurrency + ' ' + rate.toFixed(RntDecimals));
            $('.rntMachineCharge[data-idx="' + idx + '"]').text(RntCurrency + ' ' + charge.toFixed(RntDecimals));
        }
        rntCalcSummary();
    });

    // Deposit change
    $(document).on('input change', '#rntDepositCollected', function () {
        var val = parseFloat($(this).val()) || 0;
        if (val > 0) {
            $('#rntDepositPayWrap').removeClass('d-none');
        } else {
            $('#rntDepositPayWrap').addClass('d-none');
        }
        rntCalcSummary();
    });

    // Deposit payment type change
    $(document).on('change', '#rntDepositPayType', function () {
        var isCash = $(this).find(':selected').data('is-cash');
        if (isCash == 1 || !$(this).val()) {
            $('#rntDepositBankWrap').addClass('d-none');
        } else {
            $('#rntDepositBankWrap').removeClass('d-none');
        }
    });

    // Create save button
    $(document).on('click', '#rntCreateSaveBtn', rntSubmitCreate);

    // Empty state new rental button
    $(document).on('click', '#rntNewBtnEmpty', function () {
        rntOpenCreate();
    });

    // Return modal charge inputs
    $(document).on('input change', '.rtnChargeInput', rntCalcReturnTotal);

    // Return submit button
    $(document).on('click', '#rtnSubmitBtn', rntSubmitReturn);

});

// ── Product search for create modal ──────────────────────────────────────────
function rntShowProductSearch() {
    // Simple inline search using AJAX
    Swal.fire({
        title: 'Select Machine / Equipment',
        input: 'text',
        inputPlaceholder: 'Search by name...',
        showCancelButton: true,
        confirmButtonText: 'Search',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: function (term) {
            return $.post('/rental/searchRentableProducts', { term: term, [CsrfName]: CsrfToken })
                .then(function (r) {
                    if (r.Error || !r.Products || !r.Products.length) {
                        Swal.showValidationMessage(r.Error ? r.Message : 'No rentable products found for "' + term + '"');
                        return null;
                    }
                    return r.Products;
                })
                .fail(function () {
                    Swal.showValidationMessage('Request failed.');
                    return null;
                });
        },
        allowOutsideClick: function () { return !Swal.isLoading(); },
    }).then(function (result) {
        if (!result.isConfirmed || !result.value) return;

        var products = result.value;
        if (products.length === 1) {
            rntAddMachineRow(products[0]);
            return;
        }

        // Multiple results: show selection list
        var opts = {};
        products.forEach(function (p, i) {
            opts[i] = _rntEsc(p.ItemName) + (p.PartNumber ? ' (' + p.PartNumber + ')' : '');
        });

        Swal.fire({
            title: 'Select Machine',
            input: 'select',
            inputOptions: opts,
            inputPlaceholder: '— Pick one —',
            showCancelButton: true,
            confirmButtonText: 'Add',
        }).then(function (sel) {
            if (sel.isConfirmed && sel.value !== '') {
                rntAddMachineRow(products[parseInt(sel.value)]);
            }
        });
    });
}
