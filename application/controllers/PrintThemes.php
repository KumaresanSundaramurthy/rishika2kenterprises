<?php defined('BASEPATH') OR exit('No direct script access allowed');

class PrintThemes extends CI_Controller {

    public  $pageData = [];
    private $EndReturnData;

    private static $TRANSACTION_TYPES = [
        'Quotation'     => 'Quotation',
        'Invoice'       => 'Invoice',
        'SalesOrder'    => 'Sales Order',
        'PurchaseOrder' => 'Purchase Order',
        'DeliveryNote'  => 'Delivery Note',
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model('organisation_model');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function sanitizeTabInput($tab) {
        $tab = strtolower($tab ?: 'themes');
        return in_array($tab, ['themes', 'templates']) ? $tab : 'themes';
    }

    private function fetchThemeTableData($pageNo, $limit = 0) {

        $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
        if (!$limit) {
            $postLimit     = (int) $this->input->post('RowLimit');
            $settingsLimit = (int) (($this->pageData['JwtData']->GenSettings ?? null)?->RowLimit ?? 0);
            $limit = $postLimit ?: ($settingsLimit ?: 10);
        }
        $pageNo = max(1, (int) $pageNo);
        $offset = ($pageNo - 1) * $limit;

        $result  = $this->organisation_model->getPrintThemeConfigsPaginated($orgUID, $limit, $offset);
        $rowHtml = $this->load->view('printthemes/themes/list', [
            'DataLists'        => $result->rows,
            'StartFrom'        => $offset,
            'TransactionTypes' => self::$TRANSACTION_TYPES,
            'JwtData'          => $this->pageData['JwtData'],
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/settings/printthemes/getThemeList', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;

    }

    private function fetchTemplateTableData($pageNo, $limit = 0) {

        if (!$limit) {
            $postLimit     = (int) $this->input->post('RowLimit');
            $settingsLimit = (int) (($this->pageData['JwtData']->GenSettings ?? null)?->RowLimit ?? 0);
            $limit = $postLimit ?: ($settingsLimit ?: 10);
        }
        $pageNo = max(1, (int) $pageNo);
        $offset = ($pageNo - 1) * $limit;
        $search = trim($this->input->post('Search') ?: '');

        $result  = $this->organisation_model->getPrintTemplatesPaginated($limit, $offset, $search);
        $rowHtml = $this->load->view('printthemes/templates/list', [
            'DataLists' => $result->rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/settings/printthemes/getTemplateList', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;

    }

    // ── Main page ────────────────────────────────────────────────────────────

    public function index() {

        try {

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $activeTab = $this->sanitizeTabInput($this->input->get('tab', TRUE));
            $limit     = (int) ($GeneralSettings->RowLimit ?? 10);

            if ($activeTab === 'themes') {
                $tableData = $this->fetchThemeTableData(1, $limit);
            } else {
                $tableData = $this->fetchTemplateTableData(1, $limit);
            }

            $this->pageData['ModRowData']    = $tableData->RecordHtmlData;
            $this->pageData['ModPagination'] = $tableData->Pagination;
            $this->pageData['TotalCount']    = $tableData->TotalCount;
            $this->pageData['ActiveTabData'] = $activeTab;

            // Load templates for the theme-creation carousel
            $this->pageData['Templates'] = $this->organisation_model->getPrintTemplatesAll()->Data ?? [];

            $this->pageData['TransactionTypes'] = self::$TRANSACTION_TYPES;

            // Used types (for disabling in add form)
            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $configs = $this->organisation_model->getPrintThemeConfigs($orgUID)->Data ?? [];
            $this->pageData['UsedTypes'] = array_map(fn($c) => $c->TransactionType, $configs);

            // Org data for preview
            $orgData = $this->organisation_model->getOrgForReceipt($orgUID)->Data;
            $this->pageData['OrgPreviewData'] = $orgData;

            $this->load->view('printthemes/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    // ── AJAX list endpoints ──────────────────────────────────────────────────

    public function getThemeList() {

        $this->EndReturnData = new stdClass();
        try {
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $resp   = $this->fetchThemeTableData($pageNo);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $resp->RecordHtmlData;
            $this->EndReturnData->Pagination     = $resp->Pagination;
            $this->EndReturnData->TotalCount     = $resp->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getTemplateList() {

        $this->EndReturnData = new stdClass();
        try {
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $resp   = $this->fetchTemplateTableData($pageNo);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $resp->RecordHtmlData;
            $this->EndReturnData->Pagination     = $resp->Pagination;
            $this->EndReturnData->TotalCount     = $resp->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Theme CRUD ───────────────────────────────────────────────────────────

    public function saveTheme() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;

            $themeConfigUID  = (int) getPostValue($PostData, 'ThemeConfigUID');
            $transactionType = trim(getPostValue($PostData, 'TransactionType'));
            $templateUID     = (int) getPostValue($PostData, 'TemplateUID');

            if (!array_key_exists($transactionType, self::$TRANSACTION_TYPES)) {
                throw new Exception('Invalid transaction type.');
            }

            // Validate hex colors
            $primaryColor = trim(getPostValue($PostData, 'PrimaryColor') ?: '#1a3c6e');
            $accentColor  = trim(getPostValue($PostData, 'AccentColor')  ?: '#f59e0b');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor)) $primaryColor = '#1a3c6e';
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor))  $accentColor  = '#f59e0b';

            // Font validation
            $fontFamily = trim(getPostValue($PostData, 'FontFamily') ?: 'Arial');
            if (!preg_match('/^[a-zA-Z0-9 \-]+$/', $fontFamily)) $fontFamily = 'Arial';
            $fontFamily = substr($fontFamily, 0, 100);
            $fontSizePx = max(8, min(20, (int) getPostValue($PostData, 'FontSizePx') ?: 11));

            // Derive ThemeKey from selected template
            $themeKey = 'classic';
            if ($templateUID > 0) {
                $tpl = $this->organisation_model->getPrintTemplateByUID($templateUID)->Data;
                if ($tpl) $themeKey = $tpl->TemplateKey;
            } else {
                $themeKey = trim(getPostValue($PostData, 'ThemeKey') ?: 'classic');
            }

            $configData = [
                'TransactionType'  => $transactionType,
                'TemplateUID'      => $templateUID,
                'ThemeKey'         => substr($themeKey, 0, 50),
                'PrimaryColor'     => $primaryColor,
                'AccentColor'      => $accentColor,
                'ShowLogo'         => (int)(bool)getPostValue($PostData, 'ShowLogo'),
                'ShowOrgAddress'   => (int)(bool)getPostValue($PostData, 'ShowOrgAddress'),
                'ShowGSTIN'        => (int)(bool)getPostValue($PostData, 'ShowGSTIN'),
                'ShowHSN'          => (int)(bool)getPostValue($PostData, 'ShowHSN'),
                'ShowTaxBreakdown' => (int)(bool)getPostValue($PostData, 'ShowTaxBreakdown'),
                'FooterText'       => substr(getPostValue($PostData, 'FooterText') ?: '', 0, 200) ?: 'Thank you for your business!',
                'FontFamily'       => $fontFamily,
                'FontSizePx'       => $fontSizePx,
                'UpdatedBy'        => $userUID,
            ];

            $this->load->model('dbwrite_model');

            if ($themeConfigUID > 0) {
                $resp = $this->dbwrite_model->updateData(
                    'Organisation', 'PrintThemeConfigTbl', $configData,
                    ['ThemeConfigUID' => $themeConfigUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($resp->Error) throw new Exception($resp->Message);
            } else {
                $existing = $this->organisation_model->getPrintThemeByType($orgUID, $transactionType);
                if (!empty($existing->Data)) {
                    throw new Exception(self::$TRANSACTION_TYPES[$transactionType] . ' already has a theme configured. Edit it instead.');
                }
                $configData['OrgUID']    = $orgUID;
                $configData['CreatedBy'] = $userUID;
                $configData['IsActive']  = 1;
                $configData['IsDeleted'] = 0;
                $resp = $this->dbwrite_model->insertData('Organisation', 'PrintThemeConfigTbl', $configData);
                if ($resp->Error) throw new Exception($resp->Message);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Theme saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteTheme() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData       = $this->input->post();
            $themeConfigUID = (int) getPostValue($PostData, 'ThemeConfigUID');
            $orgUID         = $this->pageData['JwtData']->User->OrgUID;
            $userUID        = $this->pageData['JwtData']->User->UserUID;

            if ($themeConfigUID <= 0) throw new Exception('Invalid theme config.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Organisation', 'PrintThemeConfigTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['ThemeConfigUID' => $themeConfigUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Theme removed.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getThemeData() {

        $this->EndReturnData = new stdClass();
        try {

            $themeConfigUID = (int) $this->input->get('ThemeConfigUID');
            $orgUID         = $this->pageData['JwtData']->User->OrgUID;

            $result  = $this->organisation_model->getPrintThemeConfigsPaginated($orgUID, 200, 0);
            $found   = null;
            foreach ($result->rows as $c) {
                if ((int)$c->ThemeConfigUID === $themeConfigUID) { $found = $c; break; }
            }
            if (!$found) throw new Exception('Theme config not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $found;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Template CRUD ────────────────────────────────────────────────────────

    public function saveTemplate() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData    = $this->input->post();
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $templateUID = (int) getPostValue($PostData, 'TemplateUID');

            $templateKey  = trim(getPostValue($PostData, 'TemplateKey') ?: '');
            $templateName = trim(getPostValue($PostData, 'TemplateName') ?: '');
            $category     = trim(getPostValue($PostData, 'Category') ?: 'general');
            $description  = substr(trim(getPostValue($PostData, 'Description') ?: ''), 0, 500);
            $htmlContent  = trim(getPostValue($PostData, 'HtmlContent') ?: '');
            $previewImage = substr(trim(getPostValue($PostData, 'PreviewImage') ?: ''), 0, 500);
            $sortOrder    = max(0, (int) getPostValue($PostData, 'SortOrder'));

            if (!$templateName) throw new Exception('Template name is required.');

            // Sanitize key: lowercase alphanumeric + underscore
            $templateKey = preg_replace('/[^a-z0-9_]/', '_', strtolower($templateKey ?: $templateName));
            $templateKey = substr(trim($templateKey, '_'), 0, 100);
            if (!$templateKey) throw new Exception('Invalid template key.');

            $allowedCategories = ['general', 'gst', 'minimal', 'formal', 'modern'];
            if (!in_array($category, $allowedCategories)) $category = 'general';

            $data = [
                'TemplateKey'  => $templateKey,
                'TemplateName' => $templateName,
                'Description'  => $description,
                'Category'     => $category,
                'HtmlContent'  => $htmlContent,
                'PreviewImage' => $previewImage,
                'SortOrder'    => $sortOrder,
                'UpdatedOn'    => date('Y-m-d H:i:s'),
            ];

            $this->load->model('dbwrite_model');

            if ($templateUID > 0) {
                $resp = $this->dbwrite_model->updateData(
                    'Organisation', 'PrintTemplatesTbl', $data,
                    ['TemplateUID' => $templateUID, 'IsDeleted' => 0]
                );
                if ($resp->Error) throw new Exception($resp->Message);
            } else {
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $resp = $this->dbwrite_model->insertData('Organisation', 'PrintTemplatesTbl', $data);
                if ($resp->Error) throw new Exception($resp->Message);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Template saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteTemplate() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData    = $this->input->post();
            $templateUID = (int) getPostValue($PostData, 'TemplateUID');

            if ($templateUID <= 0) throw new Exception('Invalid template.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Organisation', 'PrintTemplatesTbl',
                ['IsDeleted' => 1, 'IsActive' => 0],
                ['TemplateUID' => $templateUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Template removed.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getTemplateData() {

        $this->EndReturnData = new stdClass();
        try {

            $templateUID = (int) $this->input->get('TemplateUID');
            $result = $this->organisation_model->getPrintTemplateByUID($templateUID);
            if (!$result->Data) throw new Exception('Template not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $result->Data;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Legacy aliases (keep old save/delete URLs working) ──────────────────

    public function save()   { $this->saveTheme();  }
    public function delete() { $this->deleteTheme(); }

}
