<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="d-flex justify-content-center align-items-center" style="min-height:60vh;">
                        <div class="text-center">
                            <i class="bx bx-error-circle text-warning" style="font-size:4rem;"></i>
                            <h4 class="mt-3 mb-2">Module Not Configured</h4>
                            <p class="text-muted mb-4">This page does not have a module entry set up in the system.<br>Please contact your administrator to configure this module.</p>
                            <a href="<?php echo base_url('dashboard'); ?>" class="btn btn-primary">
                                <i class="bx bx-home me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('common/footer_script'); ?>
