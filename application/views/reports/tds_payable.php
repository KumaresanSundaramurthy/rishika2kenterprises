<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_from = $this->pageData['_from'] ?? date('Y-m-01');
$_to   = $this->pageData['_to']   ?? date('Y-m-d');
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-filter-date{width:150px}
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-teal{background:#ccfbf1}.rpt-icon-teal i{color:#0d9488}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px}
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:10px 14px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:9px 14px;vertical-align:middle}
.rpt-table tfoot td{padding:9px 14px;font-weight:700;font-size:.82rem;background:#f8fafc;border-top:2px solid #e2e8f0}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.rpt-num-red{color:#dc2626;font-weight:700}
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.reg-status{display:inline-block;padding:1px 8px;border-radius:5px;font-size:.72rem;font-weight:600}
.reg-status-issued{background:#dcfce7;color:#15803d}
.reg-status-other{background:#f1f5f9;color:#475569}
.exr-paid-chip{display:inline-block;padding:1px 8px;border-radius:5px;font-size:.72rem;font-weight:600;background:#dcfce7;color:#15803d}
.exr-unpaid-chip{display:inline-block;padding:1px 8px;border-radius:5px;font-size:.72rem;font-weight:600;background:#fef9c3;color:#854d0e}
.g7-section-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:700;font-family:monospace;background:#fee2e2;color:#b91c1c}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td{background:#0f172a}
}
:root[data-theme="dark"] .rpt-stat-card{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .rpt-table thead th{background:#0f172a;border-color:#334155}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'TDS Payable',
                    'pageDescription' => 'TDS Deducted on Vendor Payments — Payable to Government',
                    'pageIcon'        => 'bx-minus-circle',
                    'pageIconBg'      => '#ffedd5',
                    'pageIconColor'   => '#ea580c',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="tdpFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="tdpFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="tdpToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="tdpTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="tdpApplyBtn"><i class="bx bx-search me-1"></i>Apply</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">TDS Entries</div><div class="rpt-stat-val" id="tdpStatCount">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-teal"><i class="bx bx-store"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Vendors</div><div class="rpt-stat-val" id="tdpStatVendors">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-rupee"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Total Base Amount</div><div class="rpt-stat-val" id="tdpStatBase">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-minus-circle"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Total TDS Payable</div><div class="rpt-stat-val" id="tdpStatTds">—</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="rpt-table-wrap">
                            <table class="rpt-table">
                                <thead><tr>
                                    <th>#</th><th>Expense No</th><th>Date</th>
                                    <th>Vendor Name</th><th>Vendor GSTIN</th>
                                    <th>TDS Section</th><th>Section Desc</th>
                                    <th class="rpt-col-num-hd">Base Amt</th>
                                    <th class="rpt-col-num-hd">TDS %</th>
                                    <th class="rpt-col-num-hd">TDS Payable</th>
                                    <th class="rpt-col-num-hd">Net Paid</th>
                                    <th>Payment</th><th>Status</th>
                                </tr></thead>
                                <tbody id="tdpTableBody"><tr><td colspan="13" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr></tbody>
                                <tfoot id="tdpTableFoot" class="d-none"><tr>
                                    <td colspan="7">Total</td>
                                    <td class="rpt-col-num" id="tdpFtBase"></td>
                                    <td></td>
                                    <td class="rpt-col-num rpt-num-red" id="tdpFtTds"></td>
                                    <td class="rpt-col-num rpt-num-orange" id="tdpFtNet"></td>
                                    <td colspan="2"></td>
                                </tr></tfoot>
                            </table>
                        </div>
                    </div>

                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<script>
var _tdpInitFrom = '<?php echo addslashes($_from); ?>';
var _tdpInitTo   = '<?php echo addslashes($_to); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/tds_payable.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
