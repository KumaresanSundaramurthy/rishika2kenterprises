$(document).ready(function () {
    'use strict'

    /* ----------------------------------------------------------------
       Helpers
    ---------------------------------------------------------------- */
    /** Returns current fiscal year string based on format.
     *  Indian FY starts April 1. */
    function getFiscalYear(format) {
        var now      = new Date();
        var month    = now.getMonth() + 1;  // 1-12
        var year     = now.getFullYear();
        var fyStart  = month >= 4 ? year : year - 1;
        if (format === 'LONG') {
            return fyStart + '-' + (fyStart + 1);
        }
        var s = String(fyStart  % 100).padStart(2, '0');
        var e = String((fyStart + 1) % 100).padStart(2, '0');
        return s + '-' + e;
    }

    /** Pad a number according to the NumberPadding setting. */
    function padNumber(n, padding) {
        padding = parseInt(padding, 10);
        if (padding > 1) return String(n).padStart(padding, '0');
        return String(n);
    }

    /** Build the full formatted number string for preview. */
    function buildFullPreview(name, sep, incFiscal, fiscalFmt, incShort, shortName, padding, num) {
        var parts = [];
        if (name)                       parts.push(name.toUpperCase());
        if (incShort  && shortName)     parts.push(shortName.toUpperCase());
        if (incFiscal)                  parts.push(getFiscalYear(fiscalFmt || 'SHORT'));
        parts.push(padNumber(num || 1, padding));
        return parts.join(sep);
    }

    /** Build the prefix segment shown before the running number in add.php. */
    function buildPrefixSegment(name, sep, incFiscal, fiscalFmt, incShort, shortName) {
        var parts = [];
        if (name)                       parts.push(name.toUpperCase());
        if (incShort  && shortName)     parts.push(shortName.toUpperCase());
        if (incFiscal)                  parts.push(getFiscalYear(fiscalFmt || 'SHORT'));
        return parts.length ? parts.join(sep) + sep : '';
    }

    /* ----------------------------------------------------------------
       Live preview in the Add/Edit form
    ---------------------------------------------------------------- */

    var BLOCK_COLORS = ['bg-primary', 'bg-warning text-dark', 'bg-info text-dark', 'bg-success'];

    function updateLivePreview() {
        var name      = ($('#transPrefixName').val() || '').trim().toUpperCase();
        var sep       = $('#prefixSeparator').val()  || '-';
        var incFiscal = $('#includeFiscalYear').is(':checked');
        var fiscalFmt = $('input[name="fiscalYearFormat"]:checked').val() || 'SHORT';
        var incShort  = $('#includeShortName').is(':checked');
        var shortName = ($('#companyShortName').val() || '').trim().toUpperCase();
        var padding   = parseInt($('#numberPadding').val(), 10) || 3;

        // Build parts list: [{label, colorClass}]
        var parts = [];
        if (name)                       parts.push({ label: name,                      color: BLOCK_COLORS[0] });
        if (incShort && shortName)      parts.push({ label: shortName,                 color: BLOCK_COLORS[1] });
        if (incFiscal)                  parts.push({ label: getFiscalYear(fiscalFmt),  color: BLOCK_COLORS[2] });
        parts.push({ label: padNumber(1, padding), color: BLOCK_COLORS[3] });

        // Render badge blocks separated by the chosen separator
        var html = '';
        $.each(parts, function (i, part) {
            html += '<span class="badge ' + part.color + ' px-3 py-2" style="font-size:.85rem;letter-spacing:.04em">'
                  + part.label + '</span>';
            if (i < parts.length - 1) {
                html += '<span class="fw-bold text-muted mx-1" style="font-size:1.1rem">' + sep + '</span>';
            }
        });
        $('#prefixPreviewBlocks').html(html);

        var fullStr = parts.map(function (p) { return p.label; }).join(sep);
        $('#prefixFullPreview').text(fullStr || '—');

        // Update separator hint spans in step labels
        $('.prevSepHintInline').text(sep);
        $('#prevFySepHint, #prevFySepHint2').text(sep);
    }

    /* ----------------------------------------------------------------
       List rendering
    ---------------------------------------------------------------- */
    function renderPrefixList(prefixes) {

        var tbody = $('#prefixListBody');
        tbody.empty();

        if (!prefixes || prefixes.length === 0) {
            $('#prefixEmptyState').removeClass('d-none');
            $('#prefixTableWrapper').addClass('d-none');
            return;
        }

        $('#prefixEmptyState').addClass('d-none');
        $('#prefixTableWrapper').removeClass('d-none');

        $.each(prefixes, function (_i, p) {
            var isDefault = (p.IsDefault == 1);
            var preview   = buildFullPreview(
                p.Name, p.Separator || '-',
                p.IncludeFiscalYear, p.FiscalYearFormat,
                p.IncludeShortName,  p.ShortName,
                p.NumberPadding || 1, 1
            );

            // Config badges
            var cfgBadges = '';
            if (p.IncludeFiscalYear) {
                cfgBadges += '<span class="badge bg-label-info me-1">FY '
                           + (p.FiscalYearFormat === 'LONG' ? 'Full' : 'Short') + '</span>';
            }
            if (p.IncludeShortName && p.ShortName) {
                cfgBadges += '<span class="badge bg-label-warning me-1">' + p.ShortName + '</span>';
            }
            var sepLabel = { '-':'–', '/':'/', '|':'|', '_':'_', '.':'.' }[p.Separator] || p.Separator;
            cfgBadges += '<span class="badge bg-label-secondary me-1">Sep: ' + sepLabel + '</span>';

            var padLabel = p.NumberPadding > 1 ? String(p.NumberPadding) + ' digits' : 'No pad';
            cfgBadges += '<span class="badge bg-label-secondary me-1">Pad: ' + padLabel + '</span>';

            var defaultCell = isDefault
                ? '<i class="bx bxs-star text-warning fs-5" title="Default prefix"></i>'
                : '<button type="button" class="btn btn-icon text-warning setDefaultPrefixBtn" '
                + 'data-uid="' + p.PrefixUID + '" title="Set as default"><i class="bx bx-star"></i></button>';

            var deleteBtn = isDefault ? '' :
                '<button type="button" class="btn btn-icon text-danger deletePrefixBtn" '
                + 'data-uid="' + p.PrefixUID + '" title="Delete"><i class="bx bx-trash fs-6"></i></button>';

            // Encode prefix data for edit button (avoid XSS via JSON attribute)
            var prefixJson = encodeURIComponent(JSON.stringify(p));

            var row = '<tr data-prefix-uid="' + p.PrefixUID + '">'
                + '<td><code class="fw-bold text-primary">' + preview + '</code></td>'
                + '<td>'
                +   '<span class="fw-semibold">' + p.Name + '</span>'
                +   (isDefault ? ' <span class="badge bg-success ms-1 small">Default</span>' : '')
                + '</td>'
                + '<td><div class="d-flex flex-wrap gap-1">' + cfgBadges + '</div></td>'
                + '<td class="text-center">' + defaultCell + '</td>'
                + '<td class="text-end">'
                +   '<div class="d-flex gap-2">'
                +     '<button type="button" class="btn btn-icon text-primary editPrefixBtn" '
                +       'data-prefix="' + prefixJson + '" title="Edit"><i class="bx bx-edit-alt fs-6"></i></button>'
                +     deleteBtn
                +   '</div>'
                + '</td>'
                + '</tr>';

            tbody.append(row);
        });
    }

    /* ----------------------------------------------------------------
       AJAX: Load prefix list
    ---------------------------------------------------------------- */
    function loadPrefixList() {
        $('#prefixListLoading').removeClass('d-none');
        $('#prefixListContainer').addClass('d-none');
        AjaxLoading = 0;
        $.ajax({
            url    : '/transactions/getTransactionPrefixes/',
            method : 'GET',
            success: function (resp) {
                AjaxLoading = 1;
                $('#prefixListLoading').addClass('d-none');
                $('#prefixListContainer').removeClass('d-none');
                if (!resp.Error) {
                    renderPrefixList(resp.Data);
                } else {
                    Swal.fire({ icon: 'error', title: '', text: resp.Message });
                }
            },
            error  : function () {
                AjaxLoading = 1;
                $('#prefixListLoading').addClass('d-none');
                Swal.fire({ icon: 'error', title: '', text: 'Failed to load prefixes.' });
            }
        });
    }

    /* ----------------------------------------------------------------
       Panel switching
    ---------------------------------------------------------------- */
    function showListPanel() {
        $('#prefixFormPanel').addClass('d-none');
        $('#prefixListPanel').removeClass('d-none');
        $('#prefixModalSubtitle').text('Manage how your ' + (_transTypeName || 'transaction') + ' numbers are generated');
    }

    function showFormPanel(mode, prefixData) {
        $('#prefixListPanel').addClass('d-none');
        $('#prefixFormPanel').removeClass('d-none');
        $('#prefixModalSubtitle').text(mode === 'edit' ? 'Edit prefix configuration' : 'Add a new prefix configuration');

        // Reset form
        $('#addTransPrefixForm')[0].reset();
        $('#fiscalYearOptions').addClass('d-none');
        $('#shortNameOptions').addClass('d-none');
        $('#separatorBtnGroup .sep-btn').removeClass('active');
        $('#separatorBtnGroup .sep-btn[data-sep="-"]').addClass('active');
        $('#prefixSeparator').val('-');
        $('#paddingBtnGroup .pad-btn').removeClass('active');
        $('#paddingBtnGroup .pad-btn[data-pad="3"]').addClass('active');
        $('#numberPadding').val('3');
        $('#prefixFormMode').val(mode);
        $('#prePrefixUID').val('');

        if (mode === 'edit' && prefixData) {
            $('#prePrefixUID').val(prefixData.PrefixUID);
            $('#transPrefixName').val(prefixData.Name);

            // Fiscal year
            if (parseInt(prefixData.IncludeFiscalYear, 10) === 1) {
                $('#includeFiscalYear').prop('checked', true);
                $('#fiscalYearOptions').removeClass('d-none');
                $('input[name="fiscalYearFormat"][value="' + (prefixData.FiscalYearFormat || 'SHORT') + '"]')
                    .prop('checked', true);
            }

            // Short name
            if (parseInt(prefixData.IncludeShortName, 10) === 1) {
                $('#includeShortName').prop('checked', true);
                $('#shortNameOptions').removeClass('d-none');
                $('#companyShortName').val(prefixData.ShortName || '');
            }

            // Separator
            var sep = prefixData.Separator || '-';
            $('#separatorBtnGroup .sep-btn').removeClass('active');
            $('#separatorBtnGroup .sep-btn[data-sep="' + sep + '"]').addClass('active');
            $('#prefixSeparator').val(sep);

            // Padding
            var pad = String(prefixData.NumberPadding || 3);
            $('#paddingBtnGroup .pad-btn').removeClass('active');
            $('#paddingBtnGroup .pad-btn[data-pad="' + pad + '"]').addClass('active');
            $('#numberPadding').val(pad);
        }

        updateLivePreview();
    }

    /* ----------------------------------------------------------------
       Open modal → load list
    ---------------------------------------------------------------- */

    // Grab the transaction type name from the subtitle for JS use
    var _transTypeName = '';
    $('#addTransPrefixBtn').on('click', function (e) {
        e.preventDefault();
        _transTypeName = ($('#prefixModalSubtitle').text() || '').replace('Manage how your ', '').replace(' numbers are generated', '');
        showListPanel();
        $('#transPrefixModal').modal('show');
        if($('#prefixListBody tr').length === 0) {
            loadPrefixList();
        }
    });

    /* ----------------------------------------------------------------
       List panel events
    ---------------------------------------------------------------- */

    $('#showAddPrefixFormBtn').on('click', function () {
        showFormPanel('add', null);
    });

    // Edit
    $(document).on('click', '.editPrefixBtn', function () {
        var raw  = $(this).attr('data-prefix');
        var data = JSON.parse(decodeURIComponent(raw));
        showFormPanel('edit', data);
    });

    // Delete
    $(document).on('click', '.deletePrefixBtn', function () {
        var uid = $(this).data('uid');

        Swal.fire({
            title            : 'Delete this prefix?',
            text             : 'This action cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/transactions/deleteTransactionPrefix/',
                method : 'POST',
                data   : { prePrefixUID: uid },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        loadPrefixList();
                        // Remove from main page dropdown
                        $('#transPrefixSelect option[value="' + uid + '"]').remove();
                        if ($.fn.select2) $('#transPrefixSelect').trigger('change');
                    }
                }
            });
        });
    });

    // Set default
    $(document).on('click', '.setDefaultPrefixBtn', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url    : '/transactions/setDefaultTransactionPrefix/',
            method : 'POST',
            data   : {
                prePrefixUID: uid,
                [CsrfName]: CsrfToken
            },
            success: function (resp) {
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    if (typeof resp.AllPrefixData !== "undefined" && resp.AllPrefixData !== null) {
                        renderPrefixList(resp.AllPrefixData.Data);
                        // Update main page selection + display
                        $.each(resp.AllPrefixData.Data, function (_i, p) {
                            if (p.PrefixUID === resp.PrefixUID) {
                                applyPrefixToMainPage(resp.PrefixUID);
                            }
                        });
                    } else {
                        loadPrefixList();
                    }
                }
            }
        });
    });

    /* ----------------------------------------------------------------
       Form panel events
    ---------------------------------------------------------------- */

    $('#backToListBtn').on('click', function () {
        showListPanel();
        if($('#prefixListBody tr').length === 0) {
            loadPrefixList();
        }
    });

    // Separator toggle buttons
    $('#separatorBtnGroup').on('click', '.sep-btn', function () {
        $('#separatorBtnGroup .sep-btn').removeClass('active');
        $(this).addClass('active');
        $('#prefixSeparator').val($(this).data('sep'));
        updateLivePreview();
    });

    // Padding toggle buttons
    $('#paddingBtnGroup').on('click', '.pad-btn', function () {
        $('#paddingBtnGroup .pad-btn').removeClass('active');
        $(this).addClass('active');
        $('#numberPadding').val($(this).data('pad'));
        updateLivePreview();
    });

    // Fiscal year toggle
    $('#includeFiscalYear').on('change', function () {
        $('#fiscalYearOptions').toggleClass('d-none', !$(this).is(':checked'));
        updateLivePreview();
    });

    // Short name toggle
    $('#includeShortName').on('change', function () {
        $('#shortNameOptions').toggleClass('d-none', !$(this).is(':checked'));
        updateLivePreview();
    });

    // Live-preview on any input change
    $('#transPrefixName, #companyShortName').on('input', updateLivePreview);
    $('input[name="fiscalYearFormat"]').on('change', updateLivePreview);

    /* ----------------------------------------------------------------
       Form submit (add or edit)
    ---------------------------------------------------------------- */

    $('#addTransPrefixForm').on('submit', function (e) {
        e.preventDefault();

        var mode = $('#prefixFormMode').val();
        var url  = (mode === 'edit')
            ? '/transactions/updateTransactionPrefix/'
            : '/transactions/addTransactionPrefix/';

        $.ajax({
            url    : url,
            method : 'POST',
            data   : $(this).serialize(),
            success: function (resp) {
                if (resp.Error) {
                    Swal.fire({ icon: 'error', title: '', text: resp.Message });
                    return;
                }
                showListPanel();
                loadPrefixList();

                // On Add: add option to main dropdown and select it
                if (mode === 'add' && resp.PrefixUID && resp.PrefixData) {
                    addOrUpdateMainOption(resp.PrefixUID, resp.PrefixData);
                    applyPrefixToMainPage(resp.PrefixUID);
                }
                // On Edit: update existing option data attrs
                if (mode === 'edit' && resp.PrefixData) {
                    addOrUpdateMainOption(resp.PrefixData.PrefixUID, resp.PrefixData);
                }

                Swal.fire({
                    icon             : 'success',
                    title            : 'Saved',
                    text             : resp.Message,
                    timer            : 1500,
                    showConfirmButton: false,
                });
            }
        });
    });

    /* ----------------------------------------------------------------
       Main page prefix dropdown sync
    ---------------------------------------------------------------- */

    /** Add or update a <option> in transPrefixSelect with data attrs. */
    function addOrUpdateMainOption(prefixUID, p) {
        var existing = $('#transPrefixSelect option[value="' + prefixUID + '"]');
        if (existing.length) {
            existing
                .text(p.Name)
                .attr('data-sep',           p.Separator         || '-')
                .attr('data-fiscal',        p.IncludeFiscalYear ? '1' : '0')
                .attr('data-fiscal-format', p.FiscalYearFormat  || 'SHORT')
                .attr('data-inc-short',     p.IncludeShortName  ? '1' : '0')
                .attr('data-short-name',    p.ShortName         || '')
                .attr('data-padding',       p.NumberPadding     || 1);
        } else {
            $('#transPrefixSelect').append(
                $('<option></option>')
                    .val(prefixUID)
                    .text(p.Name)
                    .attr('data-sep',           p.Separator         || '-')
                    .attr('data-fiscal',        p.IncludeFiscalYear ? '1' : '0')
                    .attr('data-fiscal-format', p.FiscalYearFormat  || 'SHORT')
                    .attr('data-inc-short',     p.IncludeShortName  ? '1' : '0')
                    .attr('data-short-name',    p.ShortName         || '')
                    .attr('data-padding',       p.NumberPadding     || 1)
            );
        }
        if ($.fn.select2) $('#transPrefixSelect').trigger('change.select2');
    }

    /** Select the given prefix in the main dropdown and update the prefix display + next number. */
    function applyPrefixToMainPage(prefixUID) {
        var sel = $('#transPrefixSelect');
        sel.val(prefixUID);
        if ($.fn.select2) sel.trigger('change');   // triggers syncPrefixDisplayFromSelect via .on('change')
    }

    /**
     * Rebuild:
     *  1. The prefix segment span (#appendPrefixVal) from the selected option's data-attrs.
     *  2. The #transNumber field → fetch next available number for this prefix.
     */
    function syncPrefixDisplayFromSelect() {
        var opt     = $('#transPrefixSelect option:selected');
        var name    = opt.text().trim();
        var sep     = opt.attr('data-sep')           || '-';
        var fiscal  = opt.attr('data-fiscal')        === '1';
        var fmtFy   = opt.attr('data-fiscal-format') || 'SHORT';
        var incShrt = opt.attr('data-inc-short')     === '1';
        var sName   = opt.attr('data-short-name')    || '';
        var padding = parseInt(opt.attr('data-padding'), 10) || 1;

        // 1. Update prefix segment span
        var seg = buildPrefixSegment(name, sep, fiscal, fmtFy, incShrt, sName);
        $('#appendPrefixVal').text(seg);

        // 2. Update the padded placeholder shown next to the number input
        //    so the user can see what format 001/00001 will look like
        var exampleNum = padNumber(1, padding);
        $('#transNumber').attr('placeholder', exampleNum);

        // 3. Set next available number from the data-next-number attribute
        //    (preloaded server-side — no AJAX needed)
        var nextNumber = parseInt(opt.attr('data-next-number'), 10) || 1;
        $('#transNumber').val(nextNumber);
    }

    // Delete: after removing from dropdown, select first remaining option and sync
    $(document).on('click', '.deletePrefixBtn', function () {
        // handled above; after the ajax call succeeds we additionally ensure the
        // main dropdown falls back to whatever is still available
    });

    // Hook into main prefix dropdown change (both native and select2)
    $('#transPrefixSelect').on('change', syncPrefixDisplayFromSelect);

    // On page load: initialise display + next number from whichever option is pre-selected
    if ($('#transPrefixSelect option:selected').length) {
        syncPrefixDisplayFromSelect();
    }

});
