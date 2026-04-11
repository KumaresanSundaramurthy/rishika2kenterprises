/**
 * Payment Section — Split Payment UI
 * Loaded after jQuery. Reads data from embedded <script type="application/json"> tags
 * and the global window._paymentCurrSymbol set by payment_section.php partial.
 */
$(function() {
    'use strict';

    if (!$('#paymentRowsBody').length) return; // partial not on this page

    var _paymentTypes = JSON.parse($('#paymentTypeOptionsData').text() || '[]');
    var _bankAccounts = JSON.parse($('#bankAccountOptionsData').text()  || '[]');
    var _rowCount     = 0;
    var _currSymbol   = window._paymentCurrSymbol || '₹';

    /* ── helpers ─────────────────────────────────── */
    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildTypeOptions(selectedUID) {
        return _paymentTypes.map(function(pt) {
            var sel = (parseInt(selectedUID) === parseInt(pt.PaymentTypeUID)) ? ' selected' : '';
            return '<option value="' + pt.PaymentTypeUID + '" data-is-cash="' + pt.IsCash + '"' + sel + '>' + esc(pt.Name) + '</option>';
        }).join('');
    }

    function buildBankOptions(selectedUID) {
        var opts = '<option value="">— Select Bank Account —</option>';
        _bankAccounts.forEach(function(ba) {
            var isDefault = parseInt(ba.IsDefault) === 1;
            var label     = esc(ba.AccountName) + ' — ' + esc(ba.BankName);
            if (isDefault) label += ' ★';
            // Auto-select default if no specific UID requested
            var sel = '';
            if (selectedUID) {
                sel = (parseInt(selectedUID) === parseInt(ba.BankAccountUID)) ? ' selected' : '';
            } else if (isDefault) {
                sel = ' selected';
            }
            opts += '<option value="' + ba.BankAccountUID + '"' + sel + '>' + label + '</option>';
        });
        return opts;
    }

    function getFirstIsCash() {
        return _paymentTypes.length === 0 || parseInt(_paymentTypes[0].IsCash) === 1;
    }

    /* ── build complete <tr> HTML string ─────────── */
    function buildRowHtml(rowId, data, isFirst) {
        data = data || {};
        var isCash = data.paymentTypeUID
            ? (function() {
                var found = _paymentTypes.find(function(p){ return parseInt(p.PaymentTypeUID) === parseInt(data.paymentTypeUID); });
                return found ? parseInt(found.IsCash) === 1 : true;
              })()
            : getFirstIsCash();

        var subLabelHtml;
        if (isCash) {
            subLabelHtml = 'Cash (-)';
        } else if (_bankAccounts.length === 0) {
            subLabelHtml = '<a href="/settings/banks" target="_blank" class="text-warning small"><i class="bx bx-plus-circle me-1"></i>Add bank account</a>';
        } else {
            subLabelHtml = '<select class="pay-bank-sel" data-row="' + rowId + '">' + buildBankOptions(data.bankAccountUID) + '</select>' +
                           ' <a href="/settings/banks" target="_blank" class="text-muted ms-1" title="Manage Banks" style="font-size:0.72rem;"><i class="bx bx-link-external"></i></a>';
        }

        var removeHtml = isFirst
            ? '<span class="pay-remove-placeholder"></span>'
            : '<button type="button" class="btn btn-sm btn-link text-danger p-0 pay-remove-btn" data-row="' + rowId + '" title="Remove"><i class="bx bx-x fs-5"></i></button>';

        return '<tr data-row="' + rowId + '">' +
            '<td class="ps-3">' +
                '<textarea class="form-control pay-notes-inp" data-row="' + rowId + '" rows="1" ' +
                    'placeholder="Advance received, UTR number etc..." maxlength="255">' +
                    esc(data.notes || '') +
                '</textarea>' +
            '</td>' +
            '<td>' +
                '<input type="text" class="form-control pay-amount-inp" data-row="' + rowId + '" ' +
                    'value="' + esc(data.amount || '0') + '" maxlength="12" autocomplete="off" />' +
            '</td>' +
            '<td>' +
                '<select class="form-select pay-type-sel" data-row="' + rowId + '">' +
                    buildTypeOptions(data.paymentTypeUID) +
                '</select>' +
                '<div class="pay-mode-sublabel" data-row="' + rowId + '">' +
                    subLabelHtml +
                '</div>' +
            '</td>' +
            '<td class="text-center">' + removeHtml + '</td>' +
        '</tr>';
    }

    function addPaymentRow(data) {
        _rowCount++;
        var isFirst = (_rowCount === 1);
        $('#paymentRowsBody').append(buildRowHtml(_rowCount, data || {}, isFirst));
        updatePaymentSummary();
    }

    /* ── initial row ─────────────────────────────── */
    addPaymentRow();

    /* ── split payment ───────────────────────────── */
    $(document).on('click', '#splitPaymentBtn', function() {
        addPaymentRow();
    });

    /* ── remove row ──────────────────────────────── */
    $(document).on('click', '.pay-remove-btn', function() {
        $(this).closest('tr').remove();
        var $rows = $('#paymentRowsBody tr');
        if ($rows.length === 1) {
            $rows.find('.pay-remove-btn').replaceWith('<span class="pay-remove-placeholder"></span>');
        }
        updatePaymentSummary();
    });

    /* ── payment type change ─────────────────────── */
    $(document).on('change', '.pay-type-sel', function() {
        var rowId  = $(this).data('row');
        var isCash = parseInt($(this).find(':selected').data('is-cash'), 10) === 1;
        var $subLabel = $('.pay-mode-sublabel[data-row="' + rowId + '"]');
        if (isCash) {
            $subLabel.html('Cash (-)');
        } else if (_bankAccounts.length === 0) {
            $subLabel.html('<a href="/settings/banks" target="_blank" class="text-warning small"><i class="bx bx-plus-circle me-1"></i>Add bank account</a>');
        } else {
            $subLabel.html(
                '<select class="pay-bank-sel" data-row="' + rowId + '">' + buildBankOptions() + '</select>' +
                ' <a href="/settings/banks" target="_blank" class="text-muted ms-1" title="Manage Banks" style="font-size:0.72rem;"><i class="bx bx-link-external"></i></a>'
            );
        }
    });

    /* ── amount input ────────────────────────────── */
    $(document).on('input', '.pay-amount-inp', function() {
        var val = $(this).val().replace(/[^0-9.]/g, '');
        var parts = val.split('.');
        if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
        $(this).val(val);
        updatePaymentSummary();
    });

    /* ── fully paid ──────────────────────────────── */
    $(document).on('change', '#isFullyPaid', function() {
        $('#PaymentIsFullyPaid').val($(this).is(':checked') ? 1 : 0);
        if ($(this).is(':checked')) {
            var billTotal = getBillTotal();
            if (billTotal > 0) {
                var $rows = $('#paymentRowsBody tr');
                $rows.first().find('.pay-amount-inp').val(billTotal.toFixed(2));
                $rows.not(':first').find('.pay-amount-inp').val('0');
            }
        }
        updatePaymentSummary();
    });

    /* ── summary ─────────────────────────────────── */
    function getBillTotal() {
        return parseFloat(String($('.bill_tot_amt').first().text()).replace(/,/g, '')) || 0;
    }

    function updatePaymentSummary() {
        var billTotal = getBillTotal();
        var totalPaid = 0;

        $('#paymentRowsBody tr').each(function() {
            totalPaid += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
        });

        var balance = billTotal - totalPaid;
        var excess  = totalPaid - billTotal;

        $('#payBillTotal').text(_currSymbol + ' ' + billTotal.toFixed(2));
        $('#payTotalPaid').text(_currSymbol + ' ' + totalPaid.toFixed(2));

        if (balance > 0.005) {
            $('#payBalanceWrap').removeClass('d-none');
            $('#payBalance').text(_currSymbol + ' ' + balance.toFixed(2));
            $('#payExcessWrap').addClass('d-none');
        } else if (excess > 0.005) {
            $('#payBalanceWrap').addClass('d-none');
            $('#payExcessWrap').removeClass('d-none');
            $('#payExcess').text(_currSymbol + ' ' + excess.toFixed(2));
        } else {
            $('#payBalanceWrap').removeClass('d-none');
            $('#payBalance').text(_currSymbol + ' 0.00 (Fully Paid)');
            $('#payExcessWrap').addClass('d-none');
        }
    }
    window.updatePaymentSummary = updatePaymentSummary;

    /* ── serialize → hidden input (called before submit) ──── */
    window.serializePaymentRows = function() {
        var rows  = [];
        var valid = true;

        $('#paymentRowsBody tr').each(function() {
            var $tr            = $(this);
            var paymentTypeUID = parseInt($tr.find('.pay-type-sel').val(), 10) || 0;
            var amount         = parseFloat($tr.find('.pay-amount-inp').val()) || 0;
            var notes          = $.trim($tr.find('.pay-notes-inp').val());
            var bankAccountUID = parseInt($tr.find('.pay-bank-sel').val(), 10) || 0;

            if (amount <= 0) {
                $tr.find('.pay-amount-inp').css('border', '1px solid #dc3545');
                valid = false;
                return;
            }
            $tr.find('.pay-amount-inp').css('border', '');

            rows.push({
                paymentTypeUID : paymentTypeUID,
                amount         : amount,
                notes          : notes || null,
                bankAccountUID : bankAccountUID || null,
                referenceNo    : null,
            });
        });

        if (!valid) return false;

        rows = rows.filter(function(r){ return r.amount > 0; });

        $('#PaymentRowsJson').val(rows.length > 0 ? JSON.stringify(rows) : '');
        return true;
    };

    /* ── bank accounts are managed via Settings → Banks ─────────── */
    /* When the user adds/edits banks they are redirected to /settings/banks */

});
