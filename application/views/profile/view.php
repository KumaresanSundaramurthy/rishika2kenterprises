<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// ── PHP helpers ───────────────────────────────────────────────────────────────
$u       = $userFullData ?? null;
$uInfo   = $userInfo     ?? null;

$firstName  = $u->FirstName    ?? ($uInfo->UserFirstName   ?? '');
$lastName   = $u->LastName     ?? ($uInfo->UserLastName    ?? '');
$userName   = $u->UserName     ?? ($uInfo->UserName        ?? '');
$email      = $u->EmailAddress ?? ($uInfo->UserEmailAddress ?? '');
$mobile     = $u->MobileNumber ?? ($uInfo->UserMobileNumber ?? '');
$countryCode= $u->CountryCode  ?? ($uInfo->UserCountryCode ?? '+91');
$userUID    = $u->UserUID      ?? ($uInfo->UserUID         ?? 0);

$empCode    = $u->UserCode ?? '';
$deptName   = $u->DepartmentName  ?? '—';
$desigName  = $u->DesignationName ?? '—';
$empStatus  = $u->EmployeeStatus  ?? 'Active';
$isRoleOne  = ((int)($u->RoleUID  ?? 0) === 1);
$doj        = $u->DateOfJoining   ?? '';
$listFmt    = $JwtData->GenSettings->ListDateFormat ?? 'd-m-Y';
$dojFmt     = !empty($doj) ? date($listFmt, strtotime($doj)) : '—';
$lastLogin  = $u->LastLoginOn ?? '—';

// Work Info — new fields
$employmentType   = $u->EmploymentType        ?? '';
$workEmail        = $u->WorkEmail             ?? '';
$workPhone        = $u->WorkPhone             ?? '';
$probationEndDate = $u->ProbationEndDate      ?? '';
$noticePeriodDays = $u->NoticePeriodDays      ?? '';
$reportingMgrUID  = (int)($u->ReportingManagerUID ?? 0);
$reportingMgrName = trim($u->ReportingManagerName ?? '');
$lastWorkingDate  = $u->LastWorkingDate       ?? '';
$exitReason       = $u->ExitReason            ?? '';
$showExitDetails  = in_array($empStatus, ['Resigned', 'Terminated']);

$statusColors = ['Active'=>'success','Resigned'=>'warning','Terminated'=>'danger','OnLeave'=>'info'];
$statusColor  = $statusColors[$empStatus] ?? 'secondary';

$addrs    = (array)($u->Addresses ?? ['Current' => null, 'Permanent' => null]);
$currAddr = $addrs['Current']   ?? null;
$permAddr = $addrs['Permanent'] ?? null;

$cdnBase   = getenv('FILE_UPLOAD') === 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
$avatarSrc = !empty($uInfo->UserImage) ? ($cdnBase . $uInfo->UserImage) : '';
$initials  = strtoupper(mb_substr($firstName, 0, 1)) . strtoupper(mb_substr($lastName, 0, 1));
if (!$initials) $initials = 'U';

$attachments = $userAttachments ?? [];
?>

<?php $this->load->view('common/header'); ?>
<link rel="stylesheet" href="/css/transactions-theme.css">

<style>
.profile-sidebar        { width:255px; min-width:255px; background:#fff; }
.profile-nav-link       { display:flex; align-items:center; gap:10px; padding:9px 14px; border-radius:8px;
                          color:#566a7f; text-decoration:none; font-size:.85rem; font-weight:500;
                          margin-bottom:2px; transition:background .15s, color .15s; }
.profile-nav-link:hover { background:#f0f0f5; color:#7c3aed; }
.profile-nav-link.active{ background:#ede9fe; color:#7c3aed; font-weight:600; }
.profile-nav-link i     { font-size:1.1rem; flex-shrink:0; width:20px; text-align:center; }
.profile-nav-label      { font-size:.76rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase;
                          color:#a0aab4; padding:14px 14px 4px; }
.profile-avatar-wrap    { width:80px; height:80px; border-radius:50%; overflow:hidden;
                          border:3px solid #ede9fe; margin:0 auto 10px; }
.profile-avatar-wrap img{ width:100%; height:100%; object-fit:cover; }
.profile-avatar-initials{ width:80px; height:80px; border-radius:50%; background:#7c3aed;
                          color:#fff; font-size:1.5rem; font-weight:700; display:flex;
                          align-items:center; justify-content:center; margin:0 auto 10px; }
.profile-section-hdr    { display:flex; align-items:center; justify-content:space-between;
                          margin-bottom:1.5rem; padding-bottom:.75rem; border-bottom:1px solid #e9eaec; }
.wf-group-label         { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
                          color:#8592a3; margin-bottom:.6rem; padding-bottom:.3rem;
                          border-bottom:1px solid #f0f0f5; }
.profile-section-hdr h5 { margin:0; font-weight:600; font-size:1rem; }
.profile-section-hdr p  { margin:0; font-size:.78rem; color:#8a92a0; }
.info-row               { display:flex; flex-wrap:wrap; gap:1rem; }
.info-card              { flex:1; min-width:180px; background:#f8f8fb; border-radius:8px;
                          padding:14px 16px; border:1px solid #e9eaec; }
.info-card-label        { font-size:.73rem; text-transform:uppercase; letter-spacing:.04em;
                          color:#a0aab4; font-weight:600; margin-bottom:4px; }
.info-card-value        { font-size:.9rem; font-weight:600; color:#3d3d4e; }
.addr-col-header        { font-size:.76rem; text-transform:uppercase; letter-spacing:.04em;
                          color:#7c3aed; font-weight:700; margin-bottom:.75rem; }
.profile-addr-box       { min-height:110px; border:2px dashed #d0d5e8; border-radius:8px;
                          padding:16px; cursor:pointer; transition:border-color .2s, background .2s; }
.profile-addr-box:hover { border-color:#7367f0; background:#f5f4ff; }
.profile-addr-box.has-addr { border-style:solid; border-color:#c8ceeb; background:#fafbff; }
.profile-addr-empty     { color:#adb5bd; font-size:.84rem; text-align:center; padding-top:14px; }
.profile-addr-text      { font-size:.86rem; line-height:1.7; color:#556070; }
.addr-edit-hint         { font-size:.75rem; color:#b0b8c8; margin-top:.4rem; }
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-user-circle',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageTitle'       => $PageTitle       ?? 'My Profile',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y p-2">
                    <div class="card p-0 overflow-hidden">
                        <div class="d-flex" style="min-height:520px;">

                            <!-- ══ LEFT SIDEBAR ════════════════════════════════ -->
                            <div class="profile-sidebar border-end d-flex flex-column">

                                <!-- Avatar + identity -->
                                <div class="text-center px-3 py-4 border-bottom">
                                    <?php if ($avatarSrc): ?>
                                    <div class="profile-avatar-wrap">
                                        <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Profile" id="sidebarAvatarImg">
                                    </div>
                                    <?php else: ?>
                                    <div class="profile-avatar-initials" id="sidebarAvatarInitials"><?php echo htmlspecialchars($initials); ?></div>
                                    <?php endif; ?>
                                    <div class="fw-semibold mt-1" style="font-size:.93rem;"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></div>
                                    <?php if ($desigName !== '—'): ?>
                                    <div class="text-muted" style="font-size:.77rem;"><?php echo htmlspecialchars($desigName); ?></div>
                                    <?php endif; ?>
                                    <?php if ($empCode !== '—'): ?>
                                    <div class="badge bg-label-primary mt-1" style="font-size:.7rem;"><?php echo htmlspecialchars($empCode); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Navigation -->
                                <nav class="p-3 flex-grow-1">
                                    <div class="profile-nav-label">Profile</div>
                                    <a href="javascript:void(0)" id="nav-personal" class="profile-nav-link active" data-section="personal">
                                        <i class="bx bx-user"></i><span>Personal</span>
                                    </a>
                                    <a href="javascript:void(0)" id="nav-workinfo" class="profile-nav-link" data-section="workinfo">
                                        <i class="bx bx-briefcase"></i><span>Work Info</span>
                                    </a>
                                    <a href="javascript:void(0)" id="nav-addresses" class="profile-nav-link" data-section="addresses">
                                        <i class="bx bx-map-pin"></i><span>Addresses</span>
                                    </a>
                                    <a href="javascript:void(0)" id="nav-emergency" class="profile-nav-link" data-section="emergency">
                                        <i class="bx bx-phone-call"></i><span>Emergency Contacts</span>
                                    </a>

                                    <div class="profile-nav-label mt-2">Account</div>
                                    <a href="javascript:void(0)" id="nav-documents" class="profile-nav-link" data-section="documents">
                                        <i class="bx bx-paperclip"></i>
                                        <span>Documents</span>
                                        <?php if (count($attachments) > 0): ?>
                                        <span class="badge bg-label-secondary ms-auto" style="font-size:.68rem;"><?php echo count($attachments); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="javascript:void(0)" id="nav-signatures" class="profile-nav-link" data-section="signatures">
                                        <i class="bx bx-pen"></i><span>Signatures</span>
                                    </a>
                                    <a href="javascript:void(0)" id="nav-bankdetails" class="profile-nav-link" data-section="bankdetails">
                                        <i class="bx bx-credit-card"></i><span>Bank Details</span>
                                    </a>

                                    <div class="profile-nav-label mt-2">Career</div>
                                    <a href="javascript:void(0)" id="nav-education" class="profile-nav-link" data-section="education">
                                        <i class="bx bx-book-open"></i><span>Education & Exp</span>
                                    </a>
                                </nav>

                            </div>
                            <!-- /LEFT SIDEBAR -->

                            <!-- ══ RIGHT CONTENT ═══════════════════════════════ -->
                            <div class="flex-grow-1">

                                <!-- ─── SECTION: Personal ─────────────────────── -->
                                <div class="profile-section d-none p-4" id="section-personal">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Personal Details</h5>
                                            <p>Update your name, photo and mobile number</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnSavePersonal">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerPersonal"></span>
                                            <i class="bx bx-save me-1" id="iconPersonal"></i>Update
                                        </button>
                                    </div>

                                    <input type="hidden" id="HuserUid" value="<?php echo (int)$userUID; ?>">

                                    <div class="row g-3">
                                        <!-- Photo -->
                                        <div class="col-md-3 d-flex justify-content-center">
                                            <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                                <div class="dz-message needsclick text-center">
                                                    <i class="upload-icon mb-3"></i>
                                                    <p class="h4 needsclick mb-2">Drag and drop image</p>
                                                    <p class="h6 text-body-secondary fw-normal mb-0">JPG, GIF or PNG · Max 1 MB</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Fields -->
                                        <div class="col-md-9">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label form-label-sm">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="fistName" name="fistName" placeholder="First name" maxlength="100" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label form-label-sm">Last Name</label>
                                                    <input type="text" class="form-control form-control-sm" id="lastName" name="lastName" maxlength="100" placeholder="Last name" value="<?php echo htmlspecialchars($lastName); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label form-label-sm">Username</label>
                                                    <input type="text" class="form-control form-control-sm bg-light" disabled value="<?php echo htmlspecialchars($userName); ?>">
                                                    <div class="form-text" style="font-size:.72rem;">Cannot be changed.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label form-label-sm">Email Address</label>
                                                    <input type="email" class="form-control form-control-sm bg-light" disabled value="<?php echo htmlspecialchars($email); ?>">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label form-label-sm">Mobile Number <span class="text-danger">*</span></label>
                                                    <div class="d-flex gap-2">
                                                        <select id="CountryCode" name="CountryCode" class="form-select form-select-sm" style="max-width:170px;">
                                                            <option value="">-- Country Code --</option>
                                                        </select>
                                                        <input type="number" id="MobileNumber" name="MobileNumber"
                                                               class="form-control form-control-sm" placeholder="Mobile number"
                                                               maxlength="20" value="<?php echo htmlspecialchars($mobile); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Personal -->

                                <!-- ─── SECTION: Work Info ────────────────────── -->
                                <div class="profile-section d-none p-4" id="section-workinfo">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Work Information</h5>
                                            <p>Update your employment details</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnSaveWorkInfo">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerWorkInfo"></span>
                                            <i class="bx bx-save me-1" id="iconWorkInfo"></i>Save
                                        </button>
                                    </div>

                                    <!-- Employment Details -->
                                    <div class="wf-group-label">Employment Details</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Employee Code</label>
                                            <input type="text" class="form-control form-control-sm bg-light" disabled
                                                   value="<?php echo htmlspecialchars($empCode); ?>" placeholder="Not assigned">
                                        </div>
                                        <?php if (!$isRoleOne): ?>
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Employment Type</label>
                                            <select id="wfEmploymentType" class="form-select form-select-sm">
                                                <option value="">-- Select Type --</option>
                                                <?php foreach (['Permanent','Contract','Part-time','Intern','Consultant'] as $et): ?>
                                                <option value="<?php echo $et; ?>" <?php echo ($employmentType === $et) ? 'selected' : ''; ?>><?php echo $et; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Employment Status</label>
                                            <select id="wfEmployeeStatus" class="form-select form-select-sm">
                                                <?php foreach (['Active','Resigned','Terminated','OnLeave'] as $st): ?>
                                                <option value="<?php echo $st; ?>" <?php echo ($empStatus === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Date of Joining</label>
                                            <input type="text" id="wfDateOfJoining" class="form-control form-control-sm"
                                                   placeholder="Select date" value="<?php echo htmlspecialchars($doj); ?>" autocomplete="off" readonly>
                                        </div>
                                        <?php endif; ?>
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Department</label>
                                            <select id="wfDepartmentUID" class="form-select form-select-sm">
                                                <option value="">-- Select Department --</option>
                                                <?php foreach ($DepartmentsList ?? [] as $dept): ?>
                                                <option value="<?php echo (int)$dept->DepartmentUID; ?>"
                                                    <?php echo ((int)($u->DepartmentUID ?? 0) === (int)$dept->DepartmentUID) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept->DepartmentName); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Designation</label>
                                            <select id="wfDesignationUID" class="form-select form-select-sm">
                                                <option value="">-- Select Designation --</option>
                                                <?php foreach ($DesignationsList ?? [] as $des): ?>
                                                <option value="<?php echo (int)$des->DesignationUID; ?>"
                                                    <?php echo ((int)($u->DesignationUID ?? 0) === (int)$des->DesignationUID) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($des->DesignationName); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Last Login</label>
                                            <input type="text" class="form-control form-control-sm bg-light" disabled value="<?php echo htmlspecialchars($lastLogin ?: '—'); ?>">
                                        </div>
                                    </div>

                                    <!-- Contact -->
                                    <div class="wf-group-label">Work Contact</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Work Email</label>
                                            <input type="email" id="wfWorkEmail" class="form-control form-control-sm"
                                                   placeholder="e.g. john@company.com" maxlength="150"
                                                   value="<?php echo htmlspecialchars($workEmail); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Work Phone</label>
                                            <input type="text" id="wfWorkPhone" class="form-control form-control-sm"
                                                   placeholder="e.g. +91 98765 43210" maxlength="30"
                                                   value="<?php echo htmlspecialchars($workPhone); ?>">
                                        </div>
                                    </div>

                                    <?php if (!$isRoleOne): ?>
                                    <!-- Reporting -->
                                    <div class="wf-group-label">Reporting</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Reporting Manager</label>
                                            <select id="wfReportingManagerUID" class="form-select form-select-sm">
                                                <option value="">-- No Reporting Manager --</option>
                                                <?php foreach ($OrgUsersList ?? [] as $ou): if ((int)$ou->UserUID === $userUID) continue; ?>
                                                <option value="<?php echo (int)$ou->UserUID; ?>"<?php echo ($reportingMgrUID === (int)$ou->UserUID) ? ' selected' : ''; ?>><?php echo htmlspecialchars(trim($ou->FullName)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!$isRoleOne): ?>
                                    <!-- HR Periods -->
                                    <div class="wf-group-label">HR Periods</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Probation End Date</label>
                                            <input type="text" id="wfProbationEndDate" class="form-control form-control-sm"
                                                   placeholder="Select date" value="<?php echo htmlspecialchars($probationEndDate); ?>" autocomplete="off" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Notice Period <span class="text-muted" style="font-size:.75rem;">(days)</span></label>
                                            <input type="number" id="wfNoticePeriodDays" class="form-control form-control-sm"
                                                   placeholder="e.g. 30" min="0" max="365"
                                                   value="<?php echo htmlspecialchars((string)$noticePeriodDays); ?>">
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!$isRoleOne): ?>
                                    <!-- Exit Details (shown only for Resigned/Terminated) -->
                                    <div id="wfExitDetailsBlock" <?php echo $showExitDetails ? '' : 'style="display:none;"'; ?>>
                                        <div class="wf-group-label text-danger">Exit Details</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label form-label-sm">Last Working Date</label>
                                                <input type="text" id="wfLastWorkingDate" class="form-control form-control-sm"
                                                       placeholder="Select date" value="<?php echo htmlspecialchars($lastWorkingDate); ?>" autocomplete="off" readonly>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label form-label-sm">Reason for Exit</label>
                                                <input type="text" id="wfExitReason" class="form-control form-control-sm"
                                                       placeholder="e.g. Better opportunity" maxlength="500"
                                                       value="<?php echo htmlspecialchars($exitReason); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <!-- /Work Info -->

                                <!-- ─── SECTION: Addresses ────────────────────── -->
                                <div class="profile-section d-none p-4" id="section-addresses">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Addresses</h5>
                                            <p>Your current and permanent address</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnSaveAddresses">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerAddr"></span>
                                            <i class="bx bx-save me-1" id="iconAddr"></i>Save
                                        </button>
                                    </div>

                                    <div class="row g-4">
                                        <!-- Current Address -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="addr-col-header mb-0"><i class="bx bx-map-pin me-1"></i>Current Address</div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 d-none" id="btnCopyToCurr" style="font-size:.74rem;">
                                                    <i class="bx bx-copy-alt me-1"></i>Copy from Permanent
                                                </button>
                                            </div>
                                            <div id="profCurrAddrBox" class="profile-addr-box">
                                                <div class="profile-addr-empty">
                                                    <i class="bx bx-map-alt" style="font-size:2rem;opacity:.35;display:block;margin-bottom:.4rem;"></i>
                                                    Click to add current address
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Permanent Address -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="addr-col-header mb-0"><i class="bx bx-home me-1"></i>Permanent Address</div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 d-none" id="btnCopyToPerm" style="font-size:.74rem;">
                                                    <i class="bx bx-copy-alt me-1"></i>Copy from Current
                                                </button>
                                            </div>
                                            <div id="profPermAddrBox" class="profile-addr-box">
                                                <div class="profile-addr-empty">
                                                    <i class="bx bx-map-alt" style="font-size:2rem;opacity:.35;display:block;margin-bottom:.4rem;"></i>
                                                    Click to add permanent address
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Addresses -->

                                <!-- ─── SECTION: Documents ────────────────────── -->
                                <div class="profile-section d-none p-4" id="section-documents">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>My Documents</h5>
                                            <p>Upload your personal documents — ID proof, certificates and more</p>
                                        </div>
                                    </div>

                                    <!-- Upload bar -->
                                    <div class="d-flex align-items-center gap-2 flex-wrap p-3 rounded border bg-light mb-2">
                                        <select class="form-select form-select-sm" id="profileAttachDocType" style="width:140px;flex-shrink:0;">
                                            <option value="ID Proof">ID Proof</option>
                                            <option value="Resume">Resume</option>
                                            <option value="Certificate">Certificate</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <label class="btn btn-sm btn-outline-primary mb-0" for="profileAttachFile" style="cursor:pointer;flex-shrink:0;">
                                            <i class="bx bx-upload me-1"></i>Choose File
                                        </label>
                                        <input type="file" id="profileAttachFile" class="d-none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                        <span id="profileAttachFileName" class="text-muted text-truncate" style="font-size:.8rem;flex:1;min-width:0;">No file chosen</span>
                                        <button type="button" class="btn btn-sm btn-success d-none" id="btnUploadProfileAttach" style="flex-shrink:0;">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerProfileAttach"></span>
                                            <i class="bx bx-cloud-upload me-1" id="iconProfileAttach"></i>Upload
                                        </button>
                                    </div>
                                    <div class="text-muted mb-3" style="font-size:.73rem;"><i class="bx bx-info-circle me-1"></i>Allowed: JPG, PNG, PDF, DOC, DOCX &nbsp;·&nbsp; Max 5 MB per file</div>

                                    <!-- Document list — rendered by JS -->
                                    <div id="profileAttachList"></div>
                                </div>
                                <!-- /Documents -->

                                <!-- ─── SECTION: Signatures ───────────────────── -->
                                <div class="profile-section d-none p-4" id="section-signatures">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>My Signatures</h5>
                                            <p>Digital signatures used on invoices and documents</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnAddSignature">
                                            <i class="bx bx-plus me-1"></i>Add Signature
                                        </button>
                                    </div>
                                    <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-3" style="font-size:.8rem;">
                                        <i class="bx bx-info-circle flex-shrink-0 me-2"></i>
                                        Only one signature can be set as default at a time.
                                    </div>
                                    <div id="signaturesContainer">
                                        <div class="text-center py-4 text-muted">
                                            <span class="spinner-border spinner-border-sm me-2"></span>Loading signatures...
                                        </div>
                                    </div>
                                </div>
                                <!-- /Signatures -->

                                <!-- ─── SECTION: Education & Experience ──────── -->
                                <div class="profile-section d-none p-4" id="section-education">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Education & Experience</h5>
                                            <p>Your academic qualifications and previous work history</p>
                                        </div>
                                    </div>

                                    <!-- Education -->
                                    <div class="mb-5">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="wf-group-label mb-0"><i class="bx bx-book-open me-1"></i>Education Details</div>
                                            <button type="button" class="btn btn-sm btn-primary" id="btnAddEdu">
                                                <i class="bx bx-plus me-1"></i>Add Education
                                            </button>
                                        </div>
                                        <div id="eduTableWrap">
                                            <div class="text-center text-muted py-4">
                                                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Experience -->
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="wf-group-label mb-0"><i class="bx bx-briefcase me-1"></i>Previous Experience</div>
                                            <button type="button" class="btn btn-sm btn-primary" id="btnAddExp">
                                                <i class="bx bx-plus me-1"></i>Add Experience
                                            </button>
                                        </div>
                                        <div id="expTableWrap">
                                            <div class="text-center text-muted py-4">
                                                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Education & Experience -->

                                <!-- ─── SECTION: Emergency Contacts ─────────── -->
                                <div class="profile-section d-none p-4" id="section-emergency">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Emergency Contacts</h5>
                                            <p>People to contact in case of an emergency</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnAddEmgContact">
                                            <i class="bx bx-plus me-1"></i>Add Contact
                                        </button>
                                    </div>
                                    <div id="emgNoPrimaryAlert" class="alert alert-warning d-flex align-items-center py-2 px-3 mb-3" style="font-size:.8rem;">
                                        <i class="bx bx-info-circle flex-shrink-0 me-2"></i>
                                        At least one emergency contact should be marked as the primary contact.
                                    </div>
                                    <div id="emgContactTableWrap">
                                        <div class="text-center text-muted py-4">
                                            <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                        </div>
                                    </div>
                                </div>
                                <!-- /Emergency Contacts -->

                                <!-- ─── SECTION: Bank Details ─────────────── -->
                                <div class="profile-section d-none p-4" id="section-bankdetails">
                                    <div class="profile-section-hdr">
                                        <div>
                                            <h5>Bank Details</h5>
                                            <p>Your bank account information for payroll processing</p>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnSaveBankDetails">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerBank"></span>
                                            <i class="bx bx-save me-1" id="iconBank"></i>Save
                                        </button>
                                    </div>

                                    <input type="hidden" id="bankDetailUID" value="0">

                                    <div class="wf-group-label mb-3"><i class="bx bx-credit-card me-1"></i>Bank Account Details</div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label form-label-sm">IFSC / Sort Code / Routing Number</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control form-control-sm text-uppercase" id="ifscCode" placeholder="e.g. SBIN0001234" maxlength="50" style="text-transform:uppercase;">
                                                <button class="btn btn-outline-primary" type="button" id="btnFetchIFSC" title="Auto-fill bank name and branch from IFSC">
                                                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinnerIFSC"></span>
                                                    <i class="bx bx-search-alt me-1" id="iconIFSC"></i>Fetch
                                                </button>
                                            </div>
                                            <div class="form-text" style="font-size:.72rem;">Enter IFSC code and click Fetch to auto-fill bank name &amp; branch.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Bank Name</label>
                                            <input type="text" class="form-control form-control-sm" id="bankName" placeholder="e.g. State Bank of India" maxlength="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Branch</label>
                                            <input type="text" class="form-control form-control-sm" id="branchName" placeholder="Branch name or city" maxlength="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Account Number</label>
                                            <div class="input-group input-group-sm">
                                                <input type="password" class="form-control form-control-sm" id="accountNumber" placeholder="Account number" maxlength="50" autocomplete="new-password">
                                                <button class="btn btn-outline-secondary toggleAccNo" type="button" data-target="accountNumber" tabindex="-1" title="Show / Hide">
                                                    <i class="bx bx-hide"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Confirm Account Number</label>
                                            <input type="text" class="form-control form-control-sm" id="confirmAccountNumber" placeholder="Re-enter account number" maxlength="50" autocomplete="off">
                                            <div class="text-danger" id="accNoMismatch" style="display:none;font-size:.72rem;"><i class="bx bx-error-circle me-1"></i>Account numbers do not match.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">Account Holder Name</label>
                                            <input type="text" class="form-control form-control-sm" id="accountHolder" placeholder="Name as per bank records" maxlength="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm d-block">Account Type</label>
                                            <div class="d-flex gap-4 mt-1">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="accountType" id="accTypeSaving" value="Saving">
                                                    <label class="form-check-label" for="accTypeSaving" style="font-size:.84rem;">Savings Account</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="accountType" id="accTypeCurrent" value="Current">
                                                    <label class="form-check-label" for="accTypeCurrent" style="font-size:.84rem;">Current Account</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- UPI Details -->
                                        <div class="col-12 mt-2">
                                            <hr class="my-1">
                                            <div class="wf-group-label my-3"><i class="bx bx-qr me-1"></i>UPI Details</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">UPI ID</label>
                                            <input type="text" class="form-control form-control-sm" id="upiId" placeholder="e.g. name@okaxis or 9876543210@upi" maxlength="100">
                                            <div class="invalid-feedback" id="upiIdFeedback" style="display:none;font-size:.72rem;"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">UPI Mobile Number</label>
                                            <input type="text" class="form-control form-control-sm" id="upiNumber" placeholder="Mobile number linked to UPI" maxlength="20">
                                        </div>
                                    </div>
                                </div>
                                <!-- /Bank Details -->

                            </div>
                            <!-- /RIGHT CONTENT -->

                        </div><!-- /d-flex -->
                    </div><!-- /card -->
                </div>

            </div><!-- /content-wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<!-- ══ Signature Modal (unchanged) ══════════════════════════════════════════ -->
<div class="modal fade" id="signatureModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-pen" id="sigModalIcon"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="sigModalTitle">Add Signature</div>
                            <div class="vtm-doc-meta" id="sigModalMeta">Upload an image or draw your signature on a canvas</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="sigEditUID" value="0">
            <div class="modal-body p-0">
                <div class="px-4 pt-4 pb-3 border-bottom">
                    <label class="form-label fw-semibold">Signature Label</label>
                    <input type="text" id="sigLabel" class="form-control" placeholder="e.g. My Official Signature" maxlength="100" value="My Signature">
                    <div class="form-text">A friendly name to identify this signature.</div>
                </div>
                <ul class="nav nav-tabs px-4 pt-3 border-bottom" id="sigMethodTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sig-upload-tab" data-bs-toggle="tab" data-bs-target="#sig-upload-pane" type="button" role="tab">
                            <i class="bx bx-upload me-1"></i>Upload Image
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sig-draw-tab" data-bs-toggle="tab" data-bs-target="#sig-draw-pane" type="button" role="tab">
                            <i class="bx bx-pencil me-1"></i>Draw
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active p-4" id="sig-upload-pane" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Select Signature Image</label>
                            <input type="file" id="sigImageInput" class="form-control" accept="image/png,image/jpeg,image/jpg">
                            <div class="form-text">PNG or JPG · Max 500 KB · Recommended: 400×150 px · White or transparent background.</div>
                        </div>
                        <div id="sigUploadPreview" class="d-none">
                            <div class="d-flex align-items-center justify-content-center p-3 rounded" style="background:#f8f9fc;border:2px dashed #d0d5dd;min-height:100px;">
                                <img id="sigUploadPreviewImg" src="" alt="Preview" style="max-width:100%;max-height:120px;object-fit:contain;">
                            </div>
                            <div id="sigUploadMeta" class="text-muted small mt-2"></div>
                        </div>
                        <div id="sigUploadError" class="alert alert-danger d-none mt-2"></div>
                    </div>
                    <div class="tab-pane fade p-4" id="sig-draw-pane" role="tabpanel">
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">Draw your signature below</label>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 small text-muted">Color</label>
                                <input type="color" id="sigPenColor" value="#000000" class="form-control form-control-color" style="width:36px;height:32px;padding:2px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearCanvas"><i class="bx bx-eraser me-1"></i>Clear</button>
                            </div>
                        </div>
                        <div style="border:2px solid #d0d5dd;border-radius:8px;background:#fff;overflow:hidden;touch-action:none;">
                            <canvas id="signatureCanvas" style="display:block;width:100%;height:200px;cursor:crosshair;"></canvas>
                        </div>
                        <div class="form-text mt-2">Use mouse or touch to sign. Tap Clear to start over.</div>
                        <div id="sigDrawError" class="alert alert-danger d-none mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveSignature">
                    <i class="bx bx-save me-1"></i>Save Signature
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Signature Modal -->

<!-- ══ Emergency Contact Modal ══════════════════════════════════════════════ -->
<div class="modal fade" id="emgContactModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#ff3e1d;--vtm-bg:#fff1ee;--vtm-icon-bg:rgba(255,62,29,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-phone-call"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="emgModalTitle">Add Emergency Contact</div>
                            <div class="vtm-doc-meta">Person to reach in case of emergency</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="emgContactUID" value="0">
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="emgName" class="form-control" placeholder="Full name" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Relationship <span class="text-danger">*</span></label>
                        <select id="emgRelationship" class="form-select">
                            <option value="">-- Select Relationship --</option>
                            <optgroup label="Immediate Family">
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Spouse (Husband)">Spouse (Husband)</option>
                                <option value="Spouse (Wife)">Spouse (Wife)</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                            </optgroup>
                            <optgroup label="Extended Family">
                                <option value="Grandfather">Grandfather</option>
                                <option value="Grandmother">Grandmother</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Nephew">Nephew</option>
                                <option value="Niece">Niece</option>
                                <option value="Cousin">Cousin</option>
                                <option value="Guardian">Guardian</option>
                            </optgroup>
                            <optgroup label="Others">
                                <option value="Friend">Friend</option>
                                <option value="Colleague">Colleague</option>
                                <option value="Neighbour">Neighbour</option>
                                <option value="Other">Other</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" id="emgPhoneNumber" class="form-control" placeholder="Mobile / Landline" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="emgEmailAddress" class="form-control" placeholder="email@example.com" maxlength="150">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Country</label>
                        <select id="emgCountry" class="form-select">
                            <option value="">-- Select Country --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" id="emgAddressLine1" class="form-control" placeholder="Street / Area" maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" id="emgAddressLine2" class="form-control" placeholder="Landmark / Flat no." maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">State</label>
                        <select id="emgState" class="form-select">
                            <option value="">-- Select State --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <select id="emgCity" class="form-select">
                            <option value="">-- Select City --</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="emgIsPrimary" value="1">
                            <label class="form-check-label fw-semibold" for="emgIsPrimary">
                                Set as Primary Contact
                            </label>
                            <div class="form-text mt-0">This person will be contacted first in an emergency.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEmgContact">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerEmg"></span>
                    <i class="bx bx-save me-1" id="iconEmg"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Emergency Contact Modal -->

<!-- ══ Education Modal ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="educationModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-book-open"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="eduModalTitle">Add Education</div>
                            <div class="vtm-doc-meta">Academic qualification details</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="eduUID" value="0">
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Institution <span class="text-danger">*</span></label>
                        <input type="text" id="eduInstitution" class="form-control" placeholder="University or school name" maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Degree</label>
                        <select id="eduDegree" class="form-select">
                            <option value="">-- Select Degree --</option>
                            <optgroup label="School">
                                <option value="Secondary (10th)">Secondary (10th)</option>
                                <option value="Higher Secondary (12th)">Higher Secondary (12th)</option>
                                <option value="Diploma">Diploma</option>
                            </optgroup>
                            <optgroup label="Under Graduate">
                                <option value="B.A.">B.A. (Bachelor of Arts)</option>
                                <option value="B.Sc.">B.Sc. (Bachelor of Science)</option>
                                <option value="B.Com.">B.Com. (Bachelor of Commerce)</option>
                                <option value="B.Tech. / B.E.">B.Tech. / B.E. (Engineering)</option>
                                <option value="BBA">BBA (Business Administration)</option>
                                <option value="BCA">BCA (Computer Applications)</option>
                                <option value="B.Arch.">B.Arch. (Architecture)</option>
                                <option value="MBBS">MBBS</option>
                                <option value="BDS">BDS (Dental Surgery)</option>
                                <option value="B.Pharm.">B.Pharm. (Pharmacy)</option>
                                <option value="LLB">LLB (Law)</option>
                                <option value="B.Ed.">B.Ed. (Education)</option>
                            </optgroup>
                            <optgroup label="Post Graduate">
                                <option value="M.A.">M.A. (Master of Arts)</option>
                                <option value="M.Sc.">M.Sc. (Master of Science)</option>
                                <option value="M.Com.">M.Com. (Master of Commerce)</option>
                                <option value="M.Tech. / M.E.">M.Tech. / M.E. (Engineering)</option>
                                <option value="MBA">MBA (Business Administration)</option>
                                <option value="MCA">MCA (Computer Applications)</option>
                                <option value="M.Ed.">M.Ed. (Education)</option>
                                <option value="M.Pharm.">M.Pharm. (Pharmacy)</option>
                                <option value="LLM">LLM (Law)</option>
                                <option value="Post Graduate Diploma">Post Graduate Diploma</option>
                            </optgroup>
                            <optgroup label="Doctorate &amp; Professional">
                                <option value="MD">MD (Doctor of Medicine)</option>
                                <option value="MS">MS (Master of Surgery)</option>
                                <option value="Ph.D.">Ph.D. (Doctorate)</option>
                            </optgroup>
                            <optgroup label="Other">
                                <option value="Certificate Course">Certificate Course</option>
                                <option value="Other">Other</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Field of Study</label>
                        <input type="text" id="eduFieldOfStudy" class="form-control" placeholder="e.g. Computer Science" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CGPA / Grade</label>
                        <input type="text" id="eduCGPA" class="form-control" placeholder="e.g. 8.5 / 10" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Completion</label>
                        <input type="text" id="eduDateOfCompletion" class="form-control" placeholder="Select date" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdu">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerEdu"></span>
                    <i class="bx bx-save me-1" id="iconEdu"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Education Modal -->

<!-- ══ Experience Modal ══════════════════════════════════════════════════════ -->
<div class="modal fade" id="experienceModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top modal-lg">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-briefcase"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="expModalTitle">Add Experience</div>
                            <div class="vtm-doc-meta">Previous work experience details</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="expUID" value="0">
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Employer Name <span class="text-danger">*</span></label>
                        <input type="text" id="expEmployerName" class="form-control" placeholder="Company name" maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Job Title</label>
                        <input type="text" id="expJobTitle" class="form-control" placeholder="e.g. Software Engineer" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="text" id="expStartDate" class="form-control" placeholder="Select date" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="text" id="expEndDate" class="form-control" placeholder="Leave blank if current" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Job Description</label>
                        <textarea id="expJobDescription" class="form-control" rows="3" placeholder="Brief description of responsibilities"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveExp">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerExp"></span>
                    <i class="bx bx-save me-1" id="iconExp"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Experience Modal -->

<?php $this->load->view('common/form/address_form'); ?>

<?php $this->load->view('common/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script src="/js/common/address.js"></script>
<script src="/js/profile.js"></script>

<script>
'use strict';

var imgData   = '<?php echo addslashes($uInfo->UserImage ?? ''); ?>';
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
var _profileInitAttachments = <?php echo json_encode(array_values($attachments)); ?>;
var OrgCountryISO2       = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;
var _profileCountryCode  = <?php echo json_encode($countryCode ?? ''); ?>;
var _wfShowExitDetails   = <?php echo json_encode($showExitDetails); ?>;
var _profCurrAddr    = { Line1: <?php echo json_encode($currAddr->AddressLine1 ?? ''); ?>, Line2: <?php echo json_encode($currAddr->AddressLine2 ?? ''); ?>, State: <?php echo json_encode($currAddr->State ?? ''); ?>, StateISO2: '', City: <?php echo json_encode($currAddr->City ?? ''); ?>, Pincode: <?php echo json_encode($currAddr->PinCode ?? ''); ?> };
var _profPermAddr    = { Line1: <?php echo json_encode($permAddr->AddressLine1 ?? ''); ?>, Line2: <?php echo json_encode($permAddr->AddressLine2 ?? ''); ?>, State: <?php echo json_encode($permAddr->State ?? ''); ?>, StateISO2: '', City: <?php echo json_encode($permAddr->City ?? ''); ?>, Pincode: <?php echo json_encode($permAddr->PinCode ?? ''); ?> };
var _bankServerData  = <?php echo json_encode($bankDetails ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

$(function () {

    // ── Section switching ─────────────────────────────────────────────────────
    var _profileSections = ['personal','workinfo','addresses','documents','signatures','bankdetails','education','emergency'];
    var _currentSection  = 'personal';
    var _sigListLoaded   = false;
    var _addrInitDone    = false;
    var _wfInitDone      = false;
    var _eduListLoaded   = false;
    var _emgListLoaded   = false;
    var _bankLoaded      = false;
    var _isInitialLoad   = true;

    function switchSection(id) {
        if (_profileSections.indexOf(id) === -1) id = 'personal';
        _profileSections.forEach(function(s) {
            $('#section-' + s).addClass('d-none');
            $('#nav-' + s).removeClass('active');
        });
        $('#section-' + id).removeClass('d-none');
        $('#nav-' + id).addClass('active');
        _currentSection = id;
        history.replaceState(null, '', window.location.pathname + '#' + id);

        if (id === 'workinfo' && !_wfInitDone) { _wfInitDone = true; }
        if (id === 'signatures' && !_sigListLoaded) {
            _loadSignatureList();
            _sigListLoaded = true;
        }
        if (id === 'addresses' && !_addrInitDone) { _addrInitDone = true; }
        if (id === 'education' && !_eduListLoaded) {
            _loadEduExpList();
            _eduListLoaded = true;
        }
        if (id === 'emergency' && !_emgListLoaded) {
            _loadEmgContacts();
            _emgListLoaded = true;
        }
        if (id === 'bankdetails' && !_bankLoaded) {
            _bankLoaded = true;
            if (_isInitialLoad) {
                // Direct URL access — data already embedded by PHP controller, no AJAX needed
                _fillBankForm(_bankServerData);
            } else {
                // User switched to this tab — fetch fresh data via AJAX
                _loadBankDetails();
            }
        }
    }

    // ── Render address boxes from initial PHP data ────────────────────────────
    _renderProfileAddr('current');
    _renderProfileAddr('permanent');
    _updateProfileAddrCopyBtns();
    $(document).on('click', '#profCurrAddrBox', function () { _openProfileAddrModal('current'); });
    $(document).on('click', '#profPermAddrBox', function () { _openProfileAddrModal('permanent'); });

    // ── Init section from URL hash ────────────────────────────────────────────
    var _hash = window.location.hash.replace('#', '');
    switchSection(_profileSections.indexOf(_hash) !== -1 ? _hash : 'personal');
    _isInitialLoad = false;

    $(document).on('click', '.profile-nav-link', function () {
        switchSection($(this).data('section'));
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // PERSONAL SECTION
    // ═══════════════════════════════════════════════════════════════════════════
    if (hasValue(imgData)) { commonSetDropzoneImageOne(CDN_URL + imgData); }

    // ── Shared country data cache (Personal phone codes + Address country selects)
    var _countryCodesCache = null;

    function _fetchCountries(onData) {
        if (_countryCodesCache !== null) { onData(_countryCodesCache); return; }
        function _done(data) { _countryCodesCache = data || []; onData(_countryCodesCache); }
        function _ajax() {
            $.ajax({ url: '/globally/getCountryInfo', method: 'POST',
                     success: function (r) { _done(!r.Error && r.Data ? r.Data : []); },
                     error:   function ()  { _done([]); } });
        }
        if (UpstashService.isEnabled()) {
            UpstashService.get(UpstashService.orgKey('loc-countries')).then(function (data) {
                Array.isArray(data) && data.length > 0 ? _done(data) : _ajax();
            });
        } else { _ajax(); }
    }

    // ── Country codes for mobile field ────────────────────────────────────────
    function _loadCountryCodes(selectedPhonecode) {
        _fetchCountries(function (countries) {
            var $sel = $('#CountryCode').empty().append('<option value="">-- Country Code --</option>');
            countries.forEach(function (c) {
                $sel.append($('<option>').val(c.phonecode).text('(+' + c.phonecode + ') ' + c.name).attr('data-ccode', c.iso2 || '').attr('data-cname', c.name || ''));
            });
            loadCountrySelect2Field('#CountryCode', 'Select Country');
            if (selectedPhonecode) {
                $sel.val(selectedPhonecode);
                if (!$sel.val()) {
                    var norm = String(selectedPhonecode).replace(/\+/g, '');
                    $sel.find('option').each(function () {
                        if (String($(this).val()).replace(/\+/g, '') === norm) { $sel.val($(this).val()); return false; }
                    });
                }
                $sel.trigger('change.select2');
            }
        });
    }

    _loadCountryCodes(_profileCountryCode);

    $('#btnSavePersonal').on('click', function () {
        var firstName = $.trim($('#fistName').val());
        if (!firstName) { showToastNotification('First name is required.', 'error'); return; }

        var Ccode  = $('#CountryCode').find('option:selected').data('ccode');
        var MobNum = $('#MobileNumber').val();
        if (validateMobileNumber(Ccode, MobNum) === false) {
            showToastNotification('Enter a valid mobile number.', 'error'); return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#spinnerPersonal').removeClass('d-none');
        $('#iconPersonal').addClass('d-none');

        var fd = new FormData();
        fd.append('userUid',     $('#HuserUid').val());
        fd.append('fistName',    firstName);
        fd.append('lastName',    $.trim($('#lastName').val()));
        fd.append('CountryCode', $('#CountryCode').val());
        fd.append('CountryISO2', Ccode || 'IN');
        fd.append('MobileNumber', MobNum);
        fd.append('IsPasswordUpdate', 0);
        fd.append(CsrfName, CsrfToken);

        if (hasValue(imgData) && myOneDropzone.files.length === 0) {
            fd.append('ImageRemoved', 1);
            imgData = '';
        }
        if (myOneDropzone && myOneDropzone.files.length > 0 && !myOneDropzone.files[0].isStored) {
            fd.append('UploadImage', myOneDropzone.files[0]);
            imgData = 1;
        }

        $.ajax({
            url: '/profile/updateProfileDetails', method: 'POST',
            data: fd, cache: false, processData: false, contentType: false,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerPersonal').addClass('d-none');
                $('#iconPersonal').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                showToastNotification('Profile updated successfully.', 'success');
                setTimeout(function () { window.location.reload(); }, 1200);
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerPersonal').addClass('d-none');
                $('#iconPersonal').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // WORK INFO SECTION
    // ═══════════════════════════════════════════════════════════════════════════

    var _fpWfDoj         = null;
    var _fpWfProbation   = null;
    var _fpWfLastWorking = null;

    var _fpConfig = {
        dateFormat : 'Y-m-d',
        altInput   : true,
        altFormat  : _transFormDateFormat,
        allowInput : false,
        static     : true,
        position   : 'below left',
    };

    if (document.getElementById('wfDateOfJoining'))    _fpWfDoj         = flatpickr('#wfDateOfJoining',    _fpConfig);
    if (document.getElementById('wfProbationEndDate')) _fpWfProbation   = flatpickr('#wfProbationEndDate', _fpConfig);
    if (document.getElementById('wfLastWorkingDate'))  _fpWfLastWorking = flatpickr('#wfLastWorkingDate',  _fpConfig);

    // Show/hide Exit Details block based on Employment Status
    function _toggleExitDetails(status) {
        var show = (status === 'Resigned' || status === 'Terminated');
        if (show) {
            $('#wfExitDetailsBlock').slideDown(150);
        } else {
            $('#wfExitDetailsBlock').slideUp(150);
        }
    }

    $('#wfEmployeeStatus').on('change', function () {
        _toggleExitDetails($(this).val());
    });

    // Init exit-details visibility on page load
    _toggleExitDetails(_wfShowExitDetails ? $('#wfEmployeeStatus').val() : 'Active');

    $('#btnSaveWorkInfo').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $('#spinnerWorkInfo').removeClass('d-none');
        $('#iconWorkInfo').addClass('d-none');

        var rawDoj         = _fpWfDoj         ? _fpWfDoj.input.value         : '';
        var rawProbEnd     = _fpWfProbation   ? _fpWfProbation.input.value   : '';
        var rawLastWorking = _fpWfLastWorking ? _fpWfLastWorking.input.value : '';

        $.ajax({
            url    : '/settings/profile/saveProfileWorkInfo',
            method : 'POST',
            data   : {
                DepartmentUID      : $('#wfDepartmentUID').val()       || 0,
                DesignationUID     : $('#wfDesignationUID').val()      || 0,
                EmployeeStatus     : $('#wfEmployeeStatus').val(),
                DateOfJoining      : rawDoj,
                EmploymentType     : $('#wfEmploymentType').val(),
                WorkEmail          : $('#wfWorkEmail').val(),
                WorkPhone          : $('#wfWorkPhone').val(),
                ProbationEndDate   : rawProbEnd,
                NoticePeriodDays   : $('#wfNoticePeriodDays').val(),
                ReportingManagerUID: $('#wfReportingManagerUID').val() || 0,
                LastWorkingDate    : rawLastWorking,
                ExitReason         : $('#wfExitReason').val(),
                [CsrfName]         : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerWorkInfo').addClass('d-none');
                $('#iconWorkInfo').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerWorkInfo').addClass('d-none');
                $('#iconWorkInfo').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // ADDRESSES SECTION
    // States/cities load using the country selected in the Personal tab (#CountryCode)
    // ═══════════════════════════════════════════════════════════════════════════

    // Get ISO2 of the country chosen in Personal tab (falls back to org country)
    function _getPersonalCountryISO2() {
        return $('#CountryCode option:selected').data('ccode') || OrgCountryISO2;
    }

    // Get text name of the country chosen in Personal tab
    function _getPersonalCountryName() {
        return $('#CountryCode option:selected').data('cname') || '';
    }

    // Get text of a state/city select's selected option, blank if placeholder/empty
    function _selText(id) {
        var $opt = $('#' + id + ' option:selected');
        var txt  = $.trim($opt.text());
        return (!$opt.val() || txt === '-- Select State --' || txt === '-- Select City --') ? '' : txt;
    }

    // Get data-iso2 from selected state option
    function _stateISO2(id) {
        return $('#' + id + ' option:selected').data('iso2') || '';
    }

    // Show/hide copy buttons based on which address boxes have data
    function _updateProfileAddrCopyBtns() {
        var hasCurr = !!$.trim(_profCurrAddr.Line1);
        var hasPerm = !!$.trim(_profPermAddr.Line1);
        $('#btnCopyToPerm').toggleClass('d-none', !(hasCurr && !hasPerm));
        $('#btnCopyToCurr').toggleClass('d-none', !(!hasCurr && hasPerm));
    }

    // Render in-memory address data into the clickable box
    function _renderProfileAddr(type) {
        var a    = (type === 'current') ? _profCurrAddr : _profPermAddr;
        var $box = $('#' + (type === 'current' ? 'profCurrAddrBox' : 'profPermAddrBox'));
        var label = (type === 'current') ? 'current address' : 'permanent address';
        if (!a.Line1 && !a.State && !a.Pincode) {
            $box.removeClass('has-addr').html(
                '<div class="profile-addr-empty">' +
                '<i class="bx bx-map-alt" style="font-size:2rem;opacity:.35;display:block;margin-bottom:.4rem;"></i>' +
                'Click to add ' + label + '</div>'
            );
            return;
        }
        var lines = [];
        if (a.Line1) lines.push('<div>' + $('<span>').text(a.Line1).html() + '</div>');
        if (a.Line2) lines.push('<div>' + $('<span>').text(a.Line2).html() + '</div>');
        var loc = [a.City, a.State, a.Pincode].filter(Boolean).join(', ');
        if (loc) lines.push('<div>' + $('<span>').text(loc).html() + '</div>');
        $box.addClass('has-addr').html(
            '<div class="profile-addr-text">' + lines.join('') +
            '<div class="addr-edit-hint"><i class="bx bx-pencil me-1"></i>Click to edit</div></div>'
        );
    }

    // Track which address box was opened ('current' or 'permanent')
    var _profAddrActive = false;
    var _profAddrType   = 'current';

    // Open the common address modal pre-filled with profile address data
    function _openProfileAddrModal(type) {
        var a     = (type === 'current') ? _profCurrAddr : _profPermAddr;
        var cISO2 = _getPersonalCountryISO2();

        $('#addrModalTitle').text(type === 'current' ? 'Current Address' : 'Permanent Address');
        $('#AddrUID').val(0);
        $('#ModalAddrLine1').val(a.Line1 || '');
        $('#ModalAddrLine2').val(a.Line2 || '');
        $('#ModalAddrPincode').val(a.Pincode || '');

        // Reset city first
        $('#ModalAddrCity').empty().append('<option value="">-- Select City --</option>');
        if ($('#ModalAddrCity').hasClass('select2'))
            $('#ModalAddrCity').select2({ width: '100%', dropdownParent: $('#addEditAddressModal .modal-content') });

        csc_loadStates('ModalAddrState', cISO2, '', function () {
            if (a.State) {
                var lower = a.State.toLowerCase();
                $('#ModalAddrState option').each(function () {
                    if ($.trim($(this).text()).toLowerCase() === lower) { $('#ModalAddrState').val($(this).val()); return false; }
                });
            }
            if ($('#ModalAddrState').hasClass('select2'))
                $('#ModalAddrState').select2({ width: '100%', dropdownParent: $('#addEditAddressModal .modal-content') });

            var sISO2 = a.StateISO2 || ($('#ModalAddrState option:selected').data('iso2') || '');
            if (sISO2 && a.City) {
                csc_loadCities('ModalAddrCity', cISO2, sISO2, '', a.City, function () {
                    if ($('#ModalAddrCity').hasClass('select2'))
                        $('#ModalAddrCity').select2({ width: '100%', dropdownParent: $('#addEditAddressModal .modal-content') });
                });
            }
        });

        _profAddrType   = type;
        _profAddrActive = true;
        $('#addEditAddressModal').modal('show');
    }

    // Intercept common modal Save — direct binding fires before address.js delegated handler
    $('#AddrSaveBtn').on('click', function (e) {
        if (!_profAddrActive) return;
        e.stopImmediatePropagation(); // prevent address.js billing/shipping logic

        var line1   = $.trim($('#ModalAddrLine1').val());
        if (!line1)   { showAlertMessageSwal('error', '', 'Address Line 1 is required.'); return; }
        var pincode = $.trim($('#ModalAddrPincode').val());
        if (!pincode) { showAlertMessageSwal('error', '', 'Pincode is required.'); return; }

        var $state = $('#ModalAddrState option:selected');
        var $city  = $('#ModalAddrCity  option:selected');
        var sISO2  = $state.data('iso2') || '';
        var sName  = ($state.val() && $state.text() !== '-- Select State --') ? $.trim($state.text()) : '';
        var cName  = ($city.val()  && $city.text()  !== '-- Select City --')  ? $.trim($city.text())  : '';
        var obj    = { Line1: line1, Line2: $.trim($('#ModalAddrLine2').val()), State: sName, StateISO2: sISO2, City: cName, Pincode: pincode };

        if (_profAddrType === 'current') _profCurrAddr = obj; else _profPermAddr = obj;
        _renderProfileAddr(_profAddrType);
        _updateProfileAddrCopyBtns();
        _profAddrActive = false;
        $('#addEditAddressModal').modal('hide');
    });

    // Clear flag when modal is dismissed via Close button
    $('#addEditAddressModal').on('hidden.bs.modal', function () { _profAddrActive = false; });

    // Copy Current → Permanent
    $('#btnCopyToPerm').on('click', function () {
        _profPermAddr = $.extend({}, _profCurrAddr);
        _renderProfileAddr('permanent');
        _updateProfileAddrCopyBtns();
    });

    // Copy Permanent → Current
    $('#btnCopyToCurr').on('click', function () {
        _profCurrAddr = $.extend({}, _profPermAddr);
        _renderProfileAddr('current');
        _updateProfileAddrCopyBtns();
    });

    // Save — POST from in-memory objects
    $('#btnSaveAddresses').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $('#spinnerAddr').removeClass('d-none');
        $('#iconAddr').addClass('d-none');
        $.ajax({
            url: '/settings/profile/saveProfileAddress', method: 'POST',
            data: {
                CurrAddressLine1: _profCurrAddr.Line1,
                CurrAddressLine2: _profCurrAddr.Line2,
                CurrCountry:      _getPersonalCountryName(),
                CurrState:        _profCurrAddr.State,
                CurrCity:         _profCurrAddr.City,
                CurrPinCode:      _profCurrAddr.Pincode,
                PermAddressLine1: _profPermAddr.Line1,
                PermAddressLine2: _profPermAddr.Line2,
                PermCountry:      _getPersonalCountryName(),
                PermState:        _profPermAddr.State,
                PermCity:         _profPermAddr.City,
                PermPinCode:      _profPermAddr.Pincode,
                [CsrfName]:       CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerAddr').addClass('d-none');
                $('#iconAddr').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerAddr').addClass('d-none');
                $('#iconAddr').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // DOCUMENTS SECTION
    // ═══════════════════════════════════════════════════════════════════════════

    var _docTypeColor = { 'ID Proof': 'primary', 'Resume': 'info', 'Certificate': 'success', 'Other': 'secondary' };
    var _docTypeOrder = ['ID Proof', 'Resume', 'Certificate', 'Other'];

    function _docFileIcon(ft, name) {
        if (/image\//i.test(ft) || /\.(jpg|jpeg|png|gif|webp)$/i.test(name)) return 'bx-image-alt text-success';
        if (/pdf/i.test(ft)     || /\.pdf$/i.test(name))                      return 'bxs-file-pdf text-danger';
        if (/word|doc/i.test(ft)|| /\.(doc|docx)$/i.test(name))               return 'bxs-file-doc text-primary';
        return 'bx-file text-secondary';
    }

    function _docFileType(ft, name) {
        if (/image\//i.test(ft) || /\.(jpg|jpeg|png|gif|webp)$/i.test(name)) return 'img';
        if (/pdf/i.test(ft)     || /\.pdf$/i.test(name))                      return 'pdf';
        return 'file';
    }

    function _docFormatSize(b) {
        if (!b) return '';
        if (b < 1024)    return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    }

    function _docFormatDate(s) {
        if (!s) return '';
        try { return new Date(s.replace(' ', 'T')).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }); }
        catch (e) { return s; }
    }

    function _renderProfileAttachments(list) {
        var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
        var $wrap  = $('#profileAttachList').empty();

        if (!list || list.length === 0) {
            $wrap.html(
                '<div class="text-center text-muted py-5">' +
                '<i class="bx bx-folder-open" style="font-size:3rem;display:block;margin-bottom:8px;opacity:.35;"></i>' +
                '<div style="font-size:.84rem;">No documents uploaded yet.</div>' +
                '</div>'
            );
            return;
        }

        // Group by DocType, ordered
        var groups = {};
        list.forEach(function (a) { var g = a.DocType || 'Other'; if (!groups[g]) groups[g] = []; groups[g].push(a); });
        var keys = _docTypeOrder.filter(function (k) { return groups[k]; });
        Object.keys(groups).forEach(function (k) { if (keys.indexOf(k) === -1) keys.push(k); });

        var serial = 1;
        keys.forEach(function (grp) {
            var items = groups[grp];
            var color = _docTypeColor[grp] || 'secondary';
            var safeGrp = $('<s>').text(grp).html();

            $wrap.append(
                '<div class="d-flex align-items-center gap-2 mb-2 ' + (serial > 1 ? 'mt-4' : 'mt-1') + '">' +
                '<span class="badge bg-label-' + color + '" style="font-size:.73rem;padding:.3em .75em;">' + safeGrp + '</span>' +
                '<span class="text-muted" style="font-size:.74rem;">' + items.length + ' file' + (items.length !== 1 ? 's' : '') + '</span>' +
                '<div class="flex-grow-1 border-top ms-1" style="opacity:.4;"></div>' +
                '</div>'
            );

            items.forEach(function (a) {
                var name     = a.FileName || '';
                var safeName = $('<s>').text(name).html();
                var fullUrl  = cdnUrl + (a.FilePath || '');
                var encUrl   = encodeURIComponent(fullUrl);
                var ftype    = _docFileType(a.FileType || '', name);
                var iconCls  = _docFileIcon(a.FileType || '', name);
                var meta     = [_docFormatSize(a.FileSize), _docFormatDate(a.CreatedOn)].filter(Boolean).join(' · ');

                $wrap.append(
                    '<div class="d-flex align-items-center gap-2 border rounded px-3 py-2 mb-2 profile-attach-item" data-uid="' + a.AttachUID + '" style="font-size:.82rem;">' +
                    '<span class="text-muted fw-semibold" style="width:20px;font-size:.72rem;flex-shrink:0;text-align:center;">' + serial + '</span>' +
                    '<i class="bx ' + iconCls + ' flex-shrink-0 profile-attach-preview-btn" data-enc="' + encUrl + '" data-ftype="' + ftype + '" data-fname="' + safeName + '" style="font-size:1.35rem;cursor:pointer;" title="Preview"></i>' +
                    '<div style="flex:1;min-width:0;">' +
                    '<div class="text-truncate fw-medium profile-attach-preview-btn" data-enc="' + encUrl + '" data-ftype="' + ftype + '" data-fname="' + safeName + '" style="cursor:pointer;" title="' + safeName + '">' + safeName + '</div>' +
                    (meta ? '<div class="text-muted" style="font-size:.71rem;">' + meta + '</div>' : '') +
                    '</div>' +
                    '<div class="d-flex gap-1 ms-auto flex-shrink-0">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary profile-attach-preview-btn" data-enc="' + encUrl + '" data-ftype="' + ftype + '" data-fname="' + safeName + '" title="Preview" style="padding:2px 6px;line-height:1;"><i class="bx bx-show" style="font-size:.9rem;"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger profile-attach-delete-btn" data-uid="' + a.AttachUID + '" title="Delete" style="padding:2px 6px;line-height:1;"><i class="bx bx-trash" style="font-size:.9rem;"></i></button>' +
                    '</div>' +
                    '</div>'
                );
                serial++;
            });
        });
    }

    function _openProfileAttachPreview(encUrl, type, name) {
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

    // Preview — delegated handler using data-fname (not data-name to avoid conflicts)
    $(document).on('click', '.profile-attach-preview-btn', function () {
        _openProfileAttachPreview($(this).data('enc'), $(this).data('ftype'), $(this).data('fname'));
    });

    function _updateDocBadge(cnt) {
        var $badge = $('#nav-documents .badge');
        if (cnt > 0) {
            if (!$badge.length) $('#nav-documents').append('<span class="badge bg-label-secondary ms-auto" style="font-size:.68rem;">' + cnt + '</span>');
            else $badge.text(cnt);
        } else { $badge.remove(); }
    }

    // Init: render from PHP-injected data
    _renderProfileAttachments(_profileInitAttachments);
    _updateDocBadge(_profileInitAttachments.length);

    // File chosen
    $('#profileAttachFile').on('change', function () {
        var name = this.files.length ? this.files[0].name : '';
        $('#profileAttachFileName').text(name || 'No file chosen');
        $('#btnUploadProfileAttach').toggleClass('d-none', !name);
    });

    // Upload
    $('#btnUploadProfileAttach').on('click', function () {
        var fileEl = document.getElementById('profileAttachFile');
        if (!fileEl || !fileEl.files.length) return;
        if (fileEl.files[0].size > 5 * 1024 * 1024) { showToastNotification('File size must be under 5 MB.', 'error'); return; }

        var fd = new FormData();
        fd.append('DocType',    $('#profileAttachDocType').val());
        fd.append('AttachFile', fileEl.files[0]);
        fd.append(CsrfName, CsrfToken);

        var $btn = $(this).prop('disabled', true);
        $('#spinnerProfileAttach').removeClass('d-none');
        $('#iconProfileAttach').addClass('d-none');

        $.ajax({
            url: '/settings/profile/saveProfileAttachment', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerProfileAttach').addClass('d-none');
                $('#iconProfileAttach').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                fileEl.value = '';
                $('#profileAttachFileName').text('No file chosen');
                $btn.addClass('d-none');
                var list = Array.isArray(resp.Attachments) ? resp.Attachments : [];
                _renderProfileAttachments(list);
                _updateDocBadge(list.length);
                showToastNotification('File uploaded successfully.', 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerProfileAttach').addClass('d-none');
                $('#iconProfileAttach').removeClass('d-none');
                showToastNotification('Upload failed. Please try again.', 'error');
            }
        });
    });

    // Delete attachment
    $(document).on('click', '.profile-attach-delete-btn', function () {
        var uid = parseInt($(this).data('uid')) || 0;
        if (!uid || !confirm('Delete this document? This cannot be undone.')) return;
        $.ajax({
            url: '/settings/profile/deleteProfileAttachment', method: 'POST',
            data: { AttachUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                var list = Array.isArray(resp.Attachments) ? resp.Attachments : [];
                _renderProfileAttachments(list);
                _updateDocBadge(list.length);
                showToastNotification('Document deleted.', 'success');
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // SIGNATURES SECTION (all existing logic preserved)
    // ═══════════════════════════════════════════════════════════════════════════
    var signaturePad    = null;
    var pendingDrawData = null;

    function _loadSignatureList() {
        $('#signaturesContainer').html('<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>');
        $.post('/settings/profile/getSignatureList', { [CsrfName]: CsrfToken })
            .done(function (html) {
                CsrfToken = (html.match(/data-csrf-token="([^"]+)"/) || [])[1] || CsrfToken;
                $('#signaturesContainer').html(html);
            })
            .fail(function () {
                $('#signaturesContainer').html('<div class="text-danger text-center py-4">Unable to load signatures.</div>');
            });
    }

    function _openSigModal(editUID, label, type, imgSrc) {
        _resetSigModal();
        editUID = parseInt(editUID) || 0;
        $('#sigEditUID').val(editUID);
        if (editUID > 0) {
            $('#sigModalTitle').text('Edit Signature');
            $('#sigModalMeta').text('Update the label or replace the signature content');
            $('#sigModalIcon').removeClass('bx-pen').addClass('bx-edit');
            $('#btnSaveSignature').html('<i class="bx bx-save me-1"></i>Update Signature');
            $('#sigLabel').val(label || '');
            if (type === 'Draw') {
                $('#sig-upload-tab').parent().hide();
                pendingDrawData = imgSrc || null;
                $('#sig-draw-tab').tab('show');
            } else {
                $('#sig-draw-tab').parent().hide();
                if (imgSrc) {
                    $('#sigUploadPreviewImg').attr('src', imgSrc);
                    $('#sigUploadMeta').text('Current signature — choose a new file to replace it');
                    $('#sigUploadPreview').removeClass('d-none');
                }
            }
        }
        $('#signatureModal').modal('show');
    }

    function _resetSigModal() {
        $('#sigEditUID').val(0);
        $('#sigLabel').val('My Signature');
        $('#sigImageInput').val('');
        $('#sigUploadPreview').addClass('d-none');
        $('#sigUploadError, #sigDrawError').addClass('d-none');
        $('#sig-upload-tab').parent().show();
        $('#sig-draw-tab').parent().show();
        $('#sig-upload-tab').tab('show');
        $('#sigModalTitle').text('Add Signature');
        $('#sigModalMeta').text('Upload an image or draw your signature on a canvas');
        $('#sigModalIcon').removeClass('bx-edit').addClass('bx-pen');
        $('#btnSaveSignature').html('<i class="bx bx-save me-1"></i>Save Signature');
        pendingDrawData = null;
        if (signaturePad) signaturePad.clear();
    }

    $(document).on('click', '#btnAddSignature, #btnAddSigEmpty', function () { _openSigModal(0); });
    $(document).on('click', '.editSigBtn', function () {
        _openSigModal($(this).data('uid'), $(this).data('label'), $(this).data('type'), $(this).data('imgsrc'));
    });

    $('#sig-draw-tab').on('shown.bs.tab', function () { _initSignaturePad(); });

    function _initSignaturePad() {
        var canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var rect  = canvas.getBoundingClientRect();
        canvas.width  = rect.width  * ratio;
        canvas.height = rect.height * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        if (signaturePad) signaturePad.clear();
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)',
            penColor: $('#sigPenColor').val() || '#000000',
            minWidth: 1, maxWidth: 3,
        });
        if (pendingDrawData) { signaturePad.fromDataURL(pendingDrawData); pendingDrawData = null; }
    }

    $('#sigPenColor').on('input', function () { if (signaturePad) signaturePad.penColor = $(this).val(); });
    $('#btnClearCanvas').on('click', function () { if (signaturePad) signaturePad.clear(); $('#sigDrawError').addClass('d-none'); });

    $('#sigImageInput').on('change', function () {
        var file = this.files[0];
        $('#sigUploadError').addClass('d-none');
        if (!file) { $('#sigUploadPreview').addClass('d-none'); return; }
        if (!['image/png','image/jpeg','image/jpg'].includes(file.type)) {
            $('#sigUploadError').removeClass('d-none').text('Only PNG and JPG files are allowed.');
            this.value = ''; return;
        }
        if (file.size > 500 * 1024) {
            $('#sigUploadError').removeClass('d-none').text('File size exceeds 500 KB.');
            this.value = ''; return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#sigUploadPreviewImg').attr('src', e.target.result);
            $('#sigUploadMeta').text(file.name + ' · ' + (file.size / 1024).toFixed(1) + ' KB');
            $('#sigUploadPreview').removeClass('d-none');
        };
        reader.readAsDataURL(file);
    });

    $('#btnSaveSignature').on('click', function () {
        var isDrawTab = $('#sig-draw-tab').hasClass('active');
        var label     = $('#sigLabel').val().trim() || 'My Signature';
        isDrawTab ? _saveSigDraw(label) : _saveSigUpload(label);
    });

    function _saveSigUpload(label) {
        var editUID = parseInt($('#sigEditUID').val()) || 0;
        var fileInput = document.getElementById('sigImageInput');
        var hasFile   = fileInput.files && fileInput.files.length > 0;
        if (!editUID && !hasFile) { $('#sigUploadError').removeClass('d-none').text('Please select an image file.'); return; }
        var url  = editUID > 0 ? '/settings/profile/updateSignature' : '/settings/profile/saveSignature';
        var $btn = $('#btnSaveSignature').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        var fd   = new FormData();
        fd.append('SignatureType', 'Upload');
        fd.append('Label', label);
        if (editUID > 0) fd.append('SignatureUID', editUID);
        if (hasFile)     fd.append('SignatureImage', fileInput.files[0]);
        fd.append(CsrfName, CsrfToken);
        $.ajax({
            url: url, method: 'POST', data: fd, cache: false, processData: false, contentType: false,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                if (resp.Error) { $('#sigUploadError').removeClass('d-none').text(resp.Message); return; }
                $('#signatureModal').modal('hide');
                _sigListLoaded = false; _loadSignatureList();
                Swal.fire(editUID > 0 ? 'Signature updated!' : 'Signature saved!', '', 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                $('#sigUploadError').removeClass('d-none').text('Network error. Please try again.');
            }
        });
    }

    function _saveSigDraw(label) {
        var editUID  = parseInt($('#sigEditUID').val()) || 0;
        var hasDrawn = signaturePad && !signaturePad.isEmpty();
        if (!editUID && !hasDrawn) { $('#sigDrawError').removeClass('d-none').text('Please draw your signature before saving.'); return; }
        var url      = editUID > 0 ? '/settings/profile/updateSignature' : '/settings/profile/saveSignature';
        var drawData = hasDrawn ? signaturePad.toDataURL('image/png') : '';
        var $btn     = $('#btnSaveSignature').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        var postData = { SignatureType: 'Draw', Label: label, [CsrfName]: CsrfToken };
        if (editUID > 0) postData.SignatureUID = editUID;
        if (drawData)    postData.DrawData     = drawData;
        $.ajax({
            url: url, method: 'POST', data: postData,
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                if (resp.Error) { $('#sigDrawError').removeClass('d-none').text(resp.Message); return; }
                $('#signatureModal').modal('hide');
                _sigListLoaded = false; _loadSignatureList();
                Swal.fire(editUID > 0 ? 'Signature updated!' : 'Signature saved!', '', 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                $('#sigDrawError').removeClass('d-none').text('Network error. Please try again.');
            }
        });
    }

    $(document).on('click', '.setDefaultSigBtn', function () {
        var uid = $(this).data('uid');
        $.post('/settings/profile/setDefaultSignature', { SignatureUID: uid, [CsrfName]: CsrfToken }, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) { Swal.fire(resp.Message, '', 'error'); return; }
            _sigListLoaded = false; _loadSignatureList();
        });
    });

    $(document).on('click', '.deleteSigBtn', function () {
        var uid   = $(this).data('uid');
        var label = $(this).data('label');
        Swal.fire({
            title: 'Delete Signature?',
            text: '"' + label + '" will be permanently removed.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, delete it', confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post('/settings/profile/deleteSignature', { SignatureUID: uid, [CsrfName]: CsrfToken }, function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { Swal.fire(resp.Message, '', 'error'); return; }
                $('#sigCard_' + uid).fadeOut(250, function () {
                    $(this).remove();
                    if ($('#signaturesContainer .col-xl-4').length === 0) {
                        _sigListLoaded = false; _loadSignatureList();
                    }
                });
            });
        });
    });

    $('#signatureModal').on('hidden.bs.modal', _resetSigModal);
    $('#signatureModal').on('shown.bs.modal', function () {
        if ($('#sig-draw-tab').hasClass('active')) _initSignaturePad();
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // EMERGENCY CONTACTS SECTION
    // ═══════════════════════════════════════════════════════════════════════════

    var _emgData           = {}; // EmgContactUID → row object
    var _pendingEmg        = null;
    var _emgCountriesReady = false; // countries loaded into #emgCountry once

    // ── Country / State / City helpers for emergency modal ────────────────────
    function _emgSelName(id) {
        var $opt = $('#' + id + ' option:selected');
        return $opt.val() ? $.trim($opt.text()) : '';
    }

    function _emgLoadCities(countryISO2, stateISO2, cityName) {
        csc_loadCities('emgCity', countryISO2, stateISO2, '', cityName);
    }

    function _emgLoadStates(countryISO2, stateName, cityName) {
        csc_loadStates('emgState', countryISO2, '', function () {
            if (stateName) {
                var lower = $.trim(stateName).toLowerCase();
                $('#emgState option').each(function () {
                    if ($.trim($(this).text()).toLowerCase() === lower) {
                        $('#emgState').val($(this).val()); return false;
                    }
                });
            }
            var stateISO2 = $('#emgState option:selected').data('iso2') || '';
            if (stateISO2) {
                _emgLoadCities(countryISO2, stateISO2, cityName || '');
            } else {
                $('#emgCity').empty().append('<option value="">-- Select City --</option>');
            }
        });
    }

    function _emgFillForm(r) {
        r = r || {};
        $('#emgName').val(r.Name             || '');
        $('#emgRelationship').val(r.Relationship || '');
        $('#emgPhoneNumber').val(r.PhoneNumber   || '');
        $('#emgEmailAddress').val(r.EmailAddress || '');
        $('#emgAddressLine1').val(r.AddressLine1 || '');
        $('#emgAddressLine2').val(r.AddressLine2 || '');
        $('#emgIsPrimary').prop('checked', r.IsPrimary == 1);

        // Resolve country ISO2 — match stored name; fall back to org country
        var countryISO2 = OrgCountryISO2;
        if (r.Country) {
            var cLower = $.trim(r.Country).toLowerCase();
            $('#emgCountry option').each(function () {
                if ($.trim($(this).text()).toLowerCase() === cLower) {
                    countryISO2 = $(this).val(); return false;
                }
            });
        }
        $('#emgCountry').val(countryISO2 || OrgCountryISO2);

        // Load states → cities
        $('#emgCity').empty().append('<option value="">-- Select City --</option>');
        _emgLoadStates(countryISO2, r.State || '', r.City || '');

        $('#emgName').focus();
    }

    // Load country list once from Upstash then call onDone
    function _emgEnsureCountries(onDone) {
        if (_emgCountriesReady) { onDone(); return; }
        _fetchCountries(function (countries) {
            var $sel = $('#emgCountry').empty().append('<option value="">-- Select Country --</option>');
            countries.forEach(function (c) {
                $sel.append($('<option>').val(c.iso2).text(c.name));
            });
            _emgCountriesReady = true;
            onDone();
        });
    }

    // Country change → reload states + clear city
    $(document).on('change', '#emgCountry', function () {
        var iso2 = $(this).val();
        $('#emgState').empty().append('<option value="">-- Select State --</option>');
        $('#emgCity').empty().append('<option value="">-- Select City --</option>');
        if (iso2) csc_loadStates('emgState', iso2, '');
    });

    // State change → reload cities
    $(document).on('change', '#emgState', function () {
        var stateISO2   = $(this).find('option:selected').data('iso2') || '';
        var countryISO2 = $('#emgCountry').val() || OrgCountryISO2;
        $('#emgCity').empty().append('<option value="">-- Select City --</option>');
        if (stateISO2) csc_loadCities('emgCity', countryISO2, stateISO2, '', '');
    });

    function _renderEmgTable(list) {
        _emgData = {};
        var $wrap  = $('#emgContactTableWrap').empty();
        var hasPrimary = list && list.some(function (r) { return r.IsPrimary == 1; });
        $('#emgNoPrimaryAlert').toggleClass('d-none', hasPrimary);
        if (!list || list.length === 0) {
            $wrap.html(
                '<div class="text-center text-muted py-5">' +
                '<i class="bx bx-phone-call" style="font-size:3rem;display:block;opacity:.3;margin-bottom:8px;"></i>' +
                '<div style="font-size:.84rem;">No emergency contacts added yet.</div>' +
                '</div>'
            );
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered table-hover align-middle mb-0" style="font-size:.84rem;">' +
            '<thead class="r2k-thead"><tr>' +
            '<th style="width:36px;text-align:center;">#</th>' +
            '<th>Name</th><th>Relationship</th><th>Phone Number</th><th>Address</th>' +
            '<th style="width:120px;text-align:center;">Primary Contact</th>' +
            '<th style="width:80px;text-align:center;">Actions</th>' +
            '</tr></thead><tbody>';
        list.forEach(function (r, i) {
            _emgData[r.EmgContactUID] = r;
            var addrParts = [r.AddressLine1, r.AddressLine2, r.City, r.State, r.Country].filter(Boolean);
            var addrText  = addrParts.length ? $('<s>').text(addrParts.join(', ')).html() : '<span class="text-muted">—</span>';
            var isPrimary    = r.IsPrimary == 1;
            var primaryBadge = isPrimary
                ? '<span class="badge bg-success" style="font-size:.72rem;">Primary</span>'
                : '<button type="button" class="btn btn-xs btn-outline-secondary setEmgPrimaryBtn py-0 px-2" data-uid="' + r.EmgContactUID + '" style="font-size:.72rem;">Set Primary</button>';
            html += '<tr>' +
                '<td class="text-center text-muted">' + (i + 1) + '</td>' +
                '<td class="fw-medium">' + $('<s>').text(r.Name         || '').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.Relationship  || '').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.PhoneNumber   || '').html() + '</td>' +
                '<td>'                  + addrText + '</td>' +
                '<td class="text-center">' + primaryBadge + '</td>' +
                '<td class="text-center">' +
                '<div class="d-flex gap-1 justify-content-center">' +
                '<button type="button" class="btn btn-icon text-primary editEmgBtn" data-uid="' + r.EmgContactUID + '" title="Edit"><i class="bx bx-edit-alt fs-6"></i></button>' +
                '<button type="button" class="btn btn-icon text-danger delEmgBtn" data-uid="' + r.EmgContactUID + '" title="Delete"><i class="bx bx-trash fs-6"></i></button>' +
                '</div>' +
                '</td></tr>';
        });
        html += '</tbody></table></div>';
        $wrap.html(html);
    }

    function _loadEmgContacts() {
        $('#emgContactTableWrap').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>');
        $.post('/settings/profile/getEmergencyContacts', { [CsrfName]: CsrfToken }, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) { $('#emgContactTableWrap').html('<div class="text-danger text-center py-3">Unable to load contacts.</div>'); return; }
            _renderEmgTable(resp.Contacts || []);
        }).fail(function () {
            $('#emgContactTableWrap').html('<div class="text-danger text-center py-3">Network error.</div>');
        });
    }

    function _openEmgModal(uid) {
        _pendingEmg = (uid > 0 && _emgData[uid]) ? _emgData[uid] : null;
        $('#emgContactUID').val(uid || 0);
        $('#emgModalTitle').text(uid > 0 ? 'Edit Emergency Contact' : 'Add Emergency Contact');
        $('#emgContactModal').modal('show');
    }

    $('#emgContactModal').on('shown.bs.modal', function () {
        var r = _pendingEmg;
        _pendingEmg = null;
        _emgEnsureCountries(function () { _emgFillForm(r); });
    });

    $('#emgContactModal').on('hidden.bs.modal', function () {
        $('#emgContactUID').val(0);
        $('#emgName,#emgPhoneNumber,#emgEmailAddress,#emgAddressLine1,#emgAddressLine2').val('');
        $('#emgRelationship').val('');
        $('#emgState').empty().append('<option value="">-- Select State --</option>');
        $('#emgCity').empty().append('<option value="">-- Select City --</option>');
        // Reset country to org default (keep countries list loaded)
        if (_emgCountriesReady) $('#emgCountry').val(OrgCountryISO2);
        $('#emgIsPrimary').prop('checked', false);
        $('#btnSaveEmgContact').prop('disabled', false);
        $('#spinnerEmg').addClass('d-none'); $('#iconEmg').removeClass('d-none');
    });

    $(document).on('click', '#btnAddEmgContact', function () { _openEmgModal(0); });
    $(document).on('click', '.editEmgBtn', function () { _openEmgModal(parseInt($(this).data('uid')) || 0); });

    $('#btnSaveEmgContact').on('click', function () {
        var name     = $.trim($('#emgName').val());
        var relation = $('#emgRelationship').val();
        var phone    = $.trim($('#emgPhoneNumber').val());
        if (!name)     { showToastNotification('Name is required.', 'error'); return; }
        if (!relation) { showToastNotification('Relationship is required.', 'error'); return; }
        if (!phone)    { showToastNotification('Phone number is required.', 'error'); return; }
        var $btn = $(this).prop('disabled', true);
        $('#spinnerEmg').removeClass('d-none'); $('#iconEmg').addClass('d-none');
        $.ajax({
            url: '/settings/profile/saveEmergencyContact', method: 'POST',
            data: {
                EmgContactUID: $('#emgContactUID').val(),
                Name         : name,
                Relationship : relation,
                PhoneNumber  : phone,
                EmailAddress : $('#emgEmailAddress').val(),
                AddressLine1 : $('#emgAddressLine1').val(),
                AddressLine2 : $('#emgAddressLine2').val(),
                City         : _emgSelName('emgCity'),
                State        : _emgSelName('emgState'),
                Country      : _emgSelName('emgCountry'),
                IsPrimary    : $('#emgIsPrimary').is(':checked') ? 1 : 0,
                [CsrfName]   : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerEmg').addClass('d-none'); $('#iconEmg').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $('#emgContactModal').modal('hide');
                _renderEmgTable(resp.Contacts || []);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerEmg').addClass('d-none'); $('#iconEmg').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.setEmgPrimaryBtn', function () {
        var $btn = $(this);
        var uid  = parseInt($btn.data('uid')) || 0;
        if (!uid) return;
        $btn.prop('disabled', true).text('Saving...');
        $.post('/settings/profile/setPrimaryContact', { EmgContactUID: uid, [CsrfName]: CsrfToken }, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) { showToastNotification(resp.Message, 'error'); $btn.prop('disabled', false).text('Set Primary'); return; }
            showToastNotification('Primary contact updated.', 'success');
            _loadEmgContacts();
        }).fail(function () {
            $btn.prop('disabled', false).text('Set Primary');
        });
    });

    $(document).on('click', '.delEmgBtn', function () {
        var uid = parseInt($(this).data('uid')) || 0;
        if (!uid) return;
        Swal.fire({
            title: 'Delete Emergency Contact?', text: 'This will permanently remove the contact.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.post('/settings/profile/deleteEmergencyContact', { EmgContactUID: uid, [CsrfName]: CsrfToken }, function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderEmgTable(resp.Contacts || []);
                showToastNotification('Contact deleted.', 'success');
            });
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // EDUCATION & EXPERIENCE SECTION
    // ═══════════════════════════════════════════════════════════════════════════

    var _eduData    = {}; // EduUID → row object
    var _expData    = {}; // ExpUID → row object
    var _fpEduDoc   = null;
    var _fpExpStart = null;
    var _fpExpEnd   = null;
    var _pendingEdu = null;
    var _pendingExp = null;

    function _fmtEduDate(s) {
        if (!s) return '—';
        try { return new Date(s.split(' ')[0].replace(/-/g, '/')).toLocaleDateString(undefined, { month: 'short', year: 'numeric' }); }
        catch (e) { return s; }
    }

    function _renderEduTable(list) {
        _eduData = {};
        var $wrap = $('#eduTableWrap').empty();
        if (!list || list.length === 0) {
            $wrap.html(
                '<div class="text-center text-muted py-5">' +
                '<i class="bx bx-book-open" style="font-size:3rem;display:block;opacity:.3;margin-bottom:8px;"></i>' +
                '<div style="font-size:.84rem;">No education records added yet.</div>' +
                '</div>'
            );
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered table-hover align-middle mb-0" style="font-size:.84rem;">' +
            '<thead class="r2k-thead"><tr>' +
            '<th style="width:36px;text-align:center;">#</th>' +
            '<th>Institution</th><th>Degree</th><th>Field of Study</th><th>CGPA</th><th>Completion</th>' +
            '<th style="width:80px;text-align:center;">Actions</th>' +
            '</tr></thead><tbody>';
        list.forEach(function (r, i) {
            _eduData[r.EduUID] = r;
            html += '<tr>' +
                '<td class="text-center text-muted">' + (i + 1) + '</td>' +
                '<td class="fw-medium">' + $('<s>').text(r.Institution  || '').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.Degree        || '—').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.FieldOfStudy  || '—').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.CGPA          || '—').html() + '</td>' +
                '<td>'                  + _fmtEduDate(r.DateOfCompletion) + '</td>' +
                '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-primary editEduBtn py-0 px-1 me-1" data-uid="' + r.EduUID + '" title="Edit"><i class="bx bx-edit-alt"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger delEduBtn py-0 px-1"         data-uid="' + r.EduUID + '" title="Delete"><i class="bx bx-trash"></i></button>' +
                '</td></tr>';
        });
        html += '</tbody></table></div>';
        $wrap.html(html);
    }

    function _renderExpTable(list) {
        _expData = {};
        var $wrap = $('#expTableWrap').empty();
        if (!list || list.length === 0) {
            $wrap.html(
                '<div class="text-center text-muted py-5">' +
                '<i class="bx bx-briefcase" style="font-size:3rem;display:block;opacity:.3;margin-bottom:8px;"></i>' +
                '<div style="font-size:.84rem;">No experience records added yet.</div>' +
                '</div>'
            );
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered table-hover align-middle mb-0" style="font-size:.84rem;">' +
            '<thead class="r2k-thead"><tr>' +
            '<th style="width:36px;text-align:center;">#</th>' +
            '<th>Employer Name</th><th>Job Title</th><th>Start Date</th><th>End Date</th><th>Job Description</th>' +
            '<th style="width:80px;text-align:center;">Actions</th>' +
            '</tr></thead><tbody>';
        list.forEach(function (r, i) {
            _expData[r.ExpUID] = r;
            var endDt = r.EndDate
                ? _fmtEduDate(r.EndDate)
                : '<span class="badge bg-label-success" style="font-size:.7rem;">Current</span>';
            var descText = $('<s>').text(r.JobDescription || '—').html();
            html += '<tr>' +
                '<td class="text-center text-muted">' + (i + 1) + '</td>' +
                '<td class="fw-medium">' + $('<s>').text(r.EmployerName || '').html() + '</td>' +
                '<td>'                  + $('<s>').text(r.JobTitle      || '—').html() + '</td>' +
                '<td>'                  + _fmtEduDate(r.StartDate) + '</td>' +
                '<td>'                  + endDt + '</td>' +
                '<td class="text-truncate" style="max-width:200px;" title="' + $('<s>').text(r.JobDescription || '').html() + '">' + descText + '</td>' +
                '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-primary editExpBtn py-0 px-1 me-1" data-uid="' + r.ExpUID + '" title="Edit"><i class="bx bx-edit-alt"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger delExpBtn py-0 px-1"         data-uid="' + r.ExpUID + '" title="Delete"><i class="bx bx-trash"></i></button>' +
                '</td></tr>';
        });
        html += '</tbody></table></div>';
        $wrap.html(html);
    }

    function _loadEduExpList() {
        $('#eduTableWrap').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>');
        $('#expTableWrap').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>');
        $.post('/settings/profile/getEduExp', { [CsrfName]: CsrfToken }, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) {
                $('#eduTableWrap,#expTableWrap').html('<div class="text-danger text-center py-3">Unable to load records.</div>');
                return;
            }
            _renderEduTable(resp.Education  || []);
            _renderExpTable(resp.Experience || []);
        }).fail(function () {
            $('#eduTableWrap,#expTableWrap').html('<div class="text-danger text-center py-3">Network error.</div>');
        });
    }

    // ── Education modal ───────────────────────────────────────────────────────
    function _openEduModal(uid) {
        var r = (uid > 0 && _eduData[uid]) ? _eduData[uid] : null;
        _pendingEdu = r;
        $('#eduUID').val(uid || 0);
        $('#eduModalTitle').text(uid > 0 ? 'Edit Education' : 'Add Education');
        $('#educationModal').modal('show');
    }

    $('#educationModal').on('shown.bs.modal', function () {
        if (!_fpEduDoc) _fpEduDoc = flatpickr('#eduDateOfCompletion', _fpConfig);
        var r = _pendingEdu || {};
        $('#eduInstitution').val(r.Institution   || '');
        $('#eduDegree').val(r.Degree             || '');
        $('#eduFieldOfStudy').val(r.FieldOfStudy || '');
        $('#eduCGPA').val(r.CGPA                 || '');
        _fpEduDoc.setDate(r.DateOfCompletion     || null, false);
        _pendingEdu = null;
        $('#eduInstitution').focus();
    });

    $('#educationModal').on('hidden.bs.modal', function () {
        $('#eduUID').val(0);
        $('#eduInstitution,#eduDegree,#eduFieldOfStudy,#eduCGPA').val('');
        if (_fpEduDoc) _fpEduDoc.clear();
        $('#btnSaveEdu').prop('disabled', false);
        $('#spinnerEdu').addClass('d-none');
        $('#iconEdu').removeClass('d-none');
    });

    $(document).on('click', '#btnAddEdu', function () { _openEduModal(0); });
    $(document).on('click', '.editEduBtn', function () { _openEduModal(parseInt($(this).data('uid')) || 0); });

    $('#btnSaveEdu').on('click', function () {
        var inst = $.trim($('#eduInstitution').val());
        if (!inst) { showToastNotification('Institution name is required.', 'error'); return; }
        var $btn = $(this).prop('disabled', true);
        $('#spinnerEdu').removeClass('d-none'); $('#iconEdu').addClass('d-none');
        $.ajax({
            url: '/settings/profile/saveEducation', method: 'POST',
            data: {
                EduUID          : $('#eduUID').val(),
                Institution     : inst,
                Degree          : $('#eduDegree').val(),
                FieldOfStudy    : $('#eduFieldOfStudy').val(),
                CGPA            : $('#eduCGPA').val(),
                DateOfCompletion: _fpEduDoc ? _fpEduDoc.input.value : '',
                [CsrfName]      : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerEdu').addClass('d-none'); $('#iconEdu').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $('#educationModal').modal('hide');
                _renderEduTable(resp.Education || []);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerEdu').addClass('d-none'); $('#iconEdu').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.delEduBtn', function () {
        var uid = parseInt($(this).data('uid')) || 0;
        if (!uid) return;
        Swal.fire({
            title: 'Delete Education Record?', text: 'This will permanently remove the record.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.post('/settings/profile/deleteEducation', { EduUID: uid, [CsrfName]: CsrfToken }, function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderEduTable(resp.Education || []);
                showToastNotification('Education record deleted.', 'success');
            });
        });
    });

    // ── Experience modal ──────────────────────────────────────────────────────
    function _openExpModal(uid) {
        var r = (uid > 0 && _expData[uid]) ? _expData[uid] : null;
        _pendingExp = r;
        $('#expUID').val(uid || 0);
        $('#expModalTitle').text(uid > 0 ? 'Edit Experience' : 'Add Experience');
        $('#experienceModal').modal('show');
    }

    $('#experienceModal').on('shown.bs.modal', function () {
        if (!_fpExpStart) {
            _fpExpStart = flatpickr('#expStartDate', _fpConfig);
            _fpExpEnd   = flatpickr('#expEndDate',   _fpConfig);
        }
        var r = _pendingExp || {};
        $('#expEmployerName').val(r.EmployerName    || '');
        $('#expJobTitle').val(r.JobTitle            || '');
        $('#expJobDescription').val(r.JobDescription || '');
        _fpExpStart.setDate(r.StartDate || null, false);
        _fpExpEnd.setDate(r.EndDate     || null, false);
        _pendingExp = null;
        $('#expEmployerName').focus();
    });

    $('#experienceModal').on('hidden.bs.modal', function () {
        $('#expUID').val(0);
        $('#expEmployerName,#expJobTitle,#expJobDescription').val('');
        if (_fpExpStart) _fpExpStart.clear();
        if (_fpExpEnd)   _fpExpEnd.clear();
        $('#btnSaveExp').prop('disabled', false);
        $('#spinnerExp').addClass('d-none');
        $('#iconExp').removeClass('d-none');
    });

    $(document).on('click', '#btnAddExp', function () { _openExpModal(0); });
    $(document).on('click', '.editExpBtn', function () { _openExpModal(parseInt($(this).data('uid')) || 0); });

    $('#btnSaveExp').on('click', function () {
        var employer = $.trim($('#expEmployerName').val());
        if (!employer) { showToastNotification('Employer name is required.', 'error'); return; }
        var $btn = $(this).prop('disabled', true);
        $('#spinnerExp').removeClass('d-none'); $('#iconExp').addClass('d-none');
        $.ajax({
            url: '/settings/profile/saveExperience', method: 'POST',
            data: {
                ExpUID        : $('#expUID').val(),
                EmployerName  : employer,
                JobTitle      : $('#expJobTitle').val(),
                StartDate     : _fpExpStart ? _fpExpStart.input.value : '',
                EndDate       : _fpExpEnd   ? _fpExpEnd.input.value   : '',
                JobDescription: $('#expJobDescription').val(),
                [CsrfName]    : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#spinnerExp').addClass('d-none'); $('#iconExp').removeClass('d-none');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $('#experienceModal').modal('hide');
                _renderExpTable(resp.Experience || []);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#spinnerExp').addClass('d-none'); $('#iconExp').removeClass('d-none');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.delExpBtn', function () {
        var uid = parseInt($(this).data('uid')) || 0;
        if (!uid) return;
        Swal.fire({
            title: 'Delete Experience Record?', text: 'This will permanently remove the record.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.post('/settings/profile/deleteExperience', { ExpUID: uid, [CsrfName]: CsrfToken }, function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderExpTable(resp.Experience || []);
                showToastNotification('Experience record deleted.', 'success');
            });
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // BANK DETAILS SECTION
    // ═══════════════════════════════════════════════════════════════════════════

    $('#btnFetchIFSC').on('click', function () {
        var ifsc = $.trim($('#ifscCode').val()).toUpperCase();
        if (!ifsc) { showToastNotification('Please enter an IFSC code first.', 'warning'); return; }
        if (ifsc.length < 8) { showToastNotification('IFSC code must be at least 8 characters.', 'warning'); return; }
        $('#spinnerIFSC').removeClass('d-none');
        $('#iconIFSC').addClass('d-none');
        $('#btnFetchIFSC').prop('disabled', true);
        $.ajax({
            url: 'https://ifsc.razorpay.com/' + encodeURIComponent(ifsc),
            method: 'GET',
            timeout: 8000,
            success: function (data) {
                if (data && data.BANK) {
                    $('#bankName').val(data.BANK  || '');
                    $('#branchName').val(data.BRANCH || '');
                    showToastNotification('Bank details fetched successfully.', 'success');
                } else {
                    showToastNotification('IFSC code not found. Please check and try again.', 'error');
                }
            },
            error: function (xhr) {
                var msg = xhr.status === 404
                    ? 'Invalid IFSC code. Please check and try again.'
                    : 'Unable to fetch bank details. Please enter manually.';
                showToastNotification(msg, 'error');
            }
        }).always(function () {
            $('#spinnerIFSC').addClass('d-none');
            $('#iconIFSC').removeClass('d-none');
            $('#btnFetchIFSC').prop('disabled', false);
        });
    });

    // Trigger fetch on Enter key in IFSC field
    $('#ifscCode').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#btnFetchIFSC').trigger('click'); }
    });

    function _fillBankForm(d) {
        var accNo = d ? (d.AccountNumber || '') : '';
        $('#bankDetailUID').val(d ? (d.BankDetailUID || 0) : 0);
        $('#bankName').val(d   ? (d.BankName      || '') : '');
        $('#branchName').val(d ? (d.BranchName    || '') : '');
        $('#ifscCode').val(d   ? (d.IFSCCode      || '') : '');
        $('#accountNumber').val(accNo);
        $('#confirmAccountNumber').val(accNo);
        $('#accountHolder').val(d ? (d.AccountHolder || '') : '');
        $('#upiId').val(d         ? (d.UpiId         || '') : '');
        $('#upiNumber').val(d     ? (d.UpiNumber     || '') : '');
        var type = d ? (d.AccountType || '') : '';
        $('input[name="accountType"]').prop('checked', false);
        if (type) $('input[name="accountType"][value="' + type + '"]').prop('checked', true);
        // Confirm field validation state
        if (accNo) {
            $('#confirmAccountNumber').removeClass('is-invalid').addClass('is-valid');
        } else {
            $('#confirmAccountNumber').removeClass('is-invalid is-valid');
        }
        $('#accNoMismatch').hide();
        // UPI ID validation state
        _validateUpiFormat($('#upiId').val());
    }

    // Show / hide account number toggle
    $(document).on('click', '.toggleAccNo', function () {
        var targetId = $(this).data('target');
        var $input   = $('#' + targetId);
        var isHidden = $input.attr('type') === 'password';
        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('i').toggleClass('bx-hide', !isHidden).toggleClass('bx-show', isHidden);
    });

    // Live confirm-account-number validation
    $('#confirmAccountNumber, #accountNumber').on('input', function () {
        var acc  = $.trim($('#accountNumber').val());
        var conf = $.trim($('#confirmAccountNumber').val());
        if (!conf) { $('#confirmAccountNumber').removeClass('is-invalid is-valid'); $('#accNoMismatch').hide(); return; }
        if (acc === conf) {
            $('#confirmAccountNumber').removeClass('is-invalid').addClass('is-valid');
            $('#accNoMismatch').hide();
        } else {
            $('#confirmAccountNumber').removeClass('is-valid').addClass('is-invalid');
            $('#accNoMismatch').show();
        }
    });

    // ── UPI ID format validation ──────────────────────────────────────────────
    // No free public API exists for live UPI ID verification.
    // We validate format: identifier@bankhandle (e.g. name@okaxis, 9876543210@upi)
    function _validateUpiFormat(val) {
        var $field = $('#upiId');
        var $feedback = $('#upiIdFeedback');
        $field.removeClass('is-valid is-invalid');
        $feedback.text('').hide();
        if (!val) return true;
        var pattern = /^[a-zA-Z0-9._\-]+@[a-zA-Z][a-zA-Z0-9]{2,63}$/;
        if (pattern.test(val)) {
            $field.addClass('is-valid');
            return true;
        } else {
            $field.addClass('is-invalid');
            $feedback.text('Invalid UPI ID format. Expected: name@bankhandle (e.g. name@okaxis)').show();
            return false;
        }
    }

    $('#upiId').on('input blur', function () { _validateUpiFormat($.trim($(this).val())); });

    function _loadBankDetails() {
        $.post('/settings/profile/getBankDetails', { [CsrfName]: CsrfToken }, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (!resp.Error) _fillBankForm(resp.Data);
        });
    }

    $('#btnSaveBankDetails').on('click', function () {
        var acc  = $.trim($('#accountNumber').val());
        var conf = $.trim($('#confirmAccountNumber').val());
        // Block if account number is set but confirm doesn't match (including empty confirm)
        if (acc && acc !== conf) {
            showToastNotification('Account numbers do not match. Please confirm the account number.', 'error');
            $('#confirmAccountNumber').removeClass('is-valid').addClass('is-invalid');
            $('#accNoMismatch').show();
            $('#confirmAccountNumber').focus();
            return;
        }
        // Block if UPI ID format is invalid
        var upiVal = $.trim($('#upiId').val());
        if (upiVal && !_validateUpiFormat(upiVal)) {
            showToastNotification('Please enter a valid UPI ID format.', 'error');
            $('#upiId').focus();
            return;
        }
        $('#spinnerBank').removeClass('d-none');
        $('#iconBank').addClass('d-none');
        $('#btnSaveBankDetails').prop('disabled', true);
        var payload = {
            BankDetailUID : $('#bankDetailUID').val(),
            BankName      : $.trim($('#bankName').val()),
            BranchName    : $.trim($('#branchName').val()),
            IFSCCode      : $.trim($('#ifscCode').val()).toUpperCase(),
            AccountNumber : acc,
            AccountHolder : $.trim($('#accountHolder').val()),
            AccountType   : $('input[name="accountType"]:checked').val() || '',
            UpiId         : $.trim($('#upiId').val()),
            UpiNumber     : $.trim($('#upiNumber').val()),
            [CsrfName]    : CsrfToken,
        };
        $.post('/settings/profile/saveBankDetails', payload, function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) { showToastNotification(resp.Message, 'error'); }
            else {
                _fillBankForm(resp.Data);
                showToastNotification('Bank details saved successfully.', 'success');
            }
        }).fail(function () {
            showToastNotification('Request failed. Please try again.', 'error');
        }).always(function () {
            $('#spinnerBank').addClass('d-none');
            $('#iconBank').removeClass('d-none');
            $('#btnSaveBankDetails').prop('disabled', false);
        });
    });

});
</script>
