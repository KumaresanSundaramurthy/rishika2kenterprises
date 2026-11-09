<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">

                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Packing Lists',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <!-- Stats strip -->
                <div class="apex-stats-strip">
                    <div class="apex-stat-item active" style="--stat-color:#696cff;">
                        <div class="apex-stat-icon" style="background:#eef2ff;">
                            <i class="bx bx-list-ul" style="color:#696cff;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Packing Lists</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="plTotalCount">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-xxl flex-grow-1">
                    <div class="card">

                        <!-- Filter row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="plSearch" placeholder="PL # or party or transporter...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>">
                                <i class="bx bx-refresh"></i>
                            </a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="plTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:44px" class="text-center">#</th>
                                        <th>PL Number</th>
                                        <th>Source Document</th>
                                        <th>Party</th>
                                        <th>PL Date</th>
                                        <th>Transporter</th>
                                        <th>Vehicle</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="plTbody"></tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center plPagination apex-pag-sticky d-none" id="plPagination"></div>

                    </div>
                </div>

            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/core/pagination.js"></script>
<script src="/js/core/time_ago.js"></script>
<script src="/js/transactions/packinglists.js"></script>
