$(document).ready(function () {
    'use strict';

    // ──────────────────────────────────────────────
    // Select2 — Item search (AJAX) for components
    // ──────────────────────────────────────────────
    $('#ComboItemSearch').select2({
        placeholder: '-- Search & Select Item --',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#comboItemModal'),
        minimumInputLength: 1,
        ajax: {
            url: '/products/getItemsForBOM',
            method: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { search: params.term, [CsrfName]: CsrfToken };
            },
            processResults: function (data) {
                if (data.Error) return { results: [] };
                return {
                    results: data.Items.map(function (item) {
                        return { id: item.ProductUID, text: item.ItemName };
                    })
                };
            },
            cache: true
        }
    });

    // Tax % and Primary Unit select2
    loadSelect2Field('#ComboTaxPercentage', '-- Select Tax Percentage --', '#comboItemModal');
    loadSelect2Field('#ComboPrimaryUnit', '-- Select Unit --', '#comboItemModal');

    // ──────────────────────────────────────────────
    // Open combo modal on "Add Combo Item" button
    // ──────────────────────────────────────────────
    $(document).on('click', '#NewComboItem', function (e) {
        e.preventDefault();
        clearComboForm();
        $('#ComboModalTitle').text('Add Combo Item');
        $('.AddEditComboBtn').text('Save');
        $('#comboItemModal').modal('show');
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
            inlineMessageAlert('.comboFormAlert', 'warning', 'Please select an item to add.', true, false);
            return;
        }
        if (qty <= 0) {
            inlineMessageAlert('.comboFormAlert', 'warning', 'Quantity must be at least 1.', true, false);
            return;
        }

        // Check duplicate
        var exists = false;
        $('#ComboComponentsBody tr[data-uid]').each(function () {
            if ($(this).data('uid') == itemUID) { exists = true; return false; }
        });
        if (exists) {
            inlineMessageAlert('.comboFormAlert', 'warning', 'This item is already added.', true, false);
            return;
        }

        addComboComponentRow(itemUID, itemName, qty);
        $('#ComboItemSearch').val(null).trigger('change');
        $('#ComboItemQty').val(1);
        updateComboComponentsData();
    });

    // ──────────────────────────────────────────────
    // Remove component item
    // ──────────────────────────────────────────────
    $(document).on('click', '.RemoveComboComponent', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        renumberComboComponentRows();
        updateComboComponentsData();
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
            inlineMessageAlert('.comboFormAlert', 'danger', 'A combo item must have at least 2 component items.', false, false);
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
                var html = '<div class="py-2">'
                         + '<div class="d-flex align-items-center mb-2" style="font-size:0.78rem;">'
                         + '<i class="bx bx-package text-warning me-1 fs-6"></i>'
                         + '<span class="fw-semibold text-warning">Combo Components</span>'
                         + '<span class="badge bg-label-warning ms-2" style="font-size:0.7rem;">' + response.Components.length + ' item' + (response.Components.length > 1 ? 's' : '') + '</span>'
                         + '</div>'
                         + '<table class="table table-sm table-borderless mb-0" style="font-size:0.81rem; max-width:500px;">'
                         + '<thead><tr>'
                         + '<th class="text-muted fw-normal ps-0" style="width:36px;">#</th>'
                         + '<th class="text-muted fw-normal">Component Item</th>'
                         + '<th class="text-muted fw-normal text-end" style="width:80px;">Qty</th>'
                         + '</tr></thead><tbody>';
                $.each(response.Components, function (i, comp) {
                    html += '<tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">'
                          + '<td class="text-muted ps-0">' + (i + 1) + '</td>'
                          + '<td><i class="bx bx-cube text-secondary me-1" style="font-size:0.85rem;"></i>' + $('<span>').text(comp.ItemName).html() + '</td>'
                          + '<td class="text-center fw-semibold">' + smartDecimal(comp.Quantity) + '</td>'
                          + '</tr>';
                });
                html += '</tbody></table></div>';
                content.html(html);
            },
            error: function () {
                bomRow.find('.combo-bom-content').html('<span class="text-danger small py-2 d-block">Failed to load components.</span>');
            }
        });
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
    var row = '<tr data-uid="' + itemUID + '">' +
                '<td>' + count + '</td>' +
                '<td>' + itemName + '</td>' +
                '<td><input type="number" class="form-control form-control-sm ComboComponentQtyInput" value="' + dispQty + '" min="0.001" step="any" style="width:90px;" /></td>' +
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
// Clear the whole combo form
// ──────────────────────────────────────────────────
function clearComboForm() {
    $('#AddEditComboForm')[0].reset();
    $('#HComboUID').val(0);
    $('#ComboTaxPercentage').val('').trigger('change');
    $('#ComboPrimaryUnit').val('').trigger('change');
    $('#ComboItemSearch').val(null).trigger('change');
    $('#ComboComponentsBody tr[data-uid]').remove();
    $('#ComboComponentEmptyRow').show();
    $('#ComboComponentsData').val('[]');
    $('.comboFormAlert').addClass('d-none');
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
            $('#ComboMRP').val(smartDecimal(d.MRP || 0));
            $('#ComboDescription').val(d.Description || '');
            if (d.TaxDetailsUID) {
                $('#ComboTaxPercentage').val(d.TaxDetailsUID).trigger('change');
            }
            if (d.PrimaryUnitUID) {
                $('#ComboPrimaryUnit').val(d.PrimaryUnitUID).trigger('change');
            }

            // Load BOM components
            if (response.Components && response.Components.length > 0) {
                $.each(response.Components, function (i, comp) {
                    if ($('#ComboItemSearch option[value="' + comp.ChildProductUID + '"]').length === 0) {
                        var option = new Option(comp.ItemName, comp.ChildProductUID, true, true);
                        $('#ComboItemSearch').append(option);
                    }
                    addComboComponentRow(comp.ChildProductUID, comp.ItemName, comp.Quantity);
                });
                updateComboComponentsData();
            }

            $('#ComboModalTitle').text('Edit Combo Item');
            $('.AddEditComboBtn').text('Update');
            $('#comboItemModal').modal('show');
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
                $('.comboFormAlert').removeClass('d-none');
                inlineMessageAlert('.comboFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#comboItemModal').modal('hide');
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
                $('.comboFormAlert').removeClass('d-none');
                inlineMessageAlert('.comboFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#comboItemModal').modal('hide');
                clearComboForm();
                if (typeof executeProdPagnFunc === 'function') {
                    executeProdPagnFunc(response, true);
                }
            }
        }
    });
}
