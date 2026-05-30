<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!isset($ModAllCount))  { $ModAllCount  = 0; }
if (!isset($ModRowData))   { $ModRowData   = ''; }
if (!isset($ModPagination)){ $ModPagination = ''; }
?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Page Header ── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#ede9fe;">
                                <i class="bx bx-user" style="color:#7c3aed;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Users'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary me-1" id="addUserBtn">
                            <i class="bx bx-plus me-1"></i>Create User
                        </button>
                    </div>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" role="tablist">
                                <li class="nav-item"><a class="nav-link active usr-status-tab" data-status="All"      href="javascript:void(0);">All      <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link usr-status-tab"        data-status="Active"   href="javascript:void(0);">Active   <span class="usr-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link usr-status-tab"        data-status="Inactive" href="javascript:void(0);">Inactive <span class="usr-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchUserData" placeholder="Name, email or username...">
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="usersTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox userHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="table-serialno <?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>" style="width:44px">S.No</th>
                                        <th>Name</th>
                                        <th>Email / Mobile</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center usersPagination" id="usersPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="usersStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center usersPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
            <?php $this->load->view('users/modals/user'); ?>
            <?php $this->load->view('common/form/address_form'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/common/address.js"></script>
<script>
var CsrfName        = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken       = '<?php echo $this->security->get_csrf_hash(); ?>';
var PageNo          = 1;
var RowLimit        = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>;
var Filter          = { Status: 'All' };
var OrgCountryISO2  = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;

// Override address modal title labels for user form context
var _origOpenAddressModal = openAddressModal;
openAddressModal = function (addrType) {
    _origOpenAddressModal(addrType);
    $('#addrModalTitle').text(addrType == 1 ? 'Current Address' : 'Permanent Address');
};

$(function () {
    'use strict';

    // ── Sticky pagination ──────────────────────────────────────────────
    var $staticPag = $('#usersPagination');
    var $stickyPag = $('#usersStickyPagination');

    function _syncSticky() { $stickyPag.find('.usersPagination').html($staticPag.html()); }
    function _toggleSticky() {
        if (!$staticPag.length) return;
        var r = $staticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) $stickyPag.stop(true,true).fadeOut(150);
        else { _syncSticky(); $stickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleSticky);
    _toggleSticky();

    // ── List refresh ───────────────────────────────────────────────────
    function _renderList(resp) {
        $('#usersTable tbody').html(resp.RecordHtmlData);
        $('.usersPagination').html(resp.Pagination);
        var count = resp.TotalCount || 0;
        $('.usr-status-tab.active .trans-tab-count').text(count > 0 ? count : '').removeClass('d-none');
        _syncSticky();
    }

    function _loadUsers() {
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
    $(document).on('click', '.usr-status-tab', function (e) {
        e.preventDefault();
        $('.usr-status-tab').removeClass('active');
        $(this).addClass('active');
        Filter.Status = $(this).data('status') || 'All';
        PageNo = 1; _loadUsers();
    });

    // ── Refresh ────────────────────────────────────────────────────────
    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault(); PageNo = 1; _loadUsers();
    });

    // ── Search ────────────────────────────────────────────────────────
    var _debounceTimer;
    $('#searchUserData').on('keyup', function () {
        clearTimeout(_debounceTimer);
        var val = $.trim($(this).val());
        _debounceTimer = setTimeout(function () {
            Filter.Name = val; PageNo = 1; _loadUsers();
        }, 400);
    });

    // ── Pagination ─────────────────────────────────────────────────────
    $(document).on('click', '.usersPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _loadUsers(); }
    });

    // ── Header checkbox ────────────────────────────────────────────────
    $(document).on('change', '.userHeaderCheck', function () {
        $('.userCheck').prop('checked', $(this).is(':checked'));
    });

    // ── Status toggle ──────────────────────────────────────────────────
    $(document).on('click', '.usr-status-toggle', function () {
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
            },
            error: function () { showToastNotification('Request failed. Please try again.', 'error'); }
        });
    });

    // ── Reset modal to blank Add state ─────────────────────────────────
    function _resetModal() {
        $('#userModal').find('input[type=text], input[type=email]').val('').prop('readonly', false);
        $('#UserModalUID').val(0);
        $('#UserRoleUID').val('');
        // Restore defaults cleared by blanket .val('')
        $('#UserCountryCode').val('+91');
        $('#UserCountryISO2').val('IN');
        // Switches
        $('#UserIsActive').prop('checked', true);
        $('#UserIsLocked').prop('checked', false);
        // Address popups
        if (typeof resetAddrData === 'function') resetAddrData();
        // Create-only vs edit-only sections
        $('#pwdSetupInfo').removeClass('d-none');
        $('#userCodeWrap').addClass('d-none');
        $('#userLockedRow').addClass('d-none');
        $('#lastLoginCard').addClass('d-none');
    }

    // ── Open Add modal ──────────────────────────────────────────────────
    function _openAddModal() {
        _resetModal();
        $('#userModalTitle').text('Add User');
        $('#userModal').modal('show');
    }

    $(document).on('click', '#addUserBtn, #addUserBtnEmpty', _openAddModal);

    // ── Open Edit modal ─────────────────────────────────────────────────
    $(document).on('click', '.userEditBtn', function () {
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

                $('#userModalTitle').text('Edit User');
                $('#UserModalUID').val(d.UserUID);

                // Personal fields
                $('#UserFirstName').val(d.FirstName   || '');
                $('#UserLastName').val(d.LastName     || '');
                $('#UserUsername').val(d.UserName     || '');
                $('#UserEmail').val(d.EmailAddress    || '');
                $('#UserMobile').val(d.MobileNumber   || '');
                $('#UserCountryCode').val(d.CountryCode || '+91');
                $('#UserCountryISO2').val(d.CountryISO2 || 'IN');

                // Account settings
                $('#UserRoleUID').val(d.RoleUID || '');
                $('#UserIsActive').prop('checked', parseInt(d.IsActive) === 1);

                // Edit-only elements
                $('#userCodeWrap').removeClass('d-none');
                $('#UserCodeDisplay').val(d.UserCode || '');
                $('#userLockedRow').removeClass('d-none');
                $('#UserIsLocked').prop('checked', parseInt(d.IsLocked) === 1);
                $('#lastLoginCard').removeClass('d-none');
                $('#lastLoginDisplay').text(d.LastLoginOn || '—');
                $('#pwdSetupInfo').addClass('d-none');

                // Lock username & email on edit — prevent accidental changes
                $('#UserUsername').prop('readonly', true);
                $('#UserEmail').prop('readonly', true);

                // Current address — populate popup summary card
                if (curr.AddressLine1 || curr.City || curr.State || curr.PinCode) {
                    billingAddrData = {
                        UID: curr.AddressUID || 0,
                        Line1: curr.AddressLine1 || '', Line2: curr.AddressLine2 || '',
                        Pincode: curr.PinCode || '',
                        StateId: '', StateName: curr.State || '', StateISO2: '',
                        CityId: '', CityName: curr.City || ''
                    };
                    renderAddrSummary(1, billingAddrData);
                }

                // Permanent address — populate popup summary card
                if (perm.AddressLine1 || perm.City || perm.State || perm.PinCode) {
                    shippingAddrData = {
                        UID: perm.AddressUID || 0,
                        Line1: perm.AddressLine1 || '', Line2: perm.AddressLine2 || '',
                        Pincode: perm.PinCode || '',
                        StateId: '', StateName: perm.State || '', StateISO2: '',
                        CityId: '', CityName: perm.City || ''
                    };
                    renderAddrSummary(2, shippingAddrData);
                }

                $('#userModal').modal('show');
            }
        });
    });

    // ── Save user ───────────────────────────────────────────────────────
    $(document).on('click', '#saveUserBtn', function () {
        var uid       = parseInt($('#UserModalUID').val()) || 0;
        var isEdit    = uid > 0;
        var firstName = $.trim($('#UserFirstName').val());
        var username  = $.trim($('#UserUsername').val());
        var email     = $.trim($('#UserEmail').val());
        var roleUID   = $('#UserRoleUID').val();

        if (!firstName) { showToastNotification('First name is required.', 'error'); return; }
        if (!isEdit && !username) { showToastNotification('Username is required.', 'error'); return; }
        if (!isEdit && !email)    { showToastNotification('Email address is required.', 'error'); return; }
        if (!roleUID)   { showToastNotification('Please select a role.', 'error'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#saveUserSpinner').removeClass('d-none');
        $('#saveUserIcon').addClass('d-none');

        var fd = new FormData();
        fd.append('UserUID',     uid);
        fd.append('FirstName',   firstName);
        fd.append('LastName',    $.trim($('#UserLastName').val()));
        // Username & Email are readonly on edit — only send for new users
        if (!isEdit) {
            fd.append('UserName', username);
            fd.append('Email',    email);
        }
        fd.append('Mobile',      $.trim($('#UserMobile').val()));
        fd.append('RoleUID',     roleUID);
        fd.append('IsActive',    $('#UserIsActive').is(':checked') ? 1 : 0);
        fd.append('IsLocked',    $('#UserIsLocked').is(':checked') ? 1 : 0);
        fd.append('CountryCode', $.trim($('#UserCountryCode').val()) || '+91');
        fd.append('CountryISO2', $('#UserCountryISO2').val() || 'IN');
        // Current address from popup
        if (billingAddrData) {
            fd.append('CurrAddressLine1', billingAddrData.Line1    || '');
            fd.append('CurrAddressLine2', billingAddrData.Line2    || '');
            fd.append('CurrPinCode',      billingAddrData.Pincode  || '');
            fd.append('CurrState',        billingAddrData.StateName|| '');
            fd.append('CurrCity',         billingAddrData.CityName || '');
        }
        // Permanent address from popup
        if (shippingAddrData) {
            fd.append('PermAddressLine1', shippingAddrData.Line1    || '');
            fd.append('PermAddressLine2', shippingAddrData.Line2    || '');
            fd.append('PermPinCode',      shippingAddrData.Pincode  || '');
            fd.append('PermState',        shippingAddrData.StateName|| '');
            fd.append('PermCity',         shippingAddrData.CityName || '');
        }
        // Send deleted address UIDs for soft-delete
        if (typeof delAddrData !== 'undefined' && delAddrData.length > 0) {
            fd.append('DelAddrUIDs', delAddrData.join(','));
        }
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


});
</script>
