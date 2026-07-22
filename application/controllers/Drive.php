<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Drive extends MY_Controller {

    private const QUOTA_BYTES = 104857600; // 100 MB per user

    public function __construct() {
        parent::__construct();
        $this->load->model('drive_model');
        $this->load->library('fileupload');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Uploads a file directly to drive/{orgUID}/{userUID}/{filename} in R2/S3,
     * bypassing the Fileupload library which always prepends 'user_uploads/'.
     * The Fileupload library constructor already triggered vendor/autoload.php,
     * so \Aws\S3\S3Client is available here without a second require.
     *
     * @param string $tmpFilePath  absolute path of the uploaded temp file
     * @param string $r2Key        full R2 key e.g. drive/1/1/timestamp_file.pdf
     * @return object  ->Error bool, ->Path string (with leading '/'), ->Message string
     */
    private function _driveUpload(string $tmpFilePath, string $r2Key): object {
        $isAws  = getenv('FILE_UPLOAD') === 'amazonaws';
        $cfg    = [
            'version'     => 'latest',
            'region'      => $isAws ? 'ap-south-1' : 'auto',
            'credentials' => [
                'key'    => $isAws ? getenv('AWS_KEY')    : getenv('CFLARE_ACC_KEY'),
                'secret' => $isAws ? getenv('AWS_SECRET') : getenv('CFLARE_SECRET'),
            ],
        ];
        if (!$isAws) $cfg['endpoint'] = getenv('CFLARE_ENDPOINT');

        $out = new stdClass();
        try {
            $s3 = new \Aws\S3\S3Client($cfg);
            $s3->putObject([
                'Bucket'      => getenv('AWS_BUCKET_NAME'),
                'Key'         => $r2Key,
                'SourceFile'  => $tmpFilePath,
                'ContentType' => mime_content_type($tmpFilePath) ?: 'application/octet-stream',
                'ACL'         => 'public-read',
            ]);
            $out->Error = false;
            $out->Path  = '/' . ltrim($r2Key, '/'); // e.g. /drive/1/1/timestamp_file.pdf
        } catch (\Aws\Exception\AwsException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }
        return $out;
    }

    /**
     * @return bool
     */
    private function _isSuperAdmin(): bool {
        $u = $this->pageData['JwtData']->User;
        return (int)($u->RoleUID ?? 0) === 1 || (int)($u->IsAdmin ?? 0) === 1;
    }

    /**
     * Returns the UserUID whose drive to browse.
     * Non-admins always get their own UID regardless of POST param.
     *
     * @return int
     */
    private function _resolveViewUser(): int {
        $jwtUID  = (int)$this->pageData['JwtData']->User->UserUID;
        $postUID = (int)($this->input->post('ViewUserUID') ?? 0);
        return ($this->_isSuperAdmin() && $postUID > 0) ? $postUID : $jwtUID;
    }

    // ── Page load ─────────────────────────────────────────────────────────────

    public function index(): void {
        $this->_loadPageTitle();

        $orgUID       = $this->_orgUID();
        $isSuperAdmin = $this->_isSuperAdmin();

        $this->pageData['PageTitle']       = 'My Drive';
        $this->pageData['PageDescription'] = 'Your personal cloud storage space';
        $this->pageData['PageIcon']        = 'bx-cloud';
        $this->pageData['PageIconBg']      = '#e0f2fe';
        $this->pageData['PageIconColor']   = '#0369a1';
        $this->pageData['IsAdmin']         = $isSuperAdmin;
        $this->pageData['CurrentUserUID']  = (int)$this->pageData['JwtData']->User->UserUID;
        $this->pageData['QuotaBytes']      = self::QUOTA_BYTES;
        $this->pageData['OrgUsers']        = $isSuperAdmin ? $this->drive_model->getOrgUsers($orgUID) : [];
        $this->pageData['CdnUrl']          = rtrim(
            getenv('FILE_UPLOAD') === 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/'
        );

        $this->load->view('drive/view', $this->pageData);
    }

    // ── AJAX: list folder contents ────────────────────────────────────────────

    public function getItems(): void {
        $orgUID    = $this->_orgUID();
        $viewUID   = $this->_resolveViewUser();
        $parentUID = (int)($this->input->post('ParentUID') ?? 0);

        $items      = $this->drive_model->getItems($orgUID, $viewUID, $parentUID);
        $breadcrumb = $this->drive_model->getFolderPath($orgUID, $viewUID, $parentUID);
        $usedBytes  = $this->drive_model->getUserUsage($orgUID, $viewUID);

        $itemsArr = [];
        foreach ($items as $item) {
            $itemsArr[] = [
                'uid'       => (int)$item->DriveItemUID,
                'type'      => $item->ItemType,
                'name'      => $item->ItemName,
                'size'      => $item->FileSize !== null ? (int)$item->FileSize : null,
                'mime'      => $item->MimeType,
                'path'      => $item->FilePath,
                'createdOn' => $item->CreatedOn,
            ];
        }

        $this->globalservice->sendJsonResponse([
            'Error'      => false,
            'Items'      => $itemsArr,
            'Breadcrumb' => $breadcrumb,
            'UsedBytes'  => $usedBytes,
            'QuotaBytes' => self::QUOTA_BYTES,
        ]);
    }

    // ── AJAX: create folder ───────────────────────────────────────────────────

    public function createFolder(): void {
        $orgUID    = $this->_orgUID();
        $actorUID  = (int)$this->pageData['JwtData']->User->UserUID;
        $viewUID   = $this->_resolveViewUser();
        $parentUID = (int)($this->input->post('ParentUID') ?? 0);
        $name      = trim((string)($this->input->post('FolderName') ?? ''));

        if (!$name || mb_strlen($name) > 255) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Folder name is required (max 255 characters).']);
            return;
        }

        $name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);

        if ($this->drive_model->isDuplicateName($orgUID, $viewUID, $parentUID, $name)) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'A folder or file with that name already exists here.']);
            return;
        }

        $newUID = $this->drive_model->createFolder($orgUID, $viewUID, $parentUID, $name, $actorUID);

        $this->globalservice->sendJsonResponse(['Error' => false, 'Message' => 'Folder created.', 'DriveItemUID' => $newUID]);
    }

    // ── AJAX: upload files ────────────────────────────────────────────────────

    public function uploadFile(): void {
        $orgUID    = $this->_orgUID();
        $actorUID  = (int)$this->pageData['JwtData']->User->UserUID;
        $viewUID   = $this->_resolveViewUser();
        $parentUID = (int)($this->input->post('ParentUID') ?? 0);

        $files = $_FILES['Files'] ?? null;
        if (!$files || empty($files['name'])) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'No files received.']);
            return;
        }

        $usedBytes = $this->drive_model->getUserUsage($orgUID, $viewUID);
        $count     = is_array($files['name']) ? count($files['name']) : 1;
        $added     = 0;
        $errors    = [];

        for ($i = 0; $i < $count; $i++) {
            $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
            $name = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
            $type = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
            $size = is_array($files['size'])     ? (int)$files['size'][$i]     : (int)$files['size'];
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];

            if ($err !== UPLOAD_ERR_OK || !$name || empty($tmp)) {
                $errors[] = ($name ?: 'File') . ': Upload error.';
                continue;
            }
            if (($usedBytes + $size) > self::QUOTA_BYTES) {
                $errors[] = $name . ': Storage quota exceeded (100 MB limit).';
                continue;
            }

            $safe     = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
            $orgToken = (string)($this->pageData['JwtData']->Org->OrgToken ?? $orgUID);
            $r2Key    = 'drive/' . $orgToken . '/' . $viewUID . '/' . $safe; // stored at R2 root (no user_uploads/)
            $result = $this->_driveUpload($tmp, $r2Key);

            if ($result->Error) {
                $errors[] = $name . ': Upload failed.';
                continue;
            }

            // $result->Path = '/drive/{orgUID}/{userUID}/filename' — stored exactly as-is
            $this->drive_model->createFile($orgUID, $viewUID, $parentUID, $name, $result->Path, $size, $type ?: 'application/octet-stream', $actorUID);
            $usedBytes += $size;
            $added++;
        }

        $this->globalservice->sendJsonResponse([
            'Error'     => false,
            'Added'     => $added,
            'Errors'    => $errors,
            'UsedBytes' => $this->drive_model->getUserUsage($orgUID, $viewUID),
        ]);
    }

    // ── AJAX: move item ───────────────────────────────────────────────────────

    public function moveItem(): void {
        $orgUID       = $this->_orgUID();
        $actorUID     = (int)$this->pageData['JwtData']->User->UserUID;
        $viewUID      = $this->_resolveViewUser();
        $driveItemUID = (int)($this->input->post('DriveItemUID') ?? 0);
        $newParentUID = (int)($this->input->post('NewParentUID') ?? 0);

        if (!$driveItemUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Invalid item.']);
            return;
        }
        if ($driveItemUID === $newParentUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Cannot move a folder into itself.']);
            return;
        }

        $item = $this->drive_model->getItemByUID($driveItemUID, $orgUID);
        if (!$item || (int)$item->UserUID !== $viewUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Item not found.']);
            return;
        }
        if ($this->drive_model->isDuplicateName($orgUID, $viewUID, $newParentUID, $item->ItemName, $driveItemUID)) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'An item with that name already exists in the destination.']);
            return;
        }

        $this->drive_model->moveItem($driveItemUID, $newParentUID, $orgUID, $viewUID, $actorUID);
        $this->globalservice->sendJsonResponse(['Error' => false, 'Message' => 'Moved successfully.']);
    }

    // ── AJAX: rename item ─────────────────────────────────────────────────────

    public function renameItem(): void {
        $orgUID       = $this->_orgUID();
        $actorUID     = (int)$this->pageData['JwtData']->User->UserUID;
        $viewUID      = $this->_resolveViewUser();
        $driveItemUID = (int)($this->input->post('DriveItemUID') ?? 0);
        $newName      = trim((string)($this->input->post('NewName') ?? ''));

        if (!$driveItemUID || !$newName || mb_strlen($newName) > 255) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Invalid parameters.']);
            return;
        }
        $newName = preg_replace('/[\/\\\\:*?"<>|]/', '_', $newName);

        $item = $this->drive_model->getItemByUID($driveItemUID, $orgUID);
        if (!$item || (int)$item->UserUID !== $viewUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Item not found.']);
            return;
        }
        if ($this->drive_model->isDuplicateName($orgUID, $viewUID, (int)$item->ParentUID, $newName, $driveItemUID)) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'An item with that name already exists here.']);
            return;
        }

        $this->drive_model->renameItem($driveItemUID, $newName, $orgUID, $viewUID, $actorUID);
        $this->globalservice->sendJsonResponse(['Error' => false, 'Message' => 'Renamed successfully.', 'NewName' => $newName]);
    }

    // ── AJAX: delete item ─────────────────────────────────────────────────────

    public function deleteItem(): void {
        $orgUID       = $this->_orgUID();
        $actorUID     = (int)$this->pageData['JwtData']->User->UserUID;
        $viewUID      = $this->_resolveViewUser();
        $driveItemUID = (int)($this->input->post('DriveItemUID') ?? 0);

        if (!$driveItemUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Invalid item.']);
            return;
        }
        $item = $this->drive_model->getItemByUID($driveItemUID, $orgUID);
        if (!$item || (int)$item->UserUID !== $viewUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Item not found.']);
            return;
        }

        $this->drive_model->softDeleteItem($driveItemUID, $orgUID, $viewUID, $actorUID);
        $this->globalservice->sendJsonResponse([
            'Error'     => false,
            'Message'   => $item->ItemType === 'Folder' ? 'Folder deleted.' : 'File deleted.',
            'UsedBytes' => $this->drive_model->getUserUsage($orgUID, $viewUID),
        ]);
    }

    // ── GET: stream file download ─────────────────────────────────────────────

    public function downloadFile(int $driveItemUID = 0): void {
        $orgUID  = $this->_orgUID();
        $jwtUser = $this->pageData['JwtData']->User;

        $item = $this->drive_model->getItemByUID($driveItemUID, $orgUID);
        if (!$item || $item->ItemType !== 'File') {
            http_response_code(404); echo 'File not found.'; exit;
        }
        if (!$this->_isSuperAdmin() && (int)$item->UserUID !== (int)$jwtUser->UserUID) {
            http_response_code(403); echo 'Access denied.'; exit;
        }

        $cdnBase = rtrim(getenv('FILE_UPLOAD') === 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
        // FilePath stored as /drive/{orgUID}/{userUID}/filename — CDN URL directly
        $url     = $cdnBase . $item->FilePath;

        $handle = @fopen($url, 'rb');
        if (!$handle) { http_response_code(502); echo 'File unavailable.'; exit; }

        header('Content-Type: ' . ($item->MimeType ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . rawurlencode($item->ItemName) . '"');
        if ($item->FileSize) header('Content-Length: ' . (int)$item->FileSize);
        header('Cache-Control: private, no-cache, no-store');
        fpassthru($handle);
        fclose($handle);
        exit;
    }

    // ── AJAX: usage stats ─────────────────────────────────────────────────────

    public function getUsage(): void {
        $orgUID  = $this->_orgUID();
        $viewUID = $this->_resolveViewUser();
        $used    = $this->drive_model->getUserUsage($orgUID, $viewUID);
        $this->globalservice->sendJsonResponse(['Error' => false, 'UsedBytes' => $used, 'QuotaBytes' => self::QUOTA_BYTES]);
    }
}
