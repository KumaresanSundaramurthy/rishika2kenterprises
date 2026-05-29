'use strict';

/**
 * CategoryAppend — single source for item category data across all pages.
 *
 * Two public methods:
 *   CategoryAppend.filterBox(boxSel, cfg, selectedUids)
 *     — renders the full floating filter box into an empty div.
 *       If the box already has checkboxes → does nothing (DOM is the state).
 *       Chain: Upstash orgKey('categories') → AJAX fallback → error message.
 *
 *   CategoryAppend.populateSelect(selSel [, callback])
 *     — populates a <select> element with category options.
 *       If the select already has options → does nothing.
 *       Chain: same as above.
 *
 * Config object for filterBox:
 *   { checkClass, applyFn, resetFn, uid }
 */
window.CategoryAppend = (function () {

    // ── Data fetch ────────────────────────────────────────────────────────────

    function _fromUpstash(onSuccess, onFail) {
        if (!UpstashService.isEnabled()) { onFail(); return; }
        UpstashService.get(UpstashService.orgKey('categories')).then(function (map) {
            if (!map || typeof map !== 'object' || Array.isArray(map)) { onFail(); return; }
            var keys = Object.keys(map);
            if (!keys.length) { onFail(); return; }
            var catgs = keys.map(function (uid) {
                return { uid: parseInt(uid, 10), name: map[uid].Name || '' };
            }).sort(function (a, b) { return a.name.localeCompare(b.name); });
            onSuccess(catgs);
        }).catch(function () { onFail(); });
    }

    function _fromServer(onSuccess, onFail) {
        $.ajax({
            url   : '/products/getCategoryOptions/',
            method: 'POST',
            cache : false,
            data  : { [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error && resp.Options && resp.Options.length) {
                    var catgs = resp.Options.map(function (o) {
                        return { uid: o.uid, name: o.name };
                    }).sort(function (a, b) { return a.name.localeCompare(b.name); });
                    onSuccess(catgs);
                } else {
                    onFail();
                }
            },
            error: function () { onFail(); }
        });
    }

    function _load(onSuccess, onFail) {
        _fromUpstash(onSuccess, function () {
            _fromServer(onSuccess, onFail);
        });
    }

    // ── Filter box render ─────────────────────────────────────────────────────

    function _renderFilterBox($box, catgs, cfg, selectedUids) {
        var checkCls = cfg.checkClass || 'ca-check';
        var uid      = cfg.uid || ($box.attr('id') || 'ca');
        selectedUids = (selectedUids || []).map(String);

        if (!catgs || !catgs.length) {
            $box.html(
                '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">' +
                '<i class="bx bx-info-circle fs-2 mb-2"></i>' +
                '<span style="font-size:.8rem;">No categories found</span></div>'
            );
            return;
        }

        var listHtml  = '';
        var boxId     = '#' + $box.attr('id');
        catgs.forEach(function (c) {
            var chk = selectedUids.indexOf(String(c.uid)) !== -1 ? ' checked' : '';
            listHtml +=
                '<label class="catg-list-item">' +
                '<input class="form-check-input ca-check ' + checkCls + '" type="checkbox" value="' + c.uid + '"' + chk + '>' +
                '<span>' + $('<span>').text(c.name).html() + '</span>' +
                '</label>';
        });

        var allChecked = selectedUids.length > 0 && selectedUids.length === catgs.length;

        $box.html(
            '<div class="catg-filter-header">' +
                '<span class="catg-filter-title"><i class="bx bx-layer me-1"></i> Category Filter</span>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<span class="badge">' + catgs.length + '</span>' +
                    '<button type="button" class="catg-filter-close-btn ca-close-btn" data-box="' + boxId + '" title="Close">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="catg-filter-search-wrap">' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text"><i class="bx bx-search"></i></span>' +
                    '<input type="text" class="form-control ca-search" data-box="' + boxId + '" placeholder="Search categories...">' +
                '</div>' +
            '</div>' +
            '<div class="catg-select-all-wrap">' +
                '<input type="checkbox" class="form-check-input ca-sel-all" id="caSelAll_' + uid + '" ' +
                    'data-box="' + boxId + '" data-check-class="' + checkCls + '"' + (allChecked ? ' checked' : '') + '>' +
                '<label class="small fw-semibold mb-0 ca-sel-all-label" for="caSelAll_' + uid + '">' +
                    (allChecked ? 'Clear All' : 'Select All') +
                '</label>' +
            '</div>' +
            '<div class="catg-list ca-list" style="max-height:180px;">' + listHtml + '</div>' +
            '<div class="catg-filter-footer">' +
                '<button type="button" class="btn btn-primary" onclick="' + cfg.applyFn + '()"><i class="bx bx-check me-1"></i>Apply</button>' +
                '<button type="button" class="btn btn-outline-secondary" onclick="' + cfg.resetFn + '()"><i class="bx bx-reset me-1"></i>Reset</button>' +
            '</div>'
        );
    }

    // ── Public: filter box ────────────────────────────────────────────────────

    function filterBox(boxSel, cfg, selectedUids) {
        var $box = $(boxSel);

        $box.html(
            '<div class="d-flex justify-content-center align-items-center p-3">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>'
        );

        _load(
            function (catgs) { _renderFilterBox($box, catgs, cfg, selectedUids); },
            function ()      { $box.html('<div class="p-3 text-center text-danger small">Failed to load categories.</div>'); }
        );
    }

    // ── Public: populate select ───────────────────────────────────────────────

    function populateSelect(selSel, callback) {
        var $sel = $(selSel);
        if (!$sel.length) { if (typeof callback === 'function') callback(); return; }

        _load(
            function (catgs) {
                $sel.find('option:not([value=""])').remove();
                catgs.forEach(function (c) {
                    $sel.append(new Option(c.name, c.uid, false, false));
                });
                if ($sel.hasClass('select2-hidden-accessible')) $sel.trigger('change');
                if (typeof callback === 'function') callback();
            },
            function () {
                if (typeof callback === 'function') callback();
            }
        );
    }

    // ── Delegated events (work for every page automatically) ─────────────────

    $(document).on('input', '.ca-search', function () {
        var term = $(this).val().toLowerCase();
        $($(this).data('box')).find('.catg-list-item').each(function () {
            $(this).toggle($(this).find('span').text().toLowerCase().indexOf(term) !== -1);
        });
    });

    $(document).on('change', '.ca-sel-all', function () {
        var checked = $(this).is(':checked');
        var $box    = $($(this).data('box'));
        var cls     = $(this).data('check-class') || 'ca-check';
        $box.find('.' + cls).prop('checked', checked);
        $(this).siblings('.ca-sel-all-label').text(checked ? 'Clear All' : 'Select All');
    });

    $(document).on('click', '.ca-close-btn', function () {
        $($(this).data('box')).hide();
    });

    return { filterBox, populateSelect, load: _load };

}());
