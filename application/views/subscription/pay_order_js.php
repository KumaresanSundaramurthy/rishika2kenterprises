<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
$(function() {

    var _orderUID = <?= (int)$order->OrderUID ?>;
    var _svcUrl   = '<?= rtrim(getenv('SERVICES_BASE_URL') ?: '', '/') . '/' ?>';
    var _jwtToken = '<?= addslashes($this->pageData['JwtToken'] ?? '') ?>';

    /**
     * @returns {object}
     */
    function _authHeaders() {
        return { Authorization: 'Bearer ' + _jwtToken };
    }

    /**
     * @returns {void}
     */
    $('#btnPayNow').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        $('#payBtnText').addClass('d-none');
        $('#paySpinner').removeClass('d-none');
        ajaxLoading(0);

        $.ajax({
            url         : _svcUrl + 'api/subscription/createOrderPayment',
            method      : 'POST',
            contentType : 'application/json',
            headers     : _authHeaders(),
            data        : JSON.stringify({ order_uid: _orderUID }),
            dataType    : 'json',
            success : function(res) {
                ajaxLoading(1);
                if (res.Status !== 'OK' || !res.OrderId) {
                    _resetBtn($btn);
                    showToastNotification(res.Message || 'Could not initiate payment. Please try again.', 'error');
                    return;
                }
                _openRazorpay(res, $btn);
            },
            error: function() {
                ajaxLoading(1);
                _resetBtn($btn);
                showToastNotification('Network error. Please try again.', 'error');
            }
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
            key         : res.KeyId,
            amount      : res.Amount,
            currency    : res.Currency,
            name        : res.OrgName,
            order_id    : res.OrderId,
            prefill     : { name: res.OrgName, email: res.OrgEmail },
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
            url         : _svcUrl + 'api/subscription/confirmOrderPayment',
            method      : 'POST',
            contentType : 'application/json',
            headers     : _authHeaders(),
            data        : JSON.stringify({
                order_uid           : _orderUID,
                razorpay_order_id   : payment.razorpay_order_id,
                razorpay_payment_id : payment.razorpay_payment_id,
                razorpay_signature  : payment.razorpay_signature,
            }),
            dataType    : 'json',
            success : function(res) {
                if (res.Status !== 'OK') {
                    _resetBtn($btn);
                    showToastNotification(res.Message || 'Payment verification failed.', 'error');
                    return;
                }
                showToastNotification('Payment successful!', 'success');
                setTimeout(function() { window.location.href = '<?= base_url('subscription/dashboard') ?>'; }, 1500);
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
