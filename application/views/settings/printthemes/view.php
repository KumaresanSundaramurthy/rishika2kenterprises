<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-palette',
                    'pageIconBg'      => '#fef3c7',
                    'pageIconColor'   => '#f59e0b',
                    'pageTitle'       => $PageTitle       ?? 'Print Themes',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y pt-2">

                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="SearchDetails" placeholder="Search...">
                                <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button class="btn btn-primary btn-sm px-3" id="btnNewTheme"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_theme', 'Add Theme'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </button>
                            <button class="btn btn-primary btn-sm px-3 d-none" id="btnNewTemplate"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_template', 'Add Template'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="ptStatusTabs" role="tablist" data-trans-path="/settings/printthemes">
                                <li class="nav-item">
                                    <a class="nav-link active" data-status="themes" data-url-tab="themes" href="javascript:void(0);">
                                        <i class="bx bx-palette me-1"></i>Themes
                                        <span class="trans-tab-count d-none" id="themeTotalCount"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-status="templates" data-url-tab="templates" href="javascript:void(0);">
                                        <i class="bx bx-file me-1"></i>Templates
                                        <span class="trans-tab-count d-none" id="templateTotalCount"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- THEMES SECTION -->
                        <div id="ThemesSection">
                            <div class="p-3">
                                <div class="row g-3" id="ThemesCardContainer">
                                    <div class="col-12 text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                                </div>
                            </div>
                            <hr class="my-0">
                            <div class="row mx-3 justify-content-between ThemesPagination apex-pag-sticky" id="ThemesPagination"></div>
                        </div>

                        <!-- TEMPLATES SECTION -->
                        <div id="TemplatesSection" style="display:none;">
                            <div class="table-responsive text-nowrap">
                                <table class="table trans-table MainviewTable mb-0" id="TemplatesTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:90px;">Preview</th>
                                            <th>Template Name</th>
                                            <th>Key</th>
                                            <th>Category</th>
                                            <th class="r2k-col-date">Last Updated</th>
                                            <th class="th-act">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="r2k-tbody table-border-bottom-0"></tbody>
                                </table>
                            </div>
                            <hr class="my-0">
                            <div class="row mx-3 justify-content-between TemplatesPagination apex-pag-sticky" id="TemplatesPagination"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('settings/printthemes/modals/theme');    ?>
<?php $this->load->view('settings/printthemes/modals/template'); ?>

<!-- Template image preview modal -->
<div class="modal fade" id="tplImgPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 border-bottom">
                <h6 class="modal-title mb-0"><i class="bx bx-image me-1"></i>Template Preview</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="tplImgPreviewSrc" src="" alt="Template Preview" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script>
$(function () {
'use strict';
var ActiveTab   = 'themes';
var _usedTypes  = [];
var _transTypes = {};
var PageNo      = 1, Filter = {};
var _themeModal = new bootstrap.Modal(document.getElementById('themeModal'));
var _tplModal   = new bootstrap.Modal(document.getElementById('templateModal'));
var _templates  = [];
var _orgPreview = { name: '', gstin: '29XXXXX1234X1Z1', phone: '9876543210', addr: '' };
var CsrfName    = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken   = '<?php echo $this->security->get_csrf_hash(); ?>';

// ── Live preview helpers ─────────────────────────────────────────────────────
var _prevTimer = null;
var _previewLoadedUID = null;
function _debouncePrev(fn, ms) { clearTimeout(_prevTimer); _prevTimer = setTimeout(fn, ms || 350); }

function _deriveAccent(hex) {
    if (!hex || !/^#[0-9a-fA-F]{6}$/.test(hex)) return '#3d6494';
    var r=parseInt(hex.slice(1,3),16)/255, g=parseInt(hex.slice(3,5),16)/255, b=parseInt(hex.slice(5,7),16)/255;
    var max=Math.max(r,g,b), min=Math.min(r,g,b), h=0, s=0, l=(max+min)/2;
    if (max !== min) {
        var d=max-min;
        s = l>0.5 ? d/(2-max-min) : d/(max+min);
        if(max===r) h=((g-b)/d+(g<b?6:0))/6;
        else if(max===g) h=((b-r)/d+2)/6;
        else h=((r-g)/d+4)/6;
    }
    l=Math.min(0.85,l+0.22); s=s*0.75;
    var q=l<0.5?l*(1+s):l+s-l*s, p2=2*l-q;
    function _hu(p,q,t){ if(t<0)t+=1;if(t>1)t-=1;if(t<1/6)return p+(q-p)*6*t;if(t<1/2)return q;if(t<2/3)return p+(q-p)*(2/3-t)*6;return p; }
    function _th(x){ var h=Math.round(x*255).toString(16);return h.length===1?'0'+h:h; }
    return '#'+_th(_hu(p2,q,h+1/3))+_th(_hu(p2,q,h))+_th(_hu(p2,q,h-1/3));
}

function _loadPreviewSrcdoc(tpl, p, acc) {
    var ff = $('#FontFamily').val() || 'Arial';
    var fs = parseInt($('#FontSizePx').val()) || 11;
    var showLogo         = $('#ShowLogo').is(':checked');
    var showAddr         = $('#ShowOrgAddress').is(':checked');
    var showGSTIN        = $('#ShowGSTIN').is(':checked');
    var showHSN          = $('#ShowHSN').is(':checked');
    var showTax          = $('#ShowTaxBreakdown').is(':checked');
    var showPartyBalance = $('#ShowPartyBalance').is(':checked');
    var showTime         = $('#ShowTime').is(':checked');

    var sysF = ['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
    var fontLink = sysF.indexOf(ff) === -1
        ? '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family='+encodeURIComponent(ff)+':wght@400;600;700&display=swap">'
        : '';
    var cssVars = '<style>:root{--primary:'+p+';--accent:'+acc+';}body{font-family:\''+ff+'\',Arial,sans-serif;font-size:'+fs+'px;}</style>'+fontLink;

    var ph = tpl.PreviewHtmlContent;
    ph = ph.replace('{{CSS_VARS}}', cssVars);
    ph = ph.replace(/\{\{ORG_NAME\}\}/g,          _orgPreview.name);
    ph = ph.replace(/\{\{ORG_ADDRESS\}\}/g,        _orgPreview.addr);
    ph = ph.replace(/\{\{ORG_GSTIN\}\}/g,          _orgPreview.gstin);
    ph = ph.replace(/\{\{ORG_PHONE\}\}/g,          _orgPreview.phone);
    ph = ph.replace(/\{\{ORG_EMAIL\}\}/g,          'info@company.com');
    ph = ph.replace(/\{\{ORG_PAN\}\}/g,            'AAAAA0000A');
    ph = ph.replace(/\{\{ORG_LOGO\}\}/g,           'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png');
    ph = ph.replace(/\{\{FOOTER_TEXT\}\}/g,        'Thank you for your business!');
    ph = ph.replace(/\{\{DOC_NUMBER\}\}/g,         'INV-2025-001');
    ph = ph.replace(/\{\{DOC_DATE\}\}/g,           '05 Apr 2025');
    ph = ph.replace(/\{\{DUE_DATE\}\}/g,           '20 Apr 2025');
    ph = ph.replace(/\{\{CUSTOMER_NAME\}\}/g,      'Sample Trading Co.');
    ph = ph.replace(/\{\{CUSTOMER_ADDRESS\}\}/g,   '123 Market Street, Chennai - 600001');
    ph = ph.replace(/\{\{SUBTOTAL\}\}/g,           '₹ 12,040.00');
    ph = ph.replace(/\{\{TAX_TOTAL\}\}/g,          '₹ 2,167.20');
    ph = ph.replace(/\{\{GRAND_TOTAL\}\}/g,        '₹ 14,207.20');
    ph = ph.replace(/\{\{BANK_NAME\}\}/g,          'State Bank of India');
    ph = ph.replace(/\{\{BANK_ACCOUNT_NAME\}\}/g,  _orgPreview.name);
    ph = ph.replace(/\{\{BANK_ACCOUNT_NO\}\}/g,    'XXXXXXXXXXXX');
    ph = ph.replace(/\{\{BANK_IFSC\}\}/g,          'SBIN0000000');
    ph = ph.replace(/\{\{BANK_BRANCH\}\}/g,        'Main Branch');
    ph = ph.replace(/\{\{BANK_UPI\}\}/g,           'company@upi');
    ph = ph.replace(/\{\{FOOTER_TEXT\}\}/g,        'Thank you for your business!');
    ph = ph.replace(/\{\{NOTES\}\}/g,              'Thank you for your business. Please mention the invoice number while making the payment.');
    ph = ph.replace(/\{\{TERMS_CONDITIONS\}\}/g,   '1. Goods once sold will not be taken back.<br>2. Payment is due within 15 days.<br>3. Interest may be charged on overdue payments.<br>4. Subject to local jurisdiction only.');
    ph = ph.replace(/\{\{SHOW_LOGO_CELL\}\}/g,     showLogo         ? 'table-cell' : 'none');
    ph = ph.replace(/\{\{SHOW_ADDRESS\}\}/g,        showAddr         ? 'block'      : 'none');
    ph = ph.replace(/\{\{SHOW_GSTIN\}\}/g,          showGSTIN        ? 'inline'     : 'none');
    ph = ph.replace(/\{\{SHOW_HSN\}\}/g,            showHSN          ? 'table-cell' : 'none');
    ph = ph.replace(/\{\{SHOW_TAX\}\}/g,            showTax          ? 'table-cell' : 'none');
    ph = ph.replace(/\{\{SHOW_TAX_ROW\}\}/g,        showTax          ? 'table-row'  : 'none');
    ph = ph.replace(/\{\{SHOW_TAX_TABLE\}\}/g,      showTax          ? 'table'      : 'none');
    ph = ph.replace(/\{\{SHOW_PARTY_BALANCE\}\}/g,  showPartyBalance ? 'table-cell' : 'none');
    ph = ph.replace(/\{\{SHOW_TIME\}\}/g,           showTime         ? 'inline'     : 'none');

    _previewLoadedUID = String($('#TemplateUID').val());
    document.getElementById('livePreviewFrame').srcdoc = ph;
}

function _updatePreviewLive(p, acc) {
    var frame = document.getElementById('livePreviewFrame');
    if (!frame || !frame.contentDocument || !frame.contentDocument.body) return;
    var doc = frame.contentDocument, root = doc.documentElement;

    root.style.setProperty('--primary', p);
    root.style.setProperty('--accent',  acc);

    var ff = $('#FontFamily').val() || 'Arial';
    var fs = parseInt($('#FontSizePx').val()) || 11;
    doc.body.style.fontFamily = "'"+ff+"', Arial, sans-serif";
    doc.body.style.fontSize   = fs+'px';

    var sysF = ['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
    if (sysF.indexOf(ff) === -1) {
        var fl = doc.getElementById('r2k-gfont');
        if (!fl) { fl=doc.createElement('link'); fl.id='r2k-gfont'; fl.rel='stylesheet'; doc.head.appendChild(fl); }
        fl.href = 'https://fonts.googleapis.com/css2?family='+encodeURIComponent(ff)+':wght@400;600;700&display=swap';
    }

    var showLogo=$('#ShowLogo').is(':checked'), showAddr=$('#ShowOrgAddress').is(':checked'),
        showGSTIN=$('#ShowGSTIN').is(':checked'), showHSN=$('#ShowHSN').is(':checked'),
        showTax=$('#ShowTaxBreakdown').is(':checked'), showPartyBalance=$('#ShowPartyBalance').is(':checked'),
        showTime=$('#ShowTime').is(':checked');

    var setD = function(attr, val) {
        var els = doc.querySelectorAll('[data-r2k="'+attr+'"]');
        for (var i=0; i<els.length; i++) { els[i].style.display = val; }
    };
    setD('logo-cell',     showLogo         ? 'table-cell' : 'none');
    setD('org-address',   showAddr         ? 'block'      : 'none');
    setD('org-gstin',     showGSTIN        ? 'inline'     : 'none');
    setD('hsn-th',        showHSN          ? 'table-cell' : 'none');
    setD('hsn-td',        showHSN          ? 'table-cell' : 'none');
    setD('tax-th',        showTax          ? 'table-cell' : 'none');
    setD('tax-td',        showTax          ? 'table-cell' : 'none');
    setD('tax-row',       showTax          ? 'table-row'  : 'none');
    setD('tax-table',     showTax          ? 'table'      : 'none');
    setD('party-balance', showPartyBalance ? 'table-cell' : 'none');
    setD('doc-time',      showTime         ? 'inline'     : 'none');
}

function _buildLivePreview(forceReload) {
    var p   = $('#BrandColor').val() || '#1a3c6e';
    var acc = _deriveAccent(p);
    $('#PrimaryColor').val(p);
    $('#AccentColor').val(acc);

    var ff  = $('#FontFamily').val()   || 'Arial';
    var fs  = parseInt($('#FontSizePx').val()) || 11;

    var selUID = $('#TemplateUID').val();
    var tpl    = _templates.find(function(x){ return String(x.TemplateUID) === String(selUID); });
    var tplKey = tpl ? (tpl.TemplateKey || 'classic') : 'classic';
    $('#previewThemeLabel').text(tpl ? tpl.TemplateName : 'Sample Preview');

    if (tpl && tpl.PreviewHtmlContent) {
        var templateChanged = (_previewLoadedUID !== String(selUID));
        if (forceReload || templateChanged) {
            _loadPreviewSrcdoc(tpl, p, acc);
        } else {
            _updatePreviewLive(p, acc);
        }
        return;
    }

    // ── No PreviewHtmlContent — show placeholder instead of fake invoice ────────
    _previewLoadedUID = null;
    (function () {
        var frame = document.getElementById('livePreviewFrame');
        if (!frame) return;
        frame.srcdoc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            + 'body{margin:0;height:100vh;display:flex;align-items:center;justify-content:center;'
            + 'font-family:Arial,sans-serif;background:#f8f9fa;}'
            + '.msg{text-align:center;color:#6c757d;user-select:none;pointer-events:none;}'
            + '</style></head><body>'
            + '<div class="msg">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" viewBox="0 0 24 24"'
            + ' fill="none" stroke="#dee2e6" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"'
            + ' style="display:block;margin:0 auto 14px;">'
            + '<rect x="3" y="3" width="18" height="18" rx="2"/>'
            + '<path d="M9 8h6M9 12h6M9 16h4"/>'
            + '</svg>'
            + '<p style="font-size:14px;font-weight:600;margin:0 0 6px;color:#6c757d;">No Preview Available</p>'
            + '<p style="font-size:11px;margin:0;color:#adb5bd;">This template has no preview configured.</p>'
            + '</div></body></html>';
    })();
    return;
}


// Tab
$(document).on('click', '.trans-status-tabs .nav-link', function(e){
    e.preventDefault();
    var tab = $(this).data('status');
    if (tab === ActiveTab) return;
    ActiveTab = tab;
    PageNo = 1; Filter = {};
    $('#SearchDetails').val(''); $('#clearSearch').addClass('d-none');
    $('.trans-status-tabs .nav-link').removeClass('active');
    $(this).addClass('active');
    $('#themeTotalCount,#templateTotalCount').addClass('d-none').text('');
    if (tab === 'themes') {
        $('#TemplatesSection').hide(); $('#ThemesSection').show();
        $('#btnNewTemplate').addClass('d-none'); $('#btnNewTheme').removeClass('d-none');
        _loadThemes();
    } else {
        $('#ThemesSection').hide(); $('#TemplatesSection').show();
        $('#btnNewTheme').addClass('d-none'); $('#btnNewTemplate').removeClass('d-none');
        _loadTemplates();
    }
    _pushTabUrl(tab, '');
});
$('.PageRefresh').on('click', function(){ if(ActiveTab==='themes') _loadThemes(); else _loadTemplates(); });

// Search
var _st;
$('#SearchDetails').on('input', function(){
    var v = $(this).val();
    $('#clearSearch').toggleClass('d-none', !v);
    clearTimeout(_st);
    _pushTabUrl(ActiveTab, v);
    if (v.length===0 || v.length>=3) {
        _st = setTimeout(function(){ PageNo=1; Filter.SearchAllData=$('#SearchDetails').val(); if(ActiveTab==='themes') _loadThemes(); else _loadTemplates(); }, 1500);
    }
});
$('#clearSearch').on('click', function(){ $('#SearchDetails').val('').trigger('input'); });

// Template image preview
$(document).on('click', '.tpl-preview-thumb', function(){
    var src = $(this).data('src');
    if (!src) return;
    $('#tplImgPreviewSrc').attr('src', src);
    new bootstrap.Modal(document.getElementById('tplImgPreviewModal')).show();
});

// ── CSS mini-thumbnail renderer ──────────────────────────────────────────────
function _buildThumb(key, primary, accent, container, h) {
    h = h || 100;
    var p = primary || '#1a3c6e';
    var a = accent  || '#f59e0b';
    var el = $(container);

    function bar(bg, height, mt) {
        return '<div style="height:'+height+'px;background:'+bg+';'+(mt?'margin-top:'+mt+'px;':'')+'"></div>';
    }
    function lines(n, bg, ht) {
        var s=''; for(var i=0;i<n;i++) s+='<div style="height:'+(ht||4)+'px;background:'+bg+';margin-bottom:2px;border-radius:1px;"></div>'; return s;
    }
    function cell(w, bg) { return '<div style="flex:'+w+';height:6px;background:'+bg+';margin:0 1px;"></div>'; }
    function tableRow(light, bdr) {
        return '<div style="display:flex;padding:2px 0;border-bottom:1px solid '+(bdr||'#eee')+';">'
            +cell(0.3,'#999')+cell(1.5,light?'#aaa':'#ccc')+cell(0.5,light?'#aaa':'#ccc')+cell(0.5,light?'#aaa':'#ccc')+cell(0.7,light?'#aaa':'#ccc')
            +'</div>';
    }

    var html = '';
    if (key === 'modern') {
        html = '<div style="height:'+h+'px;overflow:hidden;">'
             + '<div style="background:'+p+';padding:4px 5px;display:flex;justify-content:space-between;align-items:center;">'
             +   '<div style="display:flex;align-items:center;gap:3px;">'
             +     '<div style="width:12px;height:12px;background:#fff;border-radius:2px;flex-shrink:0;opacity:.9;"></div>'
             +     '<div><div style="width:28px;height:5px;background:#fff;border-radius:1px;margin-bottom:1px;"></div>'
             +          '<div style="width:20px;height:3px;background:rgba(255,255,255,.6);border-radius:1px;"></div></div>'
             +   '</div>'
             +   '<div style="font-size:7px;font-weight:900;color:'+a+';letter-spacing:1px;">TAX INV.</div>'
             + '</div>'
             + '<div style="height:3px;background:'+a+';"></div>'
             + '<div style="padding:2px 4px;margin-bottom:2px;display:flex;gap:8px;">'
             +   '<div>'+lines(3,'#ddd',3)+'</div>'
             +   '<div><div style="width:32px;height:5px;background:#333;border-radius:1px;margin-bottom:2px;"></div>'+lines(2,'#ccc',3)+'</div>'
             + '</div>'
             + bar(p,7)+tableRow(false)+tableRow(true)+tableRow(false)
             + '<div style="display:flex;justify-content:flex-end;margin-top:2px;padding:0 3px;"><div style="width:38%;background:'+p+';height:8px;border-radius:1px;"></div></div>'
             + '<div style="margin-top:3px;border-top:3px solid '+a+';text-align:center;padding-top:2px;"><div style="width:50px;height:3px;background:#ccc;border-radius:1px;margin:0 auto;"></div></div>'
             + '</div>';
    } else if (key === 'minimal') {
        html = '<div style="border-top:3px solid '+p+';padding:3px;height:'+h+'px;overflow:hidden;">'
             + '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:3px;">'
             +   '<div><div style="width:28px;height:6px;background:'+p+';border-radius:1px;margin-bottom:2px;"></div>'+lines(2,'#ddd',3)+'</div>'
             +   '<div style="text-align:right;"><div style="font-size:7px;font-weight:300;letter-spacing:2px;color:'+p+';">Invoice</div>'
             +        '<div style="width:22px;height:3px;background:#ddd;border-radius:1px;margin-left:auto;margin-top:2px;"></div></div>'
             + '</div>'
             + '<div style="border-bottom:1px solid #ddd;margin-bottom:3px;"></div>'
             + '<div style="margin-bottom:3px;"><div style="width:36px;height:5px;background:#333;border-radius:1px;"></div>'+lines(1,'#ddd',3)+'</div>'
             + bar(p,6)+tableRow(false,'#ddd')+tableRow(true,'#ddd')+tableRow(false,'#ddd')
             + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:38%;background:'+p+';height:7px;border-radius:1px;"></div></div>'
             + '<div style="border-top:1px solid #ddd;margin-top:3px;padding-top:2px;text-align:center;"><div style="width:40px;height:3px;background:#ddd;border-radius:1px;margin:0 auto;"></div></div>'
             + '</div>';
    } else {
        // classic (default)
        html = '<div style="border:2px solid '+p+';padding:3px;height:'+h+'px;overflow:hidden;">'
             + '<div style="border-bottom:2px solid '+a+';padding-bottom:3px;margin-bottom:3px;display:flex;justify-content:space-between;align-items:center;">'
             +   '<div><div style="width:30px;height:5px;background:'+p+';margin-bottom:2px;border-radius:1px;"></div>'+lines(2,'#ccc',3)+'</div>'
             +   '<div style="text-align:right;"><div style="font-size:6px;font-weight:900;color:'+p+';letter-spacing:1px;">TAX INVOICE</div>'
             +   '<div style="width:28px;height:3px;background:#ddd;margin-left:auto;margin-top:2px;border-radius:1px;"></div></div>'
             + '</div>'
             + '<div style="border:1px solid #ddd;padding:2px 3px;margin-bottom:3px;width:45%;">'
             +   '<div style="font-size:5px;color:#888;font-weight:700;">BILL TO</div>'
             +   '<div style="width:40px;height:4px;background:#333;border-radius:1px;margin-top:1px;"></div></div>'
             + bar(p,7)+tableRow(false)+tableRow(true)+tableRow(false)
             + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:40%;background:'+p+';height:8px;border-radius:1px;"></div></div>'
             + '</div>';
    }

    el.html(html);
}

function _renderThemeCards() {
    $('.theme-card-thumb').each(function() {
        _buildThumb($(this).data('key'), $(this).data('primary'), $(this).data('accent'), this, 108);
    });
}

/**
 * Escapes a string for safe HTML embedding.
 * @param {string} str
 * @returns {string}
 */
function _esc(str) { return $('<s>').text(String(str == null ? '' : str)).html(); }

/**
 * Builds Bootstrap pagination HTML from count/page/limit.
 * @param {number} total
 * @param {number} page
 * @param {number} limit
 * @returns {string}
 */
function _buildPagination(total, page, limit) {
    total = parseInt(total) || 0;
    limit = parseInt(limit) || 10;
    var totalPages = Math.ceil(total / limit);
    if (totalPages <= 1) return '';
    var html = '<ul class="pagination pagination-sm mb-0">';
    var start = Math.max(1, page - 2);
    var end   = Math.min(totalPages, page + 2);
    if (page > 1) html += '<li class="page-item"><a class="page-link" data-page="' + (page - 1) + '">&laquo;</a></li>';
    for (var i = start; i <= end; i++) {
        html += '<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" data-page="' + i + '">' + i + '</a></li>';
    }
    if (page < totalPages) html += '<li class="page-item"><a class="page-link" data-page="' + (page + 1) + '">&raquo;</a></li>';
    return html + '</ul>';
}

/**
 * Rebuilds #TransactionType select options from loaded _transTypes / _usedTypes.
 * @returns {void}
 */
function _populateModuleSelect() {
    var $sel = $('#TransactionType');
    $sel.find('option:not([value=""])').remove();
    $.each(_transTypes, function(key, label) {
        if (_usedTypes.indexOf(key) === -1) {
            $sel.append($('<option>', { value: key, text: label }));
        }
    });
}

/**
 * Renders theme card HTML from raw data array.
 * @param {Array} themes
 * @returns {string}
 */
function _renderThemeCardRows(themes) {
    if (!themes || !themes.length) {
        return '<div class="col-12"><div class="text-center py-5 text-muted">'
             + '<i class="bx bx-palette fs-1 d-block mb-2 opacity-50"></i>'
             + '<div class="mb-1">No themes configured yet.</div>'
             + '<div class="small">Click <strong>Add Theme</strong> to get started.</div>'
             + '</div></div>';
    }
    var html = '';
    themes.forEach(function(row) {
        var typeLabel = _transTypes[row.TransactionType] || row.TransactionType || '';
        var tplName   = _esc(row.TemplateName || 'Custom Theme');
        var primary   = row.PrimaryColor  || '#1a3c6e';
        var accent    = row.AccentColor   || '#f59e0b';
        var themeKey  = row.ThemeKey      || 'classic';
        var updatedOn = row.UpdatedOnDisplay || row.UpdatedOn || '—';
        html += '<div class="col-xl-3 col-lg-4 col-md-6">'
              + '<div class="card h-100 border-0 shadow-sm theme-card-wrap">'
              + '<div style="padding:10px 10px 0;background:#f4f5f7;border-radius:8px 8px 0 0;position:relative;">'
              + '<div class="theme-card-thumb"'
              +   ' data-key="' + _esc(themeKey) + '"'
              +   ' data-primary="' + _esc(primary) + '"'
              +   ' data-accent="' + _esc(accent) + '"'
              +   ' style="width:100%;height:108px;background:#fff;border:1px solid #e0e0e0;border-radius:4px;overflow:hidden;"></div>'
              + '<div class="theme-card-actions">'
              + '<button class="theme-card-action-btn editThemeBtn" data-uid="' + (row.ThemeConfigUID | 0) + '" title="Edit"><i class="bx bx-edit"></i></button>'
              + '<button class="theme-card-action-btn danger deleteThemeBtn" data-uid="' + (row.ThemeConfigUID | 0) + '" data-label="' + _esc(typeLabel) + '" title="Delete"><i class="bx bx-trash"></i></button>'
              + '</div></div>'
              + '<div class="card-body py-2 px-3">'
              + '<div class="d-flex justify-content-between align-items-start mb-1">'
              + '<div><span class="badge bg-label-primary mb-1">' + _esc(typeLabel) + '</span>'
              + '<div class="fw-semibold small">' + tplName + '</div></div>'
              + '<div class="d-flex gap-1 mt-1 flex-shrink-0">'
              + '<div title="Primary: ' + _esc(primary) + '" style="width:16px;height:16px;border-radius:50%;background:' + primary + ';border:1px solid rgba(0,0,0,.1);"></div>'
              + '<div title="Accent: ' + _esc(accent) + '" style="width:16px;height:16px;border-radius:50%;background:' + accent + ';border:1px solid rgba(0,0,0,.1);"></div>'
              + '</div></div>'
              + '<div class="d-flex flex-wrap gap-1 mb-1">';
        if (row.ShowLogo == 1 || row.ShowLogo === true)         html += '<span class="badge bg-label-secondary" style="font-size:.62rem;">Logo</span>';
        if (row.ShowOrgAddress == 1 || row.ShowOrgAddress === true)  html += '<span class="badge bg-label-secondary" style="font-size:.62rem;">Address</span>';
        if (row.ShowGSTIN == 1 || row.ShowGSTIN === true)       html += '<span class="badge bg-label-secondary" style="font-size:.62rem;">GSTIN</span>';
        if (row.ShowHSN == 1 || row.ShowHSN === true)           html += '<span class="badge bg-label-secondary" style="font-size:.62rem;">HSN</span>';
        if (row.ShowTaxBreakdown == 1 || row.ShowTaxBreakdown === true) html += '<span class="badge bg-label-secondary" style="font-size:.62rem;">Tax</span>';
        html += '</div>'
              + '<div class="text-muted" style="font-size:.67rem;">' + _esc(row.FontFamily || 'Arial') + ', ' + (row.FontSizePx || 11) + 'px &nbsp;·&nbsp;' + _esc(updatedOn) + '</div>'
              + '</div></div></div>';
    });
    return html;
}

/**
 * Renders template table row HTML from raw data array.
 * @param {Array} templates
 * @returns {string}
 */
function _renderTemplateTableRows(templates) {
    if (!templates || !templates.length) {
        return '<tr><td colspan="6" class="text-center py-4 text-muted">'
             + '<i class="bx bx-file fs-1 d-block mb-2 opacity-50"></i>'
             + 'No templates found. Click <strong>Add Template</strong> to create one.</td></tr>';
    }
    var catBadge = { general: 'bg-label-secondary', gst: 'bg-label-success', minimal: 'bg-label-info', formal: 'bg-label-warning', modern: 'bg-label-primary' };
    var html = '';
    templates.forEach(function(row) {
        var preview   = row.PreviewImage || '';
        var updatedOn = row.UpdatedOnDisplay || row.UpdatedOn || '—';
        var cat       = row.Category || 'general';
        var badge     = catBadge[cat] || 'bg-label-secondary';
        var desc      = row.Description || '';
        if (desc.length > 60) desc = desc.substring(0, 60) + '…';
        html += '<tr>'
              + '<td>' + (preview
                    ? '<img src="' + _esc(preview) + '" alt="' + _esc(row.TemplateName) + '" class="tpl-preview-thumb" data-src="' + _esc(preview) + '">'
                    : '<div class="tpl-preview-placeholder"><i class="bx bx-image text-muted fs-4"></i></div>') + '</td>'
              + '<td><div class="fw-semibold">' + _esc(row.TemplateName) + '</div>'
              + (desc ? '<div class="text-muted tinysmall">' + _esc(desc) + '</div>' : '') + '</td>'
              + '<td><code class="text-muted small">' + _esc(row.TemplateKey) + '</code></td>'
              + '<td><span class="badge ' + badge + '">' + _esc(cat.charAt(0).toUpperCase() + cat.slice(1)) + '</span></td>'
              + '<td class="r2k-col-date"><span class="text-muted small">' + _esc(updatedOn) + '</span></td>'
              + '<td class="text-center">'
              + '<a href="javascript:void(0);" class="apex-icon-btn editTemplateBtn" data-uid="' + (row.TemplateUID | 0) + '" title="Edit"><i class="bx bx-edit"></i></a>'
              + '<a href="javascript:void(0);" class="apex-icon-btn text-danger deleteTemplateBtn" data-uid="' + (row.TemplateUID | 0) + '" data-label="' + _esc(row.TemplateName) + '" title="Delete"><i class="bx bx-trash"></i></a>'
              + '</td></tr>';
    });
    return html;
}

// Loaders
/**
 * @returns {void}
 */
function _loadThemes() {
    $('#ThemesCardContainer').html('<div class="col-12 text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
    $.ajax({
        url:     global_services_url + 'api/printthemes/getThemeList',
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:    { PageNo: PageNo, RowLimit: 10, SearchAllData: Filter.SearchAllData || '', orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
        success: function(r) {
            if (r.Error) return;
            if (r.TransTypes)  { _transTypes = r.TransTypes; }
            if (r.UsedTypes)   { _usedTypes  = r.UsedTypes; _populateModuleSelect(); }
            if (r.Templates)   { _templates  = r.Templates; }
            if (r.OrgPreview)  { _orgPreview = r.OrgPreview; }
            $('#ThemesCardContainer').html(_renderThemeCardRows(r.Themes || r.Data || []));
            $('#ThemesPagination').html(_buildPagination(r.TotalCount, PageNo, 10));
            var ct = parseInt(r.TotalCount) || 0;
            $('#themeTotalCount').text(ct > 0 ? ct : '').toggleClass('d-none', ct <= 0);
            _renderThemeCards();
        }
    });
}

/**
 * @returns {void}
 */
function _loadTemplates() {
    $('#TemplatesTable tbody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');
    $.ajax({
        url:     global_services_url + 'api/printthemes/getTemplateList',
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:    { PageNo: PageNo, RowLimit: 10, Search: Filter.SearchAllData || '', orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
        success: function(r) {
            if (r.Error) return;
            if (r.Templates) _templates = r.Templates;
            $('#TemplatesTable tbody').html(_renderTemplateTableRows(r.Data || r.Templates || []));
            $('#TemplatesPagination').html(_buildPagination(r.TotalCount, PageNo, 10));
            var ct = parseInt(r.TotalCount) || 0;
            $('#templateTotalCount').text(ct > 0 ? ct : '').toggleClass('d-none', ct <= 0);
        }
    });
}

// Pagination
$(document).on('click','.ThemesPagination .page-link',    function(e){ e.preventDefault(); var p=parseInt($(this).data('page')); if(p>0){PageNo=p;_loadThemes();} });
$(document).on('click','.TemplatesPagination .page-link', function(e){ e.preventDefault(); var p=parseInt($(this).data('page')); if(p>0){PageNo=p;_loadTemplates();} });

// ── THEME MODAL ──────────────────────────────────────────────────────────────
function _renderCarousel(selUID){
    var track = document.getElementById('tplCarouselTrack');
    track.innerHTML = '';
    _templates.forEach(function(tpl){
        var sel = String(tpl.TemplateUID) === String(selUID);
        var item = document.createElement('div');
        item.className = 'tpl-carousel-item';
        item.dataset.uid = tpl.TemplateUID;
        item.style.cssText = 'width:110px;flex-shrink:0;cursor:pointer;border-radius:6px;overflow:hidden;border:2px solid '+(sel?'#0d6efd':'#dee2e6')+';'+(sel?'box-shadow:0 0 0 3px rgba(13,110,253,.2);':'')+'transition:border-color .15s;';
        var iw = document.createElement('div');
        iw.style.cssText = 'height:90px;overflow:hidden;';
        iw.innerHTML = tpl.PreviewImage
            ? '<img src="'+tpl.PreviewImage+'" style="width:100%;height:100%;object-fit:cover;" alt="'+tpl.TemplateName+'">'
            : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f0f0f0;font-size:7pt;color:#888;text-align:center;padding:4px;">'+tpl.TemplateName+'</div>';
        var lb = document.createElement('div');
        lb.style.cssText = 'background:#fff;padding:3px 6px;border-top:1px solid #eee;font-size:var(--r2k-text-70);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
        lb.textContent = tpl.TemplateName;
        item.appendChild(iw); item.appendChild(lb);
        track.appendChild(item);
    });
    _updateSelLabel(selUID);
}
function _selectTpl(uid){
    $('#TemplateUID').val(uid);
    $('.tpl-carousel-item').each(function(){
        var me = String($(this).data('uid')) === String(uid);
        $(this).css({'border-color': me?'#0d6efd':'#dee2e6','box-shadow': me?'0 0 0 3px rgba(13,110,253,.2)':'none'});
    });
    _updateSelLabel(uid);
}
function _updateSelLabel(uid){
    var t = _templates.find(function(x){ return String(x.TemplateUID)===String(uid); });
    $('#selectedTplName').text(t ? t.TemplateName : 'None selected');
}

$(document).on('click','.tpl-carousel-item', function(){
    var uid = $(this).data('uid');
    var already = String($('#TemplateUID').val()) === String(uid);
    _selectTpl(uid);
    _buildLivePreview();
    if (already){
        var t = _templates.find(function(x){ return String(x.TemplateUID)===String(uid); });
        if (!t) return;
        $('#tplPreviewLabel').text(t.TemplateName);
        $('#tplPreviewBox').html(t.PreviewImage
            ? '<img src="'+t.PreviewImage+'" style="width:100%;border-radius:4px;" alt="'+t.TemplateName+'">'
            : '<div style="padding:20px;text-align:center;color:#888;">No preview image available.</div>');
        $('#tplPreviewOverlay').css('display','flex');
    }
});
$('#tplCarouselPrev').on('click', function(){ document.getElementById('tplCarouselTrack').scrollBy({left:-140,behavior:'smooth'}); });
$('#tplCarouselNext').on('click', function(){ document.getElementById('tplCarouselTrack').scrollBy({left:140,behavior:'smooth'}); });
$('#tplPreviewClose,#tplPreviewSelect').on('click', function(){ $('#tplPreviewOverlay').hide(); });
$('#tplPreviewOverlay').on('click', function(e){ if($(e.target).is('#tplPreviewOverlay')) $(this).hide(); });

// Brand color — picker + text input
$('#BrandColorPicker').on('input', function(){
    var v = $(this).val();
    $('#BrandColor').val(v); $('#brandColorRing').css('border-color', v);
    _debouncePrev(_buildLivePreview, 50);
});
$('#BrandColor').on('input', function(){
    var v = $(this).val();
    if (/^#[0-9a-fA-F]{6}$/.test(v)) { $('#BrandColorPicker').val(v); $('#brandColorRing').css('border-color', v); _debouncePrev(_buildLivePreview, 300); }
});
// Preset swatches
$(document).on('click', '.r2k-swatch', function(){
    var c = $(this).data('color');
    $('.r2k-swatch').removeClass('r2k-swatch-active');
    $(this).addClass('r2k-swatch-active');
    $('#BrandColor').val(c); $('#BrandColorPicker').val(c); $('#brandColorRing').css('border-color', c);
    _buildLivePreview();
});

// Display toggles
$('#ShowLogo,#ShowOrgAddress,#ShowGSTIN,#ShowHSN,#ShowTaxBreakdown,#ShowPartyBalance,#ShowTime').on('change', function(){ _buildLivePreview(); });

// Font preview
function _fontPreview(){
    var f=$('#FontFamily').val()||'Arial', s=parseInt($('#FontSizePx').val())||11;
    $('#fontPreviewText').css({'font-family':"'"+f+"',sans-serif",'font-size':s+'px'});
    var sys=['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
    if(sys.indexOf(f)===-1){ var id='gfont-'+f.replace(/\s+/g,'-'); if(!$('#'+id).length) $('<link>',{id:id,rel:'stylesheet',href:'https://fonts.googleapis.com/css2?family='+encodeURIComponent(f)+':wght@400;600;700&display=swap'}).appendTo('head'); }
    _debouncePrev(_buildLivePreview, 300);
}
$('#FontFamily').on('change',_fontPreview);
$('#FontSizePx').on('input',_fontPreview);

// Sync hidden TransactionTypeVal when select changes (Add mode)
$('#TransactionType').on('change', function () {
    $('#TransactionTypeVal').val($(this).val());
});

// Open Add
$('#btnNewTheme').on('click', function(){
    $('#ThemeConfigUID').val(0);
    $('#themeModalTitle').html('<i class="bx bx-palette me-1"></i>Add Print Theme');
    $('#TransactionType').val('').show();
    $('#TransactionTypeVal').val('');
    $('#ModuleDisplayText').addClass('d-none');
    var _defTpl = _templates.find(function(x){ return x.TemplateKey === 'classic'; }) || _templates[0] || null;
    var _defUID = _defTpl ? _defTpl.TemplateUID : 0;
    $('#TemplateUID').val(_defUID);
    var _dc = '#1a3c6e';
    $('#BrandColor').val(_dc); $('#BrandColorPicker').val(_dc); $('#brandColorRing').css('border-color', _dc);
    $('#PrimaryColor').val(_dc); $('#AccentColor').val(_deriveAccent(_dc));
    $('.r2k-swatch').removeClass('r2k-swatch-active');
    $('.r2k-swatch[data-color="'+_dc+'"]').addClass('r2k-swatch-active');
    $('#ShowLogo,#ShowOrgAddress,#ShowGSTIN,#ShowHSN,#ShowTaxBreakdown,#ShowPartyBalance,#ShowTime').prop('checked',true);
    $('#FontFamily').val('Arial'); $('#FontSizePx').val(11);
    _renderCarousel(_defUID);
    _previewLoadedUID = null;
    _themeModal.show();
    setTimeout(_buildLivePreview, 200);
});

// Edit theme
$(document).on('click','.editThemeBtn', function(){
    var uid = $(this).data('uid');
    $.ajax({ url: global_services_url + 'api/printthemes/getThemeData', method:'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:{ ThemeConfigUID:uid, orgId: parseInt(JwtData.Org.OrgUID,10), userId: parseInt(JwtData.User.UserUID,10) },
        success:function(resp){
            if(resp.Error){ Swal.fire({icon:'error',text:resp.Message}); return; }
            var d = resp.Data;
            $('#ThemeConfigUID').val(d.ThemeConfigUID);
            $('#themeModalTitle').html('<i class="bx bx-edit me-1"></i>Edit Print Theme');
            $('#TransactionTypeVal').val(d.TransactionType);
            $('#TransactionType').hide();
            $('#ModuleDisplayText').text(_transTypes[d.TransactionType] || d.TransactionType).removeClass('d-none');
            var _ec = d.PrimaryColor || '#1a3c6e';
            $('#BrandColor').val(_ec); $('#BrandColorPicker').val(_ec); $('#brandColorRing').css('border-color', _ec);
            $('#PrimaryColor').val(_ec); $('#AccentColor').val(_deriveAccent(_ec));
            $('.r2k-swatch').removeClass('r2k-swatch-active');
            $('.r2k-swatch[data-color="'+_ec+'"]').addClass('r2k-swatch-active');
            $('#ShowLogo').prop('checked',d.ShowLogo==1);
            $('#ShowOrgAddress').prop('checked',d.ShowOrgAddress==1);
            $('#ShowGSTIN').prop('checked',d.ShowGSTIN==1);
            $('#ShowHSN').prop('checked',d.ShowHSN==1);
            $('#ShowTaxBreakdown').prop('checked',d.ShowTaxBreakdown==1);
            $('#ShowPartyBalance').prop('checked',d.ShowPartyBalance==1);
            $('#ShowTime').prop('checked',d.ShowTime==1);
            $('#FontFamily').val(d.FontFamily||'Arial'); $('#FontSizePx').val(d.FontSizePx||11);
            _renderCarousel(d.TemplateUID||0); _selectTpl(d.TemplateUID||0); _fontPreview();
            _previewLoadedUID = null;
            _themeModal.show();
            setTimeout(_buildLivePreview, 200);
        }
    });
});

// Save theme
$('#saveThemeBtn').on('click', function(){
    var _typeVal = $('#TransactionTypeVal').val();
    if(!_typeVal){ Swal.fire({icon:'warning',text:'Please select a module.'}); return; }
    if(!$('#TemplateUID').val()||$('#TemplateUID').val()==='0'){ Swal.fire({icon:'warning',text:'Please select a template.'}); return; }
    var _isNewTheme = parseInt($('#ThemeConfigUID').val()) === 0;
    var _bc = $('#BrandColor').val() || '#1a3c6e';
    $('#PrimaryColor').val(_bc); $('#AccentColor').val(_deriveAccent(_bc));
    $('#saveThemeSpinner').removeClass('d-none'); $('#saveThemeBtn').prop('disabled',true);
    var fd = new FormData();
    fd.append('ThemeConfigUID',$('#ThemeConfigUID').val());
    fd.append('TransactionType', _typeVal);
    fd.append('TemplateUID',$('#TemplateUID').val());
    fd.append('PrimaryColor',$('#PrimaryColor').val());
    fd.append('AccentColor',$('#AccentColor').val());
    fd.append('ShowLogo',$('#ShowLogo').is(':checked')?1:0);
    fd.append('ShowOrgAddress',$('#ShowOrgAddress').is(':checked')?1:0);
    fd.append('ShowGSTIN',$('#ShowGSTIN').is(':checked')?1:0);
    fd.append('ShowHSN',$('#ShowHSN').is(':checked')?1:0);
    fd.append('ShowTaxBreakdown',$('#ShowTaxBreakdown').is(':checked')?1:0);
    fd.append('ShowPartyBalance',$('#ShowPartyBalance').is(':checked')?1:0);
    fd.append('ShowTime',$('#ShowTime').is(':checked')?1:0);
    fd.append('FontFamily',$('#FontFamily').val());
    fd.append('FontSizePx',$('#FontSizePx').val());
    fd.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    fd.append('userId', parseInt(JwtData.User.UserUID, 10));
    ajaxLoading(0);
    $.ajax({ url: global_services_url + 'api/printthemes/saveTheme', method:'POST', data:fd, processData:false, contentType:false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        success:function(r){
            $('#saveThemeSpinner').addClass('d-none'); $('#saveThemeBtn').prop('disabled',false);
            if(r.Error){ Swal.fire({icon:'error',text:r.Message}); return; }
            _themeModal.hide();
            Swal.fire({icon:'success',text:r.Message,timer:1500,showConfirmButton:false});
            _loadThemes();
        },
        error:function(){ $('#saveThemeSpinner').addClass('d-none'); $('#saveThemeBtn').prop('disabled',false); Swal.fire({icon:'error',text:'Request failed.'}); },
        complete:function(){ ajaxLoading(1); }
    });
});

// Delete theme
$(document).on('click','.deleteThemeBtn', function(){
    var uid=$(this).data('uid'), label=$(this).data('label');
    Swal.fire({icon:'warning',title:'Remove Theme?',text:'Remove theme for '+label+'?',showCancelButton:true,confirmButtonText:'Remove',confirmButtonColor:'#d33'})
    .then(function(r){ if(!r.isConfirmed) return;
        $.ajax({ url: global_services_url + 'api/printthemes/deleteTheme', method:'POST',
            data:{ ThemeConfigUID:uid, orgId: parseInt(JwtData.Org.OrgUID,10), userId: parseInt(JwtData.User.UserUID,10) },
            headers: { Authorization: 'Bearer ' + JwtToken },
            success:function(r){ if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} Swal.fire({icon:'success',text:r.Message,timer:1200,showConfirmButton:false}); _loadThemes(); }
        });
    });
});

// ── TEMPLATE MODAL ───────────────────────────────────────────────────────────
var _tplKeyManual = false;
var _tplImgTimer  = null;

$('#btnNewTemplate').on('click', function(){
    $('#TemplateModalUID').val(0);
    $('#templateModalTitle').html('<i class="bx bx-file-plus me-1"></i>Add Template');
    $('#TemplateName,#TemplateKey,#TplDescription,#TplPreviewImageUrl,#TplPreviewHtmlContent,#TplHtmlContent').val('');
    $('#TplCategory').val('general'); $('#TplSortOrder').val(0);
    $('#tplPreviewImgWrapper').hide();
    _tplKeyManual = false;
    _tplModal.show();
});

// Auto-generate key from name
$('#TemplateName').on('input', function(){
    if (_tplKeyManual) return;
    var slug = $(this).val().toLowerCase().replace(/[^a-z0-9\s_]/g,'').trim().replace(/\s+/g,'_');
    $('#TemplateKey').val(slug);
});
$('#TemplateKey').on('input', function(){
    var clean = $(this).val().toLowerCase().replace(/[^a-z0-9_]/g,'');
    $(this).val(clean);
    _tplKeyManual = clean.length > 0;
});
$('#templateModal').on('show.bs.modal', function(){
    if ($('#TemplateModalUID').val() == '0') _tplKeyManual = false;
});

// Live preview image
$('#TplPreviewImageUrl').on('input', function(){
    clearTimeout(_tplImgTimer);
    var url = $(this).val().trim();
    if (!url) { $('#tplPreviewImgWrapper').hide(); return; }
    _tplImgTimer = setTimeout(function(){
        var img = new Image();
        img.onload  = function(){ $('#tplPreviewImg').attr('src', url); $('#tplPreviewImgWrapper').show(); };
        img.onerror = function(){ $('#tplPreviewImgWrapper').hide(); };
        img.src = url;
    }, 500);
});

$(document).on('click','.editTemplateBtn', function(){
    var uid=$(this).data('uid');
    $.ajax({ url: global_services_url + 'api/printthemes/getTemplateData', method:'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:{ TemplateUID:uid, orgId: parseInt(JwtData.Org.OrgUID,10), userId: parseInt(JwtData.User.UserUID,10) },
        success:function(resp){
            if(resp.Error){Swal.fire({icon:'error',text:resp.Message});return;}
            var d=resp.Data;
            $('#TemplateModalUID').val(d.TemplateUID);
            $('#templateModalTitle').html('<i class="bx bx-edit me-1"></i>Edit Template');
            $('#TemplateName').val(d.TemplateName);
            $('#TemplateKey').val(d.TemplateKey); _tplKeyManual = true;
            $('#TplDescription').val(d.Description||'');
            $('#TplCategory').val(d.Category||'general');
            $('#TplPreviewImageUrl').val(d.PreviewImage||'');
            $('#TplPreviewHtmlContent').val(d.PreviewHtmlContent||'');
            $('#TplSortOrder').val(d.SortOrder||0);
            $('#TplHtmlContent').val(d.HtmlContent||'');
            if(d.PreviewImage){ $('#tplPreviewImg').attr('src',d.PreviewImage); $('#tplPreviewImgWrapper').show(); }
            else { $('#tplPreviewImgWrapper').hide(); }
            _tplModal.show();
        }
    });
});

$('#saveTplBtn').on('click', function(){
    var name=$('#TemplateName').val().trim();
    if(!name){Swal.fire({icon:'warning',text:'Template name is required.'});return;}
    $('#saveTplSpinner').removeClass('d-none'); $('#saveTplBtn').prop('disabled',true);
    var fd=new FormData();
    fd.append('TemplateUID',$('#TemplateModalUID').val());
    fd.append('TemplateName',name);
    fd.append('TemplateKey',$('#TemplateKey').val());
    fd.append('Description',$('#TplDescription').val());
    fd.append('Category',$('#TplCategory').val());
    fd.append('PreviewImage',$('#TplPreviewImageUrl').val());
    fd.append('SortOrder',$('#TplSortOrder').val());
    fd.append('PreviewHtmlContent',$('#TplPreviewHtmlContent').val());
    fd.append('HtmlContent',$('#TplHtmlContent').val());
    ajaxLoading(0);
    fd.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    fd.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({ url: global_services_url + 'api/printthemes/saveTemplate', method:'POST', data:fd, processData:false, contentType:false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        success:function(r){ $('#saveTplSpinner').addClass('d-none'); $('#saveTplBtn').prop('disabled',false); if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} _tplModal.hide(); Swal.fire({icon:'success',text:r.Message,timer:1500,showConfirmButton:false}); _loadTemplates(); },
        error:function(){ $('#saveTplSpinner').addClass('d-none'); $('#saveTplBtn').prop('disabled',false); Swal.fire({icon:'error',text:'Request failed.'}); },
        complete:function(){ ajaxLoading(1); }
    });
});

$(document).on('click','.deleteTemplateBtn', function(){
    var uid=$(this).data('uid'), label=$(this).data('label');
    Swal.fire({icon:'warning',title:'Delete Template?',text:'Delete "'+label+'"?',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#d33'})
    .then(function(r){ if(!r.isConfirmed) return;
        $.ajax({ url: global_services_url + 'api/printthemes/deleteTemplate', method:'POST',
            data:{ TemplateUID:uid, orgId: parseInt(JwtData.Org.OrgUID,10), userId: parseInt(JwtData.User.UserUID,10) },
            headers: { Authorization: 'Bearer ' + JwtToken },
            success:function(r){ if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} Swal.fire({icon:'success',text:r.Message,timer:1200,showConfirmButton:false}); _loadTemplates(); }
        });
    });
});

// Load list data from services endpoint on page init
if (ActiveTab === 'themes') _loadThemes(); else _loadTemplates();

});
</script>
