<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Map ModuleUID → human-readable transaction type name
$_moduleNames = [
    101 => 'Quotation',
    102 => 'Sales Invoice',
    103 => 'Purchase Order',
    104 => 'Purchase Invoice',
    105 => 'Delivery Note',
    106 => 'Receipt',
    107 => 'Credit Note',
    108 => 'Debit Note',
];
$_transType = $_moduleNames[$JwtData->ModuleUID] ?? 'Transaction';

// Compute current fiscal year for server-side default preview
$_month   = (int) date('m');
$_yr      = (int) date('Y');
$_fyStart = $_month >= 4 ? $_yr : $_yr - 1;
$_fyShort = str_pad($_fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($_fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
$_fyLong  = $_fyStart . '-' . ($_fyStart + 1);
?>

<!-- ============================================================
     Configure Transaction Number Preferences Modal
     ============================================================ -->
<div class="modal fade" id="transPrefixModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">

    <div class="modal-dialog modal-xl modal-dialog-top">
        <div class="modal-content">

            <!-- ── Shared Modal Header ─────────────────────────────── -->
            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="bx bx-cog me-1 text-primary"></i>
                        Configure <?php echo htmlspecialchars($_transType); ?> Number Preferences
                    </h5>
                    <small class="text-muted" id="prefixModalSubtitle">
                        Manage how your <?php echo htmlspecialchars($_transType); ?> numbers are generated
                    </small>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-primary me-2" id="showAddPrefixFormBtn">
                        <i class="bx bx-plus me-1"></i> Add New Prefix
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                    <!-- <button type="button" class="btn btn-outline-danger btn-icon-square"
                            data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x fs-4"></i>
                    </button> -->
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════
                 PANEL 1 – Prefix List
                 ════════════════════════════════════════════════════════ -->
            <div id="prefixListPanel">

                <div class="modal-body">

                    <!-- Info banner -->
                    <div class="alert alert-primary border-0 d-flex align-items-start gap-2 py-2 mb-3" role="alert">
                        <i class="bx bx-info-circle fs-5 mt-1 flex-shrink-0"></i>
                        <div class="small">
                            Your <?php echo htmlspecialchars($_transType); ?> numbers are <strong>auto-generated</strong>.
                            Each prefix below defines a complete numbering style — including financial year,
                            company code, separator and digit format.
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div id="prefixListLoading" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2 text-muted small">Loading prefixes…</span>
                    </div>

                    <!-- Prefix table (populated by JS) -->
                    <div id="prefixListContainer" class="d-none">

                        <!-- Empty state -->
                        <div id="prefixEmptyState" class="text-center py-5 d-none">
                            <i class="bx bx-tag-alt fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No prefixes yet.</p>
                            <p class="text-muted small">Click <strong>Add New Prefix</strong> to get started.</p>
                        </div>

                        <div id="prefixTableWrapper">
                            <div class="table-responsive">
                                <table class="table table-hover MainviewTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width:160px">Preview</th>
                                            <th>Name</th>
                                            <th>Configuration</th>
                                            <th class="text-center" style="width:70px">Default</th>
                                            <th class="text-center" style="width:100px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prefixListBody">
                                        <!-- Rows injected by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /prefixListContainer -->

                </div><!-- /modal-body -->

                <!-- <div class="modal-footer p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div> -->

            </div><!-- /prefixListPanel -->


            <!-- ════════════════════════════════════════════════════════
                 PANEL 2 – Add / Edit Prefix Form
                 ════════════════════════════════════════════════════════ -->
            <div id="prefixFormPanel" class="d-none">

                <?php
                $FormAttribute = [
                    'id'           => 'addTransPrefixForm',
                    'name'         => 'addTransPrefixForm',
                    'autocomplete' => 'off',
                ];
                echo form_open('transactions/addTransactionPrefix', $FormAttribute);
                ?>

                <!-- Hidden controls -->
                <input type="hidden" id="preModuleUID"   name="preModuleUID"   value="<?php echo (int) $JwtData->ModuleUID; ?>">
                <input type="hidden" id="prePrefixUID"   name="prePrefixUID"   value="">
                <input type="hidden" id="prefixFormMode" value="add">

                <div class="modal-body">

                    <!-- ── Step 1: Prefix Name ──────────────────────── -->
                    <div class="mb-4">
                        <label for="transPrefixName" class="form-label fw-semibold">
                            Prefix Name <span class="text-danger">*</span>
                        </label>
                        <input class="form-control text-uppercase fw-bold" type="text" id="transPrefixName" name="transPrefixName" placeholder="e.g. QT, INV, PO" maxlength="7" oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()" />
                        <div class="form-text">2 – 7 alphanumeric characters.</div>
                    </div>

                    <hr class="my-3">

                    <!-- ── Step 2: Financial Year ───────────────────── -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <label class="form-label fw-semibold mb-0">
                                    Add financial year to your <?php echo htmlspecialchars($_transType); ?> number?
                                </label>
                                <div class="form-text">e.g. QT<span id="prevFySepHint">-</span><?php echo $_fyShort; ?><span id="prevFySepHint2">-</span>001</div>
                            </div>
                            <div class="form-check form-switch ms-3 mb-0">
                                <input class="form-check-input" type="checkbox" id="includeFiscalYear" name="includeFiscalYear" role="switch" value="1">
                            </div>
                        </div>

                        <div id="fiscalYearOptions" class="mt-2 ps-3 border-start border-2 border-primary d-none">
                            <label class="form-label small fw-semibold text-muted">Year Format</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fiscalYearFormat" id="fyShortOpt" value="SHORT" checked>
                                    <label class="form-check-label" for="fyShortOpt">
                                        Short <code><?php echo $_fyShort; ?></code>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fiscalYearFormat" id="fyLongOpt" value="LONG">
                                    <label class="form-check-label" for="fyLongOpt">
                                        Full <code><?php echo $_fyLong; ?></code>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Step 3: Company Short Name ───────────────── -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <label class="form-label fw-semibold mb-0">
                                    Add your company short name to the <?php echo htmlspecialchars($_transType); ?> number?
                                </label>
                                <div class="form-text">e.g. QT<span class="prevSepHintInline">-</span>RK<span class="prevSepHintInline">-</span>001</div>
                            </div>
                            <div class="form-check form-switch ms-3 mb-0">
                                <input class="form-check-input" type="checkbox" id="includeShortName" name="includeShortName" role="switch" value="1">
                            </div>
                        </div>

                        <div id="shortNameOptions" class="mt-2 ps-3 border-start border-2 border-warning d-none">
                            <label for="companyShortName" class="form-label small fw-semibold text-muted">Company Short Name</label>
                            <input type="text" class="form-control form-control-sm text-uppercase fw-bold" id="companyShortName" name="companyShortName" maxlength="10" placeholder="e.g. RK, BHARAT" oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()" />
                        </div>
                    </div>

                    <!-- ── Step 4: Separator ────────────────────────── -->
                    <div class="mb-4">
                        <label for="prefixSeparator" class="form-label fw-semibold">Separator between components</label>
                        <div class="d-flex flex-wrap gap-2" id="separatorBtnGroup">
                            <?php
                            $separators = [
                                '-' => 'Hyphen &nbsp;<code>-</code>',
                                '/' => 'Slash &nbsp;<code>/</code>',
                                '|' => 'Pipe &nbsp;<code>|</code>',
                                '_' => 'Underscore &nbsp;<code>_</code>',
                                '.' => 'Dot &nbsp;<code>.</code>',
                            ];
                            foreach ($separators as $val => $label) {
                                $active = $val === '-' ? 'active' : '';
                                echo "<button type=\"button\" class=\"btn btn-outline-secondary sep-btn {$active}\" data-sep=\"{$val}\">{$label}</button>";
                            }
                            ?>
                        </div>
                        <input type="hidden" id="prefixSeparator" name="prefixSeparator" value="-">
                    </div>

                    <!-- ── Step 5: Number Padding ───────────────────── -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Number format</label>
                        <div class="d-flex flex-wrap gap-2" id="paddingBtnGroup">
                            <button type="button" class="btn btn-outline-secondary pad-btn"  data-pad="1">1, 2, 3&hellip;</button>
                            <button type="button" class="btn btn-outline-secondary pad-btn active" data-pad="3">001, 002&hellip;</button>
                            <button type="button" class="btn btn-outline-secondary pad-btn"  data-pad="5">00001, 00002&hellip;</button>
                        </div>
                        <input type="hidden" id="numberPadding" name="numberPadding" value="3">
                    </div>

                    <hr class="my-3">

                    <!-- ── Live Preview ─────────────────────────────── -->
                    <div>
                        <label class="form-label fw-semibold">
                            <i class="bx bx-show me-1"></i> Live Preview
                        </label>

                        <!-- Block-style component display -->
                        <div class="prefix-preview-box p-3 rounded-3 border bg-body-tertiary">

                            <div class="d-flex align-items-center flex-wrap gap-1 mb-2"
                                 id="prefixPreviewBlocks">
                                <!-- Blocks rendered by JS -->
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="text-muted small">Full number:</span>
                                <code class="fs-6 text-primary fw-bold" id="prefixFullPreview"></code>
                            </div>

                        </div>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="backToListBtn">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="savePrefixBtn">
                            <i class="bx bx-save me-1"></i> Save Prefix
                        </button>
                    </div>
                </div>

                <?php echo form_close(); ?>

            </div><!-- /prefixFormPanel -->

        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div><!-- /transPrefixModal -->
