/**
 * Payment Section — Split Payment UI
 * Loaded after jQuery. Reads data from embedded <script type="application/json"> tags
 * and the global window._paymentCurrSymbol set by payment_section.php partial.
 */
$(function() {
    'use strict';

    if (!$('#paymentRowsBody').length) return; // partial not on this page

    var _paymentTypes  = JSON.parse($('#paymentTypeOptionsData').text() || '[]');
    var _bankAccounts  = JSON.parse($('#bankAccountOptionsData').text()  || '[]');
    var _rowCount      = 0;
    var _currSymbol    = window._paymentCurrSymbol || '₹';
    var _dec           = window._psDecimal || 2;
    var _partyType     = window._paymentPartyType || 'C';

    /* ── Credit Note state ───────────────────────────────────────── */
    var _appliedCN     = null; // {uid, number, amount, type} or null
    var _cnCustomerUID = 0;

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
        _bankAccounts.filter(function(ba) { return parseInt(ba.IsCash) !== 1; }).forEach(function(ba) {
            var isDefault = parseInt(ba.IsDefault) === 1;
            var fullLabel  = esc(ba.AccountName) + ' — ' + esc(ba.BankName) + (isDefault ? ' ★' : '');
            var shortLabel = esc(ba.BankName)    + (isDefault ? ' ★' : '');
            var sel = '';
            if (selectedUID) {
                sel = (parseInt(selectedUID) === parseInt(ba.BankAccountUID)) ? ' selected' : '';
            } else if (isDefault) {
                sel = ' selected';
            }
            // Option text = short name (bank only); full text stored in data-full for dropdown display
            opts += '<option value="' + ba.BankAccountUID + '"'
                  + ' data-short="' + shortLabel + '" data-full="' + fullLabel + '"'
                  + sel + '>' + shortLabel + '</option>';
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
            subLabelHtml = '<span class="pay-cash-label"><i class="bx bx-money me-1"></i>Cash</span>';
        } else if (_bankAccounts.length === 0) {
            subLabelHtml = '<div class="pay-mode-sublabel mt-1"><a href="/settings/banks" target="_blank" class="text-warning small"><i class="bx bx-plus-circle me-1"></i>Add bank account</a></div>';
        } else {
            subLabelHtml = '<div class="pay-bank-wrap">' +
                               '<select class="pay-bank-sel" data-row="' + rowId + '">' + buildBankOptions(data.bankAccountUID) + '</select>' +
                               '<a href="/settings/banks" target="_blank" class="pay-bank-link" title="Manage Banks"><i class="bx bx-cog"></i></a>' +
                           '</div>';
        }

        var removeHtml = isFirst
            ? '<span class="pay-remove-placeholder"></span>'
            : '<button type="button" class="pay-remove-btn" data-row="' + rowId + '" title="Remove" style="background:none;border:1px solid #dc3545;border-radius:4px;padding:3px 6px;cursor:pointer;color:#dc3545;line-height:1;"><i class="bx bx-trash" style="font-size:.85rem;"></i></button>';

        return '<tr data-row="' + rowId + '">' +
            '<td class="ps-3">' +
                '<textarea class="form-control pay-notes-inp" data-row="' + rowId + '" rows="1" ' +
                    'placeholder="Advance received, UTR number etc..." maxlength="255">' +
                    esc(data.notes || '') +
                '</textarea>' +
            '</td>' +
            '<td>' +
                '<input type="text" id="pay-date-fp-' + rowId + '" class="pay-date-inp" data-row="' + rowId + '" placeholder="Today" readonly />' +
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
        var rowId   = _rowCount;
        $('#paymentRowsBody').append(buildRowHtml(rowId, data || {}, isFirst));
        var _altFmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat) ? _transFormDateFormat : 'd-m-Y';
        flatpickr('#pay-date-fp-' + rowId, {
            dateFormat   : 'Y-m-d',
            altInput     : true,
            altInputClass: 'pay-date-inp',
            altFormat    : _altFmt,
            maxDate      : 'today',
            defaultDate  : (data && data.paymentDate) ? data.paymentDate : 'today',
            static       : true,
            position     : 'below left',
            disableMobile: true,
        });
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
            $subLabel.html('<span class="pay-cash-label"><i class="bx bx-money me-1"></i>Cash</span>');
        } else if (_bankAccounts.length === 0) {
            $subLabel.html('<div class="mt-1"><a href="/settings/banks" target="_blank" class="text-warning small"><i class="bx bx-plus-circle me-1"></i>Add bank account</a></div>');
        } else {
            $subLabel.html(
                '<div class="pay-bank-wrap">' +
                    '<select class="pay-bank-sel" data-row="' + rowId + '">' + buildBankOptions() + '</select>' +
                    '<a href="/settings/banks" target="_blank" class="pay-bank-link" title="Manage Banks"><i class="bx bx-cog"></i></a>' +
                '</div>'
            );
        }
    });

    /* ── bank selector: show full name in dropdown, short name when selected ── */
    // mousedown → dropdown about to open → swap to full labels
    $(document).on('mousedown', '.pay-bank-sel', function() {
        $(this).find('option[data-full]').each(function() {
            $(this).text($(this).data('full'));
        });
    });
    // change/blur → dropdown closed → swap back to short (bank name only)
    $(document).on('change blur', '.pay-bank-sel', function() {
        $(this).find('option[data-full]').each(function() {
            $(this).text($(this).data('short'));
        });
    });

    /* ── amount input ────────────────────────────── */
    $(document).on('input', '.pay-amount-inp', function() {
        var val = $(this).val().replace(/[^0-9.]/g, '');
        var parts = val.split('.');
        if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
        $(this).val(val);

        // When "Mark as fully paid" is checked, cap this row so the running total
        // can never exceed the bill amount (accounting for On Account and credit note too).
        if ($('#isFullyPaid').is(':checked')) {
            var billTotal  = getBillTotal();
            var onAccTotal = 0;
            try {
                var oaRaw = $('#OnAccountApplyJson').val() || '';
                if (oaRaw) {
                    (JSON.parse(oaRaw) || []).forEach(function(item) {
                        onAccTotal += parseFloat(item.ApplyAmount) || 0;
                    });
                }
            } catch(e) {}
            var creditTotal = _appliedCN ? parseFloat(_appliedCN.amount) : 0;
            var maxPayable  = billTotal - onAccTotal - creditTotal;
            var $current   = $(this);
            var otherTotal = 0;
            $('#paymentRowsBody tr').each(function() {
                var $inp = $(this).find('.pay-amount-inp');
                if (!$inp.is($current)) otherTotal += parseFloat($inp.val()) || 0;
            });
            var maxForThis = Math.max(0, maxPayable - otherTotal);
            if ((parseFloat(val) || 0) > maxForThis) {
                $(this).val(maxForThis.toFixed(_dec));
            }
        }

        updatePaymentSummary();
    });

    // Focus: if value is 0/0.00 → clear for fresh typing; else → select all
    $(document).on('focus', '.pay-amount-inp', function() {
        var v = parseFloat($(this).val()) || 0;
        if (v === 0) {
            $(this).val('');
        } else {
            this.select();
        }
    });

    // Blur: if empty → restore 0; strip leading zeros (e.g. 0100 → 100)
    $(document).on('blur', '.pay-amount-inp', function() {
        var raw = $.trim($(this).val());
        if (raw === '') {
            $(this).val('0');
        } else {
            var n = parseFloat(raw);
            $(this).val(isNaN(n) ? '0' : String(n));
        }
        updatePaymentSummary();
    });

    /* ── fully paid ──────────────────────────────── */
    $(document).on('change', '#isFullyPaid', function() {
        $('#PaymentIsFullyPaid').val($(this).is(':checked') ? 1 : 0);
        if ($(this).is(':checked')) {
            var billTotal = getBillTotal();
            var $rows = $('#paymentRowsBody tr');
            if (billTotal > 0 && $rows.length === 1) {
                var creditAmt = _appliedCN ? parseFloat(_appliedCN.amount) : 0;
                var onAcct = 0;
                try {
                    var oaJson = $('#OnAccountApplyJson').val() || '';
                    if (oaJson) {
                        (JSON.parse(oaJson) || []).forEach(function(item) {
                            onAcct += parseFloat(item.ApplyAmount) || 0;
                        });
                    }
                } catch(e) {}
                var balance = Math.max(0, billTotal - creditAmt - onAcct);
                $rows.first().find('.pay-amount-inp').val(balance.toFixed(_dec));
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
        var creditAmt = _appliedCN ? parseFloat(_appliedCN.amount) : 0;
        var totalPaid = creditAmt;

        $('#paymentRowsBody tr').each(function() {
            totalPaid += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
        });

        // Include On Account applied amount in total paid
        try {
            var oaJson = $('#OnAccountApplyJson').val() || '';
            if (oaJson) {
                (JSON.parse(oaJson) || []).forEach(function(item) {
                    totalPaid += parseFloat(item.ApplyAmount) || 0;
                });
            }
        } catch(e) {}

        // Credit amount display line
        if (creditAmt > 0) {
            $('#cnCreditAmtWrap').removeClass('d-none');
            $('#cnCreditAmt').text(_currSymbol + ' ' + creditAmt.toFixed(_dec));
        } else {
            $('#cnCreditAmtWrap').addClass('d-none');
        }

        // Disable Split Payment when already fully covered
        var isCovered = billTotal > 0 && (totalPaid >= billTotal - 0.005);
        $('#splitPaymentBtn').prop('disabled', isCovered);

        var balance = billTotal - totalPaid;
        var excess  = totalPaid - billTotal;

        // If bill total changed after "Mark as fully paid" was checked, uncheck it.
        // Don't trigger('change') here — that calls updatePaymentSummary() again.
        if ($('#isFullyPaid').is(':checked') && Math.abs(totalPaid - billTotal) > 0.005) {
            $('#isFullyPaid').prop('checked', false);
            $('#PaymentIsFullyPaid').val(0);
        }

        $('#payBillTotal').text(_currSymbol + ' ' + billTotal.toFixed(_dec));
        $('#payTotalPaid').text(_currSymbol + ' ' + totalPaid.toFixed(_dec));

        if (balance > 0.005) {
            $('#payBalanceWrap').removeClass('d-none');
            $('#payBalance').text(_currSymbol + ' ' + balance.toFixed(_dec));
            $('#payExcessWrap').addClass('d-none');
        } else if (excess > 0.005) {
            $('#payBalanceWrap').addClass('d-none');
            $('#payExcessWrap').removeClass('d-none');
            $('#payExcess').text(_currSymbol + ' ' + excess.toFixed(_dec));
        } else {
            $('#payBalanceWrap').removeClass('d-none');
            $('#payBalance').text(_currSymbol + ' ' + (0).toFixed(_dec) + ' (Fully Paid)');
            $('#payExcessWrap').addClass('d-none');
        }
    }
    window.updatePaymentSummary = updatePaymentSummary;

    /* ── serialize → hidden input (called before submit) ──── */
    window.serializePaymentRows = function() {
        var rows     = [];
        var rowTotal = 0;
        var today    = new Date().toISOString().split('T')[0];

        $('#paymentRowsBody tr').each(function() {
            var $tr            = $(this);
            var paymentTypeUID = parseInt($tr.find('.pay-type-sel').val(), 10) || 0;
            var amount         = parseFloat($tr.find('.pay-amount-inp').val()) || 0;
            var notes          = $.trim($tr.find('.pay-notes-inp').val());
            var bankAccountUID = parseInt($tr.find('.pay-bank-sel').val(), 10) || 0;
            var paymentDate    = $tr.find('.pay-date-inp').val() || today;

            $tr.find('.pay-amount-inp').css('border', '');

            if (amount <= 0) return; // skip empty rows silently — no payment entered
            rowTotal += amount;

            rows.push({
                paymentTypeUID : paymentTypeUID,
                amount         : amount,
                notes          : notes || null,
                bankAccountUID : bankAccountUID || null,
                referenceNo    : null,
                paymentDate    : paymentDate,
            });
        });

        // "Mark as fully paid" validation — total must be within ₹1 of bill amount
        if ($('#isFullyPaid').is(':checked')) {
            var billTotal   = getBillTotal();
            var onAccTotal  = 0;
            try {
                var oaRaw = $('#OnAccountApplyJson').val() || '';
                if (oaRaw) {
                    (JSON.parse(oaRaw) || []).forEach(function(item) {
                        onAccTotal += parseFloat(item.ApplyAmount) || 0;
                    });
                }
            } catch(e) {}
            var creditTotal = _appliedCN ? parseFloat(_appliedCN.amount) : 0;
            var totalPaid   = rowTotal + onAccTotal + creditTotal;
            var diff        = totalPaid - billTotal;

            if (diff < -1) {
                showToastNotification(
                    t('toast_fully_paid_short',
                      'Total paid ({paid}) is {short} short of the bill amount ({bill}). Please complete the full payment to mark as fully paid.')
                        .replace('{paid}',  _currSymbol + ' ' + totalPaid.toFixed(_dec))
                        .replace('{short}', _currSymbol + ' ' + Math.abs(diff).toFixed(_dec))
                        .replace('{bill}',  _currSymbol + ' ' + billTotal.toFixed(_dec)),
                    'error'
                );
                return false;
            }
            if (diff > 1) {
                showToastNotification(
                    t('toast_fully_paid_excess',
                      'Total paid ({paid}) exceeds the bill amount ({bill}) by {excess}. Please reduce the payment amount.')
                        .replace('{paid}',   _currSymbol + ' ' + totalPaid.toFixed(_dec))
                        .replace('{bill}',   _currSymbol + ' ' + billTotal.toFixed(_dec))
                        .replace('{excess}', _currSymbol + ' ' + diff.toFixed(_dec)),
                    'error'
                );
                return false;
            }
        }

        $('#PaymentRowsJson').val(rows.length > 0 ? JSON.stringify(rows) : '');
        return true;
    };

    /* ── bank accounts are managed via Settings → Banks ─────────── */
    /* When the user adds/edits banks they are redirected to /settings/banks */

    /* ── Payment Attachments ─────────────────────────────────────── */
    var _paymentAttachments = [];
    var _maxFiles = 3;
    var _maxFileSize = 3 * 1024 * 1024; // 3MB in bytes

    // Upload button click handler
    $(document).on('click', '#uploadPaymentAttachmentBtn', function() {
        if (_paymentAttachments.length >= _maxFiles) {
            showToastNotification(t('toast_upload_max_files', 'You can upload a maximum of {n} files. Please remove a file before adding a new one.').replace('{n}', _maxFiles), 'error');
            return;
        }
        $('#paymentAttachmentInput').click();
    });

    // File input change handler
    $(document).on('change', '#paymentAttachmentInput', function(e) {
        var files = e.target.files;
        if (!files || files.length === 0) return;

        // Check total file count
        if (_paymentAttachments.length + files.length > _maxFiles) {
            Swal.fire({
                icon: 'warning',
                title: t('swal_too_many_files', 'Too Many Files'),
                text: t('toast_upload_limit', 'You can upload a maximum of {n} files.').replace('{n}', _maxFiles)
            });
            $(this).val(''); // Clear input
            return;
        }

        // Process each file
        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            // Check file size
            if (file.size > _maxFileSize) {
                Swal.fire({
                    icon: 'warning',
                    title: t('swal_file_too_large', 'File Too Large'),
                    text: file.name + ' is larger than 3MB. Please choose a smaller file.'
                });
                continue;
            }

            // Add to array
            _paymentAttachments.push(file);
            renderPaymentAttachment(file, _paymentAttachments.length - 1);
        }

        // Clear input so same file can be selected again
        $(this).val('');
        updatePaymentAttachmentsJson();
    });

    // Render attachment in the list
    function renderPaymentAttachment(file, index) {
        var fileExt = file.name.split('.').pop().toLowerCase();
        var iconClass = 'file-default';
        var iconName = 'bx-file';

        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].indexOf(fileExt) !== -1) {
            iconClass = 'file-image';
            iconName = 'bx-image-alt';
        } else if (fileExt === 'pdf') {
            iconClass = 'file-pdf';
            iconName = 'bxs-file-pdf';
        } else if (['doc', 'docx'].indexOf(fileExt) !== -1) {
            iconClass = 'file-doc';
            iconName = 'bxs-file-doc';
        } else if (['xls', 'xlsx'].indexOf(fileExt) !== -1) {
            iconClass = 'file-xls';
            iconName = 'bx-spreadsheet';
        }

        var fileSize = formatFileSize(file.size);

        var html = '<div class="uploaded-file-item" data-index="' + index + '">' +
            '<div class="file-info">' +
                '<i class="bx ' + iconName + ' file-icon ' + iconClass + '"></i>' +
                '<div class="file-details">' +
                    '<div class="file-name" title="' + esc(file.name) + '">' + esc(file.name) + '</div>' +
                    '<div class="file-size">' + fileSize + '</div>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="file-remove-btn" data-index="' + index + '">' +
                '<i class="bx bx-trash"></i> Remove' +
            '</button>' +
        '</div>';

        $('#paymentAttachmentsList').append(html);
    }

    // Preview on click of the file-info area
    $(document).on('click', '#paymentAttachmentsList .file-info', function () {
        var index = parseInt($(this).closest('.uploaded-file-item').data('index'));
        var file  = _paymentAttachments[index];
        if (!file) return;

        var ext     = file.name.split('.').pop().toLowerCase();
        var isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].indexOf(ext) !== -1;
        var isPdf   = ext === 'pdf';

        if (isImage) {
            var blobUrl = URL.createObjectURL(file);
            if (typeof openImageGallery === 'function') {
                openImageGallery([{ url: blobUrl, name: file.name }], 0);
            }
        } else if (isPdf) {
            var pdfUrl = URL.createObjectURL(file);
            if (typeof openPdfPreview === 'function') {
                openPdfPreview(pdfUrl, file.name, true);
            } else {
                window.open(pdfUrl, '_blank', 'noopener');
                setTimeout(function () { URL.revokeObjectURL(pdfUrl); }, 60000);
            }
        }
    });

    // Remove attachment
    $(document).on('click', '.file-remove-btn', function() {
        var index = parseInt($(this).data('index'));
        $(this).closest('.uploaded-file-item').remove();
        _paymentAttachments.splice(index, 1);
        
        // Re-render all items with updated indices
        $('#paymentAttachmentsList').empty();
        _paymentAttachments.forEach(function(file, idx) {
            renderPaymentAttachment(file, idx);
        });
        
        updatePaymentAttachmentsJson();
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Update hidden input with file data
    function updatePaymentAttachmentsJson() {
        // Store file metadata (names and sizes) in JSON
        var metadata = _paymentAttachments.map(function(file) {
            return {
                name: file.name,
                size: file.size,
                type: file.type
            };
        });
        $('#PaymentAttachmentsJson').val(JSON.stringify(metadata));
    }

    // Expose function to get files for form submission
    window.getPaymentAttachmentFiles = function() {
        return _paymentAttachments;
    };

    /* ── Credit Note Banner & Modal ──────────────────────────────── */

    function _clearCreditNote() {
        _appliedCN = null;
        $('#CreditNoteUIDInput').val('');
        $('#cnAppliedStrip').addClass('d-none');
        updatePaymentSummary();
    }

    // Expose banner control so invoice.js (which loads after us) can call it
    // from its own _showOnAccountBanner override.
    window._cnUpdateBanner = function(customerUID, cnTotal) {
        _cnCustomerUID = parseInt(customerUID) || 0;
        if (_cnCustomerUID > 0 && parseFloat(cnTotal) > 0) {
            $('#cnBannerWrap').removeClass('d-none');
        } else {
            _clearCreditNote();
            $('#cnBannerWrap').addClass('d-none');
        }
    };

    // Pre-populate a CN on draft edit (called by invoice.js _restoreEditIndicators)
    window._cnPrePopulate = function(uid, number, amount, type) {
        uid    = parseInt(uid)    || 0;
        amount = parseFloat(amount) || 0;
        if (!uid || !amount) return;
        _appliedCN = { uid: uid, number: String(number || ''), amount: amount, type: String(type || '') };
        $('#CreditNoteUIDInput').val(uid);
        var label = esc(_appliedCN.number) + (_appliedCN.type ? ' · ' + esc(_appliedCN.type) : '');
        $('#cnAppliedLabel').html(label);
        $('#cnAppliedAmt').text(_currSymbol + ' ' + amount.toFixed(_dec));
        $('#cnAppliedStrip').removeClass('d-none');
        $('#cnBannerWrap').addClass('d-none');
        updatePaymentSummary();
    };

    // Clear the applied CN and recalculate totals (called by invoice.js after CreditNoteConflict SweetAlert)
    window._clearAppliedCN = function() { _clearCreditNote(); };

    // Banner click → AJAX → open modal
    $(document).on('click', '#cnBannerBtn', function() {
        if (_cnCustomerUID <= 0) return;
        var billTotal = getBillTotal();
        if (!_hasItems() || billTotal <= 0) {
            showToastNotification('Please add items to the invoice first. A credit note can only be applied once the invoice has products and a total amount greater than zero.', 'error');
            return;
        }
        $.ajax({
            url    : '/invoices/getCustomerCreditNotes',
            method : 'POST',
            data   : { CustomerUID: _cnCustomerUID, [CsrfName]: CsrfToken },
            success: function(resp) {
                if (resp.Error) {
                    showToastNotification('Could not load credit notes. Please try again.', 'error');
                    return;
                }
                _openCnModal(resp.Data || [], billTotal);
            },
            error: function() {
                showToastNotification('Failed to load credit notes.', 'error');
            }
        });
    });

    /**
     * @param {Array}  cnList
     * @param {number} billTotal
     * @returns {void}
     */
    function _openCnModal(cnList, billTotal) {
        var rows = '';
        if (!cnList.length) {
            rows = '<tr><td colspan="6" class="text-center text-muted py-4">No pending credit notes for this customer.</td></tr>';
        } else {
            cnList.forEach(function(cn) {
                var amt      = parseFloat(cn.Amount);
                var tooLarge = amt > billTotal + 0.005;
                var disAttr  = tooLarge ? ' disabled' : '';
                var rowCls   = tooLarge ? ' cn-row-disabled' : '';
                var srcNum   = esc(cn.SourceInvoiceNumber || cn.SourceTransNumber || '—');
                var type     = esc(cn.CreditNoteType || '');
                var date     = (cn.CreatedOn || '').split(' ')[0];
                rows += '<tr class="cn-modal-row' + rowCls + '">' +
                    '<td class="text-center">' +
                        '<input type="radio" name="cnSelectRadio" class="form-check-input"' +
                        ' value="' + esc(String(cn.CreditNoteUID)) + '"' +
                        ' data-amount="' + amt + '"' +
                        ' data-number="' + esc(cn.CreditNoteNumber) + '"' +
                        ' data-type="'   + type + '"' +
                        disAttr + '>' +
                    '</td>' +
                    '<td><span class="fw-semibold text-success">' + esc(cn.CreditNoteNumber) + '</span></td>' +
                    '<td>' + type + '</td>' +
                    '<td class="text-muted small">' + srcNum + '</td>' +
                    '<td class="text-end fw-semibold">' + _currSymbol + ' ' + amt.toFixed(_dec) +
                        (tooLarge ? ' <span class="badge bg-label-danger ms-1" style="font-size:.65rem;">Exceeds invoice</span>' : '') +
                    '</td>' +
                    '<td class="text-muted small">' + esc(date) + '</td>' +
                '</tr>';
            });
        }
        $('#cnModalTableBody').html(rows);
        $('#cnApplyBtn').prop('disabled', true);
        var $modal = document.getElementById('cnSelectModal');
        if ($modal) {
            (bootstrap.Modal.getInstance($modal) || new bootstrap.Modal($modal)).show();
        }
    }

    // Enable Apply button when a radio is chosen
    $(document).on('change', 'input[name="cnSelectRadio"]', function() {
        $('#cnApplyBtn').prop('disabled', false);
    });

    // Clicking a non-disabled row selects its radio
    $(document).on('click', '#cnModalTableBody .cn-modal-row:not(.cn-row-disabled)', function(e) {
        if (!$(e.target).is('input[type="radio"]')) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    // Apply selected credit note
    $(document).on('click', '#cnApplyBtn', function() {
        var $sel = $('input[name="cnSelectRadio"]:checked');
        if (!$sel.length) return;

        _appliedCN = {
            uid   : parseInt($sel.val(), 10),
            amount: parseFloat($sel.data('amount')),
            number: String($sel.data('number') || ''),
            type  : String($sel.data('type')   || '')
        };
        $('#CreditNoteUIDInput').val(_appliedCN.uid);

        // Update strip label
        var label = esc(_appliedCN.number) + (_appliedCN.type ? ' · ' + esc(_appliedCN.type) : '');
        $('#cnAppliedLabel').html(label);
        $('#cnAppliedAmt').text(_currSymbol + ' ' + _appliedCN.amount.toFixed(_dec));
        $('#cnAppliedStrip').removeClass('d-none');

        // Hide banner while credit is applied
        $('#cnBannerWrap').addClass('d-none');

        // Close modal
        var $m = document.getElementById('cnSelectModal');
        if ($m) {
            var inst = bootstrap.Modal.getInstance($m);
            if (inst) inst.hide();
        }

        // If "Mark as fully paid" was checked when the CN is applied:
        //   Single row  → recalculate balance and update the amount; checkbox stays checked.
        //   Split rows  → we can't redistribute automatically, so clear all row amounts and
        //                 uncheck; the user re-enters their split against the new balance.
        if ($('#isFullyPaid').is(':checked')) {
            var $rows = $('#paymentRowsBody tr');
            if ($rows.length === 1) {
                var billTotal = getBillTotal();
                var creditAmt = _appliedCN ? parseFloat(_appliedCN.amount) : 0;
                var onAcct = 0;
                try {
                    var oaJson = $('#OnAccountApplyJson').val() || '';
                    if (oaJson) {
                        (JSON.parse(oaJson) || []).forEach(function(item) {
                            onAcct += parseFloat(item.ApplyAmount) || 0;
                        });
                    }
                } catch(e) {}
                var balance = Math.max(0, billTotal - creditAmt - onAcct);
                $rows.first().find('.pay-amount-inp').val(balance.toFixed(_dec));
            } else {
                // Split rows — clear all amounts and uncheck so the user
                // re-enters the split against the new balance.
                $rows.each(function() {
                    $(this).find('.pay-amount-inp').val('0');
                });
                $('#isFullyPaid').prop('checked', false);
                $('#PaymentIsFullyPaid').val(0);
            }
        }

        updatePaymentSummary();
    });

    // Remove applied credit note
    $(document).on('click', '#cnRemoveBtn', function() {
        _clearCreditNote();
        // Restore banner if customer still selected and has CNs
        if (_cnCustomerUID > 0) {
            $('#cnBannerWrap').removeClass('d-none');
        }
    });

});
