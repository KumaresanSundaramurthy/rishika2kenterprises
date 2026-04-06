<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="card">
                        <div class="row">
                            <div class="col-md-12">

                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <ul class="nav nav-pills flex-row" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData === 'themes' ? 'active' : ''; ?> TabPane"
                                               data-id="themes" role="tab" data-bs-toggle="tab"
                                               data-bs-target="#NavThemesPage" href="javascript:void(0);">
                                                <i class="bx bx-palette me-1"></i>Themes
                                                <span class="badge bg-label-warning ms-1" id="themeTotalCount"><?php echo $ActiveTabData === 'themes' ? (int)$TotalCount : ''; ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData === 'templates' ? 'active' : ''; ?> TabPane"
                                               data-id="templates" role="tab" data-bs-toggle="tab"
                                               data-bs-target="#NavTemplatesPage" href="javascript:void(0);">
                                                <i class="bx bx-file me-1"></i>Templates
                                                <span class="badge bg-label-info ms-1" id="templateTotalCount"><?php echo $ActiveTabData === 'templates' ? (int)$TotalCount : ''; ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0 gap-2 align-items-center">
                                        <a href="javascript:void(0);" class="btn PageRefresh p-2"><i class="bx bx-refresh fs-4"></i></a>
                                        <div class="position-relative">
                                            <input type="text" class="form-control SearchDetails" id="SearchDetails" placeholder="Search..." style="min-width:200px;">
                                            <i class="bx bx-x position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer d-none" id="clearSearch"></i>
                                        </div>
                                        <button class="btn btn-primary btn-sm px-3 <?php echo $ActiveTabData === 'themes' ? '' : 'd-none'; ?>" id="btnNewTheme">
                                            <i class="bx bx-plus me-1"></i>Add Theme
                                        </button>
                                        <button class="btn btn-primary btn-sm px-3 <?php echo $ActiveTabData === 'templates' ? '' : 'd-none'; ?>" id="btnNewTemplate">
                                            <i class="bx bx-plus me-1"></i>Add Template
                                        </button>
                                    </div>
                                </div>

                                <div class="tab-content p-0">

                                    <!-- THEMES TAB -->
                                    <div class="tab-pane fade <?php echo $ActiveTabData === 'themes' ? 'show active' : ''; ?>" id="NavThemesPage" role="tabpanel">
                                        <div class="table-responsive text-nowrap tablecard">
                                            <table class="table table-hover MainviewTable" id="ThemesTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th style="width:80px;">Preview</th>
                                                        <th>Transaction Type</th>
                                                        <th>Template</th>
                                                        <th>Colors</th>
                                                        <th>Display Options</th>
                                                        <th>Font</th>
                                                        <th>Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData === 'themes') echo $ModRowData; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0">
                                        <div class="row mx-3 justify-content-between ThemesPagination" id="ThemesPagination">
                                            <?php if ($ActiveTabData === 'themes') echo $ModPagination; ?>
                                        </div>
                                    </div>

                                    <!-- TEMPLATES TAB -->
                                    <div class="tab-pane fade <?php echo $ActiveTabData === 'templates' ? 'show active' : ''; ?>" id="NavTemplatesPage" role="tabpanel">
                                        <div class="table-responsive text-nowrap tablecard">
                                            <table class="table table-hover MainviewTable" id="TemplatesTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th style="width:90px;">Preview</th>
                                                        <th>Template Name</th>
                                                        <th>Key</th>
                                                        <th>Category</th>
                                                        <th>Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData === 'templates') echo $ModRowData; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0">
                                        <div class="row mx-3 justify-content-between TemplatesPagination" id="TemplatesPagination">
                                            <?php if ($ActiveTabData === 'templates') echo $ModPagination; ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('printthemes/modals/theme');    ?>
<?php $this->load->view('printthemes/modals/template'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script>
$(function () {
'use strict';
var ActiveTab  = '<?php echo $ActiveTabData; ?>';
var _usedTypes = <?php echo json_encode(array_values($UsedTypes)); ?>;
var PageNo     = 1, Filter = {};
var _themeModal = new bootstrap.Modal(document.getElementById('themeModal'));
var _tplModal   = new bootstrap.Modal(document.getElementById('templateModal'));
var _templates  = <?php echo json_encode(array_values($Templates)); ?>;
var CsrfName    = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken   = '<?php echo $this->security->get_csrf_hash(); ?>';

// Tab
$('.TabPane').on('click', function(e){
    e.preventDefault();
    ActiveTab = $(this).data('id');
    PageNo = 1; Filter = {};
    $('#SearchDetails').val(''); $('#clearSearch').addClass('d-none');
    $('#btnNewTheme,#btnNewTemplate').addClass('d-none');
    if (ActiveTab === 'themes')    { $('#btnNewTheme').removeClass('d-none');    _loadThemes(); }
    if (ActiveTab === 'templates') { $('#btnNewTemplate').removeClass('d-none'); _loadTemplates(); }
});
$('.PageRefresh').on('click', function(){ if(ActiveTab==='themes') _loadThemes(); else _loadTemplates(); });

// Search
var _st;
$('#SearchDetails').on('input', function(){
    var v = $(this).val();
    $('#clearSearch').toggleClass('d-none', !v);
    clearTimeout(_st);
    if (v.length===0 || v.length>=3) {
        _st = setTimeout(function(){ PageNo=1; Filter.SearchAllData=$('#SearchDetails').val(); if(ActiveTab==='themes') _loadThemes(); else _loadTemplates(); }, 400);
    }
});
$('#clearSearch').on('click', function(){ $('#SearchDetails').val('').trigger('input'); });

// Loaders
function _loadThemes(){
    $.ajax({ url:'/print-themes/getThemeList', method:'POST',
        data:{ PageNo:PageNo, RowLimit:10, Filter:Filter, [CsrfName]:CsrfToken },
        success:function(r){ if(r.Error) return; $('#ThemesTable tbody').html(r.RecordHtmlData); $('#ThemesPagination').html(r.Pagination); if(r.TotalCount!==undefined) $('#themeTotalCount').text(r.TotalCount); }
    });
}
function _loadTemplates(){
    $.ajax({ url:'/print-themes/getTemplateList', method:'POST',
        data:{ PageNo:PageNo, RowLimit:10, Search:Filter.SearchAllData||'', [CsrfName]:CsrfToken },
        success:function(r){ if(r.Error) return; $('#TemplatesTable tbody').html(r.RecordHtmlData); $('#TemplatesPagination').html(r.Pagination); if(r.TotalCount!==undefined) $('#templateTotalCount').text(r.TotalCount); }
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
        lb.style.cssText = 'background:#fff;padding:3px 6px;border-top:1px solid #eee;font-size:.7rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
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
    // Update right-panel large preview in theme modal
    var t = _templates.find(function(x){ return String(x.TemplateUID)===String(uid); });
    if (t) {
        $('#previewThemeLabel').text(t.TemplateName);
        if (t.PreviewImage) {
            $('#tplLargePreviewImg').html('<img src="'+t.PreviewImage+'" style="max-width:100%;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,.15);" alt="'+t.TemplateName+'">');
            $('#tplNoPreviewMsg').hide();
        } else {
            $('#tplLargePreviewImg').html('');
            $('#tplNoPreviewMsg').show();
        }
    }
    if (already){
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

// Colors
$('#PrimaryColorPicker').on('input',function(){ $('#PrimaryColor').val($(this).val()); });
$('#AccentColorPicker').on('input',function(){ $('#AccentColor').val($(this).val()); });
$('#PrimaryColor').on('input',function(){ if(/^#[0-9a-fA-F]{6}$/.test($(this).val())) $('#PrimaryColorPicker').val($(this).val()); });
$('#AccentColor').on('input',function(){ if(/^#[0-9a-fA-F]{6}$/.test($(this).val())) $('#AccentColorPicker').val($(this).val()); });

// Font preview
function _fontPreview(){
    var f=$('#FontFamily').val()||'Arial', s=parseInt($('#FontSizePx').val())||11;
    $('#fontPreviewText').css({'font-family':"'"+f+"',sans-serif",'font-size':s+'px'});
    var sys=['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
    if(sys.indexOf(f)===-1){ var id='gfont-'+f.replace(/\s+/g,'-'); if(!$('#'+id).length) $('<link>',{id:id,rel:'stylesheet',href:'https://fonts.googleapis.com/css2?family='+encodeURIComponent(f)+':wght@400;600;700&display=swap'}).appendTo('head'); }
}
$('#FontFamily').on('change',_fontPreview);
$('#FontSizePx').on('input',_fontPreview);

function _filterTypeOptions(){
    var isEdit = parseInt($('#ThemeConfigUID').val())>0;
    var cur    = isEdit ? $('#TransactionType').val() : null;
    $('#TransactionType option[value!=""]').each(function(){
        var v=$(this).val();
        $(this).prop('disabled', _usedTypes.indexOf(v)!==-1 && v!==cur);
    });
}

// Open Add
$('#btnNewTheme').on('click', function(){
    $('#ThemeConfigUID').val(0);
    $('#themeModalTitle').html('<i class="bx bx-palette me-1"></i>Add Print Theme');
    $('#TransactionType').val('').prop('disabled', false);
    _filterTypeOptions();
    $('#TemplateUID').val(0); $('#selectedTplName').text('None selected');
    $('#PrimaryColor').val('#1a3c6e'); $('#PrimaryColorPicker').val('#1a3c6e');
    $('#AccentColor').val('#f59e0b');  $('#AccentColorPicker').val('#f59e0b');
    $('#FooterText').val('Thank you for your business!');
    $('#ShowLogo,#ShowOrgAddress,#ShowGSTIN,#ShowHSN,#ShowTaxBreakdown').prop('checked',true);
    $('#FontFamily').val('Arial'); $('#FontSizePx').val(11);
    $('#typeUsedNote').addClass('d-none');
    _renderCarousel(0);
    _themeModal.show();
});

// Edit theme
$(document).on('click','.editThemeBtn', function(){
    var uid = $(this).data('uid');
    $.ajax({ url:'/print-themes/getThemeData', method:'GET', data:{ThemeConfigUID:uid},
        success:function(resp){
            if(resp.Error){ Swal.fire({icon:'error',text:resp.Message}); return; }
            var d = resp.Data;
            $('#ThemeConfigUID').val(d.ThemeConfigUID);
            $('#themeModalTitle').html('<i class="bx bx-edit me-1"></i>Edit Print Theme');
            $('#TransactionType').val(d.TransactionType).prop('disabled',true);
            $('#typeUsedNote').addClass('d-none');
            $('#PrimaryColor').val(d.PrimaryColor); $('#PrimaryColorPicker').val(d.PrimaryColor);
            $('#AccentColor').val(d.AccentColor);   $('#AccentColorPicker').val(d.AccentColor);
            $('#ShowLogo').prop('checked',d.ShowLogo==1);
            $('#ShowOrgAddress').prop('checked',d.ShowOrgAddress==1);
            $('#ShowGSTIN').prop('checked',d.ShowGSTIN==1);
            $('#ShowHSN').prop('checked',d.ShowHSN==1);
            $('#ShowTaxBreakdown').prop('checked',d.ShowTaxBreakdown==1);
            $('#FooterText').val(d.FooterText||'');
            $('#FontFamily').val(d.FontFamily||'Arial'); $('#FontSizePx').val(d.FontSizePx||11);
            _renderCarousel(d.TemplateUID||0); _selectTpl(d.TemplateUID||0); _fontPreview();
            _themeModal.show();
        }
    });
});

// Save theme
$('#saveThemeBtn').on('click', function(){
    if(!$('#TransactionType').val()){ Swal.fire({icon:'warning',text:'Please select a transaction type.'}); return; }
    if(!$('#TemplateUID').val()||$('#TemplateUID').val()==='0'){ Swal.fire({icon:'warning',text:'Please select a template.'}); return; }
    $('#saveThemeSpinner').removeClass('d-none'); $('#saveThemeBtn').prop('disabled',true);
    var fd = new FormData();
    fd.append('ThemeConfigUID',$('#ThemeConfigUID').val());
    fd.append('TransactionType',$('#TransactionType').val());
    fd.append('TemplateUID',$('#TemplateUID').val());
    fd.append('PrimaryColor',$('#PrimaryColor').val());
    fd.append('AccentColor',$('#AccentColor').val());
    fd.append('ShowLogo',$('#ShowLogo').is(':checked')?1:0);
    fd.append('ShowOrgAddress',$('#ShowOrgAddress').is(':checked')?1:0);
    fd.append('ShowGSTIN',$('#ShowGSTIN').is(':checked')?1:0);
    fd.append('ShowHSN',$('#ShowHSN').is(':checked')?1:0);
    fd.append('ShowTaxBreakdown',$('#ShowTaxBreakdown').is(':checked')?1:0);
    fd.append('FooterText',$('#FooterText').val());
    fd.append('FontFamily',$('#FontFamily').val());
    fd.append('FontSizePx',$('#FontSizePx').val());
    fd.append(CsrfName,CsrfToken);
    $.ajax({ url:'/print-themes/saveTheme', method:'POST', data:fd, processData:false, contentType:false,
        success:function(r){ $('#saveThemeSpinner').addClass('d-none'); $('#saveThemeBtn').prop('disabled',false); if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} _themeModal.hide(); Swal.fire({icon:'success',text:r.Message,timer:1500,showConfirmButton:false}); _loadThemes(); },
        error:function(){ $('#saveThemeSpinner').addClass('d-none'); $('#saveThemeBtn').prop('disabled',false); Swal.fire({icon:'error',text:'Request failed.'}); }
    });
});

// Delete theme
$(document).on('click','.deleteThemeBtn', function(){
    var uid=$(this).data('uid'), label=$(this).data('label');
    Swal.fire({icon:'warning',title:'Remove Theme?',text:'Remove theme for '+label+'?',showCancelButton:true,confirmButtonText:'Remove',confirmButtonColor:'#d33'})
    .then(function(r){ if(!r.isConfirmed) return;
        $.ajax({ url:'/print-themes/deleteTheme', method:'POST', data:{ThemeConfigUID:uid,[CsrfName]:CsrfToken},
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
    $('#TemplateName,#TemplateKey,#TplDescription,#TplPreviewImageUrl,#TplHtmlContent').val('');
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
    $.ajax({ url:'/print-themes/getTemplateData', method:'GET', data:{TemplateUID:uid},
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
    fd.append('HtmlContent',$('#TplHtmlContent').val());
    fd.append(CsrfName,CsrfToken);
    $.ajax({ url:'/print-themes/saveTemplate', method:'POST', data:fd, processData:false, contentType:false,
        success:function(r){ $('#saveTplSpinner').addClass('d-none'); $('#saveTplBtn').prop('disabled',false); if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} _tplModal.hide(); Swal.fire({icon:'success',text:r.Message,timer:1500,showConfirmButton:false}); _loadTemplates(); },
        error:function(){ $('#saveTplSpinner').addClass('d-none'); $('#saveTplBtn').prop('disabled',false); Swal.fire({icon:'error',text:'Request failed.'}); }
    });
});

$(document).on('click','.deleteTemplateBtn', function(){
    var uid=$(this).data('uid'), label=$(this).data('label');
    Swal.fire({icon:'warning',title:'Delete Template?',text:'Delete "'+label+'"?',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#d33'})
    .then(function(r){ if(!r.isConfirmed) return;
        $.ajax({ url:'/print-themes/deleteTemplate', method:'POST', data:{TemplateUID:uid,[CsrfName]:CsrfToken},
            success:function(r){ if(r.Error){Swal.fire({icon:'error',text:r.Message});return;} Swal.fire({icon:'success',text:r.Message,timer:1200,showConfirmButton:false}); _loadTemplates(); }
        });
    });
});

});
</script>
