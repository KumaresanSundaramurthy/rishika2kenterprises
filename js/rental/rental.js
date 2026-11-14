'use strict';

// ── Flatpickr instances ───────────────────────────────────────────────────────
var _rntFpStart  = null;
var _rntFpReturn = null;
var _rntFpRtnDT  = null;

// ── Machine row counter ───────────────────────────────────────────────────────
var _rntRowIdx = 0;

// ── In-memory cart of machine rows {idx: {ProductUID, ItemName, Qty, RentalType, rates...}} ──
var _rntMachines = {};

// ── Unsaved-changes tracking ──────────────────────────────────────────────────
var _rntIsDirty = false;
var _rtnIsDirty = false;

// ── Row builder ───────────────────────────────────────────────────────────────
/**
 * Build table rows HTML from NestJS rental list data.
 * @param {Array}  rows   Rental row objects from NestJS
 * @param {number} offset Zero-based serial number offset
 * @returns {string}
 */
function _buildRntRows(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="11"><div class="d-flex flex-column align-items-center py-5">'
            + '<span class="text-muted" style="font-size:var(--r2k-text-90);">No rentals found</span>'
            + '<button type="button" class="btn btn-primary btn-sm px-4 mt-3" id="rntNewBtnEmpty">'
            + '<i class="bx bx-plus me-1"></i>New Rental</button></div></td></tr>';
    }
    var showSerial = typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var dec = RntDecimals || 2;
    var statusBadge = {
        Active:            '<span class="badge bg-label-success">Active</span>',
        PartiallyReturned: '<span class="badge bg-label-warning">Partial</span>',
        Overdue:           '<span class="badge bg-label-danger">Overdue</span>',
        Closed:            '<span class="badge bg-label-secondary">Closed</span>',
        Cancelled:         '<span class="badge bg-label-dark">Cancelled</span>',
    };
    var fmt = function (v) {
        return RntCurrency + ' ' + parseFloat(v || 0).toLocaleString('en-IN', {
            minimumFractionDigits: dec,
            maximumFractionDigits: dec,
        });
    };
    var html = '';
    $.each(rows, function (i, r) {
        var serial     = offset + i + 1;
        var rentalUID  = parseInt(r.RentalUID || 0);
        var rentalNum  = _rntEsc(r.RentalNumber || '');
        var startDate  = r.RentalStartDateTime ? String(r.RentalStartDateTime).substring(0, 10) : '';
        var returnDue  = r.ReturnDueDateTime   ? String(r.ReturnDueDateTime).substring(0, 10)   : '';
        var rstatus    = r.RentalStatus  || '';
        var isClosed   = rstatus === 'Closed' || rstatus === 'Cancelled';
        var grandTotal = parseFloat(r.GrandTotal   || 0);
        var totalPaid  = parseFloat(r.TotalPaid    || 0);
        var balance    = parseFloat(r.BalanceAmount || 0);
        var custName   = _rntEsc(r.CustomerName || '');
        var machines   = r.Machines || [];

        var machHtml = '';
        if (machines.length) {
            $.each(machines, function (mi, m) {
                var isReturned = m.ItemStatus === 'Returned';
                machHtml += '<div class="d-flex align-items-center gap-1 mb-1">'
                    + '<span style="font-size:var(--r2k-text-82);">' + _rntEsc(m.ItemName || '') + ' &times;' + (m.Qty || 1) + '</span>';
                if (!isClosed && !isReturned) {
                    machHtml += '<button type="button" class="btn btn-icon btn-sm rntReturnBtn"'
                        + ' data-uid="' + rentalUID + '" data-num="' + rentalNum + '"'
                        + ' data-item-uid="' + (m.RentalItemUID || '') + '"'
                        + ' data-item-name="' + _rntEsc(m.ItemName || '') + '"'
                        + ' data-item-status="' + _rntEsc(m.ItemStatus || '') + '"'
                        + ' title="Process Return" style="padding:0 4px;">'
                        + '<i class="bx bx-undo text-primary" style="font-size:1rem;"></i></button>';
                } else if (isReturned) {
                    machHtml += '<span class="badge bg-label-secondary ms-1" style="font-size:var(--r2k-text-70);">Returned</span>';
                }
                machHtml += '</div>';
            });
        } else {
            machHtml = '<span class="text-muted">—</span>';
        }

        var actHtml = '<div class="dropdown">'
            + '<button type="button" class="btn btn-icon btn-sm dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">'
            + '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
            + '<ul class="dropdown-menu dropdown-menu-end">';
        if (!isClosed) {
            actHtml += '<li><a class="dropdown-item rntRecordPayBtn" href="javascript:void(0);"'
                + ' data-uid="' + rentalUID + '" data-num="' + rentalNum + '"'
                + ' data-date="' + _rntEsc(startDate) + '" data-customer="' + custName + '"'
                + ' data-total="' + grandTotal + '" data-paid="' + totalPaid + '" data-balance="' + balance + '">'
                + '<i class="bx bx-wallet me-2"></i>Record Payment</a></li>';
            actHtml += '<li><a class="dropdown-item rntCancelBtn text-danger" href="javascript:void(0);"'
                + ' data-uid="' + rentalUID + '" data-num="' + rentalNum + '">'
                + '<i class="bx bx-x-circle me-2"></i>Cancel Rental</a></li>';
        }
        actHtml += '</ul></div>';

        html += '<tr>'
            + '<td><div class="form-check mb-0"><input class="form-check-input" type="checkbox"></div></td>'
            + '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno">' + serial + '</td>'
            + '<td><div style="font-weight:600;font-size:var(--r2k-text-90);">' + rentalNum + '</div>'
            +     '<div class="text-muted" style="font-size:var(--r2k-text-80);">' + startDate + '</div></td>'
            + '<td>' + custName + '</td>'
            + '<td style="min-width:160px;">' + machHtml + '</td>'
            + '<td><div style="font-size:var(--r2k-text-82);">' + startDate + '</div>'
            +     '<div class="text-muted" style="font-size:var(--r2k-text-80);">Due: ' + returnDue + '</div></td>'
            + '<td>' + (statusBadge[rstatus] || '<span class="badge bg-label-secondary">' + _rntEsc(rstatus) + '</span>') + '</td>'
            + '<td class="text-end"><span style="font-size:var(--r2k-text-90);">' + fmt(grandTotal) + '</span></td>'
            + '<td class="text-end"><span style="font-size:var(--r2k-text-90);">' + fmt(totalPaid) + '</span></td>'
            + '<td class="text-end"><span style="font-size:var(--r2k-text-90);color:' + (balance > 0 ? '#d97706' : 'inherit') + ';">' + fmt(balance) + '</span></td>'
            + '<td>' + actHtml + '</td>'
            + '</tr>';
    });
    return html;
}

/**
 * Build pagination HTML from NestJS response values.
 * @param {number} total  Total record count
 * @param {number} page   Current page (1-based)
 * @param {number} limit  Rows per page
 * @returns {string}
 */
function _buildRntPagination(total, page, limit) {
    if (!total || total <= limit) return '';
    var pages = Math.ceil(total / limit);
    var from  = (page - 1) * limit + 1;
    var to    = Math.min(page * limit, total);
    var html  = '<div class="col-auto text-muted" style="font-size:var(--r2k-text-82);">' + from + '–' + to + ' of ' + total + '</div>'
              + '<div class="col-auto"><ul class="pagination pagination-sm mb-0">';
    html += page > 1
        ? '<li class="page-item"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>'
        : '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    var start = Math.max(1, Math.min(page - 2, pages - 4));
    var end   = Math.min(pages, start + 4);
    for (var p = start; p <= end; p++) {
        html += p === page
            ? '<li class="page-item active"><span class="page-link">' + p + '</span></li>'
            : '<li class="page-item"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
    }
    html += page < pages
        ? '<li class="page-item"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>'
        : '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    html += '</ul></div>';
    return html;
}

// ── Pagination ────────────────────────────────────────────────────────────────
function rntLoadPage(pageNo) {
    var $body = $('#rntTableBody');
    $body.html('<tr><td colspan="11" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');

    $.ajax({
        url:     global_services_url + 'api/rental/getPageDetails/' + (pageNo || 1),
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: {
            orgId:    parseInt(JwtData.Org.OrgUID, 10),
            RowLimit: RowLimit || 10,
            Filter:   JSON.stringify(RntFilter),
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
                $body.html('<tr><td colspan="11" class="text-center text-danger py-3">Failed to load data.</td></tr>');
                return;
            }
            var offset = ((r.PageNo || 1) - 1) * (r.RowLimit || RowLimit || 10);
            $body.html(_buildRntRows(r.Data || [], offset));
            $('.rntPagination').html(_buildRntPagination(r.TotalCount || 0, r.PageNo || 1, r.RowLimit || RowLimit || 10));
            var cnt = r.TotalCount || 0;
            $('.rnt-status-tab.active .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            if (r.Stats) rntUpdateStats(r.Stats);
            initTooltips();
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
        '<tr id="rntNoMachinesRow"><td colspan="6" class="text-center text-muted py-4" style="font-size:var(--r2k-text-85);">' +
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
            altFormat:  (typeof _transFormDateTimeFormat !== 'undefined') ? _transFormDateTimeFormat : 'd-m-Y H:i',
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
            altFormat:  (typeof _transFormDateTimeFormat !== 'undefined') ? _transFormDateTimeFormat : 'd-m-Y H:i',
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
                url:      global_services_url + 'api/transactions/searchCustomers',
                dataType: 'json',
                delay:    250,
                headers:  { Authorization: 'Bearer ' + JwtToken },
                data:     function (p) { return { term: p.term, orgId: parseInt(JwtData.Org.OrgUID, 10) }; },
                processResults: function (data) {
                    var list = (data.Data || data).Lists || [];
                    return {
                        results: list.map(function (c) {
                            return { id: c.id, text: c.name, name: c.name, mobile: c.mobile };
                        })
                    };
                },
                cache: true,
            },
        });
    }

    // Populate deposit payment type dropdown
    var $dpType = $('#rntDepositPayType').empty().append('<option value="">- Select method -</option>');
    var $dpBank = $('#rntDepositBankUID').empty().append('<option value="">- Select bank -</option>');
    if (typeof _rntPayTypes !== 'undefined') {
        _rntPayTypes.forEach(function (t) {
            $dpType.append('<option value="' + t.PaymentTypeUID + '" data-is-cash="' + t.IsCash + '">' + _rntEsc(t.Name) + '</option>');
        });
    }
    if (typeof _rntBankAccts !== 'undefined') {
        _rntBankAccts.forEach(function (b) {
            $dpBank.append('<option value="' + b.BankAccountUID + '">' + _rntEsc(b.BankName) + ' - ' + _rntEsc(b.AccountName) + '</option>');
        });
    }

    new bootstrap.Modal(document.getElementById('rntCreateModal')).show();
    _rntIsDirty = false;
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
        +   '<div style="font-weight:500;font-size:var(--r2k-text-82);">' + _rntEsc(product.ItemName) + '</div>'
        +   (product.PartNumber ? '<div class="text-muted" style="font-size:var(--r2k-text-70);">P/N: ' + _rntEsc(product.PartNumber) + '</div>' : '')
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
        +   '<span class="rntMachineRate" data-idx="' + idx + '" style="font-size:var(--r2k-text-82);font-weight:500;">'
        +   RntCurrency + ' ' + rate.toFixed(RntDecimals)
        +   '</span>'
        + '</td>'
        + '<td style="padding:.4rem .5rem;text-align:right;">'
        +   '<span class="rntMachineCharge" data-idx="' + idx + '" style="font-size:var(--r2k-text-82);font-weight:600;color:#7c3aed;">'
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
        url:     global_services_url + 'api/rental/createRental',
        method:  'POST',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: {
            orgId:                 parseInt(JwtData.Org.OrgUID, 10),
            userId:                parseInt(JwtData.User.UserUID, 10),
            CustomerUID:           customerUID,
            RentalStartDateTime:   startDT,
            ReturnDueDateTime:     returnDT,
            DepositCollected:      $('#rntDepositCollected').val() || 0,
            DepositPaymentTypeUID: $('#rntDepositPayType').val() || '',
            DepositBankAccountUID: $('#rntDepositBankUID').val() || '',
            Notes:                 $('#rntNotes').val(),
            Items:                 JSON.stringify(items),
            Filter:                JSON.stringify(RntFilter),
            RowLimit:              RowLimit || 10,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                _rntIsDirty = false;
                bootstrap.Modal.getInstance(document.getElementById('rntCreateModal')).hide();
                Swal.fire({ icon: 'success', title: 'Rental Created', text: r.Message, timer: 2000, showConfirmButton: false });
                var offset = 0;
                $('#rntTableBody').html(_buildRntRows(r.Data || [], offset));
                $('.rntPagination').html(_buildRntPagination(r.TotalCount || 0, r.PageNo || 1, r.RowLimit || RowLimit || 10));
                var cnt = r.TotalCount || 0;
                $('.rnt-status-tab.active .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                if (r.Stats) rntUpdateStats(r.Stats);
                initTooltips();
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
    $('#rtnRentalNum').text(rentalNum || '-');
    $('#rtnRentalType').text('-');
    $('#rtnStartDate').text('-');
    $('#rtnDueDate').text('-');
    $('#rtnReturnedQty').val(1);
    $('#rtnDamagedQty').val(0);
    $('#rtnExtraHourCharge').val(0);
    $('#rtnLateCharge').val(0);
    $('#rtnDamageCharge').val(0);
    $('#rtnReturnNotes').val('');
    rntCalcReturnTotal();

    // Fetch rental detail to populate banner
    $.ajax({
        url:     global_services_url + 'api/rental/getRentalDetail',
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:    { orgId: parseInt(JwtData.Org.OrgUID, 10), RentalUID: rentalUID },
        success: function (r) {
            if (!r.Error && r.Data) {
                var d = r.Data;
                if (d.RentalStartDateTime) $('#rtnStartDate').text((function(d){ var fp = (typeof _transListDateTimeFormat !== 'undefined') ? _transListDateTimeFormat : 'd M Y'; var dt = new Date(d); return flatpickr.formatDate(dt, fp); })(d.RentalStartDateTime));
                if (d.ReturnDueDateTime)   $('#rtnDueDate').text((function(d){ var fp = (typeof _transListDateTimeFormat !== 'undefined') ? _transListDateTimeFormat : 'd M Y'; var dt = new Date(d); return flatpickr.formatDate(dt, fp); })(d.ReturnDueDateTime));
                if (d.Items) {
                    d.Items.forEach(function (item) {
                        if (parseInt(item.RentalItemUID) === parseInt(itemUID)) {
                            $('#rtnRentalType').text(item.RentalType);
                        }
                    });
                }
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
            altFormat:  (typeof _transFormDateTimeFormat !== 'undefined') ? _transFormDateTimeFormat : 'd-m-Y H:i',
            defaultDate: nowStr,
            time_24hr:  false,
            disableMobile: true,
            onChange: function (selectedDates) {
                _rntAutoCalcHours(selectedDates[0]);
            },
        });
    }

    new bootstrap.Modal(document.getElementById('rntReturnModal')).show();
    _rtnIsDirty = false;
}

// Auto-calculate hours when return time is set
function _rntAutoCalcHours(returnDate) {
    var rentalUID = $('#rtnRentalUID').val();
    if (!rentalUID || !returnDate) return;
    $.ajax({
        url:     global_services_url + 'api/rental/getRentalDetail',
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:    { orgId: parseInt(JwtData.Org.OrgUID, 10), RentalUID: rentalUID },
        success: function (r) {
            if (!r.Error && r.Data && r.Data.RentalStartDateTime) {
                var start = new Date(r.Data.RentalStartDateTime);
                var hours = (returnDate - start) / 3600000;
                $('#rtnActualHours').val(Math.max(0, hours).toFixed(1));
            }
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
        url:     global_services_url + 'api/rental/processReturn',
        method:  'POST',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: {
            orgId:                parseInt(JwtData.Org.OrgUID, 10),
            userId:               parseInt(JwtData.User.UserUID, 10),
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
            Filter:               JSON.stringify(RntFilter),
            RowLimit:             RowLimit || 10,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                _rtnIsDirty = false;
                bootstrap.Modal.getInstance(document.getElementById('rntReturnModal')).hide();
                Swal.fire({ icon: 'success', title: 'Return Recorded', text: r.Message, timer: 2000, showConfirmButton: false });
                var offset = 0;
                $('#rntTableBody').html(_buildRntRows(r.Data || [], offset));
                $('.rntPagination').html(_buildRntPagination(r.TotalCount || 0, r.PageNo || 1, r.RowLimit || RowLimit || 10));
                var cnt = r.TotalCount || 0;
                $('.rnt-status-tab.active .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                if (r.Stats) rntUpdateStats(r.Stats);
                initTooltips();
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
                '<tr id="rntNoMachinesRow"><td colspan="6" class="text-center text-muted py-4" style="font-size:var(--r2k-text-85);">' +
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

    // ── Dirty-tracking: create modal ─────────────────────────────────────────
    $(document).on('input change', '#rntCustomerUID, #rntNotes, #rntDepositCollected, #rntDepositPayType, #rntDepositBankUID, .rntMachineQty, .rntMachineType', function () {
        _rntIsDirty = true;
    });

    // ── Unsaved-changes guard: create modal ──────────────────────────────────
    $(document).on('hide.bs.modal', '#rntCreateModal', function (e) {
        if (!_rntIsDirty) return;
        e.preventDefault();
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _rntIsDirty = false;
                bootstrap.Modal.getInstance(document.getElementById('rntCreateModal')).hide();
            }
        });
    });

    // ── Dirty-tracking: return modal ─────────────────────────────────────────
    $(document).on('input change', '#rtnReturnedQty, #rtnDamagedQty, #rtnExtraHourCharge, #rtnLateCharge, #rtnDamageCharge, #rtnReturnNotes, #rtnActualReturnDateTime', function () {
        _rtnIsDirty = true;
    });

    // ── Unsaved-changes guard: return modal ──────────────────────────────────
    $(document).on('hide.bs.modal', '#rntReturnModal', function (e) {
        if (!_rtnIsDirty) return;
        e.preventDefault();
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _rtnIsDirty = false;
                bootstrap.Modal.getInstance(document.getElementById('rntReturnModal')).hide();
            }
        });
    });

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
            return $.ajax({
                url:     global_services_url + 'api/rental/searchRentableProducts',
                method:  'GET',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data:    { orgId: parseInt(JwtData.Org.OrgUID, 10), term: term },
            }).then(function (r) {
                var products = r.Products || r.Data || [];
                if (r.Error || !products.length) {
                    Swal.showValidationMessage(r.Error ? r.Message : 'No rentable products found for "' + term + '"');
                    return null;
                }
                return products;
            }).fail(function () {
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
