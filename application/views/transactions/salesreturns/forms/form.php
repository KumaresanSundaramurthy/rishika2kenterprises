<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($SRData);
$isDraftEdit = $isEdit && ($SRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$SRData->TransUID : 0;
$formId      = 'srForm';
$formAction  = $isEdit ? 'salesreturns/updateSalesReturn' : 'salesreturns/addSalesReturn';

if ($isEdit && !function_exists('buildSRPrefixSegment')) {
    function buildSRPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) {
            $parts[] = strtoupper($cfg->ShortName);
        }
        if (!empty($cfg->IncludeFiscalYear)) {
            $m  = (int)date('m');
            $yr = (int)date('Y');
            $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}

$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$SRData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}
$editTransNumber = $isEdit ? ($isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$SRData->TransNumber) : 0;
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildSRPrefixSegment($editPrefixConfig) : '';
?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $FormAttribute = [
                        'id'           => $formId,
                        'name'         => $formId,
                        'autocomplete' => 'off',
                        'data-csrf'       => $this->security->get_csrf_token_name(),
                        'data-csrf-value' => $this->security->get_csrf_hash(),
                    ];
                    echo form_open($formAction, $FormAttribute);
                    ?>

                    <?php if ($isEdit): ?>
                    <input type="hidden" name="TransUID" value="<?php echo $transUID; ?>" />
                    <?php endif; ?>

                    <div class="card mb-3">

                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2">
                                    <?php if (!$isEdit): ?>
                                        Create Sales Return
                                    <?php else: ?>
                                        <?php echo $isDraftEdit ? '' : 'Edit'; ?> Sales Return
                                    <?php endif; ?>
                                </h5>

                                <?php if ($isEdit && !$isDraftEdit && !empty($SRData->UniqueNumber)): ?>
                                    <span class="trans-form-doc-number"><?php echo htmlspecialchars($SRData->UniqueNumber); ?></span>
                                <?php endif; ?>

                                <?php if (!$isEdit): ?>
                                    <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                            <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                                <?php try {
                                                    if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                                                    foreach ($PrefixData as $preData) {
                                                        $isSelected = (int)$preData->PrefixUID === (int)$SRData->PrefixUID ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo (int)$preData->PrefixUID; ?>"
                                                        data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>"
                                                        data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>"
                                                        data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>"
                                                        data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>"
                                                        data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>"
                                                        data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>"
                                                        data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>"
                                                        <?php echo $isSelected; ?>
                                                    ><?php echo htmlspecialchars($preData->Name); ?></option>
                                                <?php }
                                                } catch (Exception $e) { ?>
                                                    <option value="">Error loading prefixes</option>
                                                <?php } ?>
                                            </select>
                                            <?php if ($isDraftEdit): ?>
                                            <button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="input-group input-group-sm w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                            <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
                                            <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
                                                onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                                oninput="this.value=this.value.slice(0,this.maxLength)"
                                                pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
                                                <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                        </div>
                                        <?php if (!$isDraftEdit): ?>
                                        <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$SRData->PrefixUID; ?>" />
                                        <input type="hidden" name="transNumber" value="<?php echo (int)$SRData->TransNumber; ?>" />
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$isEdit): ?>
                                    <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
                                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                                    <?php if ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="/salesreturns" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <?php if (!$isEdit): ?>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" aria-label="Add new customer" style="white-space:nowrap;"><i class="bx bx-plus-circle me-1"></i>Add Customer</button>
                                        <?php endif; ?>
                                    </div>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <div id="customerAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label for="transDate" class="form-label small fw-semibold">Return Date <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly"
                                                value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>"
                                                required />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                            placeholder="Invoice No, Ref No..." maxlength="100"
                                            value="<?php echo $isEdit ? htmlspecialchars($SRData->Reference ?? '') : ''; ?>" />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Enter notes or reason for return',
                                'transShowDropzone'        => true,
                                'transNotesContent'        => $isEdit ? ($SRData->Notes ?? '') : '',
                                'transTermsContent'        => $isEdit ? ($SRData->TermsConditions ?? '') : '',
                            ]); ?>

                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/salesreturns.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<script src="/js/transactions/attachments.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;

var _isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;

<?php if ($isEdit): ?>
var _editItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float)$item->UnitPrice,
        'taxAmount'        => (float)$item->TaxAmount,
        'sellingPrice'     => (float)$item->SellingPrice,
        'purchasePrice'    => 0,
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
        'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
        'taxPercent'       => (float)$item->TaxPercentage,
        'cgstPercent'      => (float)$item->CGST,
        'sgstPercent'      => (float)$item->SGST,
        'igstPercent'      => (float)$item->IGST,
        'taxDetailsUID'    => (int)  $item->TaxDetailsUID,
        'quantity'         => (float)$item->Quantity,
        'partNumber'       => $item->PartNumber      ?? '',
        'primaryUnit'      => $item->PrimaryUnitName ?? '',
        'discount'         => (float)$item->Discount,
        'discountType'     => 'Percentage',
        'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
        'discount_amount'  => (float)$item->DiscountAmount,
        'line_total'       => (float)$item->TaxableAmount,
        'net_total'        => (float)$item->NetAmount,
    ];
}, $SRItems)); ?>;
<?php endif; ?>

$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');

    <?php if ($isEdit): ?>
    initTransAttachments(<?php echo $transUID; ?>, '/salesreturns/getAttachments');

    <?php if (!empty($SRData->PartyUID)): ?>
    $('#customerSearch').append(new Option(
        '<?php echo addslashes($SRData->PartyName ?? ''); ?>',
        <?php echo (int)$SRData->PartyUID; ?>, true, true
    )).trigger('change');
    <?php endif; ?>

    $('#extraDiscount').val('<?php echo smartDecimal($SRData->ExtraDiscount ?? 0); ?>');
    $('#extDiscountType').val('<?php echo addslashes($SRData->ExtraDiscountType ?? ''); ?>').trigger('change');
    $('#globalDiscount').val('<?php echo smartDecimal($SRData->GlobalDiscPercent ?? 0); ?>').trigger('input');

    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function' && _editItems.length > 0) {
        $(document).one('billmanager:ready', function() {
            formationTableBillItems(_editItems);
        });
    }
    <?php endif; ?>

    var $form = $('#<?php echo $formId; ?>');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (!_isEdit && action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a sales return prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid return date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            if (!_isEdit) {
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var qty  = parseFloat(item.quantity);
                    if (!qty || qty <= 0) return showFormError('Row ' + (i + 1) + ': Quantity must be greater than 0.');
                    if (parseFloat(item.unitPrice) < 0) return showFormError('Row ' + (i + 1) + ': Price cannot be negative.');
                }
            }

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var netAmount     = summary.totals    ? (summary.totals.grandTotal       || 0) : 0;
            var subTotal      = summary.items     ? (summary.items.taxableAmount     || 0) : 0;
            var discountAmt   = summary.items     ? (summary.items.discountTotal     || 0) : 0;
            var taxAmt        = summary.taxTotals ? (summary.taxTotals.totalTax      || 0) : 0;
            var cgstAmt       = summary.taxTotals ? (summary.taxTotals.cgstTotal     || 0) : 0;
            var sgstAmt       = summary.taxTotals ? (summary.taxTotals.sgstTotal     || 0) : 0;
            var igstAmt       = summary.taxTotals ? (summary.taxTotals.igstTotal     || 0) : 0;
            var addCharges    = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff      = summary.extra ? (summary.extra.roundOff || 0) : 0;
            var extraDisc     = parseFloat($('#extraDiscount').val()) || 0;

            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function(t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                customerSearch         : customerUID,
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : extraDisc,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : subTotal,
                DiscountAmount         : discountAmt,
                TaxAmount              : taxAmt,
                CgstAmount             : cgstAmt,
                SgstAmount             : sgstAmt,
                IgstAmount             : igstAmt,
                AdditionalChargesTotal : addCharges,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            }

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            if (typeof multiDropzone !== 'undefined' && multiDropzone.files.length > 0) {
                multiDropzone.files.forEach(function(f) { formData.append('AttachFiles[]', f); });
            }
            if (_isEdit) {
                formData.append('RemovedAttachIDs', JSON.stringify(typeof _removedAttachIDs !== 'undefined' ? _removedAttachIDs : []));
            }

            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : '/<?php echo $formAction; ?>',
                method      : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                cache       : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#<?php echo $formId; ?>', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : _isEdit ? 'Sales Return Updated' : 'Sales Return Saved',
                            text             : response.Message || (_isEdit ? 'Sales return updated successfully.' : 'Sales return created successfully.'),
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/salesreturns';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#<?php echo $formId; ?>', false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        $form.on('click', 'button[type="submit"][name="action"]', function() {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

});
</script>
