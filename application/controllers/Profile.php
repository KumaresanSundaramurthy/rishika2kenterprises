<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        try {

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->load->model('user_model');
            $this->pageData['userInfo'] = $this->user_model->getUserByUserInfo(['User.UserUID' => $this->pageData['JwtData']->User->UserUID])->Data[0];

            $this->load->view('profile/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

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
                'UpdatedOn'         => $now,
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
