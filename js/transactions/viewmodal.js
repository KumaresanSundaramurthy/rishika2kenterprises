/**
 * js/transactions/viewmodal.js
 * Shared builder for the viewTransModal deep-preview panel.
 * Exposes: window._buildTransDetailHtml(resp, opts)
 */
(function () {
    'use strict';

    // ── private helpers ────────────────────────────────────────────────────────
    function _escNl(v) {
        if (v === null || v === undefined) return '';
        return $('<span>').text(String(v)).html().replace(/\n/g, '<br>');
    }

    function _fmtDate(s) {
        if (!s) return '—';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var p = String(s).split(/[-T ]/);
        if (p.length < 3) return _esc(s);
        var d = parseInt(p[2], 10), m = parseInt(p[1], 10) - 1, y = p[0];
        if (isNaN(d) || m < 0 || m > 11) return _esc(s);
        return d + ' ' + months[m] + ' ' + y;
    }

    function _smartDec(n) {
        if (n === null || n === undefined || n === '') return '0.00';
        var str    = String(n).trim();
        var dotIdx = str.indexOf('.');
        if (dotIdx !== -1) {
            var decPart = str.slice(dotIdx + 1);
            if (decPart.length >= 3 && decPart[2] !== '0') return parseFloat(str).toFixed(3);
        }
        return parseFloat(str || 0).toFixed(2);
    }

    function _statusBadge(status) {
        if (!status) return '';
        var s = String(status).toLowerCase().trim();
        var cls;
        if      (s === 'paid' || s === 'approved')                       cls = 'bg-success';
        else if (s === 'partial' || s === 'partially paid')              cls = 'bg-warning text-dark';
        else if (s === 'draft')                                          cls = 'bg-secondary';
        else if (s === 'cancelled' || s === 'rejected' || s === 'void') cls = 'bg-danger';
        else if (s === 'issued' || s === 'sent' || s === 'saved')       cls = 'bg-primary';
        else if (s === 'converted')                                      cls = 'bg-info text-dark';
        else if (s === 'expired')                                        cls = 'bg-warning text-dark';
        else                                                             cls = 'bg-secondary';
        return '<span class="badge ' + cls + ' px-2 py-1 vtm-status-badge">' + _esc(status) + '</span>';
    }

    function _buildAddr(l1, l2, city, state, pin) {
        var parts = [];
        if (l1) parts.push(_esc(l1));
        if (l2) parts.push(_esc(l2));
        var cs = [city, state].filter(Boolean).map(_esc).join(', ');
        if (pin) cs += (cs ? ' &ndash; ' : '') + _esc(pin);
        if (cs)  parts.push(cs);
        return parts.join('<br>');
    }

    function _secHdr(icon, label, colorClass) {
        return '<div class="vtm-sec-hdr">' +
            '<i class="bx ' + icon + ' ' + colorClass + '"></i>' +
            '<span class="' + colorClass + '">' + label + '</span>' +
            '</div>';
    }

    function _infoCard(content, borderClass) {
        return '<div class="vtm-info-card ' + borderClass + '">' + content + '</div>';
    }

    function _cardLabel(icon, text) {
        return '<div class="vtm-card-label"><i class="bx ' + icon + ' me-1"></i>' + text + '</div>';
    }

    function _summaryRow(label, value, rowClass) {
        return '<tr' + (rowClass ? ' class="' + rowClass + '"' : '') + '>' +
            '<td class="px-2 py-1">' + label + '</td>' +
            '<td class="text-end px-2 py-1">' + value + '</td>' +
            '</tr>';
    }

    // ── Banner HTML (header — shown instantly) ─────────────────────────────────
    function _buildBannerHtml(h, cfg) {
        var metaParts = [];
        metaParts.push('<i class="bx bx-calendar me-1 text-muted"></i>' + _fmtDate(h.TransDate));
        if (h.Reference) {
            metaParts.push('<i class="bx bx-link me-1 text-muted"></i>Ref: ' + _esc(h.Reference));
        }
        if (cfg.validLabel && h.ValidityDate) {
            metaParts.push('<i class="bx bx-time me-1 text-muted"></i>' +
                _esc(cfg.validLabel) + ': ' + _fmtDate(h.ValidityDate));
        }

        var editHref = $('#viewTransEditBtn').attr('href') || '#';
        var hideEdit = $('#viewTransEditBtn').data('hide-edit');
        var editBtn  = hideEdit ? '' :
            '<a href="' + editHref + '" class="vtm-edit-btn">' +
            '<i class="bx bx-edit"></i>Edit</a>';

        var closeBtn =
            '<button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">' +
            '<i class="bx bx-x"></i></button>';

        return '<div class="vtm-banner-inner">' +
            '<div class="vtm-banner-left">' +
                '<div class="vtm-banner-icon">' +
                    '<i class="bx ' + cfg.typeIcon + '"></i>' +
                '</div>' +
                '<div>' +
                    '<div class="vtm-doc-number">' + _esc(h.UniqueNumber || '—') + '</div>' +
                    '<div class="vtm-doc-meta">' + metaParts.join(' &nbsp;&middot;&nbsp; ') + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="vtm-banner-right">' +
                (h.PlaceOfSupply ? '<span class="badge bg-light text-secondary border vtm-pos-badge">POS: ' + _esc(h.PlaceOfSupply) + '</span>' : '') +
                _statusBadge(h.DocStatus) +
                editBtn +
                closeBtn +
            '</div>' +
        '</div>';
    }

    // ── main builder (body only — banner already shown) ────────────────────────
    window._buildTransDetailHtml = function (resp, opts) {
        opts = opts || {};
        var h   = resp.Header  || {};
        var org = resp.OrgInfo || {};
        var cur = (org.CurrenySymbol || '&#8377;') + '&nbsp;';

        var partyLabel  = opts.partyLabel  || 'Party';
        var hasPayments = !!opts.hasPayments;

        function _amt(n) { return cur + _smartDec(n); }
        function _n(n)   { return _smartDec(n); }

        var html = '<div>';

        // ── Party Details ──────────────────────────────────────────────────────
        var billAddr = _buildAddr(h.BillLine1, h.BillLine2, h.BillCity, h.BillState, h.BillPincode);
        var shipAddr = _buildAddr(h.ShipLine1, h.ShipLine2, h.ShipCity, h.ShipState, h.ShipPincode);

        var contactHtml = _cardLabel('bx-id-card', 'Contact') +
            '<div class="vtm-party-name">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyArea) {
            contactHtml += '<div class="vtm-party-sub" style="margin-top:2px;">' +
                '<i class="bx bx-map me-1 text-secondary"></i>' +
                '<span>' + _esc(h.PartyArea) + '</span>' +
                '</div>';
        }
        if (h.PartyMobile) {
            var _phoneDisplay = (h.PartyCountryCode ? _esc(h.PartyCountryCode) + ' ' : '') + _esc(h.PartyMobile);
            var _phoneCopy    = (h.PartyCountryCode || '') + h.PartyMobile;
            contactHtml += '<div class="vtm-party-sub">' +
                '<i class="bx bx-phone me-1 text-info"></i>' +
                '<span class="copy-mobile cursor-pointer" data-mobile="' + _esc(_phoneCopy) + '" title="Click to copy">' +
                _phoneDisplay + '</span>' +
                '</div>';
        }
        if (h.PartyGSTIN) {
            contactHtml += '<div class="vtm-party-gstin"><i class="bx bx-buildings me-1 text-purple"></i>GSTIN: <span>' + _esc(h.PartyGSTIN) + '</span></div>';
        }

        var partyCols = '<div class="col-sm-4 mb-2">' + _infoCard(contactHtml, 'vtm-info-card-cyan') + '</div>';
        if (billAddr) {
            var billHtml = _cardLabel('bx-home', 'Billing Address') +
                '<div class="vtm-addr-text">' + billAddr + '</div>';
            partyCols += '<div class="col-sm-4 mb-2">' + _infoCard(billHtml, 'vtm-info-card-purple') + '</div>';
        }
        if (shipAddr) {
            var shipHtml = _cardLabel('bx-map', 'Shipping Address') +
                '<div class="vtm-addr-text">' + shipAddr + '</div>';
            partyCols += '<div class="col-sm-4 mb-2">' + _infoCard(shipHtml, 'vtm-info-card-orange') + '</div>';
        }

        html += '<div class="vtm-section">' +
            _secHdr('bx-user-circle', partyLabel + ' Details', 'text-info') +
            '<div class="row g-2">' + partyCols + '</div>' +
        '</div>';

        // ── Products / Services ────────────────────────────────────────────────
        var itemRows = '';
        (resp.Items || []).forEach(function (item, i) {
            var cgstPct  = parseFloat(item.CGST  || 0);
            var sgstPct  = parseFloat(item.SGST  || 0);
            var igstPct  = parseFloat(item.IGST  || 0);
            var cgstAmt  = parseFloat(item.CgstAmount || 0);
            var sgstAmt  = parseFloat(item.SgstAmount || 0);
            var igstAmt  = parseFloat(item.IgstAmount || 0);
            var totalTax = cgstAmt + sgstAmt + igstAmt;

            var taxCell;
            if (igstPct > 0) {
                taxCell = '<span class="vtm-tax-igst">IGST ' + igstPct.toFixed(1) + '%</span>' +
                    '<br><span class="vtm-tax-amt">' + (totalTax > 0 ? _smartDec(totalTax) : '—') + '</span>';
            } else if (cgstPct > 0 || sgstPct > 0) {
                taxCell = '<span class="vtm-tax-cgst">CGST ' + cgstPct.toFixed(1) + '%</span>' +
                    ' + <span class="vtm-tax-sgst">SGST ' + sgstPct.toFixed(1) + '%</span>' +
                    '<br><span class="vtm-tax-amt">' + (totalTax > 0 ? _smartDec(totalTax) : '—') + '</span>';
            } else {
                taxCell = '<span class="text-muted">—</span>';
            }

            var discVal  = parseFloat(item.Discount || 0);
            var discCell = discVal > 0
                ? '<span class="vtm-disc-val">' + _n(discVal) + (parseInt(item.DiscountTypeUID, 10) === 2 ? '%' : '') + '</span>'
                : '<span class="text-muted">—</span>';

            itemRows +=
            '<tr>' +
                '<td class="text-center text-muted">' + (i + 1) + '</td>' +
                '<td>' +
                    '<div class="vtm-item-name">' + _esc(item.ProductName) + '</div>' +
                    (item.PartNumber ? '<div class="vtm-item-sub">Part# ' + _esc(item.PartNumber) + '</div>' : '') +
                    (item.HSNCode    ? '<div class="vtm-item-sub">HSN: '  + _esc(item.HSNCode)    + '</div>' : '') +
                '</td>' +
                '<td class="text-center">' +
                    '<span class="fw-500">' + _esc(item.Quantity) + '</span>' +
                    '<br><span class="vtm-item-sub">' + _esc(item.PrimaryUnitName) + '</span>' +
                '</td>' +
                '<td class="text-end">' + _n(item.UnitPrice) + '</td>' +
                '<td class="text-end">' + discCell + '</td>' +
                '<td class="text-end fw-500">' + _n(item.TaxableAmount) + '</td>' +
                '<td>' + taxCell + '</td>' +
                '<td class="text-end fw-semibold">' + _n(item.NetAmount) + '</td>' +
            '</tr>';
        });

        html += '<div class="vtm-section">' +
            _secHdr('bx-package', 'Products / Services', 'text-warning') +
            '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0 vtm-items-table">' +
            '<thead class="vtm-items-thead"><tr>' +
                '<th class="text-center">#</th>' +
                '<th>Product</th>' +
                '<th class="text-center">Qty</th>' +
                '<th class="text-end">Rate</th>' +
                '<th class="text-end">Disc</th>' +
                '<th class="text-end">Taxable Amt</th>' +
                '<th>Tax</th>' +
                '<th class="text-end">Net Amt</th>' +
            '</tr></thead>' +
            '<tbody>' + itemRows + '</tbody>' +
            '</table></div>' +
        '</div>';

        // ── Amount Summary ─────────────────────────────────────────────────────
        var cgstTot  = parseFloat(h.CgstAmount || 0);
        var sgstTot  = parseFloat(h.SgstAmount || 0);
        var igstTot  = parseFloat(h.IgstAmount || 0);
        var taxTot   = parseFloat(h.TaxAmount  || 0);
        var discTot  = parseFloat(h.DiscountAmount || 0);
        var addChg   = parseFloat(h.AdditionalChargesTotal || 0);
        var roundOff = parseFloat(h.RoundOff || 0);

        var summRows = '';
        summRows += _summaryRow('Sub Total', _amt(h.SubTotal));
        if (discTot > 0) {
            summRows += _summaryRow(
                '<i class="bx bx-minus-circle me-1"></i>Discount',
                '&minus;&nbsp;' + _amt(h.DiscountAmount), 'vtm-summary-disc'
            );
        }
        if (igstTot > 0) {
            summRows += _summaryRow('IGST', cur + _smartDec(h.IgstAmount));
        } else {
            if (cgstTot > 0) summRows += _summaryRow('CGST', cur + _smartDec(h.CgstAmount));
            if (sgstTot > 0) summRows += _summaryRow('SGST', cur + _smartDec(h.SgstAmount));
            if (taxTot > 0 && cgstTot === 0 && sgstTot === 0) summRows += _summaryRow('Tax', _amt(h.TaxAmount));
        }
        if (addChg   > 0) summRows += _summaryRow('Additional Charges', _amt(h.AdditionalChargesTotal));
        if (roundOff !== 0) summRows += _summaryRow('Round Off', cur + (roundOff >= 0 ? '+' : '') + _smartDec(roundOff));
        summRows += _summaryRow(
            'Net Amount',
            '<span class="vtm-net-val">' + _amt(h.NetAmount) + '</span>',
            'vtm-summary-net'
        );

        html += '<div class="vtm-section">' +
            _secHdr('bx-calculator', 'Amount Summary', 'text-success') +
            '<div class="row"><div class="col-md-5 ms-auto">' +
                '<table class="table table-sm mb-0 vtm-summary-table">' +
                '<tbody>' + summRows + '</tbody></table>' +
            '</div></div>' +
        '</div>';

        // ── Notes / Terms ──────────────────────────────────────────────────────
        if (h.Notes || h.TermsConditions) {
            html += '<div class="vtm-section-notes">';
            if (h.Notes) {
                html += '<div class="mb-2">' +
                    '<div class="vtm-notes-label"><i class="bx bx-note me-1"></i>Notes</div>' +
                    '<div class="vtm-notes-text">' + _escNl(h.Notes) + '</div>' +
                '</div>';
            }
            if (h.TermsConditions) {
                html += '<div' + (h.Notes ? ' class="mt-2"' : '') + '>' +
                    '<div class="vtm-notes-label"><i class="bx bx-file-blank me-1"></i>Terms &amp; Conditions</div>' +
                    '<div class="vtm-terms-text">' + _escNl(h.TermsConditions) + '</div>' +
                '</div>';
            }
            html += '</div>';
        }

        // ── Payment Details ────────────────────────────────────────────────────
        if (hasPayments) {
            var payments  = resp.Payments  || [];
            var paidTotal = parseFloat(resp.PaidTotal || 0);
            var netAmt    = parseFloat(h.NetAmount    || 0);
            var balance   = Math.max(0, netAmt - paidTotal);
            var settled   = balance <= 0.001;

            function _pill(pillClass, iconCls, label, value) {
                return '<div class="vtm-pay-pill ' + pillClass + '">' +
                    '<i class="bx ' + iconCls + '"></i>' +
                    '<span>' + label + ': ' + cur + _smartDec(value) + '</span></div>';
            }

            var paidPill = _pill('vtm-pay-pill-paid', 'bx-check-circle', 'Paid', paidTotal);
            var balPill  = settled
                ? _pill('vtm-pay-pill-paid', 'bx-check-circle', 'Balance', balance)
                : _pill('vtm-pay-pill-due',  'bx-error-circle', 'Balance Due', balance);

            var payRows = '';
            if (payments.length) {
                payments.forEach(function (p) {
                    var bankInfo = p.AccountName
                        ? _esc(p.AccountName) + (p.BankName ? ' / ' + _esc(p.BankName) : '')
                        : '—';
                    payRows +=
                    '<tr>' +
                        '<td>' + _fmtDate(p.CreatedOn) + '</td>' +
                        '<td><span class="badge bg-light text-dark border vtm-pay-mode">' + _esc(p.PaymentTypeName || '—') + '</span></td>' +
                        '<td class="vtm-item-sub">' + bankInfo + '</td>' +
                        '<td class="text-muted vtm-item-sub">' + _esc(p.ReferenceNo || '—') + '</td>' +
                        '<td class="text-end fw-semibold">' + cur + _smartDec(p.Amount) + '</td>' +
                    '</tr>';
                });
            } else {
                payRows = '<tr><td colspan="5" class="text-center text-muted py-3">No payments recorded yet</td></tr>';
            }

            html += '<div class="vtm-section-last">' +
                _secHdr('bx-wallet', 'Payment Details', 'text-purple') +
                '<div class="d-flex gap-3 mb-3 flex-wrap">' + paidPill + balPill + '</div>' +
                '<div class="table-responsive">' +
                '<table class="table table-sm table-hover mb-0 vtm-pay-table">' +
                '<thead class="vtm-pay-thead"><tr>' +
                '<th>Date</th><th>Mode</th><th>Account</th><th>Reference</th>' +
                '<th class="text-end">Amount</th></tr></thead>' +
                '<tbody>' + payRows + '</tbody>' +
                '</table></div>' +
            '</div>';
        } else {
            html += '<div class="vtm-spacer"></div>';
        }

        html += '</div>';
        return html;
    };

    // ── Type config ────────────────────────────────────────────────────────────
    var _typeConfig = {
        'quotation': {
            title      : 'Quotation Details',
            editPath   : '/quotations/edit/',
            dataKey    : '_quotLastPrintData',
            partyLabel : 'Customer',
            typeIcon   : 'bx-file-blank',
            typeColor  : '#0891b2',
            typeBg     : '#e0f5fb',
            hasPayments: false,
            validLabel : 'Valid Until',
        },
        'invoice': {
            title      : 'Invoice Details',
            editPath   : '/invoices/edit/',
            dataKey    : '_invLastPrintData',
            partyLabel : 'Customer',
            typeIcon   : 'bx-receipt',
            typeColor  : '#0d6efd',
            typeBg     : '#e8f0fe',
            hasPayments: true,
        },
        'purchase': {
            title      : 'Purchase Bill Details',
            editPath   : '/purchases/edit/',
            dataKey    : '_purchLastPrintData',
            partyLabel : 'Vendor',
            typeIcon   : 'bx-cart',
            typeColor  : '#6f42c1',
            typeBg     : '#f0ebff',
            hasPayments: true,
            validLabel : 'Bill Due',
        },
        'salesorder': {
            title      : 'Sales Order Details',
            editPath   : '/salesorders/edit/',
            dataKey    : '_soLastPrintData',
            partyLabel : 'Customer',
            typeIcon   : 'bx-store-alt',
            typeColor  : '#d97706',
            typeBg     : '#fff8e1',
            hasPayments: false,
            validLabel : 'Expected Delivery',
        },
        'purchaseorder': {
            title      : 'Purchase Order Details',
            editPath   : '/purchaseorders/edit/',
            dataKey    : '_poLastPrintData',
            partyLabel : 'Vendor',
            typeIcon   : 'bx-purchase-tag-alt',
            typeColor  : '#0f766e',
            typeBg     : '#e0f5f2',
            hasPayments: false,
            validLabel : 'Expected Delivery',
        },
    };

    // ── Attachment section ─────────────────────────────────────────────────────
    function _buildAttachSectionHtml(attachments) {
        if (!attachments || !attachments.length) return '';
        var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
        var cards = '';
        attachments.forEach(function (a) {
            var name     = a.FileName || '';
            var safeName = $('<span>').text(name).html();
            var fullUrl  = cdnUrl + (a.FilePath || '');
            var encUrl   = encodeURIComponent(fullUrl);
            var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(name);
            var isPdf    = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
            var previewType = isImg ? 'img' : (isPdf ? 'pdf' : 'file');
            var iconCls  = isImg ? 'bx-image-alt text-success' : (isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary');
            var bgClass  = isImg ? 'bg-success bg-opacity-10' : (isPdf ? 'bg-danger bg-opacity-10' : 'bg-light');

            cards +=
            '<div class="col-6 col-sm-4 col-md-3">' +
                '<div class="vtm-attach-card border ' + bgClass + '" ' +
                'onclick="typeof _openAttachPreview===\'function\' && _openAttachPreview(\'' + encUrl + '\',\'' + previewType + '\',\'' + safeName.replace(/'/g, "\\'") + '\')">' +
                    (isImg
                        ? '<img src="' + $('<span>').text(fullUrl).html() + '" class="w-100" style="height:80px;object-fit:cover;display:block;" loading="lazy" alt="' + safeName + '">'
                        : '<div class="d-flex align-items-center justify-content-center" style="height:80px;"><i class="bx ' + iconCls + ' fs-1"></i></div>'
                    ) +
                    '<div class="vtm-attach-name" title="' + safeName + '">' + safeName + '</div>' +
                '</div>' +
            '</div>';
        });
        return '<div class="vtm-section-last">' +
            _secHdr('bx-paperclip', 'Attachments (' + attachments.length + ')', 'text-secondary') +
            '<div class="row g-2">' + cards + '</div>' +
        '</div>';
    }

    // ── Click handler ──────────────────────────────────────────────────────────
    $(document).on('click', '.viewTransaction', function () {
        var uid       = $(this).data('uid');
        var moduleUID = $(this).data('module');
        var type      = $(this).data('type');
        var cfg       = _typeConfig[type];
        if (!cfg || !uid || !moduleUID) return;

        // Set CSS variables for banner colour
        var $modal = $('#viewTransModal');
        $modal[0].style.setProperty('--vtm-color', cfg.typeColor);
        $modal[0].style.setProperty('--vtm-bg',    cfg.typeBg);
        $modal[0].style.setProperty('--vtm-icon-bg', cfg.typeColor + '22');

        // Set edit href
        $('#viewTransEditBtn').attr('href', cfg.editPath + uid);

        // Build instant header from data attrs embedded in the row link
        var quickHeader = {
            UniqueNumber: $(this).data('number') || $(this).text().trim(),
            TransDate   : $(this).data('date')   || '',
            DocStatus   : $(this).data('status') || '',
        };
        var $hdr = $('#viewTransModalHeader');
        $hdr.html(_buildBannerHtml(quickHeader, cfg)).removeClass('d-none');

        // Show spinner in body while AJAX loads
        $('#viewTransModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary"></div></div>'
        );

        // Show modal immediately — header already visible
        $modal.modal('show');
        AjaxLoading = 0;

        $.ajax({
            url   : '/transactions/getTransactionDetail',
            method: 'POST',
            data  : { TransUID: uid, ModuleUID: moduleUID, [CsrfName]: CsrfToken },
        }).done(function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                $('#viewTransModalBody').html('<div class="alert alert-danger m-3">' + _esc(resp.Message || 'Error loading details.') + '</div>');
                return;
            }
            window[cfg.dataKey] = resp;

            // Replace quick header with full data (adds PlaceOfSupply, Reference, ValidityDate)
            $hdr.html(_buildBannerHtml(resp.Header || {}, cfg)).removeClass('d-none');

            // Populate body
            var bodyHtml = _buildTransDetailHtml(resp, cfg);

            // Append attachments if present (already included in the same response)
            if (resp.Attachments && resp.Attachments.length > 0) {
                bodyHtml += _buildAttachSectionHtml(resp.Attachments);
            }

            $('#viewTransModalBody').html(bodyHtml);

        }).fail(function () {
            AjaxLoading = 1;
            $('#viewTransModalBody').html('<div class="alert alert-danger m-3">Failed to load transaction details.</div>');
        });
    });

}());
