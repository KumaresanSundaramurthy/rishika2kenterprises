<?php defined('BASEPATH') or exit('No direct script access allowed');
$_stats    = $this->pageData['TodoStats']  ?? ['total' => 0, 'active' => 0, 'overdue' => 0, 'today' => 0, 'completed' => 0];
$_userList = $this->pageData['UserList']   ?? [];
$_myUID    = (int)($this->pageData['JwtData']->User->UserUID ?? 0);
?>
<?php $this->load->view('common/header'); ?>

<style>
.todo-pri{display:inline-block;padding:2px 9px;border-radius:5px;font-size:.70rem;font-weight:700;letter-spacing:.02em;white-space:nowrap}
.todo-pri-low    {background:#f1f5f9;color:#475569}
.todo-pri-medium {background:#dbeafe;color:#1d4ed8}
.todo-pri-high   {background:#ffedd5;color:#c2410c}
.todo-pri-urgent {background:#fee2e2;color:#b91c1c}
.todo-stat-badge{display:inline-block;padding:2px 9px;border-radius:5px;font-size:.70rem;font-weight:600;white-space:nowrap}
.todo-stat-open      {background:#dcfce7;color:#15803d}
.todo-stat-inprogress{background:#dbeafe;color:#1d4ed8}
.todo-stat-onhold    {background:#fef9c3;color:#854d0e}
.todo-stat-completed {background:#f1f5f9;color:#64748b}
.todo-stat-cancelled {background:#fee2e2;color:#991b1b}
.todo-due-overdue{color:#dc2626;font-weight:600}
.todo-due-today  {color:#ea580c;font-weight:600}
.todo-due-soon   {color:#ca8a04}
.todo-ref-chip{display:inline-block;padding:1px 7px;border-radius:4px;font-size:.68rem;font-weight:600;background:#e0e7ff;color:#4338ca;margin-top:2px}
.todo-done-cb{width:16px;height:16px;cursor:pointer;accent-color:#22c55e;flex-shrink:0}
.todo-title-wrap{display:flex;flex-direction:column;gap:2px}
.todo-title{font-weight:500;font-size:.83rem;color:#1e293b}
.todo-title-done{text-decoration:line-through;color:#94a3b8}
.todo-desc{font-size:.73rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px}
.todo-tab-bar{display:flex;gap:4px;padding:10px 14px 0;border-bottom:1px solid #e2e8f0;background:#fff;flex-wrap:wrap}
.todo-tab-btn{border:none;background:none;padding:7px 14px;font-size:.80rem;font-weight:500;color:#64748b;border-bottom:2px solid transparent;cursor:pointer;transition:color .15s,border-color .15s;white-space:nowrap}
.todo-tab-btn:hover{color:#334155}
.todo-tab-btn.active{color:#4f46e5;border-bottom-color:#4f46e5;font-weight:600}
.todo-tab-badge{display:inline-block;padding:1px 6px;border-radius:10px;font-size:.65rem;font-weight:700;background:#e0e7ff;color:#4f46e5;margin-left:4px;vertical-align:middle}
.todo-tab-badge-red{background:#fee2e2;color:#b91c1c}
.todo-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.todo-stat-icon{width:38px;height:38px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.todo-stat-icon-indigo{background:#e0e7ff;color:#4f46e5}
.todo-stat-icon-green {background:#dcfce7;color:#16a34a}
.todo-stat-icon-orange{background:#ffedd5;color:#ea580c}
.todo-stat-icon-blue  {background:#dbeafe;color:#2563eb}
.todo-stat-body{flex:1}
.todo-stat-label{font-size:.70rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.04em}
.todo-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px}
.todo-my-toggle{display:flex;align-items:center;gap:6px;font-size:.80rem;color:#475569;cursor:pointer;user-select:none}
.todo-my-toggle input{accent-color:#4f46e5;width:14px;height:14px}
.todo-pri-chip-wrap{display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.todo-pri-chip{border:1px solid #e2e8f0;background:#f8fafc;color:#475569;font-size:.75rem;padding:3px 10px;border-radius:20px;cursor:pointer;transition:all .15s;white-space:nowrap}
.todo-pri-chip.active{border-color:#4f46e5;background:#e0e7ff;color:#4f46e5;font-weight:600}
.todo-status-select{font-size:.78rem;border:1px solid #e2e8f0;border-radius:5px;padding:2px 6px;color:#374151;background:#fff;cursor:pointer}
@media(prefers-color-scheme:dark){
    .todo-tab-bar,.todo-stat-card{background:#1e293b;border-color:#334155}
    .todo-tab-btn{color:#94a3b8}.todo-tab-btn:hover{color:#f1f5f9}.todo-tab-btn.active{color:#818cf8;border-bottom-color:#818cf8}
    .todo-stat-val{color:#f1f5f9}
    .todo-title{color:#e2e8f0}
    .todo-pri-chip{background:#0f172a;border-color:#334155;color:#94a3b8}
    .todo-pri-chip.active{background:#312e81;border-color:#4f46e5;color:#a5b4fc}
    .todo-status-select{background:#1e293b;border-color:#334155;color:#e2e8f0}
}
:root[data-theme="dark"] .todo-tab-bar{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .todo-tab-btn{color:#94a3b8}
:root[data-theme="dark"] .todo-tab-btn.active{color:#818cf8;border-bottom-color:#818cf8}
:root[data-theme="dark"] .todo-stat-card{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .todo-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .todo-title{color:#e2e8f0}
:root[data-theme="dark"] .todo-status-select{background:#1e293b;border-color:#334155;color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => t('todo_page_title', 'My Tasks'),
                    'pageDescription' => t('todo_page_desc', 'Manage and track your work tasks'),
                    'pageIcon'        => 'bx-task',
                    'pageIconBg'      => '#e0e7ff',
                    'pageIconColor'   => '#4f46e5',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Stats Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="todo-stat-card">
                                <div class="todo-stat-icon todo-stat-icon-indigo"><i class="bx bx-list-ul"></i></div>
                                <div class="todo-stat-body">
                                    <div class="todo-stat-label"><?php echo t('todo_stat_active', 'Active'); ?></div>
                                    <div class="todo-stat-val" id="tStatActive"><?php echo (int)$_stats['active']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="todo-stat-card">
                                <div class="todo-stat-icon todo-stat-icon-orange"><i class="bx bx-time-five"></i></div>
                                <div class="todo-stat-body">
                                    <div class="todo-stat-label"><?php echo t('todo_stat_overdue', 'Overdue'); ?></div>
                                    <div class="todo-stat-val" id="tStatOverdue"><?php echo (int)$_stats['overdue']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="todo-stat-card">
                                <div class="todo-stat-icon todo-stat-icon-blue"><i class="bx bx-calendar-check"></i></div>
                                <div class="todo-stat-body">
                                    <div class="todo-stat-label"><?php echo t('todo_stat_today', 'Due Today'); ?></div>
                                    <div class="todo-stat-val" id="tStatToday"><?php echo (int)$_stats['today']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="todo-stat-card">
                                <div class="todo-stat-icon todo-stat-icon-green"><i class="bx bx-check-circle"></i></div>
                                <div class="todo-stat-body">
                                    <div class="todo-stat-label"><?php echo t('todo_stat_completed', 'Completed'); ?></div>
                                    <div class="todo-stat-val" id="tStatCompleted"><?php echo (int)$_stats['completed']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Card -->
                    <div class="card">

                        <!-- Tab Bar -->
                        <div class="todo-tab-bar" id="tTabBar">
                            <button class="todo-tab-btn active" data-tab="open"><?php echo t('todo_tab_open', 'Open'); ?><span class="todo-tab-badge" id="tBadgeOpen">0</span></button>
                            <button class="todo-tab-btn" data-tab="overdue"><?php echo t('todo_tab_overdue', 'Overdue'); ?><span class="todo-tab-badge todo-tab-badge-red" id="tBadgeOverdue">0</span></button>
                            <button class="todo-tab-btn" data-tab="today"><?php echo t('todo_tab_today', 'Due Today'); ?><span class="todo-tab-badge" id="tBadgeToday">0</span></button>
                            <button class="todo-tab-btn" data-tab="completed"><?php echo t('todo_tab_done', 'Completed'); ?></button>
                            <button class="todo-tab-btn" data-tab="all"><?php echo t('todo_tab_all', 'All'); ?></button>
                        </div>

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <label class="todo-my-toggle">
                                    <input type="checkbox" id="tMyOnly" checked>
                                    <?php echo t('todo_my_tasks', 'My Tasks Only'); ?>
                                </label>
                                <div class="todo-pri-chip-wrap" id="tPriFilter">
                                    <span class="todo-pri-chip active" data-pri=""><?php echo t('lbl_all', 'All'); ?></span>
                                    <span class="todo-pri-chip" data-pri="Urgent"><?php echo t('todo_pri_urgent', 'Urgent'); ?></span>
                                    <span class="todo-pri-chip" data-pri="High"><?php echo t('todo_pri_high', 'High'); ?></span>
                                    <span class="todo-pri-chip" data-pri="Medium"><?php echo t('todo_pri_medium', 'Medium'); ?></span>
                                    <span class="todo-pri-chip" data-pri="Low"><?php echo t('todo_pri_low', 'Low'); ?></span>
                                </div>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="tSearch" placeholder="<?php echo t('todo_search_ph', 'Search tasks…'); ?>">
                                    <i class="bx bx-x r2k-clear d-none" id="tClearSearch"></i>
                                </div>
                                <button class="btn btn-sm btn-primary" id="btnNewTodo">
                                    <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px"></th>
                                        <th><?php echo t('col_priority', 'Priority'); ?></th>
                                        <th><?php echo t('col_task_title', 'Task'); ?></th>
                                        <th><?php echo t('col_assigned_to', 'Assigned To'); ?></th>
                                        <th><?php echo t('col_due_date', 'Due Date'); ?></th>
                                        <th><?php echo t('col_status', 'Status'); ?></th>
                                        <th class="th-act"><?php echo t('col_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="tTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center" id="tPagination">
                            <?php echo $ModPagination; ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- New / Edit Task Modal -->
<div class="modal fade" id="todoModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-task text-primary modal-doc-icon-inner"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="todoModalTitle"><?php echo t('todo_new_task', 'New Task'); ?></h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btnSaveTodo">
                        <i class="bx bx-check me-1"></i><?php echo t('btn_save', 'Save'); ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i><?php echo t('btn_cancel', 'Close'); ?>
                    </button>
                </div>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="tTodoUID" value="0">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><?php echo t('lbl_task_title', 'Task Title'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tTitle" placeholder="<?php echo t('ph_task_title', 'What needs to be done?'); ?>" maxlength="255">
                        <div class="invalid-feedback"><?php echo t('err_task_title_req', 'Task title is required.'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php echo t('lbl_description', 'Description'); ?></label>
                        <textarea class="form-control" id="tDescription" rows="3" placeholder="<?php echo t('ph_task_desc', 'Optional details or notes…'); ?>" maxlength="2000"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo t('lbl_priority', 'Priority'); ?></label>
                        <select class="form-select" id="tPriority">
                            <option value="Low"><?php echo t('todo_pri_low', 'Low'); ?></option>
                            <option value="Medium" selected><?php echo t('todo_pri_medium', 'Medium'); ?></option>
                            <option value="High"><?php echo t('todo_pri_high', 'High'); ?></option>
                            <option value="Urgent"><?php echo t('todo_pri_urgent', 'Urgent'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo t('lbl_status', 'Status'); ?></label>
                        <select class="form-select" id="tStatus">
                            <option value="Open"><?php echo t('todo_stat_open', 'Open'); ?></option>
                            <option value="InProgress"><?php echo t('todo_stat_inprogress', 'In Progress'); ?></option>
                            <option value="OnHold"><?php echo t('todo_stat_onhold', 'On Hold'); ?></option>
                            <option value="Completed"><?php echo t('todo_stat_completed', 'Completed'); ?></option>
                            <option value="Cancelled"><?php echo t('todo_stat_cancelled', 'Cancelled'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo t('lbl_due_date', 'Due Date'); ?></label>
                        <input type="text" class="form-control" id="tDueDateDisplay" placeholder="<?php echo t('ph_pick_date', 'Pick a date'); ?>" readonly>
                        <input type="hidden" id="tDueDate">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php echo t('lbl_assigned_to', 'Assign To'); ?></label>
                        <select class="form-select" id="tAssignedTo">
                            <?php foreach ($_userList as $_u): ?>
                            <option value="<?php echo (int)$_u->UserUID; ?>"
                                <?php echo ((int)$_u->UserUID === $_myUID) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($_u->FullName); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo t('lbl_link_to', 'Link To (Optional)'); ?></label>
                        <select class="form-select" id="tRefType">
                            <option value="General"><?php echo t('todo_ref_general', 'No Link'); ?></option>
                            <option value="Customer"><?php echo t('lbl_customer', 'Customer'); ?></option>
                            <option value="Vendor"><?php echo t('lbl_vendor', 'Vendor'); ?></option>
                            <option value="Invoice"><?php echo t('lbl_invoice', 'Invoice'); ?></option>
                            <option value="PurchaseBill"><?php echo t('lbl_purchase_bill', 'Purchase Bill'); ?></option>
                            <option value="Expense"><?php echo t('lbl_expense', 'Expense'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6" id="tRefLabelWrap" style="display:none">
                        <label class="form-label"><?php echo t('lbl_ref_label', 'Reference Label'); ?></label>
                        <input type="text" class="form-control" id="tRefLabel" placeholder="e.g. INV-0023 – Ramesh Traders" maxlength="255">
                        <input type="hidden" id="tRefUID" value="0">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" id="tIsPrivate">
                            <label class="form-check-label" for="tIsPrivate">
                                <?php echo t('todo_private', 'Private — visible only to me'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
var CsrfName          = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken         = '<?php echo $this->security->get_csrf_hash(); ?>';
var _todoMyUID        = <?php echo $_myUID; ?>;
var _todoInitStats    = <?php echo json_encode($_stats); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/todos/todos.js"></script>
