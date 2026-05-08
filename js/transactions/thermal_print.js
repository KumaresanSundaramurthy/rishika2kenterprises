// ── Thermal Print — Common handler for all transaction & payment pages ────

var _thermalData    = null;
var _thermalPmtData = null;

// ── TRANSACTION pages: .thermalPrintTransaction ───────────────────────────
$(document).on('click', '.thermalPrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _thermalData    = null;
    _thermalPmtData = null;
    $('#thermalPrintBtn').addClass('d-none').off('click.thermal').on('click.thermal', function () {
        if (!_thermalData) return;
        _openThermalPrintWindow(_buildThermalHtml(_thermalData, 1), _thermalData.ThermalConfig);
    });
    $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
    AjaxLoading = 0;
    $.ajax({
        url   : '/transactions/getTransactionDetail',
        method: 'GET',
        data  : { TransUID: uid, ModuleUID: moduleUID, PrintType: 'thermal' },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                $('#thermalPrintBody').html('<div class="alert alert-danger m-2">' + _esc(resp.Message) + '</div>');
                return;
            }
            _thermalData = resp;
            $('#thermalPrintBody').html(_buildThermalHtml(resp, 0));
            $('#thermalPrintBtn').removeClass('d-none');
        },
        error: function () {
            AjaxLoading = 1;
            $('#thermalPrintBody').html('<div class="alert alert-danger m-2">Failed to load receipt.</div>');
        }
    });
});

// ── PAYMENT pages: .pmtThermalPrint ──────────────────────────────────────
$(document).on('click', '.pmtThermalPrint', function () {
    var paymentUID  = $(this).data('payment-uid');
    _thermalData    = null;
    _thermalPmtData = null;
    $('#thermalPrintBtn').addClass('d-none').off('click.thermal').on('click.thermal', function () {
        if (!_thermalPmtData) return;
        _openThermalPrintWindow(_buildPmtThermalHtml(_thermalPmtData, 1), _thermalPmtData.ThermalConfig);
    });
    $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
    AjaxLoading = 0;
    $.ajax({
        url   : '/payments/getPaymentPrintDetail',
        method: 'GET',
        data  : { PaymentUID: paymentUID, PrintType: 'thermal' },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                $('#thermalPrintBody').html('<div class="alert alert-danger m-2">' + _esc(resp.Message) + '</div>');
                return;
            }
            _thermalPmtData = resp;
            $('#thermalPrintBody').html(_buildPmtThermalHtml(resp, 0));
            $('#thermalPrintBtn').removeClass('d-none');
        },
        error: function () {
            AjaxLoading = 1;
            $('#thermalPrintBody').html('<div class="alert alert-danger m-2">Failed to load payment receipt.</div>');
        }
    });
});

// ── Shared payment data loader (used by A4 print handlers in view pages) ─
function _pmtLoadPrintData(paymentUID, printType, cb) {
    AjaxLoading = 0;
    $.ajax({
        url   : '/payments/getPaymentPrintDetail',
        method: 'GET',
        data  : { PaymentUID: paymentUID, PrintType: printType },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
            cb(resp);
        },
        error: function () {
            AjaxLoading = 1;
            Swal.fire({ icon: 'error', text: 'Failed to load payment data.' });
        }
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function _esc(v) {
    if (v === null || v === undefined) return '';
    return $('<span>').text(String(v)).html();
}

function _resolveLogoUrl(logo) {
    if (!logo) return '';
    if (/^https?:\/\//i.test(logo)) return logo;
    var cdn = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
    return cdn + logo;
}

function _stripHtml(v) {
    if (!v) return '';
    return $('<div>').html(String(v)).text().trim();
}

function _fmtDate(val) {
    if (!val) return '—';
    var d = new Date(val);
    if (isNaN(d)) return val;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var dd   = String(d.getDate()).padStart(2, '0');
    var mon  = months[d.getMonth()];
    var yyyy = d.getFullYear();
    return dd + ' ' + mon + ' ' + yyyy;
}

function _openThermalPrintWindow(html, cfg) {
    var pw  = (cfg && cfg.PaperWidth) ? cfg.PaperWidth : '80mm';
    var win = window.open('', '_blank', 'width=400,height=700');
    win.document.write(
        '<!DOCTYPE html><html><head><title>Receipt</title><style>' +
        '* { margin:0; padding:0; box-sizing:border-box; }' +
        'body { font-family: Arial, Helvetica, sans-serif; font-size:12px; width:' + pw + '; padding:4px; }' +
        '.tp-hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }' +
        '@media print { @page { margin:0; size:' + pw + ' auto; } body { width:' + pw + '; padding:0 4px 20px 4px; } }' +
        '</style></head><body style="font-family:Arial,Helvetica,sans-serif!important;font-size:12px!important;width:' + pw + ';padding:0 4px 0 4px;">' +
        html + '</body></html>'
    );
    win.document.close();
    win.focus();
    win.addEventListener('afterprint', function () { win.close(); });

    // Wait for all images (e.g. QR code) to finish loading before printing
    var imgs = win.document.images;
    if (imgs.length === 0) {
        setTimeout(function () { win.print(); }, 300);
        return;
    }
    var loaded = 0;
    var total  = imgs.length;
    var printed = false;
    function doPrint() {
        if (printed) return;
        printed = true;
        win.print();
    }
    for (var i = 0; i < total; i++) {
        if (imgs[i].complete) {
            loaded++;
        } else {
            imgs[i].addEventListener('load',  function () { loaded++; if (loaded >= total) doPrint(); });
            imgs[i].addEventListener('error', function () { loaded++; if (loaded >= total) doPrint(); });
        }
    }
    if (loaded >= total) {
        doPrint();
    } else {
        // Fallback: print after 3s even if some images are still pending
        setTimeout(doPrint, 3000);
    }
}

// ── Transaction receipt builder ───────────────────────────────────────────
function _buildThermalHtml(resp, type) {
    var h   = resp.Header;
    var org = resp.OrgInfo     || {};
    var cfg = resp.ThermalConfig || {};
    var sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';

    var line1 = org.BrandName || org.Name || '';
    var line3 = [org.Line1, org.Line2, org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');

    var showGSTIN    = cfg.ShowGSTIN            !== undefined ? parseInt(cfg.ShowGSTIN)            : 1;
    var showMobile   = cfg.ShowMobile           !== undefined ? parseInt(cfg.ShowMobile)           : 1;
    var showHSN      = cfg.ShowHSN              !== undefined ? parseInt(cfg.ShowHSN)              : 1;
    var showTaxBkd   = cfg.ShowTaxBreakdown     !== undefined ? parseInt(cfg.ShowTaxBreakdown)     : 1;
    var showLogo     = cfg.ShowLogo             !== undefined ? parseInt(cfg.ShowLogo)             : 0;
    var showCo       = cfg.ShowCompanyDetails   !== undefined ? parseInt(cfg.ShowCompanyDetails)   : 1;
    var showItemDesc = cfg.ShowItemDescription  !== undefined ? parseInt(cfg.ShowItemDescription)  : 0;
    var showTerms    = cfg.ShowTerms            !== undefined ? parseInt(cfg.ShowTerms)            : 0;
    var showTaxable  = cfg.ShowTaxableAmount    !== undefined ? parseInt(cfg.ShowTaxableAmount)    : 1;

    var orgFontSize  = Math.max(10, Math.min(40, parseInt(cfg.OrgNameFontSize)     || 22));
    var coFontSize   = Math.max(8,  Math.min(40, parseInt(cfg.CompanyNameFontSize) || 18));
    var prodFontSize = Math.max(8,  Math.min(40, parseInt(cfg.ProductInfoFontSize) || 12));
    var taxFontSize  = Math.max(6,  prodFontSize - 2);

    var footer = cfg.FooterMessage || 'Thank you for your business!';
    var html   = '';

    // Logo
    if (showLogo) {
        var logoUrl = org.Logo ? _resolveLogoUrl(org.Logo) : 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
        html += '<div style="text-align:center;margin-bottom:4px;"><img src="' + _esc(logoUrl) + '" style="max-width:80px;max-height:60px;object-fit:contain;" alt="Logo" /></div>';
    }

    // Org name
    html += '<div style="text-align:center;font-weight:bold;font-size:' + orgFontSize + 'px;">' + _esc(line1) + '</div>';

    // Address / phone / GSTIN
    if (showCo) {
        if (line3)                          html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">' + _esc(line3) + '</div>';
        if (showMobile && org.MobileNumber) html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">Ph: ' + _esc(org.MobileNumber) + '</div>';
        if (showGSTIN  && org.GSTIN)        html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">GSTIN: ' + _esc(org.GSTIN) + '</div>';
    }

    html += '<hr class="tp-hr my-1">';

    // Transaction header
    var transLabel = (h.TransType || 'Transaction');
    html += '<div style="font-size:' + prodFontSize + 'px;">';
    html += '<div style="display:flex;justify-content:space-between;"><span style="font-weight:bold;">' + _esc(transLabel) + ': </span><span style="font-weight:bold;">' + _esc(h.UniqueNumber || '—') + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>Date: </span><span>' + _fmtDate(h.TransDate) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>Customer: </span><span style="text-align:right;max-width:60%">' + _esc(h.PartyName) + '</span></div>';
    if (h.PartyMobile) html += '<div style="display:flex;justify-content:space-between;"><span>Phone: </span><span>' + _esc(h.PartyMobile) + '</span></div>';
    html += '</div>';

    html += '<hr class="tp-hr my-1">';

    // Items header
    html += '<div style="font-size:' + prodFontSize + 'px;display:flex;justify-content:space-between;">';
    html += '<span style="font-weight:bold;">Item</span></div>';
    html += '<div style="font-size:' + prodFontSize + 'px;display:flex;justify-content:space-between;">';
    html += '<span>Quantity x Price</span><span>Amount</span></div>';

    html += '<hr class="tp-hr my-1">';

    // Items
    $.each(resp.Items || [], function (i, item) {
        html += '<div style="font-size:' + prodFontSize + 'px;">';
        var lineAmt = parseFloat(item.NetAmount) || 0;
        var hsnLine = (showHSN && item.HSNCode) ? ' [HSN:' + item.HSNCode + ']' : '';

        html += '<div style="font-weight:bold;">' + _esc(item.ProductName) + _esc(hsnLine) + '</div>';

        if (showItemDesc && item.Description) {
            html += '<div style="font-size:' + (prodFontSize - 2) + 'px;font-style:italic;color:#555;">' + _stripHtml(item.Description) + '</div>';
        }

        var displayPrice = showTaxable ? _esc(item.UnitPrice) : parseFloat(item.SellingPrice || item.UnitPrice).toFixed(2);
        html += '<div style="display:flex;justify-content:space-between;">';
        html += '<span>' + _esc(item.Quantity) + ' (' + _esc(item.PrimaryUnitName || 'PCS') + ') x ' + displayPrice + '</span>';
        html += '<span>' + lineAmt.toFixed(2) + '</span></div>';

        if (showTaxable && showTaxBkd && parseFloat(item.TaxPercentage) > 0) {
            var cgst = parseFloat(item.CgstAmount) || 0;
            var sgst = parseFloat(item.SgstAmount) || 0;
            var igst = parseFloat(item.IgstAmount) || 0;
            html += '<div style="display:flex;justify-content:space-between;">';
            if (cgst > 0 && sgst > 0) {
                html += '<span style="color:#555;font-size:' + taxFontSize + 'px;font-style:italic;">CGST ' + item.CGST + '% ' + cgst.toFixed(2) + '</span>';
                html += '<span style="color:#555;font-size:' + taxFontSize + 'px;font-style:italic;">SGST ' + item.SGST + '% ' + sgst.toFixed(2) + '</span>';
            } else if (igst > 0) {
                html += '<span style="color:#555;font-size:' + taxFontSize + 'px;font-style:italic;">IGST ' + item.IGST + '% ' + igst.toFixed(2) + '</span>';
            }
            html += '</div>';
        }

        html += '</div>';
        if (resp.Items.length > 1 && i !== resp.Items.length - 1) html += '<hr class="tp-hr my-1">';
    });

    html += '<hr class="tp-hr my-1">';

    // Items/Qty summary
    var totalQty = 0;
    $.each(resp.Items || [], function (i, it) { totalQty += parseFloat(it.Quantity) || 0; });
    html += '<div style="font-size:' + prodFontSize + 'px;text-align:center;">Items/Qty: ' + (resp.Items ? resp.Items.length : 0) + ' / ' + totalQty + '</div>';

    html += '<hr class="tp-hr my-1">';

    // Totals
    html += '<div style="text-align:end;">';
    if (showTaxable) {
        html += '<div style="display:flex;justify-content:space-between;font-size:' + prodFontSize + 'px;font-weight:600;"><span>Subtotal: </span><span>' + sym + ' ' + parseFloat(h.SubTotal || 0).toFixed(2) + '</span></div>';
        if (parseFloat(h.DiscountAmount) > 0) {
            html += '<div style="display:flex;justify-content:space-between;font-size:' + prodFontSize + 'px;font-weight:600;"><span>Discount: </span><span>- ' + sym + ' ' + parseFloat(h.DiscountAmount).toFixed(2) + '</span></div>';
        }
        if (parseFloat(h.TaxAmount) > 0) {
            html += '<div style="display:flex;justify-content:space-between;font-size:' + taxFontSize + 'px;font-style:italic;font-weight:600;"><span>Total Tax: </span><span>' + sym + ' ' + parseFloat(h.TaxAmount).toFixed(2) + '</span></div>';
            if (showTaxBkd) {
                if (parseFloat(h.CgstAmount) > 0) html += '<div style="display:flex;justify-content:space-between;font-size:' + taxFontSize + 'px;font-style:italic;color:#555;"><span>  CGST: </span><span>' + sym + ' ' + parseFloat(h.CgstAmount).toFixed(2) + '</span></div>';
                if (parseFloat(h.SgstAmount) > 0) html += '<div style="display:flex;justify-content:space-between;font-size:' + taxFontSize + 'px;font-style:italic;color:#555;"><span>  SGST: </span><span>' + sym + ' ' + parseFloat(h.SgstAmount).toFixed(2) + '</span></div>';
                if (parseFloat(h.IgstAmount) > 0) html += '<div style="display:flex;justify-content:space-between;font-size:' + taxFontSize + 'px;font-style:italic;color:#555;"><span>  IGST: </span><span>' + sym + ' ' + parseFloat(h.IgstAmount).toFixed(2) + '</span></div>';
            }
        }
    }
    if (parseFloat(h.AdditionalCharges) > 0) {
        html += '<div style="display:flex;justify-content:space-between;font-size:' + prodFontSize + 'px;font-weight:600;"><span>Charges: </span><span>' + sym + ' ' + parseFloat(h.AdditionalCharges).toFixed(2) + '</span></div>';
    }
    if (parseFloat(h.RoundOff || 0) !== 0) {
        html += '<div style="display:flex;justify-content:space-between;font-size:' + prodFontSize + 'px;"><span>Round Off: </span><span>' + sym + ' ' + parseFloat(h.RoundOff).toFixed(2) + '</span></div>';
    }
    html += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:' + (prodFontSize + 2) + 'px;border-top:1px solid #000;padding-top:3px;margin-top:3px;"><span>Total Amount: </span><span>' + sym + ' ' + parseFloat(h.NetAmount || 0).toFixed(2) + '</span></div>';
    html += '</div>';

    html += '<hr class="tp-hr my-1">';

    // Terms & Conditions
    if (showTerms && h.TermsConditions) {
        var termsLines = _stripHtml(h.TermsConditions).split('\n');
        html += '<div style="font-size:' + (prodFontSize - 2) + 'px;font-style:italic;color:#555;margin-bottom:4px;">';
        termsLines.forEach(function (line) {
            if (line.trim()) html += '<div>' + _esc(line.trim()) + '</div>';
        });
        html += '</div>';
    }

    // Footer
    html += '<div style="text-align:center;font-size:' + prodFontSize + 'px;margin-top:6px;">' + _esc(footer) + '</div>';
    html += '<div style="margin-bottom:24px"></div>';

    return type === 0
        ? '<div style="font-family:\'Courier New\',Courier,monospace;font-size:13px;padding:8px;max-width:580px;margin:0 auto;">' + html + '</div>'
        : html;
}

// ── Payment receipt builder ───────────────────────────────────────────────
function _buildPmtThermalHtml(resp, forPrint) {
    var p   = resp.Payment       || {};
    var org = resp.OrgInfo       || {};
    var cfg = resp.ThermalConfig || {};
    var sym = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';

    var showLogo   = cfg.ShowLogo           !== undefined ? parseInt(cfg.ShowLogo)           : 0;
    var showCo     = cfg.ShowCompanyDetails !== undefined ? parseInt(cfg.ShowCompanyDetails) : 1;
    var showMobile = cfg.ShowMobile         !== undefined ? parseInt(cfg.ShowMobile)         : 1;
    var showGSTIN  = cfg.ShowGSTIN          !== undefined ? parseInt(cfg.ShowGSTIN)          : 1;
    var showQR     = cfg.ShowPaymentQR      !== undefined ? parseInt(cfg.ShowPaymentQR)      : 0;

    var orgFontSize  = Math.max(10, Math.min(40, parseInt(cfg.OrgNameFontSize)     || 22));
    var coFontSize   = Math.max(8,  Math.min(40, parseInt(cfg.CompanyNameFontSize) || 18));
    var prodFontSize = Math.max(8,  Math.min(40, parseInt(cfg.ProductInfoFontSize) || 12));

    var line1      = org.BrandName || org.Name || '';
    var line3      = [org.Line1, org.Line2, org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');
    var footer     = cfg.FooterMessage || 'Thank you for your business!';
    var direction  = (p.PartyType === 'C') ? 'Payment Received' : 'Payment Made';
    var partyLabel = (p.PartyType === 'C') ? 'Customer' : 'Vendor';

    var html = '';

    // Logo
    if (showLogo) {
        var logoUrl = org.Logo ? _resolveLogoUrl(org.Logo) : 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
        html += '<div style="text-align:center;margin-bottom:4px;"><img src="' + _esc(logoUrl) + '" style="max-width:80px;max-height:60px;object-fit:contain;" alt="Logo" /></div>';
    }

    // Org name
    html += '<div style="text-align:center;font-weight:bold;font-size:' + orgFontSize + 'px;">' + _esc(line1) + '</div>';

    // Address / phone / GSTIN
    if (showCo) {
        if (line3)                          html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">' + _esc(line3) + '</div>';
        if (showMobile && org.MobileNumber) html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">Ph: ' + _esc(org.MobileNumber) + '</div>';
        if (showGSTIN  && org.GSTIN)        html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">GSTIN: ' + _esc(org.GSTIN) + '</div>';
    }

    html += '<hr class="tp-hr my-1">';

    // Receipt header
    html += '<div style="font-size:' + prodFontSize + 'px;">';
    html += '<div style="display:flex;justify-content:space-between;"><span style="font-weight:bold;">' + _esc(direction) + ':</span><span style="font-weight:bold;">' + _esc(p.UniqueNumber || ('PMT-' + p.PaymentUID)) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>Date:</span><span>' + _fmtDate(p.PaymentDate || p.CreatedOn) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>' + _esc(partyLabel) + ':</span><span style="text-align:right;max-width:60%;">' + _esc(p.PartyName || '—') + '</span></div>';
    if (p.PartyMobile) html += '<div style="display:flex;justify-content:space-between;"><span>Phone:</span><span>' + _esc(p.PartyMobile) + '</span></div>';
    html += '</div>';

    html += '<hr class="tp-hr my-1">';

    // Amount
    html += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:' + (prodFontSize + 2) + 'px;border-bottom:1px solid #000;padding-bottom:3px;margin-bottom:3px;"><span>Amount:</span><span>' + sym + ' ' + parseFloat(p.Amount || 0).toFixed(2) + '</span></div>';

    // Payment mode + reference
    html += '<div style="font-size:' + prodFontSize + 'px;">';
    html += '<div style="display:flex;justify-content:space-between;"><span>Mode:</span><span>' + _esc(p.PaymentTypeName || '—') + '</span></div>';
    if (p.ReferenceNo) html += '<div style="display:flex;justify-content:space-between;"><span>Ref:</span><span>' + _esc(p.ReferenceNo) + '</span></div>';
    html += '</div>';

    // ── Fix #3: Full bank details ─────────────────────────────────────────
    if (!p.IsCash && p.BankName) {
        html += '<hr class="tp-hr my-1">';
        html += '<div style="font-size:' + prodFontSize + 'px;">';
        html += '<div style="font-weight:bold;margin-bottom:2px;">Bank Details:</div>';
        html += '<div style="display:flex;justify-content:space-between;"><span>Bank:</span><span>' + _esc(p.BankName) + '</span></div>';
        if (p.AccountName)   html += '<div style="display:flex;justify-content:space-between;"><span>A/C Name:</span><span>' + _esc(p.AccountName) + '</span></div>';
        if (p.AccountNumber) html += '<div style="display:flex;justify-content:space-between;"><span>A/C No:</span><span>' + _esc(p.AccountNumber) + '</span></div>';
        if (p.IFSC)          html += '<div style="display:flex;justify-content:space-between;"><span>IFSC:</span><span>' + _esc(p.IFSC) + '</span></div>';
        if (p.BranchName)    html += '<div style="display:flex;justify-content:space-between;"><span>Branch:</span><span>' + _esc(p.BranchName) + '</span></div>';
        html += '</div>';
    }

    // ── Fix #1 & #2: Linked document note ────────────────────────────────
    html += '<hr class="tp-hr my-1">';
    if (p.TransNumber) {
        html += '<div style="font-size:' + (prodFontSize - 1) + 'px;font-style:italic;color:#444;text-align:center;padding:2px 0;">' +
                'Amount received against the linked document as &quot;' + _esc(p.TransNumber) + '&quot;.' +
                '</div>';
    } else {
        html += '<div style="font-size:' + (prodFontSize - 1) + 'px;font-style:italic;color:#444;text-align:center;padding:2px 0;">' +
                'Advance amount received without any linked document reference.' +
                '</div>';
    }

    // ── Fix #4: Payment QR (was broken — ShowPaymentQR was never read) ───
    if (showQR && p.UPIId) {
        var upiStr = 'upi://pay?pa=' + encodeURIComponent(p.UPIId) +
                     '&pn=' + encodeURIComponent(line1) +
                     '&am=' + parseFloat(p.Amount || 0).toFixed(2) +
                     '&cu=INR';
        var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(upiStr);
        html += '<hr class="tp-hr my-1">';
        html += '<div style="text-align:center;margin:4px 0;">';
        html += '<img src="' + qrApiUrl + '" style="width:100px;height:100px;" alt="Pay QR" />';
        html += '<div style="font-size:' + (prodFontSize - 1) + 'px;margin-top:2px;">' + _esc(p.UPIId) + '</div>';
        html += '</div>';
    }

    html += '<hr class="tp-hr my-1">';

    // Footer
    html += '<div style="text-align:center;font-size:' + prodFontSize + 'px;margin-top:6px;">' + _esc(footer) + '</div>';
    html += '<div style="margin-bottom:24px"></div>';

    return forPrint === 0
        ? '<div style="font-family:\'Courier New\',Courier,monospace;font-size:13px;padding:8px;max-width:580px;margin:0 auto;">' + html + '</div>'
        : html;
}
