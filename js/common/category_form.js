/**
 * CategoryForm — shared modal for adding a category from any page.
 *
 * Usage:
 *   CategoryForm.open({ prefillName: 'Electronics', onSaveSuccess: fn });
 *
 * onSaveSuccess(response) fires after a successful save.
 *   response.InsertId     — new CategoryUID
 *   response.CategoryName — name that was saved
 *
 * Transaction pages: auto-select the new category in #prodCategory Select2.
 * Products page:     existing #categoryModal flow is unchanged.
 */
(function (window, $) {
    'use strict';

    var _onSaveSuccess = null;

    window.CategoryForm = { open: openCategoryModal };

    // ── Open ─────────────────────────────────────────────────────────────────
    function openCategoryModal(opts) {
        opts           = opts || {};
        _onSaveSuccess = opts.onSaveSuccess || null;

        _resetForm();

        if (opts.prefillName) {
            var val = opts.prefillName;
            $('#CF_CategoryName').val(val);
        }

        $('#CategoryFormModal').modal('show');
        setTimeout(function () { $('#CF_CategoryName').focus(); }, 300);
    }

    // ── Reset ─────────────────────────────────────────────────────────────────
    function _resetForm() {
        var $form = $('#CFCategoryForm');
        if ($form.length) $form[0].reset();
        $('#CF_CategoryUID').val('0');
    }

    // ── Save button ───────────────────────────────────────────────────────────
    $(document).on('click', '#CategoryFormSaveBtn', function () {
        $('#CFCategoryForm').submit();
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    $(document).on('submit', '#CFCategoryForm', function (e) {
        e.preventDefault();

        var name = $.trim($('#CF_CategoryName').val());
        if (!name) {
            showAlertMessageSwal('error', '', 'Category name is required.');
            return;
        }

        var formData = new FormData(this);
        var $btn = $('#CategoryFormSaveBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url         : '/products/addCategoryDetails',
            method      : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (response.Error) {
                    showAlertMessageSwal('error', '', response.Message);
                    return;
                }
                showToastNotification(response.Message, 'success');
                $('#CategoryFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') {
                    _onSaveSuccess(response);
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showAlertMessageSwal('error', '', 'Failed to save category.');
            }
        });
    });

})(window, jQuery);
