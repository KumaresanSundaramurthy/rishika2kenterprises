<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Additional Charges',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y pt-2">

                    <div class="card">

                        <!-- Info bar -->
                        <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;">
                                <i class="bx bx-info-circle"></i>
                                System charges are always present and cannot be deleted
                            </span>
                            <span class="badge bg-label-primary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;" id="acCountBadge">
                                <?php echo (int)($ChargeCount ?? 0); ?> / <?php echo (int)($ChargeLimit ?? 5); ?> charges used
                            </span>
                            <div class="ms-auto">
                                <button class="btn btn-primary btn-sm px-3" id="btnAddAdditionalCharge"
                                    <?php if ((int)($ChargeCount ?? 0) >= (int)($ChargeLimit ?? 5)): ?>disabled title="Charge limit reached"<?php endif; ?>>
                                    <i class="bx bx-plus me-1"></i>Add Charge
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive text-nowrap tablecard">
                            <table class="table trans-table MainviewTable" id="AdditionalChargesTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="text-center" style="width:50px;">S.No</th>
                                        <th style="min-width:180px;">Display Name</th>
                                        <th style="min-width:140px;">Default Tax</th>
                                        <th style="min-width:180px;white-space:normal;">Description</th>
                                        <th class="text-center" style="width:100px;">Show By Default</th>
                                        <th class="text-center" style="width:80px;">Sort Order</th>
                                        <th class="text-center" style="width:90px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="AdditionalChargesBody" class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>

            <?php $this->load->view('common/partials/additional_charge_form_modal'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div><!-- /layout-page -->

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script>
var CsrfName      = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken     = '<?php echo $this->security->get_csrf_hash(); ?>';
var acTaxOptions  = <?php echo json_encode(array_values($TaxList ?? [])); ?>;
var acChargeLimit    = <?php echo (int)($ChargeLimit    ?? 5); ?>;
var acChargeCount    = <?php echo (int)($ChargeCount    ?? 0); ?>;
var acNextSortOrder  = <?php echo (int)($NextSortOrder  ?? 3); ?>;
</script>
<script src="/js/common/additional_charge_form.js"></script>
<script src="/js/settings/additional_charges.js"></script>
