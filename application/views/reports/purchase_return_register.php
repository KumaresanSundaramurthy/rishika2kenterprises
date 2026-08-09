<?php defined('BASEPATH') or exit('No direct script access allowed');

$_from    = $this->pageData['_prrInitFrom'] ?? date('Y-01-01');
$_to      = $this->pageData['_prrInitTo']   ?? date('Y-m-d');
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
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-yellow{background:#fef9c3}.rpt-icon-yellow i{color:#ca8a04}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
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
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-num-yellow{color:#ca8a04;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-orange{background:#ffedd5;color:#9a3412}
.rpt-footer-chip-yellow{background:#fef9c3;color:#854d0e}
.reg-status{display:inline-block;padding:1px 8px;border-radius:5px;font-size:.72rem;font-weight:600}
.reg-status-issued{background:#dcfce7;color:#15803d}
.reg-status-paid{background:#dbeafe;color:#1d4ed8}
.reg-status-other{background:#f1f5f9;color:#475569}
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
                    'pageTitle'       => 'Purchase Return Register',
                    'pageDescription' => 'Debit note-wise purchase returns in a date range',
                    'pageIcon'        => 'bx-revision',
                    'pageIconBg'      => '#ffedd5',
                    'pageIconColor'   => '#ea580c',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="prrFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="prrFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="prrToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="prrTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="prrApplyBtn"><i class="bx bx-search me-1"></i>Apply</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Debit Notes</div>
                                    <div class="rpt-stat-val" id="prrStatCount">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-money-withdraw"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Taxable</div>
                                    <div class="rpt-stat-val" id="prrStatTaxable">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-yellow"><i class="bx bx-transfer"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Tax</div>
                                    <div class="rpt-stat-val" id="prrStatTax">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-money"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Return Value</div>
                                    <div class="rpt-stat-val" id="prrStatNet">—</div>
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
                                        <th>Debit Note No</th>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th class="rpt-col-num-hd">Taxable</th>
                                        <th class="rpt-col-num-hd">CGST</th>
                                        <th class="rpt-col-num-hd">SGST</th>
                                        <th class="rpt-col-num-hd">IGST</th>
                                        <th class="rpt-col-num-hd">Total Tax</th>
                                        <th class="rpt-col-num-hd">Net Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="prrTableBody">
                                    <tr><td colspan="11" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="prrTableFoot" class="d-none">
                                    <tr>
                                        <td colspan="4">Total</td>
                                        <td class="rpt-col-num" id="prrFtTaxable"></td>
                                        <td class="rpt-col-num" id="prrFtCgst"></td>
                                        <td class="rpt-col-num" id="prrFtSgst"></td>
                                        <td class="rpt-col-num" id="prrFtIgst"></td>
                                        <td class="rpt-col-num rpt-num-yellow" id="prrFtTax"></td>
                                        <td class="rpt-col-num rpt-num-orange" id="prrFtNet"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="rpt-tbl-footer d-none" id="prrTblFooter">
                            <div class="rpt-footer-left"><span id="prrRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rpt-footer-chip rpt-footer-chip-yellow"><i class="bx bx-transfer"></i>Tax: <span id="prrFooterTax"></span></span>
                                <span class="rpt-footer-chip rpt-footer-chip-orange"><i class="bx bx-money"></i>Return Value: <span id="prrFooterNet"></span></span>
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
var _prrInitFrom = '<?php echo addslashes($_from); ?>';
var _prrInitTo   = '<?php echo addslashes($_to); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/purchase_return_register.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
