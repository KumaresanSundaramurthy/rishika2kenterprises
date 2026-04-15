<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<style>
/* ── Action bar ── */
.mod-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px 10px 0 0;
    border-bottom: none;
    gap: 10px;
    flex-wrap: wrap;
}
.mod-title {
    font-size: .95rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0;
}
.mod-title i { color: #2563eb; font-size: 1.1rem; }

.mod-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

/* search */
.mod-search-wrap { position: relative; }
.mod-search-wrap input {
    height: 34px; width: 200px;
    padding: 0 30px 0 32px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: .82rem; color: #1e293b;
    outline: none; background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.mod-search-wrap input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.mod-search-wrap .si { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .9rem; pointer-events: none; }
.mod-search-wrap #clearSearch { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; font-size: .95rem; }

/* icon btn */
.mod-icon-btn {
    height: 34px; width: 34px; border-radius: 7px;
    border: 1.5px solid #e2e8f0; background: #fff;
    color: #64748b; display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 1rem; cursor: pointer;
    transition: all .15s; text-decoration: none;
}
.mod-icon-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }

/* actions dropdown btn */
.mod-dd-btn {
    height: 34px; padding: 0 12px;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    background: #fff; color: #374151;
    font-size: .82rem; font-weight: 500;
    display: inline-flex; align-items: center; gap: 5px;
    cursor: pointer; transition: background .15s;
}
.mod-dd-btn:hover { background: #f8fafc; }
.mod-dd-btn::after { display: none; }

/* create btn */
.mod-create-btn {
    height: 34px; padding: 0 14px;
    background: #2563eb; color: #fff;
    border: none; border-radius: 7px;
    font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none; transition: background .15s;
}
.mod-create-btn:hover { background: #1d4ed8; color: #fff; }

/* dropdown menu */
.mod-actions .dropdown-menu {
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 6px 20px rgba(0,0,0,.09);
    font-size: .82rem; min-width: 160px; padding: 5px;
}
.mod-actions .dropdown-item {
    border-radius: 6px; padding: 7px 11px;
    display: flex; align-items: center; gap: 7px; color: #374151;
}
.mod-actions .dropdown-item:hover { background: #eff6ff; color: #2563eb; }
.mod-actions .dropdown-item.text-danger:hover { background: #fef2f2; color: #dc2626; }

/* ── Table card ── */
.mod-table-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 10px 10px;
    overflow: hidden;
}
.mod-table-wrap { overflow-x: auto; }

.mod-table {
    width: 100%; border-collapse: collapse;
    font-size: .84rem; white-space: nowrap;
}

/* Header — soft multi-color gradient */
.mod-table thead tr {
    background: linear-gradient(90deg, #ede9fe 0%, #f3e8ff 20%, #fce7f3 40%, #fff7ed 60%, #ecfdf5 80%, #eff6ff 100%);
}
.mod-table thead th {
    background: transparent;
    color: #4c1d95;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    padding: 11px 14px;
    border-bottom: 2px solid rgba(109,40,217,.15);
    border-right: 1px solid rgba(109,40,217,.08);
    position: sticky; top: 0; z-index: 2;
    white-space: nowrap;
}
.mod-table thead th:last-child { border-right: none; }

.mod-table thead th.th-chk { width: 40px; text-align: center; }
.mod-table thead th.th-sno { width: 48px; text-align: center; }
.mod-table thead th.th-act { width: 84px; text-align: center; }

/* Zebra rows */
.mod-table tbody tr:nth-child(odd)  td { background: #ffffff; }
.mod-table tbody tr:nth-child(even) td { background: #f8fafc; }

.mod-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155; vertical-align: middle;
    max-width: 200px; overflow: hidden; text-overflow: ellipsis;
    transition: background .12s;
}
.mod-table tbody tr:last-child td { border-bottom: none; }

/* Hover — left blue accent + soft blue wash */
.mod-table tbody tr:hover td {
    background: #eff6ff !important;
    color: #1e40af;
}
.mod-table tbody tr:hover td:first-child {
    border-left: 3px solid #2563eb;
}

/* Selected row */
.mod-table tbody tr.row-sel td {
    background: #dbeafe !important;
    color: #1d4ed8;
}
.mod-table tbody tr.row-sel td:first-child {
    border-left: 3px solid #1d4ed8;
}

.mod-table td.td-chk, .mod-table td.td-sno { text-align: center; }
.mod-table td.td-sno { color: #94a3b8; font-size: .78rem; }
.mod-table td.td-act { text-align: center; }

/* row actions */
.row-acts { display: flex; align-items: center; justify-content: center; gap: 4px; }
.btn-re {
    width: 27px; height: 27px; border-radius: 6px;
    background: #fffbeb; color: #d97706;
    border: 1px solid #fde68a;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .88rem; text-decoration: none; transition: background .15s;
}
.btn-re:hover { background: #fde68a; color: #92400e; }
.btn-rd {
    width: 27px; height: 27px; border-radius: 6px;
    background: #fef2f2; color: #dc2626;
    border: 1px solid #fecaca;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .88rem; cursor: pointer; transition: background .15s;
}
.btn-rd:hover { background: #fecaca; color: #991b1b; }

/* sort icon */
.sort-ic { color: #cbd5e1; font-size: .8rem; cursor: pointer; margin-left: 3px; vertical-align: middle; }
.sort-ic.on { color: #2563eb; }

/* pagination strip */
.mod-pag { padding: 9px 16px; border-top: 1px solid #e2e8f0; background: #fafbfc; }

/* empty state */
.mod-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; gap: 12px; }
.mod-empty img { max-height: 150px; opacity: .8; }
.mod-empty p { color: #64748b; font-size: .85rem; margin: 0; }

/* checkbox */
.form-check-input:checked { background-color: #2563eb; border-color: #2563eb; }
</style>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <?php $this->load->view('common/navbar_view'); ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Single action bar row -->
                    <div class="mod-action-bar">
                        <h6 class="mod-title">
                            <i class="bx bxs-group"></i> Customers
                        </h6>
                        <div class="mod-actions">
                            <!-- Search -->
                            <div class="mod-search-wrap">
                                <i class="bx bx-search si"></i>
                                <input type="text" class="SearchDetails" id="SearchDetails" name="SearchDetails" placeholder="Search customers…" />
                                <i class="bx bx-x d-none" id="clearSearch"></i>
                            </div>
                            <!-- Refresh -->
                            <a href="javascript:void(0);" class="mod-icon-btn PageRefresh" title="Refresh Page"><i class="bx bx-refresh"></i></a>
                            <!-- Settings -->
                            <a href="javascript:void(0);" id="btnPageSettings" class="mod-icon-btn" title="Column Settings"><i class="bx bx-cog"></i></a>
                            <!-- Actions dropdown -->
                            <div class="dropdown" id="ActionsDD-Div">
                                <button class="mod-dd-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-slider-alt"></i> Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="d-none" id="CloneOption">
                                        <a class="dropdown-item" href="javascript:void(0);" id="btnClone"><i class="bx bx-duplicate"></i> Clone</a>
                                    </li>
                                    <li class="d-none" id="DeleteOption">
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash"></i> Delete</a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportPrint"><i class="bx bx-printer"></i> Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportCSV"><i class="bx bx-file"></i> CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportExcel"><i class="bx bxs-file-export"></i> Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportPDF"><i class="bx bxs-file-pdf"></i> PDF</a></li>
                                </ul>
                            </div>
                            <!-- Create -->
                            <a href="/customers/create" class="mod-create-btn"><i class="bx bx-plus"></i> Create Customer</a>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="mod-table-card">
                        <div class="mod-table-wrap">
                            <table class="mod-table MainviewTable" id="CustomersTable">
                                <thead>
                                    <tr>
                                        <th class="th-chk">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox customerHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="th-sno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">#</th>
                                        <?php foreach (array_column($ModColumnData, 'DisplayName') as $ItemKey => $ItemVal) { ?>
                                            <th <?php echo $ModColumnData[$ItemKey]->MainPageColumnAddon; ?>>
                                                <?php echo $ItemVal; ?>
                                                <?php if ($ModColumnData[$ItemKey]->MPSortApplicable == 1) { ?>
                                                    <i class="bx bx-sort-alt-2 sort-ic"></i>
                                                <?php } ?>
                                            </th>
                                        <?php } ?>
                                        <th class="th-act">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mod-pag">
                            <div class="row justify-content-between CustomersPagination" id="CustomersPagination">
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/customers.js"></script>
<script src="/js/common/pagecheckbox.js"></script>

<script>
let ModuleId = <?php echo $ModuleId; ?>;
const ModuleTable  = '#CustomersTable';
const ModulePag    = '.CustomersPagination';
const ModuleHeader = '.customerHeaderCheck';
const ModuleRow    = '.customerCheck';
const ModuleFileName  = 'Customer_Data';
const ModuleSheetName = 'Customer';
const previewName     = 'Customer Details';
let sortState = 0;

$(function() {
    'use strict';

    $('#SearchDetails').val('');
    $(ModuleRow).prop('checked', false).trigger('change');

    baseExportFunctions();
    basePaginationFunc(ModulePag, getCustomersDetails);
    baseRefreshPageFunc('.PageRefresh', getCustomersDetails);
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(document).on('change', ModuleRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
    });

    $(document).on('click', ModuleRow, function() {
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        $('#CloneOption').addClass('d-none');
        if (SelectedUIDs.length == 1) $('#CloneOption').removeClass('d-none');
        MultipleDeleteOption();
    });

    $('#btnClone').click(function(e) {
        e.preventDefault();
        if (SelectedUIDs.length == 1)
            window.location.href = '/customers/' + SelectedUIDs[0] + '/clone';
    });

    $('.SearchDetails').keyup(inputDelay(function() {
        PageNo = 0;
        let s = $('#SearchDetails').val();
        if (s.length >= 3) {
            SelectedUIDs = [];
            delete Filter['SearchAllData'];
            $('#clearSearch').removeClass('d-none');
            Filter['SearchAllData'] = s;
            $('#SearchDetails').blur();
            getCustomersDetails(PageNo, RowLimit, Filter);
        }
    }, 500));

    $('#clearSearch').click(function(e) {
        e.preventDefault();
        let s = $('#SearchDetails').val();
        $('#SearchDetails').val('');
        $('#clearSearch').addClass('d-none');
        if ($.trim(s) != '') {
            PageNo = 0; SelectedUIDs = [];
            delete Filter['SearchAllData'];
            $('#SearchDetails').blur();
            getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });

    $(document).on('click', '.DeleteCustomer', function(e) {
        e.preventDefault();
        let id = $(this).data('customeruid');
        if (!id) return;
        Swal.fire({
            title: 'Delete this customer?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(r => { if (r.isConfirmed) deleteCustomer(id); });
    });

    $('#btnDelete').click(function(e) {
        e.preventDefault();
        if (!SelectedUIDs.length) return;
        Swal.fire({
            title: `Delete ${SelectedUIDs.length} customer(s)?`,
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete all',
        }).then(r => { if (r.isConfirmed) deleteMultipleCustomers(); });
    });

    $(document).on('click', '.name-sortable', function(e) {
        e.preventDefault();
        sortState = (sortState + 1) % 3;
        const ic = $(this).find('.sort-ic');
        ic.removeClass('bx-sort-alt-2 bx-up-arrow-alt bx-down-arrow-alt on');
        if (sortState == 1) {
            ic.addClass('bx-up-arrow-alt on'); Filter['NameSorting'] = 1;
        } else if (sortState == 2) {
            ic.addClass('bx-down-arrow-alt on'); Filter['NameSorting'] = 2;
        } else {
            ic.addClass('bx-sort-alt-2'); delete Filter['NameSorting'];
        }
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

});
</script>
