<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-empty-lg{display:flex;flex-direction:column;align-items:center;padding:60px 30px;color:#94a3b8;text-align:center}
.rpt-empty-icon-purple{font-size:3rem;margin-bottom:14px;color:#7c3aed;opacity:.7}
.rpt-empty-title{font-size:1.1rem;font-weight:700;color:#374151;margin-bottom:8px}
.rpt-empty-desc{font-size:.85rem;color:#64748b;max-width:520px;line-height:1.6;margin-bottom:20px}
.rpt-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;max-width:600px;width:100%;text-align:left}
.rpt-info-item{display:flex;align-items:flex-start;gap:8px;font-size:.82rem;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px}
.rpt-info-item i{font-size:1rem;flex-shrink:0;margin-top:1px}
.rpt-info-item i.bx-check-circle{color:#7c3aed}
.rpt-info-item i.bx-info-circle{color:#2563eb}
@media(prefers-color-scheme:dark){
    .rpt-empty-title{color:#f1f5f9}
    .rpt-empty-desc{color:#94a3b8}
    .rpt-info-item{background:#1e293b;border-color:#334155;color:#94a3b8}
}
:root[data-theme="dark"] .rpt-empty-title{color:#f1f5f9}
:root[data-theme="dark"] .rpt-info-item{background:#1e293b;border-color:#334155;color:#94a3b8}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'TCS Payable',
                    'pageDescription' => 'Tax Collected at Source — Payable to Government (Section 206C)',
                    'pageIcon'        => 'bx-building-house',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">
                    <div class="card">
                        <div class="rpt-empty-lg">
                            <i class="bx bx-building-house rpt-empty-icon-purple"></i>
                            <div class="rpt-empty-title">TCS Payable — Not Yet Configured</div>
                            <div class="rpt-empty-desc">
                                TCS Payable represents tax you have collected from buyers that must be remitted to the
                                government under Section 206C. This requires TCS collection to be enabled and mapped
                                against your invoice transactions.
                            </div>
                            <div class="rpt-info-grid">
                                <div class="rpt-info-item"><i class="bx bx-check-circle"></i><span>TCS collected must be remitted by 7th of the following month</span></div>
                                <div class="rpt-info-item"><i class="bx bx-check-circle"></i><span>Filed quarterly in Form 27EQ (TCS return)</span></div>
                                <div class="rpt-info-item"><i class="bx bx-check-circle"></i><span>TAN (Tax Deduction &amp; Collection Account No.) is mandatory</span></div>
                                <div class="rpt-info-item"><i class="bx bx-info-circle"></i><span>Contact support to enable TCS remittance tracking</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<script>
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<?php $this->load->view('common/footer_desc'); ?>
