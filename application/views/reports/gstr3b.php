<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_month = $this->pageData['_gstrMonth'] ?? (int)date('n');
$_year  = $this->pageData['_gstrYear']  ?? (int)date('Y');
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-select{min-width:130px}
/* GSTR-3B form-style layout */
.g3b-section{border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:0}
.g3b-section-head{display:flex;align-items:center;gap:12px;padding:12px 16px;font-weight:700;font-size:.88rem}
.g3b-head-indigo{background:#eef2ff;color:#4338ca;border-bottom:1px solid #c7d2fe}
.g3b-head-green{background:#f0fdf4;color:#16a34a;border-bottom:1px solid #bbf7d0}
.g3b-head-red{background:#fef2f2;color:#dc2626;border-bottom:1px solid #fecaca}
.g3b-section-num{width:32px;height:32px;border-radius:8px;background:rgba(0,0,0,.08);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;flex-shrink:0}
.g3b-table{width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:0}
.g3b-table td{padding:9px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.g3b-table tr:last-child td{border-bottom:none}
.g3b-count{width:60px;text-align:right;font-variant-numeric:tabular-nums;color:#64748b}
.g3b-amt{text-align:right;font-variant-numeric:tabular-nums}
.g3b-tax{text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#4338ca}
.g3b-row-light td{background:#fafafa}
.g3b-row-return td{background:#fff5f5;color:#dc2626}
.g3b-row-net td{background:#f0f9ff;border-top:2px solid #bae6fd!important}
.g3b-liability-box{border:1px solid #e2e8f0;border-radius:8px;padding:14px;text-align:center}
.g3b-liability-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px}
.g3b-liability-value{font-size:1rem;font-weight:700;color:#dc2626}
.g3b-liability-total{background:#fef2f2;border-color:#fecaca}
.g3b-liability-total .g3b-liability-value{font-size:1.1rem}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar{border-color:#334155}
    .g3b-section{border-color:#334155}
    .g3b-head-indigo{background:#1e1b4b;border-color:#4338ca}
    .g3b-head-green{background:#052e16;border-color:#16a34a}
    .g3b-head-red{background:#450a0a;border-color:#dc2626}
    .g3b-table td{border-color:#1e293b}
    .g3b-row-light td{background:#0f172a}
    .g3b-liability-box{border-color:#334155}
}
:root[data-theme="dark"] .g3b-section{border-color:#334155}
:root[data-theme="dark"] .g3b-head-indigo{background:#1e1b4b;border-color:#4338ca}
:root[data-theme="dark"] .g3b-table td{border-color:#1e293b}
:root[data-theme="light"] .g3b-section{border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'GSTR-3B',
                    'pageDescription' => 'Monthly Consolidated Tax Return — Outward Supplies and ITC Summary',
                    'pageIcon'        => 'bx-spreadsheet',
                    'pageIconBg'      => '#eef2ff',
                    'pageIconColor'   => '#4338ca',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">Month</div>
                                <select id="g3Month" class="form-control rpt-select">
                                    <?php $months=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                    foreach($months as $i=>$mn){ $sel=($i+1)===$_month?'selected':''; echo "<option value='".($i+1)."' $sel>$mn</option>"; } ?>
                                </select>
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">Year</div>
                                <select id="g3Year" class="form-control rpt-select">
                                    <?php for($y=date('Y');$y>=2020;$y--){ $sel=$y===$_year?'selected':''; echo "<option value='$y' $sel>$y</option>"; } ?>
                                </select>
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="g3ApplyBtn"><i class="bx bx-search me-1"></i>Fetch Report</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- 3.1 Outward Supplies -->
                        <div class="col-12 col-lg-6">
                            <div class="g3b-section card">
                                <div class="g3b-section-head g3b-head-indigo">
                                    <span class="g3b-section-num">3.1</span>Outward Taxable Supplies
                                </div>
                                <table class="g3b-table">
                                    <tbody>
                                        <tr><td>Sales Invoices</td><td class="g3b-count" id="g3SalesCount">—</td><td class="g3b-amt" id="g3SalesTaxable">—</td><td class="g3b-tax" id="g3SalesTax">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ CGST</td><td></td><td></td><td class="g3b-tax" id="g3SalesCgst">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ SGST</td><td></td><td></td><td class="g3b-tax" id="g3SalesSgst">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ IGST</td><td></td><td></td><td class="g3b-tax" id="g3SalesIgst">—</td></tr>
                                        <tr class="g3b-row-return"><td>Less: Sales Returns (Credit Notes)</td><td class="g3b-count" id="g3ReturnCount">—</td><td class="g3b-amt" id="g3ReturnTaxable">—</td><td class="g3b-tax" id="g3ReturnTax">—</td></tr>
                                        <tr class="g3b-row-net"><td><strong>Net Outward (3.1)</strong></td><td></td><td class="g3b-amt"><strong id="g3NetTaxable">—</strong></td><td class="g3b-tax"><strong id="g3NetTax">—</strong></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- 4. ITC -->
                        <div class="col-12 col-lg-6">
                            <div class="g3b-section card">
                                <div class="g3b-section-head g3b-head-green">
                                    <span class="g3b-section-num">4</span>Eligible ITC Available
                                </div>
                                <table class="g3b-table">
                                    <tbody>
                                        <tr><td>Purchase Bills (GSTIN Suppliers)</td><td class="g3b-count" id="g3PurchaseCount">—</td><td class="g3b-amt" id="g3PurchaseTaxable">—</td><td class="g3b-tax" id="g3PurchaseTax">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ CGST ITC</td><td></td><td></td><td class="g3b-tax" id="g3PurchaseCgst">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ SGST ITC</td><td></td><td></td><td class="g3b-tax" id="g3PurchaseSgst">—</td></tr>
                                        <tr class="g3b-row-light"><td class="ps-4 text-muted" style="font-size:.78rem">↳ IGST ITC</td><td></td><td></td><td class="g3b-tax" id="g3PurchaseIgst">—</td></tr>
                                        <tr class="g3b-row-return"><td>Less: Purchase Returns (Debit Notes)</td><td></td><td></td><td class="g3b-tax" id="g3PurchaseReturnTax">—</td></tr>
                                        <tr class="g3b-row-net"><td><strong>Net ITC (4)</strong></td><td></td><td class="g3b-amt"><strong id="g3ItcTaxable">—</strong></td><td class="g3b-tax"><strong id="g3ItcNet">—</strong></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- 6. Net Tax Liability -->
                        <div class="col-12">
                            <div class="g3b-section card">
                                <div class="g3b-section-head g3b-head-red">
                                    <span class="g3b-section-num">6</span>Net Tax Payable (3.1 Tax − 4 ITC)
                                </div>
                                <div class="row g-3 p-3">
                                    <div class="col-6 col-md-3"><div class="g3b-liability-box"><div class="g3b-liability-label">CGST Payable</div><div class="g3b-liability-value" id="g3LiabCgst">—</div></div></div>
                                    <div class="col-6 col-md-3"><div class="g3b-liability-box"><div class="g3b-liability-label">SGST Payable</div><div class="g3b-liability-value" id="g3LiabSgst">—</div></div></div>
                                    <div class="col-6 col-md-3"><div class="g3b-liability-box"><div class="g3b-liability-label">IGST Payable</div><div class="g3b-liability-value" id="g3LiabIgst">—</div></div></div>
                                    <div class="col-6 col-md-3"><div class="g3b-liability-box g3b-liability-total"><div class="g3b-liability-label">Total Net Payable</div><div class="g3b-liability-value" id="g3LiabTotal">—</div></div></div>
                                </div>
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
var _g3InitMonth = <?php echo (int)$_month; ?>;
var _g3InitYear  = <?php echo (int)$_year; ?>;
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/gstr3b.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
