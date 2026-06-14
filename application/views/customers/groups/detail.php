<?php defined('BASEPATH') or exit('No direct script access allowed');
$g   = $GroupData;
$ov  = $GroupOverview ?? new stdClass();
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$uid = (int)$g->GroupUID;

$typeColors = [
    'Business Group'  => ['bg' => '#f0efff', 'c' => '#696cff'],
    'Branch Group'    => ['bg' => '#e0f7fa', 'c' => '#0097a7'],
    'Family Group'    => ['bg' => '#fce8ff', 'c' => '#9333ea'],
    'Corporate Group' => ['bg' => '#e8f5e9', 'c' => '#2e7d32'],
    'Dealer Network'  => ['bg' => '#fff3e0', 'c' => '#ef6c00'],
    'Franchise Group' => ['bg' => '#fce4ec', 'c' => '#c62828'],
    'Custom'          => ['bg' => '#f5f5f5', 'c' => '#616161'],
];
$tc = $typeColors[$g->GroupType ?? ''] ?? ['bg' => '#f5f5f5', 'c' => '#616161'];
?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle ?? 'Customer Group',
                    'pageDescription' => '',
                ]); ?>

                <div class="container-xxl flex-grow-1 py-3">

                    <div class="card mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar" style="width:56px;height:56px;background:<?php echo $tc['bg']; ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                        <i class="bx bxs-layer" style="font-size:1.8rem;color:<?php echo $tc['c']; ?>;"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo htmlspecialchars($g->GroupName); ?></h4>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <?php if ($g->GroupCode): ?>
                                            <span class="badge bg-label-secondary" style="font-size:.72rem;font-family:monospace;"><?php echo htmlspecialchars($g->GroupCode); ?></span>
                                            <?php endif; ?>
                                            <span class="badge" style="background:<?php echo $tc['bg']; ?>;color:<?php echo $tc['c']; ?>;font-size:.72rem;font-weight:600;"><?php echo htmlspecialchars($g->GroupType); ?></span>
                                            <span class="badge <?php echo $g->IsActive ? 'bg-label-success' : 'bg-label-danger'; ?>" style="font-size:.7rem;">
                                                <?php echo $g->IsActive ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/customers/groupEdit/<?php echo $uid; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bx bx-edit me-1"></i>Edit
                                    </a>
                                    <a href="/customers" class="btn btn-outline-secondary btn-sm">
                                        <i class="bx bx-arrow-back me-1"></i>Back
                                    </a>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 text-center" style="background:#f8f9fa;">
                                        <div style="font-size:1.5rem;font-weight:700;color:#9333ea;"><?php echo (int)($ov->MemberCount ?? 0); ?></div>
                                        <div class="text-muted" style="font-size:.76rem;">Members</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 text-center" style="background:#f0fdf4;">
                                        <div style="font-size:1.1rem;font-weight:700;color:#16a34a;"><?php echo $cur . ' ' . number_format((float)($ov->TotalReceivable ?? 0), $dec); ?></div>
                                        <div class="text-muted" style="font-size:.76rem;">Total Receivable</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 text-center" style="background:#fff5f5;">
                                        <div style="font-size:1.1rem;font-weight:700;color:#dc2626;"><?php echo $cur . ' ' . number_format((float)($ov->TotalPayable ?? 0), $dec); ?></div>
                                        <div class="text-muted" style="font-size:.76rem;">Total Payable</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 text-center" style="background:#f5f3ff;">
                                        <div style="font-size:.85rem;font-weight:600;color:#7c3aed;"><?php echo $g->ContactPerson ? htmlspecialchars($g->ContactPerson) : '—'; ?></div>
                                        <div class="text-muted" style="font-size:.76rem;"><?php echo $g->Mobile ? htmlspecialchars($g->Mobile) : 'Contact Person'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0">
                            <ul class="nav nav-tabs card-header-tabs" id="cgDetailTabs" role="tablist" style="padding:0 16px;">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-members">
                                        <i class="bx bxs-group me-1"></i>Members <span class="badge bg-label-primary ms-1"><?php echo (int)($ov->MemberCount ?? 0); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-outstanding" id="tabOutstandingLink">
                                        <i class="bx bx-wallet me-1"></i>Outstanding
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content p-0">

                            <div class="tab-pane fade show active" id="tab-members">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                                        <thead class="r2k-thead">
                                            <tr>
                                                <th>#</th>
                                                <th>Customer Name</th>
                                                <th>Area</th>
                                                <th>Mobile</th>
                                                <th class="text-end">Balance</th>
                                                <th style="width:80px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($Members)): ?>
                                            <?php foreach ($Members as $i => $m): ?>
                                            <tr>
                                                <td class="text-muted"><?php echo $i + 1; ?></td>
                                                <td>
                                                    <div class="fw-semibold">
                                                        <?php echo htmlspecialchars($m->Name); ?>
                                                        <?php if ($m->IsGroupPrimary): ?>
                                                        <span class="badge bg-label-warning ms-1" style="font-size:.64rem;">Primary</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($m->Area ?? '—'); ?></td>
                                                <td><?php echo htmlspecialchars($m->MobileNumber ?? '—'); ?></td>
                                                <td class="text-end" style="font-weight:600;color:<?php echo ($m->BalanceType ?? '') === 'Credit' ? '#dc3545' : '#28a745'; ?>">
                                                    <?php echo $cur . ' ' . number_format((float)($m->Balance ?? 0), $dec); ?>
                                                    <div class="text-muted fw-normal" style="font-size:.7rem;"><?php echo $m->BalanceType === 'Credit' ? 'Payable' : 'Receivable'; ?></div>
                                                </td>
                                                <td>
                                                    <a href="/customers?search=<?php echo urlencode($m->Name); ?>" class="btn btn-sm btn-icon btn-outline-info" title="View Customer">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr><td colspan="6" class="text-center py-4 text-muted">No members in this group.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-outstanding">
                                <div id="cgOutstandingContent" class="p-3">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary spinner-border-sm"></div>
                                        <div class="text-muted mt-2" style="font-size:.82rem;">Loading outstanding…</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var _groupUID    = <?php echo $uid; ?>;
    var _currency    = '<?php echo addslashes($cur); ?>';
    var _dec         = <?php echo $dec; ?>;
    var _outstanding = null;

    $('#tabOutstandingLink').one('shown.bs.tab', function () {
        if (_outstanding !== null) { _renderOutstanding(_outstanding); return; }
        $.ajax({
            url   : '/customers/getGroupOutstanding/' + _groupUID,
            method: 'GET',
            data  : { [CsrfName]: CsrfToken },
            success: function (res) {
                if (res.Error) { $('#cgOutstandingContent').html('<div class="text-danger p-3">' + res.Message + '</div>'); return; }
                _outstanding = res.Data || [];
                _renderOutstanding(_outstanding);
            },
            error: function () {
                $('#cgOutstandingContent').html('<div class="text-danger p-3">Failed to load outstanding data.</div>');
            }
        });
    });

    function _renderOutstanding(rows) {
        if (!rows.length) {
            $('#cgOutstandingContent').html('<div class="text-center py-4 text-muted">No outstanding data found.</div>');
            return;
        }
        var totalReceivable = 0, totalPayable = 0;
        var tableRows = rows.map(function (r, i) {
            var bal  = parseFloat(r.Balance || 0);
            var type = r.BalanceType || 'Debit';
            if (type === 'Debit')  totalReceivable += bal;
            if (type === 'Credit') totalPayable    += bal;
            var balColor = type === 'Credit' ? '#dc3545' : '#28a745';
            return '<tr>' +
                '<td class="text-muted">' + (i + 1) + '</td>' +
                '<td><div class="fw-semibold">' + _esc(r.Name) + (r.IsGroupPrimary ? ' <span class="badge bg-label-warning" style="font-size:.62rem;">Primary</span>' : '') + '</div>' +
                     '<div class="text-muted" style="font-size:.74rem;">' + _esc(r.Area || '') + '</div></td>' +
                '<td>' + _esc(r.MobileNumber || '—') + '</td>' +
                '<td class="text-end" style="font-weight:600;color:' + balColor + ';">' +
                    _currency + ' ' + bal.toFixed(_dec) +
                    '<div class="text-muted fw-normal" style="font-size:.7rem;">' + (type === 'Credit' ? 'Payable' : 'Receivable') + '</div>' +
                '</td>' +
            '</tr>';
        }).join('');
        var totHtml = '';
        if (totalReceivable > 0) totHtml += '<span class="me-3" style="font-weight:700;color:#16a34a;">Receivable: ' + _currency + ' ' + totalReceivable.toFixed(_dec) + '</span>';
        if (totalPayable > 0)    totHtml += '<span style="font-weight:700;color:#dc2626;">Payable: ' + _currency + ' ' + totalPayable.toFixed(_dec) + '</span>';
        var html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size:.85rem;">' +
            '<thead class="r2k-thead"><tr><th style="width:40px;">#</th><th>Customer</th><th style="width:130px;">Mobile</th><th class="text-end" style="width:160px;">Balance</th></tr></thead>' +
            '<tbody>' + tableRows + '</tbody>' +
            '<tfoot><tr><td colspan="4" class="text-end py-3 border-top" style="background:#f8f9fa;">' + totHtml + '</td></tr></tfoot>' +
            '</table></div>';
        $('#cgOutstandingContent').html(html);
    }

    function _esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
}());
</script>
