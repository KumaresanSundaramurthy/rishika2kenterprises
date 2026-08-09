<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_month = $this->pageData['_gstrMonth'] ?? (int)date('n');
$_year  = $this->pageData['_gstrYear']  ?? (int)date('Y');
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-select{min-width:130px}
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-indigo{background:#e0e7ff}.rpt-icon-indigo i{color:#4338ca}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-icon-green{background:#dcfce7}.rpt-icon-green i{color:#16a34a}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px}
.rpt-tabs{display:flex;gap:2px;padding:0 16px;border-bottom:2px solid #e2e8f0;margin-bottom:0}
.rpt-tab-btn{padding:8px 16px;font-size:.82rem;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;display:flex;align-items:center;gap:6px}
.rpt-tab-btn.active{color:#4338ca;border-bottom-color:#4338ca}
.rpt-tab-badge{background:#e0e7ff;color:#4338ca;border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700}
.rpt-tab-btn.active .rpt-tab-badge{background:#4338ca;color:#fff}
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:10px 14px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:9px 14px;vertical-align:middle}
.rpt-table tfoot td{padding:9px 14px;font-weight:700;font-size:.82rem;background:#f8fafc;border-top:2px solid #e2e8f0}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.rpt-num-indigo{color:#4338ca;font-weight:700}
.rpt-num-teal{color:#0d9488;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.g1-gstin-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:600;font-family:monospace;background:#e0e7ff;color:#4338ca;letter-spacing:.03em}
.g1-unregd-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:600;background:#f1f5f9;color:#94a3b8}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td{background:#0f172a}
    .rpt-tabs{border-color:#334155}
}
:root[data-theme="dark"] .rpt-stat-card{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .rpt-table thead th{background:#0f172a;border-color:#334155}
:root[data-theme="dark"] .rpt-tabs{border-color:#334155}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'GSTR-1',
                    'pageDescription' => 'Outward Supplies — B2B, B2CS, and Credit Notes (CDNR)',
                    'pageIcon'        => 'bx-buildings',
                    'pageIconBg'      => '#e0e7ff',
                    'pageIconColor'   => '#4338ca',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- Filter bar -->
                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">Month</div>
                                <select id="g1Month" class="form-control rpt-select">
                                    <?php $months=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                    foreach($months as $i=>$mn){ $sel=($i+1)===$_month?'selected':''; echo "<option value='".($i+1)."' $sel>$mn</option>"; } ?>
                                </select>
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">Year</div>
                                <select id="g1Year" class="form-control rpt-select">
                                    <?php for($y=date('Y');$y>=2020;$y--){ $sel=$y===$_year?'selected':''; echo "<option value='$y' $sel>$y</option>"; } ?>
                                </select>
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="g1ApplyBtn"><i class="bx bx-search me-1"></i>Fetch Report</button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-indigo"><i class="bx bx-buildings"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">B2B Invoices</div><div class="rpt-stat-val" id="g1StatB2b">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-user"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">B2CS Invoices</div><div class="rpt-stat-val" id="g1StatB2cs">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Credit Notes (CDNR)</div><div class="rpt-stat-val" id="g1StatCdnr">—</div></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-rupee"></i></div>
                                <div class="rpt-stat-body"><div class="rpt-stat-label">Net Tax Liability</div><div class="rpt-stat-val" id="g1StatTax">—</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="card">
                        <div id="g1Tabs" class="rpt-tabs">
                            <button class="rpt-tab-btn active" data-tab="b2b"><i class="bx bx-buildings"></i>B2B <span class="rpt-tab-badge" id="g1TabB2bCount">0</span></button>
                            <button class="rpt-tab-btn" data-tab="b2cs"><i class="bx bx-user"></i>B2CS <span class="rpt-tab-badge" id="g1TabB2csCount">0</span></button>
                            <button class="rpt-tab-btn" data-tab="cdnr"><i class="bx bx-receipt"></i>CDNR <span class="rpt-tab-badge" id="g1TabCdnrCount">0</span></button>
                        </div>

                        <!-- B2B panel -->
                        <div id="g1PanelB2b" class="rpt-tab-panel">
                            <div class="rpt-table-wrap">
                                <table class="rpt-table">
                                    <thead><tr>
                                        <th>#</th><th>Invoice No</th><th>Date</th>
                                        <th>Recipient GSTIN</th><th>Recipient Name</th><th>Place of Supply</th>
                                        <th class="rpt-col-num-hd">Taxable</th><th class="rpt-col-num-hd">CGST</th>
                                        <th class="rpt-col-num-hd">SGST</th><th class="rpt-col-num-hd">IGST</th>
                                        <th class="rpt-col-num-hd">Total Tax</th><th class="rpt-col-num-hd">Invoice Value</th>
                                    </tr></thead>
                                    <tbody id="g1B2bBody"><tr><td colspan="12" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr></tbody>
                                    <tfoot id="g1B2bFoot" class="d-none"><tr>
                                        <td colspan="6">Total</td>
                                        <td class="rpt-col-num" id="g1B2bFtTaxable"></td><td class="rpt-col-num" id="g1B2bFtCgst"></td>
                                        <td class="rpt-col-num" id="g1B2bFtSgst"></td><td class="rpt-col-num" id="g1B2bFtIgst"></td>
                                        <td class="rpt-col-num rpt-num-indigo" id="g1B2bFtTax"></td><td class="rpt-col-num rpt-num-teal" id="g1B2bFtNet"></td>
                                    </tr></tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- B2CS panel -->
                        <div id="g1PanelB2cs" class="rpt-tab-panel d-none">
                            <div class="rpt-table-wrap">
                                <table class="rpt-table">
                                    <thead><tr>
                                        <th>#</th><th>Invoice No</th><th>Date</th>
                                        <th>Customer Name</th><th>Place of Supply</th>
                                        <th class="rpt-col-num-hd">Taxable</th><th class="rpt-col-num-hd">CGST</th>
                                        <th class="rpt-col-num-hd">SGST</th><th class="rpt-col-num-hd">IGST</th>
                                        <th class="rpt-col-num-hd">Total Tax</th><th class="rpt-col-num-hd">Invoice Value</th>
                                    </tr></thead>
                                    <tbody id="g1B2csBody"><tr><td colspan="11" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr></tbody>
                                    <tfoot id="g1B2csFoot" class="d-none"><tr>
                                        <td colspan="5">Total</td>
                                        <td class="rpt-col-num" id="g1B2csFtTaxable"></td><td class="rpt-col-num" id="g1B2csFtCgst"></td>
                                        <td class="rpt-col-num" id="g1B2csFtSgst"></td><td class="rpt-col-num" id="g1B2csFtIgst"></td>
                                        <td class="rpt-col-num rpt-num-indigo" id="g1B2csFtTax"></td><td class="rpt-col-num rpt-num-teal" id="g1B2csFtNet"></td>
                                    </tr></tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- CDNR panel -->
                        <div id="g1PanelCdnr" class="rpt-tab-panel d-none">
                            <div class="rpt-table-wrap">
                                <table class="rpt-table">
                                    <thead><tr>
                                        <th>#</th><th>Credit Note No</th><th>Date</th>
                                        <th>Recipient GSTIN</th><th>Recipient Name</th>
                                        <th class="rpt-col-num-hd">Taxable</th><th class="rpt-col-num-hd">CGST</th>
                                        <th class="rpt-col-num-hd">SGST</th><th class="rpt-col-num-hd">IGST</th>
                                        <th class="rpt-col-num-hd">Total Tax</th><th class="rpt-col-num-hd">Note Value</th>
                                    </tr></thead>
                                    <tbody id="g1CdnrBody"><tr><td colspan="11" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr></tbody>
                                    <tfoot id="g1CdnrFoot" class="d-none"><tr>
                                        <td colspan="5">Total</td>
                                        <td class="rpt-col-num" id="g1CdnrFtTaxable"></td><td class="rpt-col-num" id="g1CdnrFtCgst"></td>
                                        <td class="rpt-col-num" id="g1CdnrFtSgst"></td><td class="rpt-col-num" id="g1CdnrFtIgst"></td>
                                        <td class="rpt-col-num rpt-num-indigo" id="g1CdnrFtTax"></td><td class="rpt-col-num rpt-num-teal" id="g1CdnrFtNet"></td>
                                    </tr></tfoot>
                                </table>
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
var _g1InitMonth = <?php echo (int)$_month; ?>;
var _g1InitYear  = <?php echo (int)$_year; ?>;
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/gstr1.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
