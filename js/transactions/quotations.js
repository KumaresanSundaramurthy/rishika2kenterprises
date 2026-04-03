function getQuotationsDetails(pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    $.ajax({
        url: '/quotations/getQuotationsPageDetails/' + pageNo,
        method: 'POST',
        cache: false,
        data: {
            RowLimit:              rowLimit,
            PageNo:                pageNo,
            Filter:                filter,
            ModuleId:              ModuleId,
            [CsrfName]:            CsrfToken,
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger m-2"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.pagination);
                $(ModuleTable + ' tbody').html(response.dataList);
                initTooltips();
            }
        },
        error: function() {
            $(ModulePag).html('<div class="alert alert-danger m-2">Failed to load quotations.</div>');
        }
    });
}

$(function () {
    'use strict';

    // ── Add Quotation form submit ─────────────────────────
    var $form = $('#addQuotationForm');
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            // ── Client-side validation ──────────────────
            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) {
                return showFormError('Please select a customer.');
            }

            var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
            if (!prefixUID || prefixUID <= 0) {
                return showFormError('Please select a quotation prefix.');
            }

            var transNumber = $.trim($('#transNumber').val());
            if (!transNumber || parseInt(transNumber, 10) <= 0) {
                return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) {
                return showFormError('Please enter a valid transaction date.');
            }

            var validityDate = $.trim($('#validityDate').val());
            if (validityDate && !/^\d{4}-\d{2}-\d{2}$/.test(validityDate)) {
                return showFormError('Validity date format is invalid.');
            }

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) {
                return showFormError('Please add at least one product.');
            }

            // Validate each item row
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var qty  = parseFloat(item.quantity);
                if (!qty || qty <= 0 || !Number.isInteger(qty)) {
                    return showFormError('Row ' + (i + 1) + ': Quantity must be a positive whole number.');
                }
                if (parseFloat(item.unitPrice) < 0) {
                    return showFormError('Row ' + (i + 1) + ': Selling price cannot be negative.');
                }
                var tax = parseFloat(item.taxPercent || 0);
                if (tax < 0 || tax > 100) {
                    return showFormError('Row ' + (i + 1) + ': Tax percentage must be between 0 and 100.');
                }
            }

            var extraDiscount = parseFloat($('#extraDiscount').val()) || 0;
            if (extraDiscount < 0) {
                return showFormError('Extra discount cannot be negative.');
            }

            // ── Build summary from BillManager ─────────
            var bm       = typeof billManager !== 'undefined' ? billManager : null;
            var summary  = bm ? bm.summary : {};
            var netAmount = summary.totals ? (summary.totals.grandTotal || 0) : 0;

            var subTotal          = summary.items       ? (summary.items.taxableAmount    || 0) : 0;
            var discountAmtTotal  = summary.items       ? (summary.items.discountTotal    || 0) : 0;
            var taxAmtTotal       = summary.taxTotals   ? (summary.taxTotals.totalTax     || 0) : 0;
            var cgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.cgstTotal    || 0) : 0;
            var sgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.sgstTotal    || 0) : 0;
            var igstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.igstTotal    || 0) : 0;
            var addChargesTotal   = (summary.additionalCharges && summary.additionalCharges.total)
                                        ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct     = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff          = summary.extra       ? (summary.extra.roundOff          || 0) : 0;

            // ── Build POST data ─────────────────────────
            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function (t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : prefixUID,
                transNumber            : transNumber,
                transDate              : transDate,
                validityDate           : validityDate,
                validityDays           : parseInt($('#validityDays').val(), 10) || 0,
                customerSearch         : customerUID,
                quotationType          : $('#quotationType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : extraDiscount,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : subTotal,
                DiscountAmount         : discountAmtTotal,
                TaxAmount              : taxAmtTotal,
                CgstAmount             : cgstAmtTotal,
                SgstAmount             : sgstAmtTotal,
                IgstAmount             : igstAmtTotal,
                AdditionalChargesTotal : addChargesTotal,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading(true);

            $.ajax({
                url     : '/quotations/addQuotation',
                method  : 'POST',
                data    : postData,
                cache   : false,
                success : function (response) {
                    setFormLoading(false);
                    if (response.Error) {
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Quotation Saved',
                            text             : response.Message || 'Quotation created successfully.',
                            confirmButtonText: 'OK',
                        }).then(function () {
                            window.location.href = '/quotations';
                        });
                    }
                },
                error: function () {
                    setFormLoading(false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        // Track which submit button was clicked
        $form.on('click', 'button[type="submit"][name="action"]', function () {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

    // Initial load (listing page only)
    if ($('#quotTable').length) {
        getQuotationsDetails(PageNo, RowLimit, Filter);
    }

    // ── Listing page handlers (only on view.php) ─────────
    if ($('#quotTable').length) {

        // Search
        $('#searchTransactionData').on('keyup', debounce(function () {
            Filter.Name = $.trim($(this).val());
            PageNo = 1;
            getQuotationsDetails();
        }, 400));

        // Date range
        $(document).on('click', '.date-option', function () {
            $('.date-option').removeClass('active');
            $(this).addClass('active');
            var range = $(this).data('range');
            var dates = getDateRange(range);
            $('#dateFilterBtn').text($.trim($(this).text()));
            Filter.DateFrom = dates.from;
            Filter.DateTo   = dates.to;
            PageNo = 1;
            getQuotationsDetails();
        });

        // Status filter
        $(document).on('click', '.status-option', function () {
            $('.status-option').removeClass('active');
            $(this).addClass('active');
            var status = $(this).data('status');
            Filter.Status = (status === 'all') ? '' : status;
            PageNo = 1;
            getQuotationsDetails();
        });

        // Pagination
        $(document).on('click', '.quotPagination .page-link', function (e) {
            e.preventDefault();
            var href  = $(this).attr('href') || '';
            var match = href.match(/\/(\d+)$/);
            if (match) {
                PageNo = parseInt(match[1]);
                getQuotationsDetails();
            }
        });

        // Delete
        $(document).on('click', '.deleteQuotation', function () {
            var uid = $(this).data('uid');
            Swal.fire({
                title            : 'Delete Quotation?',
                text             : 'This cannot be undone.',
                icon             : 'warning',
                showCancelButton : true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d33',
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url    : '/quotations/deleteQuotation',
                        method : 'POST',
                        data   : { TransUID: uid, [CsrfName]: CsrfToken },
                        success: function (response) {
                            if (response.Error) {
                                Swal.fire({ icon: 'error', title: 'Error', text: response.Message });
                            } else {
                                getQuotationsDetails();
                            }
                        }
                    });
                }
            });
        });

        // Page refresh
        $('.PageRefresh').on('click', function () {
            getQuotationsDetails();
        });

    }

});

// ── helpers ──────────────────────────────────────────────

function getDateRange(range) {
    var today  = new Date();
    var from   = '';
    var to     = formatDate(today);

    switch (range) {
        case 'today':
            from = to = formatDate(today);
            break;
        case 'yesterday':
            var y = new Date(today); y.setDate(today.getDate() - 1);
            from = to = formatDate(y);
            break;
        case 'this_week':
            var s = new Date(today); s.setDate(today.getDate() - today.getDay());
            from = formatDate(s);
            break;
        case 'last_week':
            var ls = new Date(today); ls.setDate(today.getDate() - today.getDay() - 7);
            var le = new Date(ls);    le.setDate(ls.getDate() + 6);
            from = formatDate(ls); to = formatDate(le);
            break;
        case 'last_7_days':
            var l7 = new Date(today); l7.setDate(today.getDate() - 6);
            from = formatDate(l7);
            break;
        case 'this_month':
            from = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
            break;
        case 'previous_month':
            from = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
            to   = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
            break;
        case 'last_30_days':
            var l30 = new Date(today); l30.setDate(today.getDate() - 29);
            from = formatDate(l30);
            break;
        case 'this_year':
            from = today.getFullYear() + '-01-01';
            break;
        case 'last_year':
            var ly = today.getFullYear() - 1;
            from = ly + '-01-01'; to = ly + '-12-31';
            break;
        case 'last_quarter':
            var q = Math.floor(today.getMonth() / 3);
            from = formatDate(new Date(today.getFullYear(), (q - 1) * 3, 1));
            to   = formatDate(new Date(today.getFullYear(), q * 3, 0));
            break;
        case 'fy_25_26':
            from = '2025-04-01'; to = '2026-03-31';
            break;
        default:
            from = ''; to = '';
    }
    return { from: from, to: to };
}

function formatDate(date) {
    var d = String(date.getDate()).padStart(2, '0');
    var m = String(date.getMonth() + 1).padStart(2, '0');
    return date.getFullYear() + '-' + m + '-' + d;
}

function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el);
    });
}

function debounce(fn, delay) {
    var t;
    return function () {
        var ctx = this, args = arguments;
        clearTimeout(t);
        t = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
}

function showFormError(message) {
    Swal.fire({ icon: 'error', title: 'Validation Error', text: message });
}

function setFormLoading(isLoading) {
    var $btns = $('#addQuotationForm button[type="submit"]');
    if (isLoading) {
        $btns.prop('disabled', true);
        $btns.filter('[value="save"]').html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
    } else {
        $btns.prop('disabled', false);
        $btns.filter('[value="save"]').text('Save');
        $btns.filter('[value="draft"]').text('Save as Draft');
    }
}
