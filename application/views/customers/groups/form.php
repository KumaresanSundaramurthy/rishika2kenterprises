<?php defined('BASEPATH') or exit('No direct script access allowed');
$isEdit   = ($FormMode === 'edit');
$d        = $FormData;
$members  = $Members ?? [];
$saveUrl  = $isEdit ? '/customers/updateGroupData' : '/customers/addGroupData';
$pageHead = $isEdit ? 'Edit Customer Group' : 'Create Customer Group';
?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y pt-2">

                    <form id="CGroupForm" autocomplete="off" novalidate>
                        <?php if ($isEdit): ?>
                        <input type="hidden" name="GroupUID" value="<?php echo (int)($d->GroupUID ?? 0); ?>">
                        <?php endif; ?>

                        <div class="card mb-3">
                            <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bxs-layer" style="font-size:1.3rem;color:#9333ea;"></i>
                                    <h5 class="modal-title mb-0"><?php echo $pageHead; ?></h5>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-primary" id="btnSaveCGroup">
                                        <i class="bx bx-save me-1"></i><?php echo $isEdit ? 'Update' : 'Save'; ?>
                                    </button>
                                    <a href="/customers" class="btn btn-label-secondary">Cancel</a>
                                </div>
                            </div>

                            <div class="card-body card-body-form-static p-4">

                                <div class="card-header modal-header-center-sticky p-1 mb-3">
                                    <h5 class="modal-title mb-0">Group Details</h5>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-5">
                                        <label class="form-label">Group Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="GroupName" id="GroupName"
                                               maxlength="200" placeholder="e.g. ABC Tractors Group"
                                               value="<?php echo htmlspecialchars($d->GroupName ?? ''); ?>" required>
                                        <div class="invalid-feedback">Group name is required.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Group Code</label>
                                        <input type="text" class="form-control" name="GroupCode"
                                               maxlength="50" placeholder="e.g. ABC-GRP"
                                               value="<?php echo htmlspecialchars($d->GroupCode ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Group Type</label>
                                        <select class="form-select" name="GroupType" id="GroupType">
                                            <?php foreach ($GroupTypes as $type): ?>
                                                <option value="<?php echo htmlspecialchars($type); ?>"
                                                    <?php echo ($d->GroupType ?? 'Business Group') === $type ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" name="ContactPerson"
                                               maxlength="150" placeholder="Name"
                                               value="<?php echo htmlspecialchars($d->ContactPerson ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Mobile</label>
                                        <input type="text" class="form-control" name="Mobile"
                                               maxlength="20" placeholder="+91 9999 000 000"
                                               value="<?php echo htmlspecialchars($d->Mobile ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="Email"
                                               maxlength="150" placeholder="email@example.com"
                                               value="<?php echo htmlspecialchars($d->Email ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">GST No</label>
                                        <input type="text" class="form-control" name="GSTNo"
                                               maxlength="20" placeholder="27XXXXX..."
                                               value="<?php echo htmlspecialchars($d->GSTNo ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="City"
                                               maxlength="100" placeholder="City"
                                               value="<?php echo htmlspecialchars($d->City ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="State"
                                               maxlength="100" placeholder="State"
                                               value="<?php echo htmlspecialchars($d->State ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Country</label>
                                        <input type="text" class="form-control" name="Country"
                                               maxlength="100" placeholder="India"
                                               value="<?php echo htmlspecialchars($d->Country ?? 'India'); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="Address" rows="2"
                                                  placeholder="Group head office address"><?php echo htmlspecialchars($d->Address ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="Notes" rows="2"
                                                  placeholder="Internal notes about this group"><?php echo htmlspecialchars($d->Notes ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="card-header modal-header-center-sticky p-1 mb-3 d-flex align-items-center justify-content-between">
                                    <h5 class="modal-title mb-0">Group Members</h5>
                                    <small class="text-muted">Add existing customers · ★ marks primary contact</small>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-8">
                                        <select id="cgAddMemberSelect" class="form-select" style="width:100%;">
                                            <option value="">Search &amp; add customer...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-outline-primary w-100" id="btnAddMember">
                                            <i class="bx bx-user-plus me-1"></i>Add to Group
                                        </button>
                                    </div>
                                </div>

                                <div id="memberInputsContainer"></div>

                                <div id="cgMembersBox">
                                    <?php if (empty($members)): ?>
                                    <div class="text-center py-4 text-muted" id="cgNoMembersMsg">
                                        <i class="bx bx-user-plus fs-2 d-block mb-2"></i>
                                        <div style="font-size:.85rem;">No members yet. Search and add customers above.</div>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0" id="cgMembersTable">
                                            <thead style="background:#f8f9fa;">
                                                <tr style="font-size:.76rem;text-transform:uppercase;color:#566a7f;">
                                                    <th style="width:36px;"></th>
                                                    <th>Customer</th>
                                                    <th style="width:120px;">Area</th>
                                                    <th style="width:130px;">Mobile</th>
                                                    <th style="width:130px;text-align:right;">Balance</th>
                                                    <th style="width:90px;text-align:center;">Primary</th>
                                                    <th style="width:50px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="cgMemberRows">
                                                <?php foreach ($members as $m): ?>
                                                <?php $mUid = (int)$m->CustomerUID; ?>
                                                <tr data-uid="<?php echo $mUid; ?>">
                                                    <td><i class="bx bx-drag text-muted" style="cursor:grab;"></i></td>
                                                    <td class="fw-semibold" style="font-size:.85rem;"><?php echo htmlspecialchars($m->Name); ?></td>
                                                    <td style="font-size:.8rem;"><?php echo htmlspecialchars($m->Area ?? '—'); ?></td>
                                                    <td style="font-size:.8rem;"><?php echo htmlspecialchars($m->MobileNumber ?? '—'); ?></td>
                                                    <td class="text-end" style="font-size:.8rem;color:<?php echo ($m->BalanceType ?? '') === 'Credit' ? '#dc3545' : '#28a745'; ?>">
                                                        <?php echo number_format((float)($m->Balance ?? 0), 2); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-icon <?php echo $m->IsGroupPrimary ? 'btn-warning' : 'btn-outline-secondary'; ?> cg-set-primary"
                                                                data-uid="<?php echo $mUid; ?>" title="Set as primary">
                                                            <i class="bx bx-star"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger cg-remove-member"
                                                                data-uid="<?php echo $mUid; ?>" title="Remove">
                                                            <i class="bx bx-x"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <input type="hidden" name="MemberUIDs[]" value="<?php echo $mUid; ?>">
                                                <?php if ($m->IsGroupPrimary): ?>
                                                <input type="hidden" name="PrimaryUID" id="primaryUIDInput" value="<?php echo $mUid; ?>">
                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </form>

                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>
    </div>
</div>

<?php if (empty($members)): ?>
<input type="hidden" id="primaryUIDInput" value="">
<?php endif; ?>

<script>
(function () {
    'use strict';

    var _members   = <?php echo json_encode(array_map(function($m) {
        return [
            'uid'     => (int)$m->CustomerUID,
            'name'    => $m->Name,
            'area'    => $m->Area ?? '',
            'mobile'  => $m->MobileNumber ?? '',
            'balance' => (float)($m->Balance ?? 0),
            'balType' => $m->BalanceType ?? 'Debit',
            'primary' => (bool)$m->IsGroupPrimary,
        ];
    }, $members), JSON_UNESCAPED_UNICODE); ?>;
    var _primaryUID = parseInt($('#primaryUIDInput').val() || 0);

    $('#cgAddMemberSelect').select2({
        placeholder        : 'Search customer by name, mobile...',
        minimumInputLength : 1,
        allowClear         : true,
        width              : '100%',
        ajax: {
            url     : '/customers/searchCustomers',
            dataType: 'json',
            delay   : 300,
            data    : function (p) { return { term: p.term }; },
            processResults: function (d) {
                return { results: (d.Lists || []).map(function (c) {
                    return { id: c.id, text: c.text, name: c.name || c.text, area: c.area || '', mobile: c.mobile || '' };
                })};
            },
        },
        escapeMarkup: function (m) { return m; },
        templateResult: function (d) {
            if (!d.id) return d.text;
            return '<div style="font-size:.85rem;font-weight:600;">' + _esc(d.text) + '</div>';
        },
    });

    $('#btnAddMember').on('click', function () {
        var sel  = $('#cgAddMemberSelect');
        var data = sel.select2('data')[0];
        if (!data || !data.id) { toastr.warning('Please select a customer first.'); return; }
        var uid = parseInt(data.id);
        if (_members.some(function (m) { return m.uid === uid; })) { toastr.info('Already in group.'); return; }
        _members.push({ uid: uid, name: data.text, area: data.area || '', mobile: data.mobile || '', balance: 0, balType: 'Debit', primary: false });
        if (!_primaryUID) { _primaryUID = uid; _members[_members.length - 1].primary = true; }
        _renderMembers();
        sel.val(null).trigger('change');
    });

    function _renderMembers() {
        $('#memberInputsContainer').empty();
        if (!_members.length) {
            $('#cgMembersBox').html('<div class="text-center py-4 text-muted"><i class="bx bx-user-plus fs-2 d-block mb-2"></i><div style="font-size:.85rem;">No members yet.</div></div>');
            return;
        }
        var rows = _members.map(function (m) {
            var balCol = m.balType === 'Credit' ? '#dc3545' : '#28a745';
            var isPri  = (m.uid === _primaryUID);
            return '<tr data-uid="' + m.uid + '">' +
                '<td><i class="bx bx-drag text-muted" style="cursor:grab;"></i></td>' +
                '<td class="fw-semibold" style="font-size:.85rem;">' + _esc(m.name) + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.area || '—') + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.mobile || '—') + '</td>' +
                '<td class="text-end" style="font-size:.8rem;color:' + balCol + ';">' + parseFloat(m.balance).toFixed(2) + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-icon ' + (isPri ? 'btn-warning' : 'btn-outline-secondary') + ' cg-set-primary" data-uid="' + m.uid + '" title="Set as primary"><i class="bx bx-star"></i></button></td>' +
                '<td><button type="button" class="btn btn-sm btn-icon btn-outline-danger cg-remove-member" data-uid="' + m.uid + '" title="Remove"><i class="bx bx-x"></i></button></td>' +
            '</tr>';
        }).join('');
        $('#cgMembersBox').html('<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead style="background:#f8f9fa;"><tr style="font-size:.76rem;text-transform:uppercase;color:#566a7f;"><th style="width:36px;"></th><th>Customer</th><th style="width:120px;">Area</th><th style="width:130px;">Mobile</th><th style="width:130px;text-align:right;">Balance</th><th style="width:90px;text-align:center;">Primary</th><th style="width:50px;"></th></tr></thead><tbody>' + rows + '</tbody></table></div>');
        var inputs = _members.map(function (m) { return '<input type="hidden" name="MemberUIDs[]" value="' + m.uid + '">'; }).join('');
        inputs += '<input type="hidden" name="PrimaryUID" id="primaryUIDInput" value="' + (_primaryUID || '') + '">';
        $('#memberInputsContainer').html(inputs);
    }

    $(document).on('click', '.cg-set-primary', function () {
        _primaryUID = parseInt($(this).data('uid'));
        _members.forEach(function (m) { m.primary = (m.uid === _primaryUID); });
        _renderMembers();
    });

    $(document).on('click', '.cg-remove-member', function () {
        var uid = parseInt($(this).data('uid'));
        _members = _members.filter(function (m) { return m.uid !== uid; });
        if (_primaryUID === uid) {
            _primaryUID = _members.length ? _members[0].uid : 0;
            if (_members.length) _members[0].primary = true;
        }
        _renderMembers();
    });

    $('#btnSaveCGroup').on('click', function () {
        var groupName = $.trim($('#GroupName').val());
        if (!groupName) { $('#GroupName').addClass('is-invalid').focus(); return; }
        $('#GroupName').removeClass('is-invalid');
        var formData = $('#CGroupForm').serializeArray();
        formData.push({ name: CsrfName, value: CsrfToken });
        AjaxLoading = 0;
        $.ajax({
            url   : '<?php echo $saveUrl; ?>',
            method: 'POST',
            data  : formData,
            success: function (res) {
                AjaxLoading = 1;
                if (res.Error) { toastr.error(res.Message); return; }
                toastr.success(res.Message);
                setTimeout(function () {
                    window.location.href = res.GroupUID ? '/customers/groupDetail/' + res.GroupUID : '/customers';
                }, 800);
            },
            error: function () { AjaxLoading = 1; toastr.error('Request failed. Please try again.'); }
        });
    });

    function _esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    if (_members.length) { _renderMembers(); }
}());
</script>
