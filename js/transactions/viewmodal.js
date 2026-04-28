/**
 * js/transactions/viewmodal.js
 * Shared builder for the viewTransModal deep-preview panel.
 * Exposes: window._buildTransDetailHtml(resp, opts)
 */
(function () {
    'use strict';

    // ── private helpers ────────────────────────────────────────────────────────

    function _esc(v) {
        if (v === null || v === undefined) return '';
        return $('<span>').text(String(v)).html();
    }

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

    /**
     * Smart decimal: show 3 decimal places only when the 3rd decimal digit is non-zero.
     * Works on both numeric and string inputs — checks the string representation
     * directly so trailing zeros preserved by PHP/DB are handled correctly.
     */
    function _smartDec(n) {
        if (n === null || n === undefined || n === '') return '0.00';
        var str     = String(n).trim();
        var dotIdx  = str.indexOf('.');
        if (dotIdx !== -1) {
            var decPart = str.slice(dotIdx + 1);
            if (decPart.length >= 3 && decPart[2] !== '0') {
                return parseFloat(str).toFixed(3);
            }
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
        return '<span class="badge ' + cls + ' px-2 py-1" style="font-size:.72rem;font-weight:600;">' +
            _esc(status) + '</span>';
    }

    function _buildAddr(l1, l2, city, state, pin) {
        var parts = [];
        if (l1)  parts.push(_esc(l1));
        if (l2)  parts.push(_esc(l2));
        var cs = [city, state].filter(Boolean).map(_esc).join(', ');
        if (pin) cs += (cs ? ' &ndash; ' : '') + _esc(pin);
        if (cs)  parts.push(cs);
        return parts.join('<br>');
    }

    function _secHdr(icon, label, color) {
        return '<div class="d-flex align-items-center gap-2" style="padding:4px 0 8px;">' +
            '<i class="bx ' + icon + '" style="font-size:1.05rem;color:' + color + ';"></i>' +
            '<span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:' + color + ';">' +
            label + '</span></div>';
    }

    function _infoCard(content, borderColor) {
        return '<div style="background:#fafafa;border:1px solid #e9ecef;border-left:3px solid ' +
            borderColor + ';border-radius:6px;padding:10px 12px;height:100%;min-height:70px;">' +
            content + '</div>';
    }

    function _cardLabel(icon, text) {
        return '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;' +
            'color:#6c757d;margin-bottom:5px;"><i class="bx ' + icon + ' me-1"></i>' + text + '</div>';
    }

    function _summaryRow(label, value, color, topBorder) {
        var rowStyle   = topBorder ? 'border-top:2px solid ' + topBorder + ';' : '';
        var labelStyle = color ? 'color:' + color + ';font-weight:700;' : '';
        var valStyle   = color ? 'color:' + color + ';font-weight:700;' : '';
        return '<tr style="' + rowStyle + '">' +
            '<td style="padding:5px 8px;' + labelStyle + '">' + label + '</td>' +
            '<td class="text-end" style="padding:5px 8px;' + valStyle + '">' + value + '</td>' +
        '</tr>';
    }

    // ── main builder ───────────────────────────────────────────────────────────

    window._buildTransDetailHtml = function (resp, opts) {
        opts = opts || {};
        var h   = resp.Header  || {};
        var org = resp.OrgInfo || {};
        var cur = (org.CurrenySymbol || '&#8377;') + '&nbsp;';

        var partyLabel  = opts.partyLabel  || 'Party';
        var typeIcon    = opts.typeIcon    || 'bx-file-blank';
        var typeColor   = opts.typeColor   || '#0d6efd';
        var typeBg      = opts.typeBg      || '#e7f0ff';
        var hasPayments = !!opts.hasPayments;
        var validLabel  = opts.validLabel  || '';

        function _amt(n)  { return cur + _smartDec(n); }
        function _n(n)    { return _smartDec(n); }

        var html = '<div>';

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 1 — Document Banner
        // ══════════════════════════════════════════════════════════════════════
        var metaParts = [];
        metaParts.push('<i class="bx bx-calendar me-1" style="color:#6c757d;"></i>' + _fmtDate(h.TransDate));
        if (h.Reference) {
            metaParts.push('<i class="bx bx-link me-1" style="color:#6c757d;"></i>Ref: ' + _esc(h.Reference));
        }
        if (validLabel && h.ValidityDate) {
            metaParts.push('<i class="bx bx-time me-1" style="color:#6c757d;"></i>' +
                _esc(validLabel) + ': ' + _fmtDate(h.ValidityDate));
        }

        var editHref = $('#viewTransEditBtn').attr('href') || '#';
        var editBtn  =
            '<a href="' + editHref + '" style="display:inline-flex;align-items:center;gap:4px;' +
            'font-size:.75rem;font-weight:600;color:' + typeColor + ';border:1px solid ' + typeColor + ';' +
            'border-radius:5px;padding:3px 10px;text-decoration:none;white-space:nowrap;' +
            'background:rgba(255,255,255,.7);">' +
            '<i class="bx bx-edit" style="font-size:.9rem;"></i>Edit</a>';

        var closeBtn =
            '<button type="button" data-bs-dismiss="modal" aria-label="Close" ' +
            'style="background:rgba(255,255,255,.85);border:none;border-radius:50%;width:28px;height:28px;' +
            'display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;' +
            'box-shadow:0 1px 4px rgba(0,0,0,.15);padding:0;">' +
            '<i class="bx bx-x" style="font-size:1.2rem;color:#555;line-height:1;"></i></button>';

        html +=
        '<div style="background:' + typeBg + ';border-left:4px solid ' + typeColor + ';padding:14px 20px;">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
                '<div style="display:flex;align-items:center;gap:12px;">' +
                    '<div style="background:' + typeColor + '22;border-radius:10px;padding:9px 11px;flex-shrink:0;">' +
                        '<i class="bx ' + typeIcon + '" style="font-size:1.7rem;color:' + typeColor + ';display:block;"></i>' +
                    '</div>' +
                    '<div>' +
                        '<div style="font-size:1.12rem;font-weight:800;color:' + typeColor + ';letter-spacing:.2px;line-height:1.2;">' +
                            _esc(h.UniqueNumber || '—') +
                        '</div>' +
                        '<div style="font-size:.77rem;color:#6c757d;margin-top:4px;">' +
                            metaParts.join(' &nbsp;&middot;&nbsp; ') +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">' +
                    (h.PlaceOfSupply
                        ? '<span class="badge bg-light text-secondary border" style="font-size:.7rem;font-weight:500;">POS: ' + _esc(h.PlaceOfSupply) + '</span>'
                        : '') +
                    _statusBadge(h.DocStatus) +
                    editBtn +
                    closeBtn +
                '</div>' +
            '</div>' +
        '</div>';

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 2 — Party Details
        // ══════════════════════════════════════════════════════════════════════
        var billAddr = _buildAddr(h.BillLine1, h.BillLine2, h.BillCity, h.BillState, h.BillPincode);
        var shipAddr = _buildAddr(h.ShipLine1, h.ShipLine2, h.ShipCity, h.ShipState, h.ShipPincode);

        var contactHtml = _cardLabel('bx-id-card', 'Contact') +
            '<div style="font-size:.9rem;font-weight:600;color:#212529;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) {
            contactHtml += '<div style="font-size:.8rem;color:#6c757d;margin-top:3px;">' +
                '<i class="bx bx-phone me-1" style="color:#17a2b8;"></i>' + _esc(h.PartyMobile) + '</div>';
        }
        if (h.PartyGSTIN) {
            contactHtml += '<div style="font-size:.78rem;color:#6c757d;margin-top:2px;">' +
                '<i class="bx bx-buildings me-1" style="color:#6f42c1;"></i>GSTIN: ' +
                '<span style="font-family:monospace;font-size:.82rem;">' + _esc(h.PartyGSTIN) + '</span></div>';
        }

        var partyCols = '<div class="col-sm-4 mb-2">' + _infoCard(contactHtml, '#17a2b8') + '</div>';
        if (billAddr) {
            var billHtml = _cardLabel('bx-home', 'Billing Address') +
                '<div style="font-size:.8rem;color:#495057;line-height:1.55;">' + billAddr + '</div>';
            partyCols += '<div class="col-sm-4 mb-2">' + _infoCard(billHtml, '#6f42c1') + '</div>';
        }
        if (shipAddr) {
            var shipHtml = _cardLabel('bx-map', 'Shipping Address') +
                '<div style="font-size:.8rem;color:#495057;line-height:1.55;">' + shipAddr + '</div>';
            partyCols += '<div class="col-sm-4 mb-2">' + _infoCard(shipHtml, '#fd7e14') + '</div>';
        }

        html +=
        '<div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">' +
            _secHdr('bx-user-circle', partyLabel + ' Details', '#17a2b8') +
            '<div class="row g-2">' + partyCols + '</div>' +
        '</div>';

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 3 — Products / Services  (Tax Amt column merged into Tax column)
        // ══════════════════════════════════════════════════════════════════════
        var itemRows = '';
        (resp.Items || []).forEach(function (item, i) {
            var cgstPct  = parseFloat(item.CGST  || 0);
            var sgstPct  = parseFloat(item.SGST  || 0);
            var igstPct  = parseFloat(item.IGST  || 0);
            var cgstAmt  = parseFloat(item.CgstAmount || 0);
            var sgstAmt  = parseFloat(item.SgstAmount || 0);
            var igstAmt  = parseFloat(item.IgstAmount || 0);
            var totalTax = cgstAmt + sgstAmt + igstAmt;

            // Tax column: percentage on line 1, total tax amount on line 2
            var taxCell;
            if (igstPct > 0) {
                taxCell =
                    '<span style="color:#6f42c1;font-weight:600;">IGST ' + igstPct.toFixed(1) + '%</span>' +
                    '<br><span style="color:#6c757d;font-size:.78rem;">' +
                    (totalTax > 0 ? _smartDec(totalTax) : '—') + '</span>';
            } else if (cgstPct > 0 || sgstPct > 0) {
                taxCell =
                    '<span style="color:#0d6efd;">CGST ' + cgstPct.toFixed(1) + '%</span>' +
                    ' + <span style="color:#17a2b8;">SGST ' + sgstPct.toFixed(1) + '%</span>' +
                    '<br><span style="color:#6c757d;font-size:.78rem;">' +
                    (totalTax > 0 ? _smartDec(totalTax) : '—') + '</span>';
            } else {
                taxCell = '<span class="text-muted">—</span>';
            }

            var discVal  = parseFloat(item.Discount || 0);
            var discCell = discVal > 0
                ? '<span style="color:#dc3545;">' + _n(discVal) +
                  (parseInt(item.DiscountTypeUID, 10) === 2 ? '%' : '') + '</span>'
                : '<span class="text-muted">—</span>';

            itemRows +=
            '<tr>' +
                '<td class="text-center text-muted" style="width:32px;">' + (i + 1) + '</td>' +
                '<td>' +
                    '<div style="font-weight:500;">' + _esc(item.ProductName) + '</div>' +
                    (item.PartNumber ? '<div style="font-size:.72rem;color:#6c757d;">Part# ' + _esc(item.PartNumber) + '</div>' : '') +
                    (item.HSNCode    ? '<div style="font-size:.72rem;color:#6c757d;">HSN: '  + _esc(item.HSNCode)    + '</div>' : '') +
                '</td>' +
                '<td class="text-center">' +
                    '<span style="font-weight:500;">' + _esc(item.Quantity) + '</span>' +
                    '<br><span style="font-size:.72rem;color:#6c757d;">' + _esc(item.PrimaryUnitName) + '</span>' +
                '</td>' +
                '<td class="text-end">' + _n(item.UnitPrice) + '</td>' +
                '<td class="text-end">' + discCell + '</td>' +
                '<td class="text-end" style="font-weight:500;">' + _n(item.TaxableAmount) + '</td>' +
                '<td style="font-size:.8rem;line-height:1.5;">' + taxCell + '</td>' +
                '<td class="text-end fw-semibold">' + _n(item.NetAmount) + '</td>' +
            '</tr>';
        });

        html +=
        '<div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">' +
            _secHdr('bx-package', 'Products / Services', '#fd7e14') +
            '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0" style="font-size:.8rem;">' +
            '<thead><tr style="background:#fff3e0;">' +
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

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 4 — Amount Summary
        // ══════════════════════════════════════════════════════════════════════
        var cgstTot  = parseFloat(h.CgstAmount || 0);
        var sgstTot  = parseFloat(h.SgstAmount || 0);
        var igstTot  = parseFloat(h.IgstAmount || 0);
        var taxTot   = parseFloat(h.TaxAmount  || 0);
        var discTot  = parseFloat(h.DiscountAmount || 0);
        var addChg   = parseFloat(h.AdditionalChargesTotal || 0);
        var roundOff = parseFloat(h.RoundOff || 0);

        var summRows = '';
        summRows += _summaryRow('Sub Total', _amt(h.SubTotal), null, null);
        if (discTot > 0) {
            summRows += _summaryRow(
                '<i class="bx bx-minus-circle me-1"></i>Discount',
                '&minus;&nbsp;' + _amt(h.DiscountAmount), '#dc3545', null
            );
        }
        if (igstTot > 0) {
            summRows += _summaryRow('IGST', cur + _smartDec(h.IgstAmount), null, null);
        } else {
            if (cgstTot > 0) summRows += _summaryRow('CGST', cur + _smartDec(h.CgstAmount), null, null);
            if (sgstTot > 0) summRows += _summaryRow('SGST', cur + _smartDec(h.SgstAmount), null, null);
            if (taxTot > 0 && cgstTot === 0 && sgstTot === 0) {
                summRows += _summaryRow('Tax', _amt(h.TaxAmount), null, null);
            }
        }
        if (addChg > 0) {
            summRows += _summaryRow('Additional Charges', _amt(h.AdditionalChargesTotal), null, null);
        }
        if (roundOff !== 0) {
            summRows += _summaryRow(
                'Round Off',
                cur + (roundOff >= 0 ? '+' : '') + _smartDec(roundOff), null, null
            );
        }
        summRows += _summaryRow(
            'Net Amount',
            '<span style="font-size:.95rem;">' + _amt(h.NetAmount) + '</span>',
            '#198754', '#198754'
        );

        html +=
        '<div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">' +
            _secHdr('bx-calculator', 'Amount Summary', '#198754') +
            '<div class="row"><div class="col-md-5 ms-auto">' +
                '<table class="table table-sm mb-0" style="font-size:.85rem;border:1px solid #dee2e6;border-radius:6px;overflow:hidden;">' +
                '<tbody>' + summRows + '</tbody></table>' +
            '</div></div>' +
        '</div>';

        // ── Notes / Terms ────────────────────────────────────────────────────
        if (h.Notes || h.TermsConditions) {
            html += '<div style="padding:12px 20px;border-bottom:1px solid #e9ecef;background:#fafafa;">';
            if (h.Notes) {
                html +=
                '<div class="mb-2">' +
                    '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:4px;">' +
                    '<i class="bx bx-note me-1"></i>Notes</div>' +
                    '<div style="font-size:.82rem;color:#495057;line-height:1.6;">' + _escNl(h.Notes) + '</div>' +
                '</div>';
            }
            if (h.TermsConditions) {
                html +=
                '<div' + (h.Notes ? ' class="mt-2"' : '') + '>' +
                    '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:4px;">' +
                    '<i class="bx bx-file-blank me-1"></i>Terms &amp; Conditions</div>' +
                    '<div style="font-size:.82rem;color:#495057;line-height:1.7;">' + _escNl(h.TermsConditions) + '</div>' +
                '</div>';
            }
            html += '</div>';
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 5 — Payment Details  (Invoice & Purchase only)
        // ══════════════════════════════════════════════════════════════════════
        if (hasPayments) {
            var payments  = resp.Payments  || [];
            var paidTotal = parseFloat(resp.PaidTotal || 0);
            var netAmt    = parseFloat(h.NetAmount    || 0);
            var balance   = Math.max(0, netAmt - paidTotal);
            var settled   = balance <= 0.001;

            function _pill(bg, border, iconCls, textColor, label, value) {
                return '<div style="display:inline-flex;align-items:center;gap:6px;background:' + bg +
                    ';border:1px solid ' + border + ';border-radius:20px;padding:6px 16px;">' +
                    '<i class="bx ' + iconCls + '" style="color:' + textColor + ';font-size:1rem;"></i>' +
                    '<span style="font-size:.82rem;font-weight:600;color:' + textColor + ';">' +
                    label + ': ' + cur + _smartDec(value) + '</span></div>';
            }

            var paidPill = _pill('#d1e7dd','#a3cfbb','bx-check-circle','#0f5132','Paid', paidTotal);
            var balPill  = settled
                ? _pill('#d1e7dd','#a3cfbb','bx-check-circle','#0f5132','Balance', balance)
                : _pill('#f8d7da','#f1aeb5','bx-error-circle','#842029','Balance Due', balance);

            var payRows = '';
            if (payments.length) {
                payments.forEach(function (p) {
                    var bankInfo = p.AccountName
                        ? _esc(p.AccountName) + (p.BankName ? ' / ' + _esc(p.BankName) : '')
                        : '—';
                    payRows +=
                    '<tr>' +
                        '<td>' + _fmtDate(p.CreatedOn) + '</td>' +
                        '<td><span class="badge bg-light text-dark border" style="font-size:.72rem;">' +
                            _esc(p.PaymentTypeName || '—') + '</span></td>' +
                        '<td style="font-size:.78rem;">' + bankInfo + '</td>' +
                        '<td class="text-muted" style="font-size:.78rem;">' + _esc(p.ReferenceNo || '—') + '</td>' +
                        '<td class="text-end fw-semibold">' + cur + _smartDec(p.Amount) + '</td>' +
                    '</tr>';
                });
            } else {
                payRows = '<tr><td colspan="5" class="text-center text-muted py-3">No payments recorded yet</td></tr>';
            }

            html +=
            '<div style="padding:14px 20px;">' +
                _secHdr('bx-wallet', 'Payment Details', '#6f42c1') +
                '<div class="d-flex gap-3 mb-3 flex-wrap">' + paidPill + balPill + '</div>' +
                '<div class="table-responsive">' +
                '<table class="table table-sm table-hover mb-0" style="font-size:.8rem;">' +
                '<thead style="background:#f3eeff;"><tr>' +
                '<th>Date</th><th>Mode</th><th>Account</th><th>Reference</th>' +
                '<th class="text-end">Amount</th></tr></thead>' +
                '<tbody>' + payRows + '</tbody>' +
                '</table></div>' +
            '</div>';
        } else {
            html += '<div style="height:8px;"></div>';
        }

        html += '</div>';
        return html;
    };

    // ── Type config: one entry per transaction type ────────────────────────────
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

    // ── Attachment section builder ─────────────────────────────────────────────
    function _buildAttachSectionHtml(attachments) {
        if (!attachments || !attachments.length) return '';
        var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
        var cards = '';
        attachments.forEach(function (a) {
            var name    = a.FileName || '';
            var safeName = $('<span>').text(name).html();
            var fullUrl  = cdnUrl + (a.FilePath || '');
            var encUrl   = encodeURIComponent(fullUrl);
            var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(name);
            var isPdf    = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
            var previewType = isImg ? 'img' : (isPdf ? 'pdf' : 'file');
            var iconCls  = isImg ? 'bx-image-alt text-success' : (isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary');
            var bgColor  = isImg ? '#f0fff4' : (isPdf ? '#fff5f5' : '#f8f9fa');

            cards +=
            '<div class="col-6 col-sm-4 col-md-3">' +
                '<div class="border rounded overflow-hidden" style="cursor:pointer;background:' + bgColor + ';" ' +
                'onclick="typeof _openAttachPreview===\'function\' && _openAttachPreview(\'' + encUrl + '\',\'' + previewType + '\',\'' + safeName.replace(/'/g, "\\'") + '\')">' +
                    (isImg
                        ? '<img src="' + $('<span>').text(fullUrl).html() + '" style="width:100%;height:80px;object-fit:cover;display:block;" loading="lazy" alt="' + safeName + '">'
                        : '<div class="d-flex align-items-center justify-content-center" style="height:80px;">' +
                          '<i class="bx ' + iconCls + '" style="font-size:2.2rem;"></i></div>'
                    ) +
                    '<div class="px-2 py-1 border-top" style="background:#fff;font-size:.7rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeName + '">' +
                        safeName +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        return '<div style="padding:14px 20px;">' +
            _secHdr('bx-paperclip', 'Attachments (' + attachments.length + ')', '#6c757d') +
            '<div class="row g-2">' + cards + '</div>' +
        '</div>';
    }

    // ── Single common click handler for all transaction view buttons ──────────
    $(document).on('click', '.viewTransaction', function () {
        var uid       = $(this).data('uid');
        var moduleUID = $(this).data('module');
        var type      = $(this).data('type');
        var cfg       = _typeConfig[type];
        if (!cfg || !uid || !moduleUID) return;

        $('#viewTransModal').modal('show');
        $('#viewTransModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewTransEditBtn').attr('href', cfg.editPath + uid);
        AjaxLoading = 0;

        var detailReq = $.ajax({
            url   : '/transactions/getTransactionDetail',
            method: 'POST',
            data  : { TransUID: uid, ModuleUID: moduleUID, [CsrfName]: CsrfToken },
        });

        // Fetch attachments in parallel for invoice type
        var attachReq = (type === 'invoice')
            ? $.ajax({ url: '/invoices/getAttachments', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken } })
            : null;

        detailReq.done(function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                $('#viewTransModalBody').html('<div class="alert alert-danger m-3">' + _esc(resp.Message || 'Error loading details.') + '</div>');
                return;
            }
            window[cfg.dataKey] = resp;
            $('#viewTransModalBody').html(_buildTransDetailHtml(resp, cfg));

            if (attachReq) {
                attachReq.done(function (aResp) {
                    if (!aResp.Error && aResp.Attachments && aResp.Attachments.length > 0) {
                        $('#viewTransModalBody > div').first().append(_buildAttachSectionHtml(aResp.Attachments));
                    }
                });
            }
        }).fail(function () {
            AjaxLoading = 1;
            $('#viewTransModalBody').html('<div class="alert alert-danger m-3">Failed to load transaction details.</div>');
        });
    });

}());
