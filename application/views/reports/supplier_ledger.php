<?php defined('BASEPATH') or exit('No direct script access allowed');

$_from    = $this->input->get('from') ?: date('Y-m-01');
$_to      = $this->input->get('to') ?: date('Y-m-d');
$_listFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-filter-control{min-width:200px}
.rpt-filter-date{width:150px}
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-green{background:#dcfce7}.rpt-icon-green i{color:#16a34a}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
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
.rpt-num-blue{color:#2563eb;font-weight:700}
.rpt-num-green{color:#16a34a;font-weight:700}
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-orange{background:#ffedd5;color:#9a3412}
.rpt-footer-chip-blue{background:#dbeafe;color:#1d4ed8}
.sl-type-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600;white-space:nowrap}
.sl-type-bill{background:#ffedd5;color:#9a3412}
.sl-type-return{background:#fef9c3;color:#854d0e}
.sl-type-payment{background:#dcfce7;color:#15803d}
.sl-ob-row td{background:#fef9c3!important;font-style:italic;color:#854d0e}
.sl-placeholder{display:flex;flex-direction:column;align-items:center;padding:70px 20px;color:#94a3b8}
.sl-placeholder i{font-size:3rem;margin-bottom:10px;opacity:.4}
.sl-placeholder-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td,.rpt-tbl-footer{background:#0f172a}
    .rpt-tbl-footer{border-color:#334155}
    .sl-ob-row td{background:#1c1208!important}
}
:root[data-theme="dark"] .rpt-filter-bar,.rpt-tbl-footer{border-color:#334155}
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
                    'pageTitle'       => 'Supplier Ledger',
                    'pageDescription' => 'Transaction-wise supplier account statement',
                    'pageIcon'        => 'bx-book-open',
                    'pageIconBg'      => '#ffedd5',
                    'pageIconColor'   => '#ea580c',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group" style="flex:1;min-width:220px">
                                <div class="rpt-filter-label">Supplier</div>
                                <select id="slSupplier" class="form-control rpt-filter-control" style="width:100%">
                                    <option value="">Select supplier…</option>
                                </select>
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="slFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="slFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="slToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="slTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="slApplyBtn">
                                    <i class="bx bx-search me-1"></i>Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="slStatsRow" style="display:none!important">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-wallet"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Opening Balance</div>
                                    <div class="rpt-stat-val" id="slStatOpening">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Credit (Bills)</div>
                                    <div class="rpt-stat-val" id="slStatCredit">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-money"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Debit (Payments)</div>
                                    <div class="rpt-stat-val" id="slStatDebit">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-trending-up"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Closing Balance</div>
                                    <div class="rpt-stat-val" id="slStatClosing">—</div>
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
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Ref No</th>
                                        <th class="rpt-col-num-hd">Debit</th>
                                        <th class="rpt-col-num-hd">Credit</th>
                                        <th class="rpt-col-num-hd">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="slTableBody">
                                    <tr><td colspan="7">
                                        <div class="sl-placeholder">
                                            <i class="bx bx-store-alt"></i>
                                            <div class="sl-placeholder-title">Select a supplier to view ledger</div>
                                            <div style="font-size:.82rem">Choose a supplier and date range, then click Apply</div>
                                        </div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="slTableFoot" class="d-none">
                                    <tr>
                                        <td colspan="4">Total</td>
                                        <td class="rpt-col-num rpt-num-green" id="slFtDebit"></td>
                                        <td class="rpt-col-num rpt-num-orange" id="slFtCredit"></td>
                                        <td class="rpt-col-num rpt-num-orange" id="slFtBalance"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="rpt-tbl-footer d-none" id="slTblFooter">
                            <div class="rpt-footer-left"><span id="slRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rpt-footer-chip rpt-footer-chip-blue">Debit: <span id="slFooterDebit"></span></span>
                                <span class="rpt-footer-chip rpt-footer-chip-orange">Balance: <span id="slFooterBalance"></span></span>
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
var _slInitFrom = '<?php echo addslashes($_from); ?>';
var _slInitTo   = '<?php echo addslashes($_to); ?>';
var _slListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/supplier_ledger.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
