<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageIcon'        => 'bx-calculator',
          'pageIconBg'      => '#eff6ff',
          'pageIconColor'   => '#2563eb',
          'pageTitle'       => 'Payroll',
          'pageDescription' => 'Process monthly salaries',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3">
            <a href="/payroll/process" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>Process Payroll</a>
          </div>

          <!-- Stat cards -->
          <?php $st = $PayrollStats ?? null; $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
          <div class="row g-3 mb-3">
            <div class="col-6 col-md"><div class="trans-stat-card stat-all"><div class="trans-stat-label">Total Payrolls</div><div class="trans-stat-count"><?php echo (int)($st->Total ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-layer trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-draft"><div class="trans-stat-label">Draft</div><div class="trans-stat-count"><?php echo (int)($st->Draft ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-edit trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-active"><div class="trans-stat-label">Processed</div><div class="trans-stat-count"><?php echo (int)($st->Processed ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-check trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-converted"><div class="trans-stat-label">Paid</div><div class="trans-stat-count"><?php echo (int)($st->Paid ?? 0); ?></div><div class="trans-stat-amount"><?php echo $cur . ' ' . number_format((float)($st->TotalPaid ?? 0), 2); ?></div><i class="bx bx-money trans-stat-icon"></i></div></div>
          </div>

          <div class="card">
            <div class="trans-toolbar">
              <div class="trans-toolbar-tabs">
                <ul class="nav trans-status-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active prl-tab" data-status=""          href="#">All</a></li>
                  <li class="nav-item"><a class="nav-link prl-tab"        data-status="Draft"     href="#">Draft</a></li>
                  <li class="nav-item"><a class="nav-link prl-tab"        data-status="Processed" href="#">Processed</a></li>
                  <li class="nav-item"><a class="nav-link prl-tab"        data-status="Paid"      href="#">Paid</a></li>
                </ul>
              </div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Search payroll…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th>#</th><th>Month / Year</th><th>Employees</th><th>Gross Amount</th><th>Deductions</th><th>Net Payable</th><th>Status</th><th>Processed By</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="PayrollTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center PayrollPagination" id="PayrollPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/payroll.js"></script>
