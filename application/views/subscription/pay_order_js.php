<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
$(function() {

    var _orderUID = <?= (int)$order->OrderUID ?>;

    /**
     * @returns {void}
     */
    $('#btnPayNow').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        $('#payBtnText').addClass('d-none');
        $('#paySpinner').removeClass('d-none');
        ajaxLoading(0);

        $.ajax({
            url     : '<?= base_url('subscription/createOrderPayment') ?>',
            method  : 'POST',
            data    : { order_uid: _orderUID },
            dataType: 'json',
            success : function(res) {
                if (res.Error) {
                    _resetBtn($btn);
                    showToastNotification(res.Message || 'Could not initiate payment. Please try again.', 'error');
                    return;
                }
                _openRazorpay(res, $btn);
            },
            error: function() {
                _resetBtn($btn);
                showToastNotification('Network error. Please try again.', 'error');
            },
            complete: function() { ajaxLoading(1); }
        });
    });

    /**
     * Open the Razorpay checkout modal.
     * @param {object} res  Response from createOrderPayment
     * @param {jQuery} $btn
     * @returns {void}
     */
    function _openRazorpay(res, $btn) {
        var options = {
            key         : res.key_id,
            amount      : res.amount,
            currency    : res.currency,
            name        : res.name,
            description : res.description,
            order_id    : res.order_id,
            prefill     : res.prefill,
            theme       : { color: '#696cff' },
            handler: function(payment) {
                _verifyPayment(payment, $btn);
            },
            modal: {
                ondismiss: function() { _resetBtn($btn); }
            }
        };
        new Razorpay(options).open();
    }

    /**
     * POST payment details to confirm and mark the order paid.
     * @param {object} payment  Razorpay success payload
     * @param {jQuery} $btn
     * @returns {void}
     */
    function _verifyPayment(payment, $btn) {
        ajaxLoading(0);
        $.ajax({
            url     : '<?= base_url('subscription/confirmOrderPayment') ?>',
            method  : 'POST',
            data    : {
                order_uid           : _orderUID,
                razorpay_order_id   : payment.razorpay_order_id,
                razorpay_payment_id : payment.razorpay_payment_id,
                razorpay_signature  : payment.razorpay_signature,
            },
            dataType: 'json',
            success : function(res) {
                if (res.Error) {
                    _resetBtn($btn);
                    showToastNotification(res.Message || 'Payment verification failed.', 'error');
                    return;
                }
                showToastNotification(res.Message || 'Payment successful!', 'success');
                setTimeout(function() { window.location.href = res.Redirect; }, 1500);
            },
            error: function() {
                _resetBtn($btn);
                showToastNotification('Network error during verification. Please contact support.', 'error');
            },
            complete: function() { ajaxLoading(1); }
        });
    }

    /**
     * Re-enable the Pay button after an error or modal dismiss.
     * @param {jQuery} $btn
     * @returns {void}
     */
    function _resetBtn($btn) {
        $btn.prop('disabled', false);
        $('#payBtnText').removeClass('d-none');
        $('#paySpinner').addClass('d-none');
    }

});
</script>
