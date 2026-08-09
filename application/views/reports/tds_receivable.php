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
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-teal{background:#ccfbf1}.rpt-icon-teal i{color:#0d9488}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-yellow{background:#fef9c3}.rpt-icon-yellow i{color:#ca8a04}
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
.rpt-num-blue{color:#2563eb;font-weight:700}
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.reg-status{display:inline-block;padding:1px 8px;border-radius:5px;font-size:.72rem;font-weight:600}
.reg-status-issued{background:#dcfce7;color:#15803d}
.reg-status-paid{background:#dbeafe;color:#1d4ed8}
.reg-status-other{background:#f1f5f9;color:#475569}
.g1-gstin-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:600;font-family:monospace;background:#e0e7ff;color:#4338ca}
.g1-unregd-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:600;background:#f1f5f9;color:#94a3b8}
.tdr-info-bar{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:.82rem;color:#1e40af;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px}
.tdr-info-bar i{font-size:1.1rem;flex-shrink:0;margin-top:1px}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td{background:#0f172a}
    .tdr-info-bar{background:#1e3a5f;border-color:#2563eb;color:#93c5fd}
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
                    'pageTitle'       => 'TDS Receivable',
                    'pageDescription' => 'Invoices where customers may deduct TDS before payment',
                    'pageIcon'        => 'bx-file-blank',
                    'pageIconBg'      => '#dbeafe',
                    'pageIconColor'   => '#2563eb',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="tdrFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="tdrFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="tdrToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="tdrTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="tdrApplyBtn"><i class="bx bx-search me-1"></i>Apply</button>
                            </div>
                        </div>
                    </div>

                    <div class="tdr-info-bar">
                        <i class="bx bx-info-circle"></i>
                        <div>Shows sales invoices &ge; &#8377;30,000 where customers may deduct income tax (TDS) before payment. Estimated TDS shown at 2% for reference. Actual TDS is confirmed via customer's Form 26AS / TDS certificate.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-file-blank"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Qualifying Invoices</div><div class="rpt-stat-val" id="tdrStatCount">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-teal"><i class="bx bx-user-check"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Customers</div><div class="rpt-stat-val" id="tdrStatCustomers">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-rupee"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Total Invoice Value</div><div class="rpt-stat-val" id="tdrStatNet">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-yellow"><i class="bx bx-calculator"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Est. TDS @ 2%</div><div class="rpt-stat-val" id="tdrStatEst">—</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="rpt-table-wrap">
                            <table class="rpt-table">
                                <thead><tr>
                                    <th>#</th><th>Invoice No</th><th>Date</th>
                                    <th>Customer Name</th><th>GSTIN</th>
                                    <th class="rpt-col-num-hd">Taxable</th>
                                    <th class="rpt-col-num-hd">Tax</th>
                                    <th class="rpt-col-num-hd">Invoice Value</th>
                                    <th class="rpt-col-num-hd">Est. TDS @ 2%</th>
                                    <th>Status</th>
                                </tr></thead>
                                <tbody id="tdrTableBody"><tr><td colspan="10" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr></tbody>
                                <tfoot id="tdrTableFoot" class="d-none"><tr>
                                    <td colspan="5">Total</td>
                                    <td class="rpt-col-num" id="tdrFtTaxable"></td>
                                    <td class="rpt-col-num" id="tdrFtTax"></td>
                                    <td class="rpt-col-num rpt-num-blue" id="tdrFtNet"></td>
                                    <td class="rpt-col-num rpt-num-orange" id="tdrFtEst"></td>
                                    <td></td>
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
var _tdrInitFrom = '<?php echo addslashes($_from); ?>';
var _tdrInitTo   = '<?php echo addslashes($_to); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/tds_receivable.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
