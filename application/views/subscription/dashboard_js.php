<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
$(function() {

    /* ── Change plan: auto-fill amount when plan selected ── */
    $('#selPlanChange').on('change', function() {
        $('#inpPlanAmount').val($(this).find(':selected').data('price') || 0);
    });

    /* ── Show/hide payment mode based on is_paid ── */
    $('#chkIsPaid').on('change', function() {
        $('#payModeWrap').toggle($(this).is(':checked'));
    });

    /* ── Confirm plan change ── */
    $('#btnConfirmPlanChange').on('click', function() {
        var $form = $('#frmChangePlan');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var $btn = $(this).prop('disabled', true);
        $('#spinPlanChange').removeClass('d-none');
        ajaxLoading(0);
        $.ajax({
            url: '<?= base_url('subscription/changePlan') ?>',
            type: 'POST',
            data: new FormData($form[0]),
            contentType: false,
            processData: false,
            success: function(res) {
                $('#spinPlanChange').addClass('d-none');
                $btn.prop('disabled', false);
                if (res.Status === 'OK') {
                    bootstrap.Modal.getInstance(document.getElementById('changePlanModal')).hide();
                    showToastNotification(res.Message, 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToastNotification(res.Message || 'Failed to change plan.', 'error');
                }
            },
            error: function() {
                $('#spinPlanChange').addClass('d-none');
                $btn.prop('disabled', false);
                showToastNotification('Network error. Please try again.', 'error');
            },
            complete: function() { ajaxLoading(1); }
        });
    });

    /* ── Renew current plan ── */
    $('#btnRenewPlan').on('click', function() {
        var $btn = $(this);
        Swal.fire({
            title: 'Renew Plan',
            text: 'Renew the current plan now?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            confirmButtonText: 'Yes, Renew',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true);
            ajaxLoading(0);
            $.ajax({
                url: '<?= base_url('subscription/renewPlan') ?>',
                type: 'POST',
                data: new FormData(),
                contentType: false,
                processData: false,
                success: function(res) {
                    $btn.prop('disabled', false);
                    if (res.Status === 'OK') {
                        showToastNotification(res.Message, 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showToastNotification(res.Message || 'Renewal failed.', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    showToastNotification('Network error.', 'error');
                },
                complete: function() { ajaxLoading(1); }
            });
        });
    });


});
</script>
