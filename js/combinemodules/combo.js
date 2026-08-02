// ──────────────────────────────────────────────────
// Combo item search — via ProductAppend (Upstash → AJAX fallback)
// ──────────────────────────────────────────────────
var _comboItemsCache    = null; // null = not loaded yet
var _comboIsDirty       = false;
var _comboIsCreateMode  = false;

function _loadComboItemsCache() {
    if (_comboItemsCache !== null) return Promise.resolve(_comboItemsCache);
    return new Promise(function (resolve) {
        ProductAppend.load(
            function (list) {
                _comboItemsCache = list
                    .filter(function (p) { return !p.isComposite; })
                    .map(function (p) { return { id: p.id, text: p.text }; });
                resolve(_comboItemsCache);
            },
            function () {
                _comboItemsCache = [];
                resolve(_comboItemsCache);
            }
        );
    });
}

function _getAddedComboUIDs() {
    var map = {};
    $('#ComboComponentsBody tr[data-uid]').each(function () { map[$(this).data('uid')] = true; });
    return map;
}

function _reinitComboItemSearch() {
    var $el = $('#ComboItemSearch');
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    var addedUIDs = _getAddedComboUIDs();
    if (_comboItemsCache && _comboItemsCache.length > 0) {
        var available = _comboItemsCache.filter(function (item) { return !addedUIDs[item.id]; });
        $el.select2({
            placeholder      : '-- Search & Select Item --',
            allowClear       : true,
            width            : '100%',
            minimumInputLength: 1,
            dropdownParent   : $('#comboItemModal'),
            data             : [{ id: '', text: '' }].concat(available)
        });
    } else {
        $el.select2({
            placeholder      : '-- Search & Select Item --',
            allowClear       : true,
            width            : '100%',
            dropdownParent   : $('#comboItemModal'),
            minimumInputLength: 1,
            ajax: {
                url         : '/products/getItemsForBOM',
                method      : 'POST',
                dataType    : 'json',
                delay       : 250,
                data        : function (params) {
                    return { search: params.term, [CsrfName]: CsrfToken };
                },
                processResults: function (data) {
                    if (data.Error) return { results: [] };
                    var added = _getAddedComboUIDs();
                    return {
                        results: data.Items.filter(function (item) {
                            return !added[item.ProductUID];
                        }).map(function (item) {
                            return { id: item.ProductUID, text: item.ItemName };
                        })
                    };
                },
                cache: false
            }
        });
    }
}

$(document).ready(function () {
    'use strict';

    // Tax % — styled with left (percentage) / right (tax name) templates
    loadComboTaxSelect2();

    loadSelect2Field('#ComboPrimaryUnit', '-- Select Unit --', '#comboItemModal');

    // ──────────────────────────────────────────────
    // Open combo modal on "Add Combo Item" button
    // ──────────────────────────────────────────────
    $(document).on('click', '#NewComboItem', function (e) {
        e.preventDefault();
        clearComboForm();
        $('#ComboModalTitle').text('Add Combo Item');
        $('.AddEditComboBtn').text('Save');
        Promise.all([DropdownCache.ready(), _loadComboItemsCache()]).then(function (results) {
            DropdownCache.populateProductModal(results[0]);
            _reinitComboItemSearch();
            var defTax       = (typeof _pfDefProdTaxUID   !== 'undefined' && _pfDefProdTaxUID)   ? _pfDefProdTaxUID   : null;
            var defTaxDetail = (typeof _pfDefTaxDetailUID !== 'undefined' && _pfDefTaxDetailUID) ? _pfDefTaxDetailUID : null;
            if (defTax)       { $('#ComboSellingTaxOption,#ComboPurchaseTaxOption').val(defTax).trigger('change'); }
            if (defTaxDetail) { $('#ComboTaxPercentage').val(defTaxDetail).trigger('change'); }
            $('#comboItemModal').modal('show');
            _comboIsCreateMode = true;
            _comboIsDirty      = false;
        });
    });

    // ──────────────────────────────────────────────
    // Add component item
    // ──────────────────────────────────────────────
    $(document).on('click', '#AddComboComponentBtn', function (e) {
        e.preventDefault();
        var itemUID  = $('#ComboItemSearch').val();
        var itemName = $('#ComboItemSearch option:selected').text().trim();
        var qty      = parseFloat($('#ComboItemQty').val()) || 1;

        if (!itemUID) {
            showToastNotification('Please select an item to add.', 'warning');
            return;
        }
        if (qty <= 0) {
            showToastNotification('Quantity must be at least 1.', 'warning');
            return;
        }

        // Check duplicate
        var exists = false;
        $('#ComboComponentsBody tr[data-uid]').each(function () {
            if ($(this).data('uid') == itemUID) { exists = true; return false; }
        });
        if (exists) {
            showToastNotification('This item is already added.', 'warning');
            return;
        }

        addComboComponentRow(itemUID, itemName, qty);
        updateComboComponentsData();
        _reinitComboItemSearch();
        $('#ComboItemSearch').val(null).trigger('change');
        $('#ComboItemQty').val(1);
    });

    // ──────────────────────────────────────────────
    // Remove component item
    // ──────────────────────────────────────────────
    $(document).on('click', '.RemoveComboComponent', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        renumberComboComponentRows();
        updateComboComponentsData();
        _reinitComboItemSearch();
        if ($('#ComboComponentsBody tr[data-uid]').length === 0) {
            $('#ComboComponentEmptyRow').show();
        }
    });

    // ──────────────────────────────────────────────
    // Component qty change (inline)
    // ──────────────────────────────────────────────
    $(document).on('change', '.ComboComponentQtyInput', function () {
        var val = parseFloat($(this).val()) || 1;
        if (val <= 0) val = 1;
        $(this).val((typeof smartDecimal === 'function') ? smartDecimal(val) : val);
        updateComboComponentsData();
    });

    // ──────────────────────────────────────────────
    // Form submit
    // ──────────────────────────────────────────────
    $('#AddEditComboForm').submit(function (e) {
        e.preventDefault();

        var componentCount = $('#ComboComponentsBody tr[data-uid]').length;
        if (componentCount < 2) {
            showToastNotification('A combo item must have at least 2 component items.', 'error');
            return;
        }

        updateComboComponentsData();

        var formData = new FormData($('#AddEditComboForm')[0]);
        formData.append('getTableDetails', 1);
        if (typeof PageNo       !== 'undefined') formData.append('PageNo',    PageNo);
        if (typeof RowLimit     !== 'undefined') formData.append('RowLimit',  RowLimit);
        if (typeof ItemModuleId !== 'undefined') formData.append('ModuleId',  ItemModuleId);
        if (typeof Filter !== 'undefined' && Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }

        var comboUID = $('#HComboUID').val();
        if (comboUID == 0) {
            addComboItemData(formData);
        } else {
            editComboItemData(formData);
        }
    });

    // ──────────────────────────────────────────────
    // Combo BOM row expand / collapse
    // ──────────────────────────────────────────────
    $(document).on('click', '.ComboExpandBtn', function (e) {
        e.stopPropagation();
        var btn       = $(this);
        var uid       = btn.data('uid');
        var bomRow    = $('#combo-bom-row-' + uid);
        var icon      = btn.find('i');
        var isOpen    = !bomRow.hasClass('d-none');

        if (isOpen) {
            bomRow.addClass('d-none');
            icon.removeClass('bx-chevron-down').addClass('bx-chevron-right');
            return;
        }

        bomRow.removeClass('d-none');
        icon.removeClass('bx-chevron-right').addClass('bx-chevron-down');

        // Already loaded — just show
        if (btn.data('loaded') == 1) return;

        // Lazy-load via AJAX
        $.ajax({
            url: '/products/retrieveComboDetails',
            method: 'POST',
            data: { ComboUID: uid, [CsrfName]: CsrfToken },
            success: function (response) {
                btn.data('loaded', 1);
                var content = bomRow.find('.combo-bom-content');
                if (response.Error || !response.Components || response.Components.length === 0) {
                    content.html('<span class="text-muted small py-2 d-block">No components found.</span>');
                    return;
                }
                var sym   = (typeof currencySymbol !== 'undefined') ? currencySymbol : '';
                var count = response.Components.length;
                var html  = '<div style="padding:10px 16px 6px 16px;">'
                          + '<div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.78rem;">'
                          + '<i class="bx bx-package text-warning fs-6"></i>'
                          + '<span class="fw-semibold text-warning">Combo Components</span>'
                          + '<span class="badge bg-label-warning" style="font-size:0.7rem;">' + count + ' item' + (count !== 1 ? 's' : '') + '</span>'
                          + '</div>'
                          + '<div style="border-top:1px solid rgba(0,0,0,0.07); margin-top:6px;">';

                $.each(response.Components, function (i, comp) {
                    var mrp      = parseFloat(comp.MRP)          || 0;
                    var selling  = parseFloat(comp.SellingPrice)  || 0;
                    var purchase = parseFloat(comp.PurchasePrice) || 0;
                    var name     = $('<span>').text(comp.ItemName).html();

                    html += '<div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid rgba(0,0,0,0.05); font-size:0.82rem;">'

                          // Serial number
                          + '<span class="text-muted fw-normal" style="min-width:20px; text-align:right;">' + (i + 1) + '</span>'

                          // Item name — takes remaining space
                          + '<div class="d-flex align-items-center gap-1 flex-grow-1" style="min-width:0;">'
                          + '<i class="bx bx-cube text-secondary flex-shrink-0" style="font-size:0.9rem;"></i>'
                          + '<span class="fw-medium text-truncate">' + name + '</span>'
                          + '</div>'

                          // Price chips
                          + '<div class="d-flex align-items-center gap-2 flex-shrink-0">'
                          + '<div class="d-flex align-items-center gap-1">'
                          + '<span class="text-muted" style="font-size:0.72rem;">MRP</span>'
                          + '<span class="fw-semibold">' + (mrp > 0 ? sym + smartDecimal(mrp) : '<span class="text-muted">—</span>') + '</span>'
                          + '</div>'
                          + '<span class="text-muted">|</span>'
                          + '<div class="d-flex align-items-center gap-1">'
                          + '<span class="text-muted" style="font-size:0.72rem;">Sell</span>'
                          + '<span class="fw-semibold text-primary">' + (selling > 0 ? sym + smartDecimal(selling) : '<span class="text-muted">—</span>') + '</span>'
                          + '</div>'
                          + '<span class="text-muted">|</span>'
                          + '<div class="d-flex align-items-center gap-1">'
                          + '<span class="text-muted" style="font-size:0.72rem;">Purchase</span>'
                          + '<span class="fw-semibold">' + (purchase > 0 ? sym + smartDecimal(purchase) : '<span class="text-muted">—</span>') + '</span>'
                          + '</div>'
                          + '</div>'

                          // Qty badge
                          + '<span class="badge bg-label-secondary flex-shrink-0" style="font-size:0.78rem; min-width:36px;">×' + smartDecimal(comp.Quantity) + '</span>'

                          + '</div>';
                });

                html += '</div></div>';
                content.html(html);
            },
            error: function () {
                bomRow.find('.combo-bom-content').html('<span class="text-danger small py-2 d-block">Failed to load components.</span>');
            }
        });
    });

    // ──────────────────────────────────────────────
    // Selling / Purchase tax option → dynamic label
    // ──────────────────────────────────────────────
    $(document).on('change', '#ComboSellingTaxOption', function () {
        var txt = $(this).find('option:selected').text().toLowerCase();
        $('#ComboSellingPriceTaxHelp,#ComboSellingPriceWTaxHelp').addClass('d-none');
        if (txt.indexOf('with tax') !== -1)  { $('#ComboSellingPriceTaxHelp').removeClass('d-none'); }
        else if (txt.indexOf('without') !== -1) { $('#ComboSellingPriceWTaxHelp').removeClass('d-none'); }
    });

    $(document).on('change', '#ComboPurchaseTaxOption', function () {
        var txt = $(this).find('option:selected').text().toLowerCase();
        $('#ComboPurchasePriceTaxHelp,#ComboPurchasePriceWTaxHelp').addClass('d-none');
        if (txt.indexOf('with tax') !== -1)  { $('#ComboPurchasePriceTaxHelp').removeClass('d-none'); }
        else if (txt.indexOf('without') !== -1) { $('#ComboPurchasePriceWTaxHelp').removeClass('d-none'); }
    });

    // ──────────────────────────────────────────────
    // Reset modal on close
    // ──────────────────────────────────────────────
    $('#comboItemModal').on('hidden.bs.modal', function () {
        clearComboForm();
    });

});

// ──────────────────────────────────────────────────
// Helper: add a component row
// ──────────────────────────────────────────────────
function addComboComponentRow(itemUID, itemName, qty) {
    $('#ComboComponentEmptyRow').hide();
    var count   = $('#ComboComponentsBody tr[data-uid]').length + 1;
    var dispQty = (typeof smartDecimal === 'function') ? smartDecimal(qty) : parseFloat(qty);
    var _maxLen = (typeof _comboQtyMaxLen !== 'undefined') ? _comboQtyMaxLen : 10;
    var _dec    = (typeof _comboQtyDecimals !== 'undefined') ? _comboQtyDecimals : 2;
    var row = '<tr data-uid="' + itemUID + '">' +
                '<td>' + count + '</td>' +
                '<td>' + itemName + '</td>' +
                '<td><input type="text" class="form-control form-control-sm ComboComponentQtyInput" value="' + dispQty + '"' +
                    ' onkeydown="return handleDotOnly(event)"' +
                    ' oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this,' + _maxLen + ',' + _dec + ')"' +
                    ' maxlength="' + _maxLen + '"' +
                    ' onpaste="handlePricePaste(event,' + _maxLen + ',' + _dec + ')"' +
                    ' ondrop="handlePriceDrop(event,' + _maxLen + ',' + _dec + ')"' +
                    ' style="width:90px;" /></td>' +
                '<td><button type="button" class="btn btn-sm btn-danger RemoveComboComponent"><i class="bx bx-trash"></i></button></td>' +
              '</tr>';
    $('#ComboComponentsBody').append(row);
}

function renumberComboComponentRows() {
    $('#ComboComponentsBody tr[data-uid]').each(function (i) {
        $(this).find('td:first').text(i + 1);
    });
}

function updateComboComponentsData() {
    var components = [];
    $('#ComboComponentsBody tr[data-uid]').each(function () {
        components.push({
            ItemUID: $(this).data('uid'),
            Qty: parseFloat($(this).find('.ComboComponentQtyInput').val()) || 1
        });
    });
    $('#ComboComponentsData').val(JSON.stringify(components));
}

// ──────────────────────────────────────────────────
// Tax % Select2 — styled left (%) / right (name)
// ──────────────────────────────────────────────────
function loadComboTaxSelect2() {
    var $el = $('#ComboTaxPercentage');
    if (!$el.length) return;
    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

    function taxTemplate(data) {
        if (!data.id) return data.text;
        var el    = $(data.element);
        var left  = el.data('left');
        var right = el.data('right');
        if (!left && !right) return data.text;
        return $('<div class="d-flex justify-content-between">' +
                '<span class="fw-semibold">' + left + '</span>' +
                '<span class="text-muted small">' + right + '</span>' +
                '</div>');
    }

    $el.select2({
        placeholder: '-- Select Tax Percentage --',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: $('#comboItemModal'),
        templateResult:    taxTemplate,
        templateSelection: taxTemplate
    });
}

// ──────────────────────────────────────────────────
// Clear the whole combo form
// ──────────────────────────────────────────────────
function clearComboForm() {
    _comboIsCreateMode = false;
    _comboIsDirty      = false;
    $('#AddEditComboForm')[0].reset();
    $('#HComboUID').val(0);
    $('#ComboTaxPercentage').val('').trigger('change');
    $('#ComboPrimaryUnit').val('').trigger('change');
    $('#ComboItemSearch').val(null).trigger('change');
    $('#ComboComponentsBody tr[data-uid]').remove();
    $('#ComboComponentEmptyRow').show();
    $('#ComboComponentsData').val('[]');
}

// ──────────────────────────────────────────────────
// Load combo data for edit
// ──────────────────────────────────────────────────
function loadComboForEdit(comboUID) {
    $.ajax({
        url: '/products/retrieveComboDetails',
        method: 'POST',
        data: { ComboUID: comboUID, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                Swal.fire({ icon: 'error', title: 'Oops...', text: response.Message });
                return;
            }
            clearComboForm();
            var d = response.Data;
            $('#HComboUID').val(d.ProductUID);
            $('#ComboName').val(d.ItemName);
            $('#ComboSellingPrice').val(smartDecimal(d.SellingPrice));
            $('#ComboPurchasePrice').val(smartDecimal(d.PurchasePrice || 0));
            $('#ComboMRP').val(smartDecimal(d.MRP || 0));
            $('#ComboDescription').val(d.Description || '');
            // Load BOM components
            if (response.Components && response.Components.length > 0) {
                $.each(response.Components, function (i, comp) {
                    addComboComponentRow(comp.ChildProductUID, comp.ItemName, comp.Quantity);
                });
                updateComboComponentsData();
            }

            $('#ComboModalTitle').text('Edit Combo Item');
            $('.AddEditComboBtn').text('Update');

            Promise.all([DropdownCache.ready(), _loadComboItemsCache()]).then(function (results) {
                DropdownCache.populateProductModal(results[0]);
                _reinitComboItemSearch();
                if (d.SellingProductTaxUID)       { $('#ComboSellingTaxOption').val(d.SellingProductTaxUID).trigger('change'); }
                if (d.PurchasePriceProductTaxUID) { $('#ComboPurchaseTaxOption').val(d.PurchasePriceProductTaxUID).trigger('change'); }
                if (d.TaxDetailsUID)  { $('#ComboTaxPercentage').val(d.TaxDetailsUID).trigger('change'); }
                if (d.PrimaryUnitUID) { $('#ComboPrimaryUnit').val(d.PrimaryUnitUID).trigger('change'); }
                $('#comboItemModal').modal('show');
            });
        }
    });
}

// ──────────────────────────────────────────────────
// AJAX — Add combo
// ──────────────────────────────────────────────────
function addComboItemData(formData) {
    $.ajax({
        url: '/products/addComboItem',
        method: 'POST',
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to save combo.', 'error');
            } else {
                _comboIsDirty      = false;
                _comboIsCreateMode = false;
                $('#comboItemModal').modal('hide');
                showToastNotification(response.Message, 'success');
                clearComboForm();
                if (typeof executeProdPagnFunc === 'function') {
                    executeProdPagnFunc(response, true);
                }
            }
        }
    });
}

// ──────────────────────────────────────────────────
// AJAX — Edit combo
// ──────────────────────────────────────────────────
function editComboItemData(formData) {
    $.ajax({
        url: '/products/editComboItem',
        method: 'POST',
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to update combo.', 'error');
            } else {
                $('#comboItemModal').modal('hide');
                showToastNotification(response.Message, 'success');
                clearComboForm();
                if (typeof executeProdPagnFunc === 'function') {
                    executeProdPagnFunc(response, true);
                }
            }
        }
    });
}

// ── Dirty-tracking listener ───────────────────────────────────────────────
$(document).on('input change', '#AddEditComboForm input, #AddEditComboForm textarea, #AddEditComboForm select', function () {
    if (_comboIsCreateMode) _comboIsDirty = true;
});

// ── Unsaved-changes guard ─────────────────────────────────────────────────
$(document).on('hide.bs.modal', '#comboItemModal', function (e) {
    if (!_comboIsDirty || !_comboIsCreateMode) return;
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
            _comboIsDirty      = false;
            _comboIsCreateMode = false;
            $('#comboItemModal').modal('hide');
        }
    });
});
