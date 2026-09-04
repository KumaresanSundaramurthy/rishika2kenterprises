<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 50;

    }

    private function _fetchTableData(int $pageNo, int $limit, array $filter = []): object {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('branches_model');
        $result  = $this->branches_model->getBranchListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('settings/branches/list', [
            'DataLists'    => $result->rows,
            'SerialNumber' => $offset,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/settings/branches/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    public function index(): void {
        $this->_loadPageTitle($this->pageModuleUID);
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'Branches';
        try {
            $this->load->model('branches_model');
            $limit = $this->_rowLimit();
            $pd    = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->pageData['BranchTypes']   = $this->branches_model->getBranchTypesList();
            $this->load->view('settings/branches/view', $this->pageData);
        } catch (Exception $e) { notifyError('Branches::index', $e); redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails(int $pageNo = 0): void {
        $this->EndReturnData = new stdClass();
        try {
            $filter = $this->input->post('Filter') ?: [];
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            notifyError('Branches::getPageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p    = $this->input->post();
            $uid  = (int)($p['BranchUID'] ?? 0);
            $name = trim($p['Name'] ?? '');
            $code = strtoupper(trim($p['BranchCode'] ?? ''));

            if (empty($name)) throw new ValidationException('Branch name is required.');
            if (empty($code)) throw new ValidationException('Branch code is required.');

            $this->load->model('branches_model');
            $this->load->model('dbwrite_model');

            if ($this->branches_model->getBranchCodeExists($this->_orgUID(), $code, $uid)) {
                throw new ValidationException('Branch code "' . $code . '" is already in use.');
            }

            $isHQ = (int)(bool)($p['IsHeadOffice'] ?? 0);

            // On CREATE: if marking as HQ, clear HQ from all other branches first.
            // On EDIT: IsHeadOffice is never set to 0 via this form — HQ can only be
            // reassigned through the dedicated setHeadOffice action, so the flag is
            // excluded from the update payload when the checkbox is unchecked.
            if ($isHQ === 1) {
                $this->dbwrite_model->updateData(
                    'Organisation', 'BranchesTbl',
                    ['IsHeadOffice' => 0, 'UpdatedBy' => $this->_userUID()],
                    ['OrgUID' => $this->_orgUID(), 'IsDeleted' => 0]
                );
            }

            $data = [
                'OrgUID'          => $this->_orgUID(),
                'Name'            => $name,
                'BranchCode'      => $code,
                'ShortDescription'=> trim($p['ShortDescription']  ?? ''),
                'BranchTypeUID'   => !empty($p['BranchTypeUID']) ? (int)$p['BranchTypeUID'] : null,
                'ContactPerson'   => trim($p['ContactPerson']     ?? ''),
                'MobileNumber'    => trim($p['MobileNumber']      ?? ''),
                'AlternateNumber' => trim($p['AlternateNumber']   ?? ''),
                'CountryCode'     => trim($p['CountryCode']       ?? ''),
                'CountryISO2'     => trim($p['CountryISO2']       ?? ''),
                'EmailAddress'    => trim($p['EmailAddress']      ?? ''),
                'PANNumber'       => strtoupper(trim($p['PANNumber'] ?? '')),
                'GSTIN'           => strtoupper(trim($p['GSTIN']  ?? '')),
                'AddressLine1'    => trim($p['AddressLine1']      ?? ''),
                'AddressLine2'    => trim($p['AddressLine2']      ?? ''),
                'Pincode'         => trim($p['Pincode']           ?? ''),
                'StateId'         => trim($p['StateId']           ?? ''),
                'StateText'       => trim($p['StateText']         ?? ''),
                'CityId'          => trim($p['CityId']            ?? ''),
                'CityText'        => trim($p['CityText']          ?? ''),
                'Landmark'        => trim($p['Landmark']          ?? ''),
                'IsWarehouse'     => (int)(bool)($p['IsWarehouse']     ?? 0),
                'IsDispatchPoint' => (int)(bool)($p['IsDispatchPoint'] ?? 0),
                'IsSalesPoint'    => (int)(bool)($p['IsSalesPoint']    ?? 0),
                'IsServiceCenter' => (int)(bool)($p['IsServiceCenter'] ?? 0),
                'IsActive'        => 1,
                'UpdatedBy'       => $this->_userUID(),
            ];

            // Only include IsHeadOffice in the payload when explicitly setting it to 1
            if ($isHQ === 1) {
                $data['IsHeadOffice'] = 1;
            }

            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'BranchesTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'BranchesTbl', $data, ['BranchUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $uid ? 'Branch updated.' : 'Branch created.';
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Branches::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function toggleStatus(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid      = (int)$this->input->post('BranchUID');
            $isActive = (int)$this->input->post('IsActive');
            if (!$uid) throw new ValidationException('Invalid branch.');
            $newStatus = $isActive === 1 ? 0 : 1;
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData(
                'Organisation', 'BranchesTbl',
                ['IsActive' => $newStatus, 'UpdatedBy' => $this->_userUID()],
                ['BranchUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $newStatus ? 'Branch activated.' : 'Branch deactivated.';
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Branches::toggleStatus', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('BranchUID');
            if (!$uid) throw new ValidationException('Invalid branch.');
            $this->load->model('branches_model');
            if ($this->branches_model->hasLinkedRecords($uid, $this->_orgUID())) {
                throw new ValidationException('This branch cannot be deleted because it has linked transactions or records.');
            }
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData(
                'Organisation', 'BranchesTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $this->_userUID()],
                ['BranchUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Branch deleted.';
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Branches::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('branches_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->branches_model->getBranchList($this->_orgUID());
        } catch (Exception $e) {
            notifyError('Branches::getList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function setHeadOffice(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('BranchUID');
            if (!$uid) throw new ValidationException('Invalid branch.');

            $this->load->model('dbwrite_model');

            // Clear HQ from all branches in this org, then set the target
            $this->dbwrite_model->updateData(
                'Organisation', 'BranchesTbl',
                ['IsHeadOffice' => 0, 'UpdatedBy' => $this->_userUID()],
                ['OrgUID' => $this->_orgUID(), 'IsDeleted' => 0]
            );

            $res = $this->dbwrite_model->updateData(
                'Organisation', 'BranchesTbl',
                ['IsHeadOffice' => 1, 'UpdatedBy' => $this->_userUID()],
                ['BranchUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );

            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Head office updated successfully.';
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Branches::setHeadOffice', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function switchBranch(): void {
        $this->EndReturnData = new stdClass();
        try {
            $requestedUID = (int)$this->input->post('BranchUID');
            if (!$requestedUID) throw new ValidationException('Invalid branch.');

            $jwtData  = $this->pageData['JwtData'];
            $redisKey = $this->pageData['JwtUserKey'] ?? null;
            if (!$redisKey) throw new Exception('Session error.');

            // Validate the requested branch is in the user's accessible list
            $accessible = $jwtData->Org->AccessibleBranches ?? [];
            $target     = null;
            foreach ($accessible as $b) {
                if ((int)$b->BranchUID === $requestedUID) { $target = $b; break; }
            }
            if (!$target) throw new ValidationException('You do not have access to this branch.');

            // Already on this branch — no-op
            if ((int)$jwtData->Org->BranchUID === $requestedUID) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Already on this branch.';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            // Update branch fields in the Redis payload
            $jwtData->Org->BranchUID  = $requestedUID;
            $jwtData->Org->BranchName = $target->BranchName ?? '';
            $jwtData->Org->BranchCode = $target->BranchCode ?? '';

            // Write updated payload back to Redis (same key, refresh TTL)
            $expiry = (int)getenv('LOGIN_EXPIRE_SECS');
            $this->redisservice->setCache($redisKey, $jwtData, $expiry);

            // Re-set the JWT cookie — same encoded value, refreshed expiry
            set_cookie(getenv('JWT_COOKIE_NAME'), $this->pageData['JwtToken'], $expiry);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Switched to ' . ($target->BranchName ?? '');
            $this->EndReturnData->BranchUID  = $requestedUID;
            $this->EndReturnData->BranchName = $target->BranchName ?? '';
            $this->EndReturnData->BranchCode = $target->BranchCode ?? '';
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Branches::switchBranch', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

}
