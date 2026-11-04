<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!isset($ModAllCount))  { $ModAllCount  = 0; }
if (!isset($ModRowData))   { $ModRowData   = ''; }
if (!isset($ModPagination)){ $ModPagination = ''; }
$stats = $StaffStats ?? null;
?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle ?? 'Staff',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1">

                    <?php if ($JwtData->GenSettings->ShowStats ?? 1): ?>
                    <!-- ── Stats Strip ── -->
                    <div class="apex-stats-strip">
                        <a href="javascript:void(0);" class="apex-stat-item active" data-filter-status="All" style="--stat-color:#0ea5e9">
                            <div class="apex-stat-icon" style="background:#e0f2fe"><i class="bx bxs-group" style="color:#0ea5e9"></i></div>
                            <div class="apex-stat-body">
                                <div class="apex-stat-label">Total Staff</div>
                                <div class="apex-stat-bottom"><span class="apex-stat-count" id="statTotal"><?php echo number_format((int)($stats->Total ?? 0)); ?></span></div>
                            </div>
                        </a>
                        <a href="javascript:void(0);" class="apex-stat-item" data-filter-status="Active" style="--stat-color:#16a34a">
                            <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#16a34a"></i></div>
                            <div class="apex-stat-body">
                                <div class="apex-stat-label">Active</div>
                                <div class="apex-stat-bottom"><span class="apex-stat-count" id="statActive"><?php echo number_format((int)($stats->Active ?? 0)); ?></span></div>
                            </div>
                        </a>
                        <a href="javascript:void(0);" class="apex-stat-item" data-filter-status="Resigned" style="--stat-color:#f59e0b">
                            <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-log-out" style="color:#f59e0b"></i></div>
                            <div class="apex-stat-body">
                                <div class="apex-stat-label">Resigned</div>
                                <div class="apex-stat-bottom"><span class="apex-stat-count" id="statResigned"><?php echo number_format((int)($stats->Resigned ?? 0)); ?></span></div>
                            </div>
                        </a>
                        <a href="javascript:void(0);" class="apex-stat-item" data-filter-status="Terminated" style="--stat-color:#dc2626">
                            <div class="apex-stat-icon" style="background:#fee2e2"><i class="bx bx-x-circle" style="color:#dc2626"></i></div>
                            <div class="apex-stat-body">
                                <div class="apex-stat-label">Terminated</div>
                                <div class="apex-stat-bottom"><span class="apex-stat-count" id="statTerminated"><?php echo number_format((int)($stats->Terminated ?? 0)); ?></span></div>
                            </div>
                        </a>
                        <a href="javascript:void(0);" class="apex-stat-item" data-filter-login="1" style="--stat-color:#7c3aed">
                            <div class="apex-stat-icon" style="background:#ede9fe"><i class="bx bx-log-in-circle" style="color:#7c3aed"></i></div>
                            <div class="apex-stat-body">
                                <div class="apex-stat-label">Login Users</div>
                                <div class="apex-stat-bottom"><span class="apex-stat-count" id="statLogin"><?php echo number_format((int)($stats->LoginUsers ?? 0)); ?></span></div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchStaffData" placeholder="Name, code, mobile...">
                                <i class="bx bx-x r2k-clear d-none" id="clearStaffSearch"></i>
                            </div>
                            <a href="javascript:void(0);" id="deptFilterBtn" class="apex-filter-btn" title="Filter by Department">
                                <i class="bx bx-building-house"></i>Department
                            </a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button type="button" class="btn btn-primary btn-sm" id="addStaffBtn">
                                <i class="bx bx-plus me-1"></i>Add Staff
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <?php
                            $initStatus = $InitStatus ?? 'All';
                            $initCount  = (int)($ModAllCount ?? 0);
                            function _uTabBadge(string $s, string $initStatus, int $initCount): string {
                                $show = ($s === $initStatus && $initCount > 0);
                                return '<span class="trans-tab-count ms-1' . ($show ? '' : ' d-none') . '">' . ($show ? $initCount : '') . '</span>';
                            }
                        ?>
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'All'        ? ' active' : ''; ?> staff-status-tab" data-status="All"        href="javascript:void(0);">All        <?php echo _uTabBadge('All',        $initStatus, $initCount); ?></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Active'     ? ' active' : ''; ?> staff-status-tab" data-status="Active"     href="javascript:void(0);">Active     <?php echo _uTabBadge('Active',     $initStatus, $initCount); ?></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Resigned'   ? ' active' : ''; ?> staff-status-tab" data-status="Resigned"   href="javascript:void(0);">Resigned   <?php echo _uTabBadge('Resigned',   $initStatus, $initCount); ?></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Terminated' ? ' active' : ''; ?> staff-status-tab" data-status="Terminated" href="javascript:void(0);">Terminated <?php echo _uTabBadge('Terminated', $initStatus, $initCount); ?></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'OnLeave'    ? ' active' : ''; ?> staff-status-tab" data-status="OnLeave"    href="javascript:void(0);">On Leave   <?php echo _uTabBadge('OnLeave',    $initStatus, $initCount); ?></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="staffTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox staffHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="table-serialno <?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>" style="width:44px">#</th>
                                        <th>Staff Member</th>
                                        <th>Department / Designation</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Joining Date</th>
                                        <th>Access</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="staffTableBody">
                                    <tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center staffPagination apex-pag-sticky" id="staffPagination"></div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('users/modals/user'); ?>
            <?php $this->load->view('common/form/address_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'        => 'deptFilterBox',
        'triggerId' => 'deptFilterBtn',
        'checkClass'=> 'dept-chk',
        'title'     => 'Department',
        'icon'      => 'bx-building-house',
        'filterKey' => 'DeptUIDs',
        'items'     => array_map(function ($d) {
            return ['value' => (string)(int)$d->DepartmentUID, 'label' => $d->DepartmentName];
        }, $DepartmentList ?? []),
    ],
]); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="<?php echo _assetV('/js/core/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/core/pagination.js'); ?>"></script>
<script>
var CsrfName        = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken       = '<?php echo $this->security->get_csrf_hash(); ?>';

function _esc(str) { return $('<div>').text(str == null ? '' : String(str)).html(); }
function _escA(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function _fmtDate(str) {
    if (!str) return '—';
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d = new Date(str);
    return isNaN(d.getTime()) ? String(str) : (('0'+d.getDate()).slice(-2)+' '+months[d.getMonth()]+' '+d.getFullYear());
}

/**
 * Build tbody HTML from services users data array.
 * Mirrors application/views/users/list.php exactly.
 * @param {Array}  data
 * @param {number} serialStart  0-based offset for serial numbers
 * @returns {string}
 */
function _generateUsersHtml(data, serialStart) {
    if (!data || !data.length) {
        return '<tr><td colspan="9">' +
               '<div class="d-flex flex-column align-items-center py-5">' +
               '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:140px;object-fit:contain;">' +
               '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No staff found</span>' +
               '<button type="button" class="btn btn-primary btn-sm px-4" id="addStaffBtnEmpty"><i class="bx bx-plus me-1"></i>Add Staff</button>' +
               '</div></td></tr>';
    }
    var showSerial = (JwtData.GenSettings && parseInt(JwtData.GenSettings.SerialNoDisplay, 10) === 1);
    var empStatusBadge = { Active: 'bg-label-success', Resigned: 'bg-label-warning', Terminated: 'bg-label-danger', OnLeave: 'bg-label-info' };
    var html = '';
    $.each(data, function (i, r) {
        var uid          = parseInt(r.UserUID, 10);
        var isActive     = parseInt(r.IsActive, 10) === 1;
        var hasLogin     = parseInt(r.HasLoginAccess, 10) === 1;
        var empStatus    = r.EmployeeStatus || 'Active';
        var isSuperAdmin = (r.RoleName || '') === 'Super Admin';
        var sno          = serialStart + i + 1;
        var empLabel     = empStatus === 'OnLeave' ? 'On Leave' : _esc(empStatus);
        var badgeCls     = empStatusBadge[empStatus] || 'bg-label-secondary';
        var initials     = ((r.FirstName || '').charAt(0) + (r.LastName || '').charAt(0)).toUpperCase();
        var fullName     = _esc($.trim((r.FirstName || '') + ' ' + (r.LastName || '')));

        html += '<tr>';
        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox staffCheck" type="checkbox" value="' + uid + '"></div></td>';
        /* Serial */
        html += '<td class="table-serialno' + (showSerial ? '' : ' d-none') + '" style="width:44px"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>';
        /* Staff member */
        html += '<td><div class="d-flex align-items-center gap-2">' +
                '<div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-primary" style="font-size:var(--r2k-text-sm);">' + initials + '</span></div>' +
                '<div><div class="fw-semibold" style="font-size:.87rem;">' + fullName + '</div>' +
                (r.EmployeeCode ? '<div class="text-muted" style="font-size:var(--r2k-text-sm);"><span class="me-1">' + _esc(r.EmployeeCode) + '</span></div>' : '') +
                '</div></div></td>';
        /* Department / Designation */
        html += '<td>';
        if (r.DepartmentName)  html += '<div style="font-size:var(--r2k-text-82);">' + _esc(r.DepartmentName) + '</div>';
        if (r.DesignationName) html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _esc(r.DesignationName) + '</div>';
        else if (!r.DepartmentName) html += '<span class="text-muted">—</span>';
        html += '</td>';
        /* Contact */
        html += '<td>';
        if (r.EmailAddress)  html += '<div style="font-size:var(--r2k-text-md);">' + _esc(r.EmailAddress) + '</div>';
        if (r.MobileNumber)  html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _esc(r.MobileNumber) + '</div>';
        else if (!r.EmailAddress) html += '<span class="text-muted">—</span>';
        html += '</td>';
        /* Employment Status */
        html += '<td>';
        if (isSuperAdmin) {
            html += '<span class="badge ' + badgeCls + '" style="font-size:var(--r2k-text-sm);" title="Super Admin is always active">' + empLabel + '</span>';
        } else {
            html += '<div class="dropdown d-inline-block">' +
                    '<span class="badge ' + badgeCls + ' cursor-pointer" style="font-size:var(--r2k-text-sm);" data-bs-toggle="dropdown">' +
                    empLabel + ' <i class="bx bx-chevron-down" style="font-size:var(--r2k-text-65);"></i></span>' +
                    '<ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:var(--r2k-text-82);">' +
                    '<li><button class="dropdown-item staff-status-toggle" data-uid="' + uid + '" data-newstatus="' + (isActive ? 0 : 1) + '">' +
                    (isActive ? '<i class="bx bx-x-circle me-2 text-danger"></i>Mark Inactive' : '<i class="bx bx-check-circle me-2 text-success"></i>Mark Active') +
                    '</button></li></ul></div>';
        }
        html += '</td>';
        /* Joining Date */
        html += '<td>' + (r.DateOfJoining ? '<div style="font-size:var(--r2k-text-82);">' + _fmtDate(r.DateOfJoining) + '</div>' : '<span class="text-muted">—</span>') + '</td>';
        /* Login Access */
        html += '<td>';
        if (hasLogin) {
            html += '<span class="badge bg-label-primary" style="font-size:var(--r2k-text-70);" title="@' + _escA(r.UserName || '') + '"><i class="bx bx-log-in me-1"></i>Login</span>';
            if (r.RoleName) html += '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + _esc(r.RoleName) + '</div>';
        } else {
            html += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-70);">Staff Only</span>';
        }
        html += '</td>';
        /* Actions */
        html += '<td style="width:60px"><div class="d-flex align-items-center justify-content-end gap-1">' +
                '<button class="btn btn-icon btn-sm text-warning staffEditBtn" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></button>' +
                '</div></td>';
        html += '</tr>';
    });
    return html;
}
var PageNo          = 1;
var RowLimit        = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>;
var Filter          = { EmpStatus: 'All' };
var OrgCountryISO2  = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;
var NextEmpCode     = <?php echo json_encode($NextEmpCode ?? 'EMP-0001'); ?>;
var CanSeeSalary    = <?php echo ($CanSeeSalary ?? false) ? 'true' : 'false'; ?>;
var _usersInitStatus = <?php echo json_encode($InitStatus ?? 'All'); ?>;

var _umCCCfg = {
    btn      : '#UM_MobileCCBtn',
    dropdown : '#UM_CCDropdown',
    search   : '#UM_CCSearch',
    list     : '#UM_CCList',
    codeInput: '#UM_CountryCode',
    iso2Input: '#UM_CountryISO2'
};

var _origOpenAddressModal = openAddressModal;
openAddressModal = function (addrType) {
    _origOpenAddressModal(addrType);
    $('#addrModalTitle').text(addrType == 1 ? 'Current Address' : 'Permanent Address');
};

$(function () {
    'use strict';

    var _deptFilter;

    PhoneCCDropdown.init(_umCCCfg);

    // ── Date of Joining picker ─────────────────────────────────────────
    flatpickr('#UserDOJ', {
        dateFormat   : 'Y-m-d',
        altInput     : true,
        altFormat    : _transFormDateFormat,
        allowInput   : false,
        disableMobile: true,
        static       : true,
        position     : 'below left',
    });

    // ── Portal Access Expiry picker ────────────────────────────────────
    var _loginExpiryFp = flatpickr('#UserLoginExpiry', {
        dateFormat   : 'Y-m-d',
        altInput     : true,
        altFormat    : _transFormDateFormat,
        allowInput   : false,
        disableMobile: true,
        static       : true,
        position     : 'below left',
        minDate      : 'today',
    });
    $(document).on('click', '#UserLoginExpiryClear', function () {
        _loginExpiryFp.clear();
    });

    // ── URL state helper ───────────────────────────────────────────────
    function _updateUrl(status) {
        var slug = (status || 'All').toLowerCase().replace(' ', '');
        var qs   = slug === 'all' ? window.location.pathname : window.location.pathname + '?status=' + slug;
        history.replaceState(null, '', qs);
    }

    // ── Restore initial tab from URL param, then load data via services ──
    (function () {
        var s = _usersInitStatus || 'All';
        Filter.EmpStatus = s;
        if (s !== 'All') {
            $('.staff-status-tab').removeClass('active');
            $('.staff-status-tab[data-status="' + s + '"]').addClass('active');
            $('.apex-stat-item').removeClass('active');
            $('.apex-stat-item[data-filter-status="' + s + '"]').addClass('active');
        }
    }());
    _loadStaff();

    // ── Stats update ───────────────────────────────────────────────────
    function _updateStats(s) {
        if (!s) return;
        $('#statTotal').text(s.Total || 0);
        $('#statActive').text(s.Active || 0);
        $('#statResigned').text(s.Resigned || 0);
        $('#statTerminated').text(s.Terminated || 0);
        $('#statLogin').text(s.LoginUsers || 0);
    }

    function _syncSticky() {}

    // ── List refresh ───────────────────────────────────────────────────
    function _renderList(resp) {
        var serialStart = (PageNo - 1) * RowLimit;
        $('#staffTable tbody').html(_generateUsersHtml(resp.Data || [], serialStart));
        $('.staffPagination').html(generatePagination(resp.TotalCount || 0, PageNo, RowLimit));
        $('.staff-status-tab .trans-tab-count').text('').addClass('d-none');
        var $badge = $('.staff-status-tab.active .trans-tab-count');
        if (resp.TotalCount > 0) { $badge.text(resp.TotalCount).removeClass('d-none'); }
        if (resp.Stats) _updateStats(resp.Stats);
        if (resp.NextEmpCode) NextEmpCode = resp.NextEmpCode;
        _syncSticky();
    }

    function _showTableSpinner() {
        var cols = $('#staffTable thead tr:first th:visible').length || 6;
        $('#staffTable tbody').html(
            '<tr><td colspan="' + cols + '" class="text-center py-4">' +
            '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
            '<span class="text-muted" style="font-size:var(--r2k-text-85);">Loading...</span>' +
            '</td></tr>'
        );
        $('.staffPagination').empty();
    }

    function _loadStaff() {
        ajaxLoading(0);
        _showTableSpinner();
        var f = $.extend({}, Filter, _deptFilter ? _deptFilter.getState() : {});
        $.ajax({
            url    : global_services_url + 'api/users/getPageDetails/' + PageNo,
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                Filter  : JSON.stringify(f),
                RowLimit: RowLimit,
                orgId   : parseInt(JwtData.Org.OrgUID, 10),
                userId  : parseInt(JwtData.User.UserUID, 10),
            },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderList(resp);
            },
            error: function () {
                ajaxLoading(1);
            }
        });
    }

    // ── Status tabs ────────────────────────────────────────────────────
    $(document).on('click', '.staff-status-tab', function (e) {
        e.preventDefault();
        var status = $(this).data('status') || 'All';
        $('.staff-status-tab').removeClass('active');
        $(this).addClass('active');
        // Sync active state on the stats strip
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-filter-status="' + status + '"]').addClass('active');
        Filter.EmpStatus = status;
        delete Filter.LoginAccess;
        _updateUrl(status);
        PageNo = 1; _loadStaff();
    });

    // ── Stat card click ────────────────────────────────────────────────
    $(document).on('click', '.apex-stat-item', function () {
        var status = $(this).data('filter-status');
        var login  = $(this).data('filter-login');
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        Filter = { EmpStatus: 'All' };
        if (status && status !== 'All') Filter.EmpStatus = status;
        if (login !== undefined)        { Filter.LoginAccess = login; Filter.EmpStatus = 'All'; }
        $('.staff-status-tab').removeClass('active');
        if (status) {
            $('.staff-status-tab[data-status="' + (status || 'All') + '"]').addClass('active');
        }
        _updateUrl(login !== undefined ? 'All' : (status || 'All'));
        PageNo = 1; _loadStaff();
    });

    // ── Refresh ────────────────────────────────────────────────────────
    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault(); PageNo = 1; _loadStaff();
    });

    // ── Department filter ──────────────────────────────────────────────
    _deptFilter = new TransColFilter({
        boxId      : 'deptFilterBox',
        triggerId  : 'deptFilterBtn',
        filterKey  : 'DeptUIDs',
        activeClass: 'has-filter',
        onApply    : function () { PageNo = 1; _loadStaff(); }
    });

    // ── Search ────────────────────────────────────────────────────────
    var _debounce;
    $('#searchStaffData').on('input', function () {
        clearTimeout(_debounce);
        var val = $.trim($(this).val());
        var hasVal = val.length > 0;
        $('#clearStaffSearch').toggleClass('d-none', !hasVal);
        $(this).closest('.r2k-search-wrap').toggleClass('r2k-search-active', hasVal);
        _debounce = setTimeout(function () {
            Filter.Name = val || undefined; PageNo = 1; _loadStaff();
        }, 1500);
    });

    $('#clearStaffSearch').on('click', function () {
        $('#searchStaffData').val('').trigger('input');
    });

    // ── Pagination ─────────────────────────────────────────────────────
    $(document).on('click', '.staffPagination .r2k-pag-btn:not(.disabled)', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10);
        if (page && page !== PageNo) { PageNo = page; _loadStaff(); }
    });

    // ── Header checkbox ────────────────────────────────────────────────
    $(document).on('change', '.staffHeaderCheck', function () {
        $('.staffCheck').prop('checked', $(this).is(':checked'));
    });

    // ── Status toggle ──────────────────────────────────────────────────
    $(document).on('click', '.staff-status-toggle', function () {
        var uid       = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        $.ajax({
            url    : global_services_url + 'api/users/toggleStatus',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                UserUID : uid,
                IsActive: newStatus,
                Filter  : JSON.stringify(Filter),
                RowLimit: RowLimit,
                orgId   : parseInt(JwtData.Org.OrgUID, 10),
                userId  : parseInt(JwtData.User.UserUID, 10),
            },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            }
        });
    });

    // ── Open modal (Add) ───────────────────────────────────────────────
    var _userIsDirty      = false;
    var _userIsCreateMode = false;

    function _resetModal() {
        _userIsCreateMode = false;
        _userIsDirty      = false;
        var $m = $('#userModal');
        $m.find('input[type=text], input[type=email], input[type=number]').val('');
        $('#UserModalUID').val(0);
        // Personal
        $('#UM_MobileCCBtn').text('+91');
        $('#UM_CountryCode').val('+91');
        $('#UM_CountryISO2').val('IN');
        // Employment
        $('#UserEmpCode').val(NextEmpCode);
        $('#UserDeptUID').val('');
        $('#UserDesigUID').val('');
        $('#UserDOJ').val('');
        $('#UserEmpStatus').val('Active');
        // Salary
        $('#UserSalaryType').val('Monthly');
        // Login
        $('#UserHasLoginAccess').prop('checked', true);
        $('#loginAccessSection').show();
        $('#UserRoleUID').val('');
        $('#UserIsActive').prop('checked', true);
        $('#UserIsLocked').prop('checked', false);
        _loginExpiryFp.clear();
        // Reset address
        if (typeof resetAddrData === 'function') resetAddrData();
        // Show/hide create-only vs edit-only
        $('#pwdSetupInfo').removeClass('d-none');
        $('#userCodeWrap').addClass('d-none');
        $('#userLockedRow').addClass('d-none');
        $('#lastLoginCard').addClass('d-none');
        $('#UserUsername').prop('readonly', false);
        $('#UserEmail').prop('readonly', false);
        // Attachments tab — hide in add mode, clear list
        $('#attachTabNavItem').addClass('d-none');
        $('#userAttachItems').empty();
        $('#userAttachEmpty').removeClass('d-none');
        $('#attachTabCount').addClass('d-none').text('');
        $('#userAttachFile').val('');
        $('#userAttachFileName').text('No file chosen');
        $('#btnUploadAttach').addClass('d-none');
        // Branch access — clear selection
        $('.branch-access-chk').prop('checked', false);
        // Go to first tab
        $('#staffFormTabs a[data-bs-target="#tabPersonal"]').tab('show');
    }

    function _openAddModal() {
        _resetModal();
        $('#userModalTitle').text('Add Staff');
        $('#userModal').modal('show');
        _userIsCreateMode = true;
        _userIsDirty      = false;
    }

    $(document).on('click', '#addStaffBtn, #addStaffBtnEmpty', _openAddModal);

    // ── Open modal (Edit) ──────────────────────────────────────────────
    $(document).on('click', '.staffEditBtn', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url    : global_services_url + 'api/users/getUserDetail',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                UserUID: uid,
                orgId  : parseInt(JwtData.Org.OrgUID, 10),
                userId : parseInt(JwtData.User.UserUID, 10),
            },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _resetModal();
                var d    = resp.Data;
                var curr = (d.Addresses && d.Addresses.Current)   || {};
                var perm = (d.Addresses && d.Addresses.Permanent) || {};

                $('#userModalTitle').text('Edit Staff');
                $('#UserModalUID').val(d.UserUID);

                // Personal
                $('#UserFirstName').val(d.FirstName   || '');
                $('#UserLastName').val(d.LastName     || '');
                $('#UserEmail').val(d.EmailAddress    || '');
                $('#UserMobile').val(d.MobileNumber   || '');
                var _cc = d.CountryCode || '+91';
                $('#UM_MobileCCBtn').text(_cc);
                $('#UM_CountryCode').val(_cc);
                $('#UM_CountryISO2').val(d.CountryISO2 || 'IN');

                // Employment
                $('#UserEmpCode').val(d.EmployeeCode  || '');
                $('#UserDeptUID').val(d.DepartmentUID || '');
                $('#UserDesigUID').val(d.DesignationUID || '');
                $('#UserDOJ').val(d.DateOfJoining     || '');
                $('#UserEmpStatus').val(d.EmployeeStatus || 'Active');

                // Salary
                if (CanSeeSalary) {
                    $('#UserSalaryType').val(d.SalaryType || 'Monthly');
                    $('#UserBasicSalary').val(d.BasicSalary     || '');
                    $('#UserAllowances').val(d.Allowances       || '');
                    $('#UserIncentives').val(d.Incentives       || '');
                    $('#UserFixedDeductions').val(d.FixedDeductions || '');
                }

                // Login
                var hasLogin = parseInt(d.HasLoginAccess) === 1;
                $('#UserHasLoginAccess').prop('checked', hasLogin);
                $('#loginAccessSection').toggle(hasLogin);
                if (hasLogin) {
                    $('#UserUsername').val(d.UserName || '').prop('readonly', true);
                    $('#UserRoleUID').val(d.RoleUID || '');
                    $('#UserIsActive').prop('checked', parseInt(d.IsActive) === 1);
                    $('#userCodeWrap').removeClass('d-none');
                    $('#UserCodeDisplay').val(d.EmployeeCode || '');
                    $('#userLockedRow').removeClass('d-none');
                    $('#UserIsLocked').prop('checked', parseInt(d.IsLocked) === 1);
                    $('#lastLoginCard').removeClass('d-none');
                    $('#lastLoginDisplay').text(d.LastLoginOn || '—');
                    $('#pwdSetupInfo').addClass('d-none');
                    $('#UserEmail').prop('readonly', true);
                    // Portal access expiry
                    if (d.LoginExpiryDateTime) {
                        _loginExpiryFp.setDate(d.LoginExpiryDateTime.substring(0, 10), false);
                    } else {
                        _loginExpiryFp.clear();
                    }
                }

                // Addresses
                if (curr.AddressLine1 || curr.City || curr.State) {
                    billingAddrData = { UID: curr.AddressUID||0, Line1: curr.AddressLine1||'', Line2: curr.AddressLine2||'', Pincode: curr.PinCode||'', StateId:'', StateName: curr.State||'', StateISO2:'', CityId:'', CityName: curr.City||'' };
                    renderAddrSummary(1, billingAddrData);
                }
                if (perm.AddressLine1 || perm.City || perm.State) {
                    shippingAddrData = { UID: perm.AddressUID||0, Line1: perm.AddressLine1||'', Line2: perm.AddressLine2||'', Pincode: perm.PinCode||'', StateId:'', StateName: perm.State||'', StateISO2:'', CityId:'', CityName: perm.City||'' };
                    renderAddrSummary(2, shippingAddrData);
                }

                // Attachments tab — show and populate
                $('#attachTabNavItem').removeClass('d-none');
                _renderUserAttachments(resp.Attachments || []);

                // Branch access — select the default (or first assigned) branch
                var _baList = resp.BranchAccess || [];
                var _defBranch = _baList.find(function(ba) { return parseInt(ba.IsDefault) === 1; })
                               || _baList[0];
                if (_defBranch) $('#branchChk_' + parseInt(_defBranch.BranchUID)).prop('checked', true);

                $('#userModal').modal('show');
            }
        });
    });

    // ── Toggle login section visibility ────────────────────────────────
    $(document).on('change', '#UserHasLoginAccess', function () {
        $('#loginAccessSection').toggle($(this).is(':checked'));
    });

    // ── Save ───────────────────────────────────────────────────────────
    $(document).on('click', '#saveUserBtn', function () {
        var uid      = parseInt($('#UserModalUID').val()) || 0;
        var isEdit   = uid > 0;
        var hasLogin = $('#UserHasLoginAccess').is(':checked');
        var firstName= $.trim($('#UserFirstName').val());
        var username = $.trim($('#UserUsername').val());
        var email    = $.trim($('#UserEmail').val());
        var roleUID  = $('#UserRoleUID').val();

        if (!firstName)                        { showToastNotification('First name is required.', 'error'); return; }
        if (!email)                            { showToastNotification('Email address is required.', 'error'); return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToastNotification('Enter a valid email address.', 'error'); return; }
        var deptUID  = $('#UserDeptUID').val();
        var desigUID = $('#UserDesigUID').val();
        if (!deptUID)  { showToastNotification('Department is required.', 'error'); return; }
        if (!desigUID) { showToastNotification('Designation is required.', 'error'); return; }
        if (hasLogin && !isEdit && !username)  { showToastNotification('Username is required for login access.', 'error'); return; }
        if (hasLogin && !roleUID)              { showToastNotification('Please select a role for login access.', 'error'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#saveUserSpinner').removeClass('d-none');
        $('#saveUserIcon').addClass('d-none');

        var fd = new FormData();
        fd.append('UserUID',          uid);
        fd.append('HasLoginAccess',   hasLogin ? 1 : 0);
        fd.append('FirstName',        firstName);
        fd.append('LastName',         $.trim($('#UserLastName').val()));
        fd.append('Mobile',           $.trim($('#UserMobile').val()));
        fd.append('CountryCode',      $('#UM_CountryCode').val() || '+91');
        fd.append('CountryISO2',      $('#UM_CountryISO2').val() || 'IN');
        fd.append('Email',            email);
        // Employment
        fd.append('EmployeeCode',     $.trim($('#UserEmpCode').val()));
        fd.append('DepartmentUID',    $('#UserDeptUID').val() || '');
        fd.append('DesignationUID',   $('#UserDesigUID').val() || '');
        fd.append('DateOfJoining',    $('#UserDOJ').val() || '');
        fd.append('EmployeeStatus',   $('#UserEmpStatus').val());
        // Salary
        if (CanSeeSalary) {
            fd.append('SalaryType',       $('#UserSalaryType').val());
            fd.append('BasicSalary',      $('#UserBasicSalary').val() || 0);
            fd.append('Allowances',       $('#UserAllowances').val()  || 0);
            fd.append('Incentives',       $('#UserIncentives').val()  || 0);
            fd.append('FixedDeductions',  $('#UserFixedDeductions').val() || 0);
        }
        // Login
        if (hasLogin) {
            if (!isEdit) fd.append('UserName', username);
            fd.append('RoleUID',            roleUID);
            fd.append('IsActive',           $('#UserIsActive').is(':checked') ? 1 : 0);
            fd.append('IsLocked',           $('#UserIsLocked').is(':checked') ? 1 : 0);
            fd.append('LoginExpiryDateTime', $('#UserLoginExpiry').val() || '');
        }
        // Addresses
        if (billingAddrData)  {
            fd.append('CurrAddressLine1', billingAddrData.Line1     || '');
            fd.append('CurrAddressLine2', billingAddrData.Line2     || '');
            fd.append('CurrPinCode',      billingAddrData.Pincode   || '');
            fd.append('CurrState',        billingAddrData.StateName || '');
            fd.append('CurrCity',         billingAddrData.CityName  || '');
        }
        if (shippingAddrData) {
            fd.append('PermAddressLine1', shippingAddrData.Line1     || '');
            fd.append('PermAddressLine2', shippingAddrData.Line2     || '');
            fd.append('PermPinCode',      shippingAddrData.Pincode   || '');
            fd.append('PermState',        shippingAddrData.StateName || '');
            fd.append('PermCity',         shippingAddrData.CityName  || '');
        }
        if (typeof delAddrData !== 'undefined' && delAddrData.length > 0) {
            fd.append('DelAddrUIDs', delAddrData.join(','));
        }
        // Branch access — radio so at most one selected; that branch is always default
        var branchAccess = [];
        var $selBranch = $('.branch-access-chk:checked');
        if ($selBranch.length) branchAccess.push({ BranchUID: parseInt($selBranch.val()), IsDefault: 1 });
        fd.append('BranchAccessJson', JSON.stringify(branchAccess));
        fd.append('Filter',   JSON.stringify(Filter));
        fd.append('RowLimit', RowLimit);
        fd.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
        fd.append('userId', parseInt(JwtData.User.UserUID, 10));

        $.ajax({
            url    : global_services_url + 'api/users/saveUser',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false);
                $('#saveUserSpinner').addClass('d-none');
                $('#saveUserIcon').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _userIsDirty      = false;
                _userIsCreateMode = false;
                if (resp.NextEmpCode) NextEmpCode = resp.NextEmpCode;
                $('#userModal').modal('hide');
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#saveUserSpinner').addClass('d-none');
                $('#saveUserIcon').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ── Attachment helpers ─────────────────────────────────────────────────────
    function _renderUserAttachments(attachments) {
        var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
        var $items = $('#userAttachItems').empty();
        var $empty = $('#userAttachEmpty');
        var $count = $('#attachTabCount');

        if (!attachments || attachments.length === 0) {
            $empty.removeClass('d-none');
            $count.addClass('d-none').text('');
            return;
        }
        $empty.addClass('d-none');
        $count.removeClass('d-none').text(attachments.length);

        attachments.forEach(function (a) {
            var uid      = a.AttachUID;
            var name     = a.FileName || '';
            var safeName = $('<span>').text(name).html();
            var fullUrl  = cdnUrl + (a.FilePath || '');
            var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
            var isPdf    = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
            var iconCls  = isImg ? 'bx-image-alt text-success' : (isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary');
            var ftype    = isImg ? 'img' : (isPdf ? 'pdf' : 'file');
            var encUrl   = encodeURIComponent(fullUrl);
            var docLabel = a.DocType ? ('<span class="badge bg-label-info me-1" style="font-size:var(--r2k-text-xs);">' + $('<span>').text(a.DocType).html() + '</span>') : '';

            var $item = $('<div>', {
                'class'    : 'd-flex align-items-center gap-2 border rounded px-3 py-2 mb-2 bg-light user-attach-item',
                'data-uid' : uid,
                'style'    : 'font-size:var(--r2k-text-82);'
            }).append(
                $('<i>', { 'class': 'bx ' + iconCls, 'style': 'font-size:1.2rem;flex-shrink:0;cursor:pointer;' })
                    .on('click', function () { _openUserAttachPreview(encUrl, ftype, name); }),
                $('<div>', { 'style': 'flex:1;min-width:0;' }).append(
                    $('<div>', { 'style': 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;', 'title': name })
                        .html(docLabel + safeName)
                        .on('click', function () { _openUserAttachPreview(encUrl, ftype, name); })
                ),
                $('<button>', {
                    'type'       : 'button',
                    'class'      : 'btn btn-icon btn-sm text-danger user-attach-delete-btn',
                    'title'      : 'Delete',
                    'data-uid'   : uid
                }).html('<i class="bx bx-trash" style="font-size:.95rem;"></i>')
            );
            $items.append($item);
        });
    }

    function _openUserAttachPreview(encUrl, type, name) {
        var url  = decodeURIComponent(encUrl);
        var body = '';
        if (type === 'img') {
            body = '<img src="' + url + '" style="max-width:100%;max-height:80vh;display:block;margin:auto;" onerror="this.outerHTML=\'<div class=text-white text-center py-5>Image could not be loaded.</div>\'">';
        } else if (type === 'pdf') {
            body = '<iframe src="' + url + '" style="width:100%;height:80vh;border:none;"></iframe>';
        } else {
            body = '<div class="text-white text-center py-5"><i class="bx bx-download" style="font-size:3rem;"></i><br><a href="' + url + '" target="_blank" class="btn btn-light mt-3"><i class="bx bx-download me-1"></i>Download File</a></div>';
        }
        $('#attachPreviewTitle').text(name || 'Preview');
        $('#attachPreviewBody').html(body);
        new bootstrap.Modal(document.getElementById('attachPreviewModal')).show();
    }

    // File input change
    $(document).on('change', '#userAttachFile', function () {
        var name = this.files.length ? this.files[0].name : '';
        $('#userAttachFileName').text(name || 'No file chosen');
        $('#btnUploadAttach').toggleClass('d-none', !name);
    });

    // Upload
    $(document).on('click', '#btnUploadAttach', function () {
        var uid    = parseInt($('#UserModalUID').val()) || 0;
        var fileEl = document.getElementById('userAttachFile');
        if (uid <= 0) { showToastNotification('Save the staff record first before uploading attachments.', 'warning'); return; }
        if (!fileEl || !fileEl.files.length) return;
        var file = fileEl.files[0];
        if (file.size > 5 * 1024 * 1024) { showToastNotification('File size must be under 5 MB.', 'error'); return; }

        var fd = new FormData();
        fd.append('UserUID',    uid);
        fd.append('DocType',    $('#userAttachDocType').val());
        fd.append('AttachFile', file);
        fd.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
        fd.append('userId', parseInt(JwtData.User.UserUID, 10));

        var $btn = $(this).prop('disabled', true);
        $('#uploadAttachSpinner').removeClass('d-none');
        $('#uploadAttachIcon').addClass('d-none');

        $.ajax({
            url    : global_services_url + 'api/users/saveUserAttachment',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false);
                $('#uploadAttachSpinner').addClass('d-none');
                $('#uploadAttachIcon').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                fileEl.value = '';
                $('#userAttachFileName').text('No file chosen');
                $btn.addClass('d-none');
                _renderUserAttachments(resp.Attachments || []);
                showToastNotification('File uploaded successfully.', 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#uploadAttachSpinner').addClass('d-none');
                $('#uploadAttachIcon').removeClass('d-none');
                showToastNotification('Upload failed. Please try again.', 'error');
            }
        });
    });

    // Delete attachment
    $(document).on('click', '.user-attach-delete-btn', function () {
        var attachUID = parseInt($(this).data('uid')) || 0;
        var userUID   = parseInt($('#UserModalUID').val()) || 0;
        if (!attachUID || !confirm('Delete this attachment? This cannot be undone.')) return;

        $.ajax({
            url    : global_services_url + 'api/users/deleteUserAttachment',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                AttachUID: attachUID,
                UserUID  : userUID,
                orgId    : parseInt(JwtData.Org.OrgUID, 10),
                userId   : parseInt(JwtData.User.UserUID, 10),
            },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderUserAttachments(resp.Attachments || []);
                showToastNotification('Attachment deleted.', 'success');
            }
        });
    });

    // ── Dirty-tracking listener ─────────────────────────────────────────────
    $(document).on('input change', '#userModal input, #userModal textarea, #userModal select', function () {
        if (_userIsCreateMode) _userIsDirty = true;
    });

    // ── Unsaved-changes guard ───────────────────────────────────────────────
    $(document).on('hide.bs.modal', '#userModal', function (e) {
        if (!_userIsDirty || !_userIsCreateMode) return;
        e.preventDefault();
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _userIsDirty      = false;
                _userIsCreateMode = false;
                $('#userModal').modal('hide');
            }
        });
    });

});
</script>
