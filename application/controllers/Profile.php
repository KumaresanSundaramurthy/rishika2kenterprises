<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        try {

            $this->load->model('user_model');
            $this->pageData['userInfo'] = $this->user_model->getUserByUserInfo(['User.UserUID' => $this->pageData['JwtData']->User->UserUID])->Data[0];

            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('users_model');
            $this->pageData['userFullData']       = $this->users_model->getUserById($userUID, $orgUID);
            $this->pageData['userAttachments']    = $this->users_model->getUserAttachments($userUID, $orgUID);
            $this->pageData['DepartmentsList']    = $this->users_model->getDepartmentList($orgUID);
            $this->pageData['DesignationsList']   = $this->users_model->getDesignationList($orgUID);
            $this->pageData['OrgUsersList']       = $this->users_model->getOrgUsersForDropdown($orgUID);
            $this->pageData['bankDetails']        = $this->users_model->getBankDetails($userUID);

            $this->load->view('profile/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    // ── Save profile addresses ────────────────────────────────────────────────
    public function saveProfileAddress() {
        $this->EndReturnData = new stdClass();
        try {
            $JwtData = $this->pageData['JwtData'];
            $userUID = (int)$JwtData->User->UserUID;
            $now     = date('Y-m-d H:i:s');
            $p       = $this->input->post();

            $this->load->model('users_model');
            $this->load->model('dbwrite_model');

            $saved = 0;
            foreach (['Current', 'Permanent'] as $type) {
                $pfx   = $type === 'Current' ? 'Curr' : 'Perm';
                $line1 = trim($p[$pfx . 'AddressLine1'] ?? '');
                $city  = trim($p[$pfx . 'City']         ?? '');
                $state = trim($p[$pfx . 'State']        ?? '');
                $pin   = trim($p[$pfx . 'PinCode']      ?? '');
                if (!$line1 && !$city && !$state && !$pin) continue;

                $country = trim($p[$pfx . 'Country'] ?? 'India') ?: 'India';

                $addrData = [
                    'UserUID'      => $userUID,
                    'AddressType'  => $type,
                    'AddressLine1' => $line1   ?: null,
                    'AddressLine2' => trim($p[$pfx . 'AddressLine2'] ?? '') ?: null,
                    'City'         => $city    ?: null,
                    'State'        => $state   ?: null,
                    'PinCode'      => $pin     ?: null,
                    'Country'      => $country,
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'UpdatedBy'    => $userUID,
                ];

                $existing = $this->users_model->getUserAddressForType($userUID, $type);
                if ($existing) {
                    $this->dbwrite_model->updateData('Users', 'UserAddressTbl', $addrData, ['AddressUID' => $existing->AddressUID]);
                } else {
                    $addrData['CreatedBy'] = $userUID;
                    $addrData['CreatedOn'] = $now;
                    $this->dbwrite_model->insertData('Users', 'UserAddressTbl', $addrData);
                }
                $saved++;
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $saved ? 'Address saved successfully.' : 'Nothing to save.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Save work information ─────────────────────────────────────────────────
    public function saveProfileWorkInfo() {
        $this->EndReturnData = new stdClass();
        try {
            $JwtData = $this->pageData['JwtData'];
            $userUID = (int)$JwtData->User->UserUID;
            $orgUID  = (int)$JwtData->Org->OrgUID;
            $p       = $this->input->post();

            $deptUID        = (int)($p['DepartmentUID']       ?? 0);
            $desigUID       = (int)($p['DesignationUID']      ?? 0);
            $empStatus      = trim($p['EmployeeStatus']       ?? 'Active');
            $doj            = trim($p['DateOfJoining']        ?? '');
            $employmentType = trim($p['EmploymentType']       ?? '');
            $workEmail      = trim($p['WorkEmail']            ?? '');
            $workPhone      = trim($p['WorkPhone']            ?? '');
            $probEndDate    = trim($p['ProbationEndDate']     ?? '');
            $noticeDays     = strlen(trim($p['NoticePeriodDays'] ?? '')) ? (int)$p['NoticePeriodDays'] : null;
            $mgrUID         = (int)($p['ReportingManagerUID'] ?? 0);
            $lastWorkDate   = trim($p['LastWorkingDate']      ?? '');
            $exitReason     = trim($p['ExitReason']           ?? '');

            $validStatuses = ['Active', 'Resigned', 'Terminated', 'OnLeave'];
            if (!in_array($empStatus, $validStatuses)) $empStatus = 'Active';

            $validEmpTypes = ['Permanent', 'Contract', 'Part-time', 'Intern', 'Consultant', ''];
            if (!in_array($employmentType, $validEmpTypes)) $employmentType = '';

            // Role 1 is always Active
            $this->load->model('users_model');
            $currentUser = $this->users_model->getUserById($userUID, $orgUID);
            if ($currentUser && (int)$currentUser->RoleUID === 1) $empStatus = 'Active';

            $data = ['UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')];

            if ($deptUID  > 0) $data['DepartmentUID']  = $deptUID;
            if ($desigUID > 0) $data['DesignationUID'] = $desigUID;
            $data['EmployeeStatus'] = $empStatus;
            $data['EmploymentType'] = $employmentType ?: null;
            $data['WorkEmail']      = $workEmail      ?: null;
            $data['WorkPhone']      = $workPhone      ?: null;
            $data['NoticePeriodDays'] = $noticeDays;
            $data['ReportingManagerUID'] = ($mgrUID > 0 && $mgrUID !== $userUID) ? $mgrUID : null;
            $data['ExitReason']     = $exitReason     ?: null;

            if (!empty($doj)          && strtotime($doj))          $data['DateOfJoining']    = date('Y-m-d', strtotime($doj));
            if (!empty($probEndDate)  && strtotime($probEndDate))  $data['ProbationEndDate'] = date('Y-m-d', strtotime($probEndDate));
            else                                                    $data['ProbationEndDate'] = null;
            if (!empty($lastWorkDate) && strtotime($lastWorkDate)) $data['LastWorkingDate']  = date('Y-m-d', strtotime($lastWorkDate));
            else                                                    $data['LastWorkingDate']  = null;

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserTbl', $data, ['UserUID' => $userUID, 'OrgUID' => $orgUID]);
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Work information saved successfully.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    // ── Upload own document/attachment ────────────────────────────────────────
    public function saveProfileAttachment() {
        $this->EndReturnData = new stdClass();
        try {
            $JwtData = $this->pageData['JwtData'];
            $userUID = (int)$JwtData->User->UserUID;
            $orgUID  = (int)$JwtData->Org->OrgUID;
            $docType = trim($this->input->post('DocType') ?: 'General');

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
                'CreatedBy' => $userUID,
                'CreatedOn' => date('Y-m-d H:i:s'),
            ]);
            if ($res->Error) throw new Exception($res->Message);

            $this->load->model('users_model');
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'File uploaded successfully.';
            $this->EndReturnData->Attachments = $this->users_model->getUserAttachments($userUID, $orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete own attachment ─────────────────────────────────────────────────
    public function deleteProfileAttachment() {
        $this->EndReturnData = new stdClass();
        try {
            $JwtData   = $this->pageData['JwtData'];
            $userUID   = (int)$JwtData->User->UserUID;
            $orgUID    = (int)$JwtData->Org->OrgUID;
            $attachUID = (int)($this->input->post('AttachUID') ?: 0);

            if ($attachUID <= 0) throw new Exception('Invalid attachment.');

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserAttachmentTbl',
                ['IsDeleted' => 1, 'IsActive' => 0],
                ['AttachUID' => $attachUID, 'UserUID' => $userUID, 'OrgUID' => $orgUID]
            );
            if ($res->Error) throw new Exception($res->Message);

            $this->load->model('users_model');
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Deleted.';
            $this->EndReturnData->Attachments = $this->users_model->getUserAttachments($userUID, $orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updateProfileDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->profValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            if (!empty($PostData['IsPasswordUpdate']) && $PostData['IsPasswordUpdate'] == 1) {
                $this->load->model('global_model');
                $userData = $this->global_model->getSingleRow('Users', 'UserTbl', ['UserUID' => $userUID]);
                if (!$userData) {
                    throw new Exception('User not found');
                }
                if($PostData['oldPassword'] !== base64_decode($userData->Password)) {
                    throw new Exception('Old password is incorrect');
                }
                if ($PostData['newPassword'] !== $PostData['confirmPassword']) {
                    throw new Exception('New password and Confirm password do not match');
                }
                if ($PostData['oldPassword'] === $PostData['newPassword']) {
                    throw new Exception('Old Password and New Password cannot be the same');
                }
            }

            $updateProfData = [
                'FirstName'         => getPostValue($PostData, 'fistName'),
                'LastName'          => getPostValue($PostData, 'lastName'),
                'CountryCode'       => getPostValue($PostData, 'CountryCode'),
                'CountryISO2'       => getPostValue($PostData, 'CountryISO2'),
                'MobileNumber'      => getPostValue($PostData, 'MobileNumber', 'Array', NULL, false),
                'UpdatedBy'         => $userUID,
            ];
            if (!empty($PostData['ImageRemoved'])) $updateProfData['Image'] = NULL;
            if (!empty($PostData['IsPasswordUpdate']) && $PostData['IsPasswordUpdate'] == 1) {
                $updateProfData['Password'] = base64_encode($PostData['newPassword']);
            }

            $this->load->model('dbwrite_model');
            $updateResp = $this->dbwrite_model->updateData('Users', 'UserTbl', $updateProfData, array('UserUID' => $PostData['userUid']));
            if ($updateResp->Error) {
                throw new Exception($updateResp->Message);
            }

            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'profile/images/', 'Image', ['Users', 'UserTbl', array('UserUID' => $PostData['userUid'])]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $this->globalservice->refreshUserCache();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Signatures ────────────────────────────────────────────────────────────

    public function getSignaturesJson() {

        $this->EndReturnData = new stdClass();
        try {

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('signature_model');
            $result = $this->signature_model->getSignatureList($userUID, $orgUID);

            $cdnBase = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
            $items   = [];

            if ($result->Error === FALSE) {
                foreach ($result->Data as $sig) {
                    $imgSrc = ($sig->SignatureType === 'Draw')
                        ? ($sig->DrawData ?? '')
                        : ($cdnBase . ($sig->ImagePath ?? ''));

                    $items[] = [
                        'SignatureUID'   => (int)$sig->SignatureUID,
                        'Label'         => $sig->Label,
                        'SignatureType' => $sig->SignatureType,
                        'ImgSrc'        => $imgSrc,
                        'IsDefault'     => (int)$sig->IsDefault,
                    ];
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $items;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = [];
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getSignatureList() {

        try {

            $this->load->helper('transaction');

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('signature_model');
            $result = $this->signature_model->getSignatureList($userUID, $orgUID);

            $this->pageData['signatures'] = $result->Error === FALSE ? $result->Data : [];
            $this->load->view('profile/signatures_list', $this->pageData);

        } catch (Exception $e) {
            echo '<div class="alert alert-danger m-3">Error loading signatures: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

    }

    public function saveSignature() {

        $this->EndReturnData = new stdClass();
        try {

            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $PostData = $this->input->post();

            $sigType = trim(getPostValue($PostData, 'SignatureType'));
            $label   = trim(getPostValue($PostData, 'Label')) ?: 'My Signature';

            if (!in_array($sigType, ['Upload', 'Draw'])) {
                throw new Exception('Invalid signature type');
            }

            $insertData = [
                'UserUID'       => (int)$userUID,
                'OrgUID'        => (int)$orgUID,
                'Label'         => substr($label, 0, 100),
                'SignatureType' => $sigType,
                'IsDefault'     => 0,
                'CreatedBy'     => (int)$userUID,
                'UpdatedBy'     => (int)$userUID,
            ];

            if ($sigType === 'Draw') {
                $drawData = getPostValue($PostData, 'DrawData');
                if (empty($drawData) || strpos($drawData, 'data:image/') !== 0) {
                    throw new Exception('Signature drawing is empty. Please draw your signature first.');
                }
                $insertData['DrawData'] = $drawData;
                $insertData['MimeType'] = 'image/png';
            }

            $this->load->model('dbwrite_model');
            $insertResp = $this->dbwrite_model->insertData('Users', 'UserSignaturesTbl', $insertData);
            if ($insertResp->Error) {
                throw new Exception($insertResp->Message);
            }

            $newUID = $insertResp->ID;

            if ($sigType === 'Upload') {
                if (!isset($_FILES['SignatureImage']) || $_FILES['SignatureImage']['error'] !== UPLOAD_ERR_OK) {
                    $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                        ['IsDeleted' => 1], ['SignatureUID' => $newUID]);
                    throw new Exception('No image file uploaded or upload error occurred.');
                }

                $UploadResp = $this->globalservice->fileUploadService(
                    $_FILES['SignatureImage'],
                    'signatures/',
                    'ImagePath',
                    ['Users', 'UserSignaturesTbl', ['SignatureUID' => $newUID]]
                );

                if ($UploadResp->Error === TRUE) {
                    $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                        ['IsDeleted' => 1], ['SignatureUID' => $newUID]);
                    throw new Exception($UploadResp->Message);
                }

                // Update metadata from uploaded file
                $mime = $_FILES['SignatureImage']['type'] ?? 'image/png';
                $size = $_FILES['SignatureImage']['size'] ?? 0;
                $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                    ['MimeType' => $mime, 'FileSize' => (int)$size],
                    ['SignatureUID' => $newUID]
                );
            }

            // Auto-set as default if it's the user's first signature
            $this->load->model('signature_model');
            $existing = $this->signature_model->getSignatureList($userUID, $orgUID);
            if ($existing->Error === FALSE && count($existing->Data) === 1) {
                $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                    ['IsDefault' => 1], ['SignatureUID' => $newUID]
                );
            }

            $this->_patchSignaturesJWT();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Signature saved successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateSignature() {

        $this->EndReturnData = new stdClass();
        try {

            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            $PostData     = $this->input->post();
            $signatureUID = (int)getPostValue($PostData, 'SignatureUID');
            $sigType      = trim(getPostValue($PostData, 'SignatureType'));
            $label        = trim(getPostValue($PostData, 'Label')) ?: 'My Signature';

            if (!$signatureUID) {
                throw new Exception('Invalid signature');
            }
            if (!in_array($sigType, ['Upload', 'Draw'])) {
                throw new Exception('Invalid signature type');
            }

            // Verify ownership
            $this->load->model('signature_model');
            $existing = $this->signature_model->getSignatureByUID($signatureUID, $userUID);
            if ($existing->Error || !$existing->Data) {
                throw new Exception('Signature not found');
            }

            $updateData = [
                'Label'         => substr($label, 0, 100),
                'SignatureType' => $sigType,
                'UpdatedBy'     => (int)$userUID,
            ];

            if ($sigType === 'Draw') {
                $drawData = getPostValue($PostData, 'DrawData');
                if (!empty($drawData) && strpos($drawData, 'data:image/') === 0) {
                    $updateData['DrawData']  = $drawData;
                    $updateData['ImagePath'] = null;
                    $updateData['MimeType']  = 'image/png';
                    $updateData['FileSize']  = null;
                }
            }

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Users', 'UserSignaturesTbl',
                $updateData,
                ['SignatureUID' => $signatureUID, 'UserUID' => (int)$userUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) {
                throw new Exception($resp->Message);
            }

            if ($sigType === 'Upload' && isset($_FILES['SignatureImage']) && $_FILES['SignatureImage']['error'] === UPLOAD_ERR_OK) {
                $UploadResp = $this->globalservice->fileUploadService(
                    $_FILES['SignatureImage'],
                    'signatures/',
                    'ImagePath',
                    ['Users', 'UserSignaturesTbl', ['SignatureUID' => $signatureUID]]
                );
                if ($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
                $mime = $_FILES['SignatureImage']['type'] ?? 'image/png';
                $size = $_FILES['SignatureImage']['size'] ?? 0;
                $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                    ['MimeType' => $mime, 'FileSize' => (int)$size, 'DrawData' => null],
                    ['SignatureUID' => $signatureUID]
                );
            }

            $this->_patchSignaturesJWT();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Signature updated successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteSignature() {

        $this->EndReturnData = new stdClass();
        try {

            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $signatureUID = (int)$this->input->post('SignatureUID');

            if (!$signatureUID) {
                throw new Exception('Invalid signature');
            }

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Users', 'UserSignaturesTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'IsDefault' => 0, 'UpdatedBy' => (int)$userUID],
                ['SignatureUID' => $signatureUID, 'UserUID' => (int)$userUID]
            );

            if ($resp->Error) {
                throw new Exception($resp->Message);
            }

            $this->_patchSignaturesJWT();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Signature deleted';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function setDefaultSignature() {

        $this->EndReturnData = new stdClass();
        try {

            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            $signatureUID = (int)$this->input->post('SignatureUID');

            if (!$signatureUID) {
                throw new Exception('Invalid signature');
            }

            $this->load->model('dbwrite_model');

            // Clear all defaults for this user first
            $this->dbwrite_model->updateData('Users', 'UserSignaturesTbl',
                ['IsDefault' => 0, 'UpdatedBy' => (int)$userUID],
                ['UserUID' => (int)$userUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]
            );

            // Set the chosen one as default
            $resp = $this->dbwrite_model->updateData(
                'Users', 'UserSignaturesTbl',
                ['IsDefault' => 1, 'UpdatedBy' => (int)$userUID],
                ['SignatureUID' => $signatureUID, 'UserUID' => (int)$userUID]
            );

            if ($resp->Error) {
                throw new Exception($resp->Message);
            }

            $this->_patchSignaturesJWT();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Default signature updated';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Emergency Contacts ────────────────────────────────────────────────────
    public function getEmergencyContacts() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->model('users_model');
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Contacts = $this->users_model->getEmergencyContacts($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error    = TRUE;
            $this->EndReturnData->Message  = $e->getMessage();
            $this->EndReturnData->Contacts = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function saveEmergencyContact() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID   = (int)$this->pageData['JwtData']->User->UserUID;
            $p         = $this->input->post();
            $emgUID    = (int)($p['EmgContactUID'] ?? 0);
            $name      = trim($p['Name']         ?? '');
            $relation  = trim($p['Relationship'] ?? '');
            $phone     = trim($p['PhoneNumber']  ?? '');
            if (!$name)     throw new Exception('Name is required.');
            if (!$relation) throw new Exception('Relationship is required.');
            if (!$phone)    throw new Exception('Phone number is required.');
            $isPrimary = (int)($p['IsPrimary'] ?? 0) ? 1 : 0;
            $now       = date('Y-m-d H:i:s');

            $this->load->model('dbwrite_model');

            // If marking as primary — clear existing primary for this user first
            if ($isPrimary) {
                $this->dbwrite_model->updateData('Users', 'UserEmergencyContactTbl',
                    ['IsPrimary' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now],
                    ['UserUID' => $userUID, 'IsDeleted' => 0]
                );
            }

            $data = [
                'Name'         => substr($name,     0, 100),
                'Relationship' => substr($relation, 0, 100),
                'PhoneNumber'  => substr($phone,    0, 20),
                'EmailAddress' => substr(trim($p['EmailAddress'] ?? ''), 0, 150) ?: null,
                'AddressLine1' => substr(trim($p['AddressLine1'] ?? ''), 0, 200) ?: null,
                'AddressLine2' => substr(trim($p['AddressLine2'] ?? ''), 0, 200) ?: null,
                'City'         => substr(trim($p['City']         ?? ''), 0, 100) ?: null,
                'State'        => substr(trim($p['State']        ?? ''), 0, 100) ?: null,
                'Country'      => substr(trim($p['Country']      ?? ''), 0, 100) ?: null,
                'IsPrimary'    => $isPrimary,
                'UpdatedBy'    => $userUID,
                'UpdatedOn'    => $now,
            ];

            if ($emgUID > 0) {
                $res = $this->dbwrite_model->updateData('Users', 'UserEmergencyContactTbl', $data, ['EmgContactUID' => $emgUID, 'UserUID' => $userUID]);
            } else {
                $data['UserUID']   = $userUID;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $data['CreatedOn'] = $now;
                $res = $this->dbwrite_model->insertData('Users', 'UserEmergencyContactTbl', $data);
            }
            if ($res->Error) throw new Exception($res->Message);

            $this->load->model('users_model');
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = $emgUID > 0 ? 'Contact updated.' : 'Contact added.';
            $this->EndReturnData->Contacts = $this->users_model->getEmergencyContacts($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteEmergencyContact() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $emgUID  = (int)($this->input->post('EmgContactUID') ?? 0);
            if ($emgUID <= 0) throw new Exception('Invalid record.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserEmergencyContactTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'IsPrimary' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['EmgContactUID' => $emgUID, 'UserUID' => $userUID]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->load->model('users_model');
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Contact deleted.';
            $this->EndReturnData->Contacts = $this->users_model->getEmergencyContacts($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function setPrimaryContact() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $emgUID  = (int)($this->input->post('EmgContactUID') ?? 0);
            if ($emgUID <= 0) throw new Exception('Invalid contact.');

            $this->load->model('dbwrite_model');
            $db  = $this->dbwrite_model->getWriteDb();
            $now = date('Y-m-d H:i:s');

            $db->trans_start();

            // Step 1: clear all primaries for this user
            $db->where(['UserUID' => $userUID, 'IsDeleted' => 0]);
            $db->update('Users.UserEmergencyContactTbl',
                ['IsPrimary' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now]);

            // Step 2: set the chosen contact as primary
            $db->where(['EmgContactUID' => $emgUID, 'UserUID' => $userUID]);
            $db->update('Users.UserEmergencyContactTbl',
                ['IsPrimary' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now]);

            $db->trans_complete();

            if ($db->trans_status() === FALSE) {
                throw new Exception('DB error while updating primary contact.');
            }

            $this->load->model('users_model');
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Primary contact updated.';
            $this->EndReturnData->Contacts = $this->users_model->getEmergencyContacts($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Bank Details ─────────────────────────────────────────────────────────
    public function getBankDetails() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->model('users_model');
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Data   = $this->users_model->getBankDetails($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = null;
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function saveBankDetails() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID      = (int)$this->pageData['JwtData']->User->UserUID;
            $p            = $this->input->post();
            $bankDetailUID = (int)($p['BankDetailUID'] ?? 0);
            $now          = date('Y-m-d H:i:s');

            $data = [
                'BankName'      => substr(trim($p['BankName']      ?? ''), 0, 100) ?: null,
                'BranchName'    => substr(trim($p['BranchName']    ?? ''), 0, 100) ?: null,
                'IFSCCode'      => strtoupper(substr(trim($p['IFSCCode'] ?? ''), 0, 50)) ?: null,
                'AccountNumber' => substr(trim($p['AccountNumber'] ?? ''), 0, 50)  ?: null,
                'AccountType'   => substr(trim($p['AccountType']   ?? ''), 0, 20)  ?: null,
                'AccountHolder' => substr(trim($p['AccountHolder'] ?? ''), 0, 100) ?: null,
                'UpiId'         => substr(trim($p['UpiId']         ?? ''), 0, 100) ?: null,
                'UpiNumber'     => substr(trim($p['UpiNumber']     ?? ''), 0, 20)  ?: null,
                'UpdatedBy'     => $userUID,
                'UpdatedOn'     => $now,
            ];

            $this->load->model('dbwrite_model');
            if ($bankDetailUID > 0) {
                $res = $this->dbwrite_model->updateData('Users', 'UserBankDetailsTbl', $data,
                    ['BankDetailUID' => $bankDetailUID, 'UserUID' => $userUID]);
            } else {
                $data['UserUID']   = $userUID;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $data['CreatedOn'] = $now;
                $res = $this->dbwrite_model->insertData('Users', 'UserBankDetailsTbl', $data);
            }
            if ($res->Error) throw new Exception($res->Message);

            $this->load->model('users_model');
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Message = 'Bank details saved.';
            $this->EndReturnData->Data    = $this->users_model->getBankDetails($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Education & Experience ────────────────────────────────────────────────
    public function getEduExp() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->model('users_model');
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Education  = $this->users_model->getEducationList($userUID);
            $this->EndReturnData->Experience = $this->users_model->getExperienceList($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error      = TRUE;
            $this->EndReturnData->Message    = $e->getMessage();
            $this->EndReturnData->Education  = [];
            $this->EndReturnData->Experience = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function saveEducation() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $p       = $this->input->post();
            $eduUID  = (int)($p['EduUID'] ?? 0);
            $inst    = trim($p['Institution'] ?? '');
            if (!$inst) throw new Exception('Institution name is required.');
            $degree  = trim($p['Degree']           ?? '');
            $field   = trim($p['FieldOfStudy']     ?? '');
            $cgpa    = trim($p['CGPA']             ?? '');
            $doc     = trim($p['DateOfCompletion'] ?? '');
            $now     = date('Y-m-d H:i:s');
            $data    = [
                'Institution'      => substr($inst,   0, 200),
                'Degree'           => substr($degree, 0, 100) ?: null,
                'FieldOfStudy'     => substr($field,  0, 100) ?: null,
                'CGPA'             => substr($cgpa,   0, 20)  ?: null,
                'DateOfCompletion' => ($doc && strtotime($doc)) ? date('Y-m-d', strtotime($doc)) : null,
                'UpdatedBy'        => $userUID,
                'UpdatedOn'        => $now,
            ];
            $this->load->model('dbwrite_model');
            if ($eduUID > 0) {
                $res = $this->dbwrite_model->updateData('Users', 'UserEducationTbl', $data, ['EduUID' => $eduUID, 'UserUID' => $userUID]);
            } else {
                $data['UserUID']   = $userUID;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $data['CreatedOn'] = $now;
                $res = $this->dbwrite_model->insertData('Users', 'UserEducationTbl', $data);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->load->model('users_model');
            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $eduUID > 0 ? 'Education updated.' : 'Education added.';
            $this->EndReturnData->Education = $this->users_model->getEducationList($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteEducation() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $eduUID  = (int)($this->input->post('EduUID') ?? 0);
            if ($eduUID <= 0) throw new Exception('Invalid record.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserEducationTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['EduUID' => $eduUID, 'UserUID' => $userUID]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->load->model('users_model');
            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = 'Deleted.';
            $this->EndReturnData->Education = $this->users_model->getEducationList($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function saveExperience() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID   = (int)$this->pageData['JwtData']->User->UserUID;
            $p         = $this->input->post();
            $expUID    = (int)($p['ExpUID'] ?? 0);
            $employer  = trim($p['EmployerName']   ?? '');
            if (!$employer) throw new Exception('Employer name is required.');
            $jobTitle  = trim($p['JobTitle']       ?? '');
            $startDate = trim($p['StartDate']      ?? '');
            $endDate   = trim($p['EndDate']        ?? '');
            $jobDesc   = trim($p['JobDescription'] ?? '');
            $now       = date('Y-m-d H:i:s');
            $data      = [
                'EmployerName'   => substr($employer, 0, 200),
                'JobTitle'       => substr($jobTitle, 0, 100) ?: null,
                'StartDate'      => ($startDate && strtotime($startDate)) ? date('Y-m-d', strtotime($startDate)) : null,
                'EndDate'        => ($endDate   && strtotime($endDate))   ? date('Y-m-d', strtotime($endDate))   : null,
                'JobDescription' => $jobDesc ?: null,
                'UpdatedBy'      => $userUID,
                'UpdatedOn'      => $now,
            ];
            $this->load->model('dbwrite_model');
            if ($expUID > 0) {
                $res = $this->dbwrite_model->updateData('Users', 'UserExperienceTbl', $data, ['ExpUID' => $expUID, 'UserUID' => $userUID]);
            } else {
                $data['UserUID']   = $userUID;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $data['CreatedOn'] = $now;
                $res = $this->dbwrite_model->insertData('Users', 'UserExperienceTbl', $data);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->load->model('users_model');
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = $expUID > 0 ? 'Experience updated.' : 'Experience added.';
            $this->EndReturnData->Experience = $this->users_model->getExperienceList($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteExperience() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $expUID  = (int)($this->input->post('ExpUID') ?? 0);
            if ($expUID <= 0) throw new Exception('Invalid record.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Users', 'UserExperienceTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['ExpUID' => $expUID, 'UserUID' => $userUID]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->load->model('users_model');
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted.';
            $this->EndReturnData->Experience = $this->users_model->getExperienceList($userUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Patch User.Signatures in the JWT Redis payload ────────────────────────
    private function _patchSignaturesJWT() {
        try {
            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('signature_model');
            $result  = $this->signature_model->getSignatureList($userUID, $orgUID);

            $cdnBase = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
            $list    = [];
            if ($result->Error === FALSE) {
                foreach ($result->Data as $sig) {
                    $imgSrc = ($sig->SignatureType === 'Draw')
                        ? ($sig->DrawData ?? '')
                        : ($cdnBase . ($sig->ImagePath ?? ''));
                    $list[] = [
                        'SignatureUID'  => (int)$sig->SignatureUID,
                        'Label'         => $sig->Label,
                        'SignatureType' => $sig->SignatureType,
                        'ImgSrc'        => $imgSrc,
                        'IsDefault'     => (int)$sig->IsDefault,
                    ];
                }
            }

            $jwtKey      = $this->pageData['JwtUserKey'] ?? null;
            $redisPayload = $jwtKey ? $this->redisservice->getCache($jwtKey) : null;
            if ($redisPayload && !$redisPayload->Error && !empty($redisPayload->Value)) {
                $redisPayload->Value->User->Signatures = $list;
                $this->redisservice->setCache($jwtKey, $redisPayload->Value, $redisPayload->TTL);
            }
        } catch (Exception $e) {
            // Silently fail — JWT patch is best-effort, not critical
        }
    }

}
