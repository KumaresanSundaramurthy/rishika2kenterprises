<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('users_model');
        $this->load->model('roles_model');
        $this->load->model('dbwrite_model');
    }

    // ── List page ─────────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit   = $GeneralSettings->RowLimit ?? 10;
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $filter  = ['EmpStatus' => 'All'];

            $allData      = $this->users_model->getUsersList($orgUID, $filter, $limit, 0);
            $allDataCount = $this->users_model->getUsersCount($orgUID, $filter);

            $this->pageData['ModRowData']    = $this->load->view('users/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
                'CanSeeSalary' => $this->_canSeeSalary(),
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/settings/users/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;

            $rolesResult = $this->roles_model->getRolesList($orgUID);
            $this->pageData['RolesList']       = $rolesResult->Error === FALSE ? $rolesResult->Data : [];
            $this->pageData['DepartmentList']  = $this->users_model->getDepartmentList($orgUID);
            $this->pageData['DesignationList'] = $this->users_model->getDesignationList($orgUID);
            $this->pageData['NextEmpCode']     = $this->users_model->getNextEmployeeCode($orgUID);
            $this->pageData['StaffStats']      = $this->users_model->getUserStats($orgUID);
            $this->pageData['CanSeeSalary']    = $this->_canSeeSalary();

            $this->load->view('users/view', $this->pageData);

        } catch (Throwable $e) {
            log_message('error', 'Users::index — ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX pagination ───────────────────────────────────────────────────────
    public function getPageDetails($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $allData      = $this->users_model->getUsersList($orgUID, $filter, $limit, $offset);
            $allDataCount = $this->users_model->getUsersCount($orgUID, $filter);

            $rowHtml = $this->load->view('users/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
                'CanSeeSalary' => $this->_canSeeSalary(),
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/settings/users/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->Stats          = $this->users_model->getUserStats($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Get single user for edit modal ────────────────────────────────────────
    public function getUserDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)($this->input->post('UserUID') ?: 0);
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

            if ($userUID <= 0) throw new Exception('Invalid user.');

            $user = $this->users_model->getUserById($userUID, $orgUID);
            if (!$user) throw new Exception('User not found.');

            // Strip salary info if caller doesn't have permission
            if (!$this->_canSeeSalary()) {
                unset($user->BasicSalary, $user->Allowances, $user->Incentives, $user->FixedDeductions);
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Data        = $user;
            $this->EndReturnData->Attachments = $this->users_model->getUserAttachments($userUID, $orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Upload employee attachment ────────────────────────────────────────────
    public function saveUserAttachment() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)($this->input->post('UserUID') ?: 0);
            $docType = trim($this->input->post('DocType') ?: 'General');
            $JwtData = $this->pageData['JwtData'];
            $orgUID  = $JwtData->Org->OrgUID;

            if ($userUID <= 0) throw new Exception('Invalid user.');

            $file = $_FILES['AttachFile'] ?? null;
            if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('No file received or upload error.');
            }
            if ($file['size'] > 5 * 1024 * 1024) throw new Exception('File size must be under 5 MB.');

            $origName    = basename($file['name']);
            $safeName    = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = 'employees/' . $userUID . '/' . $safeName;

            $this->load->library('fileupload');
            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $file['tmp_name']);
            if ($uploadResult->Error) throw new Exception('Upload failed: ' . $uploadResult->Message);

            $filePath = '/' . ltrim($uploadResult->Path, '/');

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->insertData('Users', 'UserAttachmentTbl', [
                'UserUID'   => $userUID,
                'OrgUID'    => $orgUID,
                'FileName'  => $origName,
                'FilePath'  => $filePath,
                'FileType'  => $file['type'],
                'FileSize'  => (int)$file['size'],
                'DocType'   => $docType,
                'IsActive'  => 1,
                'IsDeleted' => 0,
                'CreatedBy' => $JwtData->User->UserUID,
                'CreatedOn' => date('Y-m-d H:i:s'),
            ]);
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'File uploaded successfully.';
            $this->EndReturnData->Attachments = $this->users_model->getUserAttachments($userUID, $orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete employee attachment ────────────────────────────────────────────
    public function deleteUserAttachment() {
        $this->EndReturnData = new stdClass();
        try {
            $attachUID = (int)($this->input->post('AttachUID') ?: 0);
            $userUID   = (int)($this->input->post('UserUID')   ?: 0);
            $JwtData   = $this->pageData['JwtData'];
            $orgUID    = $JwtData->Org->OrgUID;

            if ($attachUID <= 0) throw new Exception('Invalid attachment.');

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserAttachmentTbl',
                ['IsDeleted' => 1, 'IsActive' => 0],
                ['AttachUID' => $attachUID, 'OrgUID' => $orgUID]
            );
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Deleted.';
            $this->EndReturnData->Attachments = $this->users_model->getUserAttachments($userUID, $orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Toggle active status ──────────────────────────────────────────────────
    public function toggleStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID  = (int)($this->input->post('UserUID') ?: 0);
            $isActive = (int)($this->input->post('IsActive') ?? 0);
            $JwtData  = $this->pageData['JwtData'];

            if ($userUID <= 0) throw new Exception('Invalid user.');

            $result = $this->dbwrite_model->updateData('Users', 'UserTbl',
                ['IsActive' => $isActive, 'UpdatedBy' => $JwtData->User->UserUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['UserUID'  => $userUID]
            );
            if ($result->Error) throw new Exception($result->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated successfully.';
            $this->_appendListResponse($JwtData->Org->OrgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Save (create / update) ────────────────────────────────────────────────
    public function saveUser() {
        $this->EndReturnData = new stdClass();
        try {
            $this->dbwrite_model->startTransaction();

            $PostData       = $this->input->post();
            $UserUID        = (int)($PostData['UserUID'] ?? 0);
            $JwtData        = $this->pageData['JwtData'];
            $now            = date('Y-m-d H:i:s');
            $HasLoginAccess = (int)($PostData['HasLoginAccess'] ?? 0);

            // ── Common fields ──────────────────────────────────────────────────
            $FirstName   = trim($PostData['FirstName']   ?? '');
            $LastName    = trim($PostData['LastName']    ?? '');
            $Email       = trim($PostData['Email']       ?? '');
            $Mobile      = trim($PostData['Mobile']      ?? '');
            $CountryCode = trim($PostData['CountryCode'] ?? '');
            $CountryISO2 = trim($PostData['CountryISO2'] ?? '');

            if (empty($FirstName)) throw new Exception('First name is required.');

            // ── Login-only fields ──────────────────────────────────────────────
            $UserName = trim($PostData['UserName'] ?? '');
            $RoleUID  = !empty($PostData['RoleUID']) ? (int)$PostData['RoleUID'] : NULL;
            $IsActive = (int)($PostData['IsActive'] ?? 1);
            $IsLocked = (int)($PostData['IsLocked'] ?? 0);

            if ($HasLoginAccess) {
                if (!$UserUID && empty($UserName)) throw new Exception('Username is required for login users.');
                if (!$UserUID && empty($Email))    throw new Exception('Email address is required for login users.');
                if (!$RoleUID)                     throw new Exception('Role is required for login users.');
            }

            // ── HR / Employment fields ─────────────────────────────────────────
            $EmployeeCode   = trim($PostData['EmployeeCode']   ?? '');
            $DepartmentUID  = !empty($PostData['DepartmentUID'])  ? (int)$PostData['DepartmentUID']  : NULL;
            $DesignationUID = !empty($PostData['DesignationUID']) ? (int)$PostData['DesignationUID'] : NULL;
            $DateOfJoining  = !empty($PostData['DateOfJoining'])  ? $PostData['DateOfJoining']       : NULL;
            $EmployeeStatus = in_array($PostData['EmployeeStatus'] ?? '', ['Active','Resigned','Terminated','OnLeave'])
                              ? $PostData['EmployeeStatus'] : 'Active';

            // ── Salary fields (only applied when caller has permission) ────────
            $SalaryType      = in_array($PostData['SalaryType'] ?? '', ['Monthly','Daily','Hourly'])
                               ? $PostData['SalaryType'] : 'Monthly';
            $BasicSalary     = $this->_canSeeSalary() ? (float)($PostData['BasicSalary']     ?? 0) : NULL;
            $Allowances      = $this->_canSeeSalary() ? (float)($PostData['Allowances']      ?? 0) : NULL;
            $Incentives      = $this->_canSeeSalary() ? (float)($PostData['Incentives']      ?? 0) : NULL;
            $FixedDeductions = $this->_canSeeSalary() ? (float)($PostData['FixedDeductions'] ?? 0) : NULL;

            // ── Build data array ───────────────────────────────────────────────
            $userData = [
                'FirstName'      => $FirstName,
                'LastName'       => $LastName,
                'MobileNumber'   => $Mobile      ?: NULL,
                'CountryCode'    => $CountryCode ?: NULL,
                'CountryISO2'    => $CountryISO2 ?: 'IN',
                'HasLoginAccess' => $HasLoginAccess,
                'RoleUID'        => $HasLoginAccess ? $RoleUID : NULL,
                'IsActive'       => $HasLoginAccess ? $IsActive : 1,
                'OrgUID'         => $JwtData->Org->OrgUID,
                'BranchUID'      => $JwtData->Org->BranchUID,
                'EmployeeCode'   => $EmployeeCode   ?: NULL,
                'DepartmentUID'  => $DepartmentUID,
                'DesignationUID' => $DesignationUID,
                'DateOfJoining'  => $DateOfJoining,
                'EmployeeStatus' => $EmployeeStatus,
                'SalaryType'     => $SalaryType,
                'IsDeleted'      => 0,
            ];

            if ($BasicSalary !== NULL)     $userData['BasicSalary']     = $BasicSalary;
            if ($Allowances  !== NULL)     $userData['Allowances']      = $Allowances;
            if ($Incentives  !== NULL)     $userData['Incentives']      = $Incentives;
            if ($FixedDeductions !== NULL) $userData['FixedDeductions'] = $FixedDeductions;

            if ($UserUID > 0) {
                // Edit
                if ($HasLoginAccess) {
                    $userData['IsLocked'] = $IsLocked;
                }
                $userData['UpdatedBy'] = $JwtData->User->UserUID;
                $userData['UpdatedOn'] = $now;
                $result = $this->dbwrite_model->updateData('Users', 'UserTbl', $userData, ['UserUID' => $UserUID]);
                if ($result->Error) throw new Exception($result->Message);
                $msg = 'Staff record updated successfully.';
            } else {
                // Create
                $userData['UserCode']     = '';
                $userData['Password']     = '';
                $userData['IsPasswordSet']= 0;
                $userData['CreatedBy']    = $JwtData->User->UserUID;
                $userData['CreatedOn']    = $now;

                if ($HasLoginAccess) {
                    $userData['UserName']     = $UserName;
                    $userData['EmailAddress'] = $Email;
                    $token = bin2hex(random_bytes(32));
                    $userData['PasswordSetToken'] = $token;
                } else {
                    $userData['UserName']     = NULL;
                    $userData['EmailAddress'] = $Email ?: NULL;
                    $userData['PasswordSetToken'] = NULL;
                }

                $result = $this->dbwrite_model->insertData('Users', 'UserTbl', $userData);
                if ($result->Error) throw new Exception($result->Message);

                $UserUID  = (int)$result->ID;
                $userCode = 'U-' . str_pad($UserUID, 4, '0', STR_PAD_LEFT);
                $this->dbwrite_model->updateData('Users', 'UserTbl', ['UserCode' => $userCode], ['UserUID' => $UserUID]);

                if ($HasLoginAccess && !empty($Email)) {
                    $this->_sendPasswordSetupEmail($Email, $FirstName, $token);
                    $msg = 'Staff record created. A password setup link has been sent to ' . $Email . '.';
                } else {
                    $msg = 'Staff record created successfully.';
                }
            }

            // ── Save addresses ─────────────────────────────────────────────────
            $delUIDs = trim($PostData['DelAddrUIDs'] ?? '');
            if ($delUIDs) {
                foreach (array_filter(array_map('intval', explode(',', $delUIDs))) as $addrUID) {
                    $this->dbwrite_model->updateData('Users', 'UserAddressTbl',
                        ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $JwtData->User->UserUID],
                        ['AddressUID' => $addrUID, 'UserUID' => $UserUID]
                    );
                }
            }

            foreach (['Current', 'Permanent'] as $type) {
                $prefix = $type === 'Current' ? 'Curr' : 'Perm';
                $line1  = trim($PostData[$prefix . 'AddressLine1'] ?? '');
                $city   = trim($PostData[$prefix . 'City']         ?? '');
                $state  = trim($PostData[$prefix . 'State']        ?? '');
                $pin    = trim($PostData[$prefix . 'PinCode']      ?? '');

                if (!$line1 && !$city && !$state && !$pin) continue;

                $addrData = [
                    'UserUID'      => $UserUID,
                    'AddressType'  => $type,
                    'AddressLine1' => $line1 ?: NULL,
                    'AddressLine2' => trim($PostData[$prefix . 'AddressLine2'] ?? '') ?: NULL,
                    'City'         => $city  ?: NULL,
                    'State'        => $state ?: NULL,
                    'PinCode'      => $pin   ?: NULL,
                    'Country'      => 'India',
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'UpdatedBy'    => $JwtData->User->UserUID,
                ];

                $existing = $this->users_model->getUserAddressForType($UserUID, $type);
                if ($existing) {
                    $this->dbwrite_model->updateData('Users', 'UserAddressTbl', $addrData, ['AddressUID' => $existing->AddressUID]);
                } else {
                    $addrData['CreatedBy'] = $JwtData->User->UserUID;
                    $addrData['CreatedOn'] = $now;
                    $this->dbwrite_model->insertData('Users', 'UserAddressTbl', $addrData);
                }
            }

            $this->dbwrite_model->commitTransaction();

            // Refresh login-users cache
            $orgUID   = $JwtData->Org->OrgUID;
            $orgUsers = $this->users_model->getOrgUsersForCache($orgUID);
            $this->redisservice->setCache($this->redisservice->orgKey('org_users'), $orgUsers, 86400);

            if ($UserUID > 0 && $UserUID === (int)$JwtData->User->UserUID) {
                $this->globalservice->refreshUserCache();
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $msg;
            $this->EndReturnData->UID     = $UserUID;
            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Employee dropdown for Attendance / Payroll ────────────────────────────
    public function getEmployeeList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->users_model->getEmployeeDropdownList($orgUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Org login-users cache ─────────────────────────────────────────────────
    public function getOrgUsers() {
        $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
        $cacheKey = $this->redisservice->orgKey('org_users');
        $users    = $this->redisservice->getCache($cacheKey);
        if (empty($users)) {
            $users = $this->users_model->getOrgUsersForCache($orgUID);
            $this->redisservice->setCache($cacheKey, $users, 86400);
        }
        $this->EndReturnData = new stdClass();
        $this->EndReturnData->Error = false;
        $this->EndReturnData->Users = $users;
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Private helpers ───────────────────────────────────────────────────────
    private function _canSeeSalary() {
        $roleUID = (int)($this->pageData['JwtData']->User->RoleUID ?? 0);
        return in_array($roleUID, [1, 2]);
    }

    private function _sendPasswordSetupEmail($email, $firstName, $token) {
        try {
            $setupUrl  = base_url('setpassword/' . $token);
            $fromEmail = getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_USERNAME');
            $fromName  = getenv('MAIL_FROM_NAME')  ?: 'R2K Enterprises';
            $htmlBody  = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6;margin:0;padding:20px;}
.btn{display:inline-block;padding:10px 24px;background:#7c3aed;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;}</style>
</head><body>
<p>Hi ' . htmlspecialchars($firstName) . ',</p>
<p>Your account has been created. Please click the button below to set your password and activate your account:</p>
<p><a class="btn" href="' . $setupUrl . '">Set My Password</a></p>
<p>If the button does not work, copy and paste this link:<br>
<a href="' . $setupUrl . '">' . $setupUrl . '</a></p>
<p>This link has no expiry — you can use it any time until your password is set.</p>
<p>Regards,<br>' . $fromName . '</p>
</body></html>';

            $this->load->library('email');
            $this->email->initialize([
                'protocol'    => 'smtp',
                'smtp_host'   => getenv('MAIL_HOST')    ?: 'smtp-relay.brevo.com',
                'smtp_port'   => (int)(getenv('MAIL_PORT') ?: 587),
                'smtp_user'   => getenv('MAIL_USERNAME') ?: '',
                'smtp_pass'   => getenv('MAIL_PASSWORD') ?: '',
                'smtp_crypto' => 'tls',
                'mailtype'    => 'html',
                'charset'     => 'utf-8',
                'newline'     => "\r\n",
            ]);
            $this->email->clear();
            $this->email->from($fromEmail, $fromName);
            $this->email->to($email);
            $this->email->subject('Set Your Password — ' . $fromName);
            $this->email->message($htmlBody);
            $this->email->send(false);
        } catch (Throwable $e) {
            log_message('error', 'Users::_sendPasswordSetupEmail — ' . $e->getMessage());
        }
    }

    private function _appendListResponse($orgUID) {
        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
        $filterJson      = $this->input->post('Filter');
        $filter = ($filterJson && ($decoded = json_decode($filterJson, true))) ? $decoded : ['EmpStatus' => 'All'];
        $limit  = (int)($this->input->post('RowLimit') ?: ($GeneralSettings->RowLimit ?? 10));

        $allData  = $this->users_model->getUsersList($orgUID, $filter, $limit, 0);
        $allCount = $this->users_model->getUsersCount($orgUID, $filter);

        $rowHtml = $this->load->view('users/list', [
            'DataLists'    => $allData,
            'SerialNumber' => 0,
            'JwtData'      => $this->pageData['JwtData'],
            'CanSeeSalary' => $this->_canSeeSalary(),
        ], TRUE);

        $this->EndReturnData->RecordHtmlData = $rowHtml;
        $this->EndReturnData->TotalCount     = $allCount;
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/settings/users/getPageDetails', $allCount, 1, $limit);
        $this->EndReturnData->Stats          = $this->users_model->getUserStats($orgUID);
    }
}
