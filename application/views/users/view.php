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

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <?php
                                $initStatus  = $InitStatus ?? 'All';
                                $allCount    = (int)($ModAllCount ?? 0);
                            ?>
                            <ul class="nav trans-status-tabs gap-1" role="tablist">
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'All'        ? ' active' : ''; ?> staff-status-tab" data-status="All"        href="javascript:void(0);">All        <span class="trans-tab-count ms-1<?php echo ($initStatus === 'All' && $allCount > 0) ? '' : ' d-none'; ?>"><?php echo $initStatus === 'All' ? $allCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Active'     ? ' active' : ''; ?> staff-status-tab" data-status="Active"     href="javascript:void(0);">Active     <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Resigned'   ? ' active' : ''; ?> staff-status-tab" data-status="Resigned"   href="javascript:void(0);">Resigned   <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'Terminated' ? ' active' : ''; ?> staff-status-tab" data-status="Terminated" href="javascript:void(0);">Terminated <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initStatus === 'OnLeave'    ? ' active' : ''; ?> staff-status-tab" data-status="OnLeave"    href="javascript:void(0);">On Leave   <span class="trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <!-- Department filter -->
                                <select class="form-select form-select-sm" id="filterDept" style="width:160px;">
                                    <option value="">All Departments</option>
                                    <?php foreach ($DepartmentList as $dept): ?>
                                    <option value="<?php echo (int)$dept->DepartmentUID; ?>"><?php echo htmlspecialchars($dept->DepartmentName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchStaffData" placeholder="Name, code, mobile...">
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="addStaffBtn">
                                    <i class="bx bx-plus me-1"></i>Add Staff
                                </button>
                            </div>
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
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-3 my-2 justify-content-between align-items-center staffPagination apex-pag-sticky" id="staffPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('users/modals/user'); ?>
            <?php $this->load->view('common/form/address_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script>
var CsrfName        = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken       = '<?php echo $this->security->get_csrf_hash(); ?>';
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

    // ── URL state helper ───────────────────────────────────────────────
    function _updateUrl(status) {
        var slug = (status || 'All').toLowerCase().replace(' ', '');
        var qs   = slug === 'all' ? window.location.pathname : window.location.pathname + '?status=' + slug;
        history.replaceState(null, '', qs);
    }

    // ── Restore initial tab from URL ───────────────────────────────────
    (function () {
        var s = _usersInitStatus || 'All';
        if (s !== 'All') {
            Filter.EmpStatus = s;
            $('.staff-status-tab').removeClass('active');
            $('.staff-status-tab[data-status="' + s + '"]').addClass('active');
            $('.apex-stat-item').removeClass('active');
            $('.apex-stat-item[data-filter-status="' + s + '"]').addClass('active');
            _loadStaff();
        }
    }());

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
        $('#staffTable tbody').html(resp.RecordHtmlData);
        $('.staffPagination').html(resp.Pagination);
        $('.staff-status-tab .trans-tab-count').text('').addClass('d-none');
        var $badge = $('.staff-status-tab.active .trans-tab-count');
        if (resp.TotalCount > 0) { $badge.text(resp.TotalCount).removeClass('d-none'); }
        if (resp.Stats) _updateStats(resp.Stats);
        _syncSticky();
    }

    function _loadStaff() {
        $.ajax({
            url: '/settings/users/getPageDetails/' + PageNo,
            method: 'POST',
            data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderList(resp);
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

    // ── Dept filter ────────────────────────────────────────────────────
    $('#filterDept').on('change', function () {
        Filter.DeptUID = $(this).val(); PageNo = 1; _loadStaff();
    });

    // ── Search ────────────────────────────────────────────────────────
    var _debounce;
    $('#searchStaffData').on('input', function () {
        clearTimeout(_debounce);
        var val = $.trim($(this).val());
        _debounce = setTimeout(function () {
            Filter.Name = val; PageNo = 1; _loadStaff();
        }, 1500);
    });

    // ── Pagination ─────────────────────────────────────────────────────
    $(document).on('click', '.staffPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _loadStaff(); }
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
            url: '/settings/users/toggleStatus', method: 'POST',
            data: { UserUID: uid, IsActive: newStatus, Filter: JSON.stringify(Filter), RowLimit: RowLimit, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
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
            url: '/settings/users/getUserDetail', method: 'POST',
            data: { UserUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
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
                    $('#UserCodeDisplay').val(d.UserCode || '');
                    $('#userLockedRow').removeClass('d-none');
                    $('#UserIsLocked').prop('checked', parseInt(d.IsLocked) === 1);
                    $('#lastLoginCard').removeClass('d-none');
                    $('#lastLoginDisplay').text(d.LastLoginOn || '—');
                    $('#pwdSetupInfo').addClass('d-none');
                    $('#UserEmail').prop('readonly', true);
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
            fd.append('RoleUID',   roleUID);
            fd.append('IsActive',  $('#UserIsActive').is(':checked') ? 1 : 0);
            fd.append('IsLocked',  $('#UserIsLocked').is(':checked') ? 1 : 0);
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
        fd.append(CsrfName, CsrfToken);

        $.ajax({
            url: '/settings/users/saveUser', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
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
            var docLabel = a.DocType ? ('<span class="badge bg-label-info me-1" style="font-size:.68rem;">' + $('<span>').text(a.DocType).html() + '</span>') : '';

            var $item = $('<div>', {
                'class'    : 'd-flex align-items-center gap-2 border rounded px-3 py-2 mb-2 bg-light user-attach-item',
                'data-uid' : uid,
                'style'    : 'font-size:.82rem;'
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
        fd.append(CsrfName, CsrfToken);

        var $btn = $(this).prop('disabled', true);
        $('#uploadAttachSpinner').removeClass('d-none');
        $('#uploadAttachIcon').addClass('d-none');

        $.ajax({
            url: '/settings/users/saveUserAttachment', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
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
            url: '/settings/users/deleteUserAttachment', method: 'POST',
            data: { AttachUID: attachUID, UserUID: userUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
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
