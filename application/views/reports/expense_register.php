<?php defined('BASEPATH') or exit('No direct script access allowed');

$_from    = $this->pageData['_exrInitFrom'] ?? date('Y-01-01');
$_to      = $this->pageData['_exrInitTo']   ?? date('Y-m-d');
$_listFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-filter-date{width:150px}
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-icon-teal{background:#ccfbf1}.rpt-icon-teal i{color:#0d9488}
.rpt-icon-yellow{background:#fef9c3}.rpt-icon-yellow i{color:#ca8a04}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:10px 14px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:9px 14px;vertical-align:middle}
.rpt-table tfoot td{padding:9px 14px;font-weight:700;font-size:.82rem;background:#f8fafc;border-top:2px solid #e2e8f0}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.rpt-num-purple{color:#7c3aed;font-weight:700}
.rpt-num-yellow{color:#ca8a04;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-purple{background:#ede9fe;color:#5b21b6}
.rpt-footer-chip-yellow{background:#fef9c3;color:#854d0e}
.exr-cat-chip{display:inline-block;padding:1px 7px;border-radius:5px;font-size:.72rem;font-weight:600;background:#ede9fe;color:#5b21b6;white-space:nowrap}
.exr-paid-chip{display:inline-block;padding:1px 7px;border-radius:5px;font-size:.72rem;font-weight:600;background:#dcfce7;color:#15803d}
.exr-unpaid-chip{display:inline-block;padding:1px 7px;border-radius:5px;font-size:.72rem;font-weight:600;background:#fee2e2;color:#991b1b}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar,.rpt-tbl-footer{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td,.rpt-tbl-footer{background:#0f172a}
}
:root[data-theme="dark"] .rpt-stat-card{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .rpt-table thead th{background:#0f172a;border-color:#334155}
:root[data-theme="dark"] .rpt-tbl-footer{background:#0f172a;border-color:#334155}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Expense Register',
                    'pageDescription' => 'All expenses recorded in a date range',
                    'pageIcon'        => 'bx-wallet-alt',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="exrFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="exrFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="exrToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="exrTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="exrApplyBtn"><i class="bx bx-search me-1"></i>Apply</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Expenses</div>
                                    <div class="rpt-stat-val" id="exrStatCount">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-teal"><i class="bx bx-category"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Categories</div>
                                    <div class="rpt-stat-val" id="exrStatCats">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-yellow"><i class="bx bx-transfer"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Tax</div>
                                    <div class="rpt-stat-val" id="exrStatTax">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-money"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Net Amount</div>
                                    <div class="rpt-stat-val" id="exrStatNet">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="rpt-table-wrap">
                            <table class="rpt-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Expense No</th>
                                        <th>Date</th>
                                        <th>Vendor</th>
                                        <th>Category</th>
                                        <th class="rpt-col-num-hd">Amount</th>
                                        <th class="rpt-col-num-hd">Tax</th>
                                        <th class="rpt-col-num-hd">Net Amount</th>
                                        <th>Paid</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="exrTableBody">
                                    <tr><td colspan="10" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="exrTableFoot" class="d-none">
                                    <tr>
                                        <td colspan="5">Total</td>
                                        <td class="rpt-col-num" id="exrFtAmount"></td>
                                        <td class="rpt-col-num rpt-num-yellow" id="exrFtTax"></td>
                                        <td class="rpt-col-num rpt-num-purple" id="exrFtNet"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="rpt-tbl-footer d-none" id="exrTblFooter">
                            <div class="rpt-footer-left"><span id="exrRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rpt-footer-chip rpt-footer-chip-yellow"><i class="bx bx-transfer"></i>Tax: <span id="exrFooterTax"></span></span>
                                <span class="rpt-footer-chip rpt-footer-chip-purple"><i class="bx bx-money"></i>Net: <span id="exrFooterNet"></span></span>
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
var _exrInitFrom = '<?php echo addslashes($_from); ?>';
var _exrInitTo   = '<?php echo addslashes($_to); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/expense_register.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
