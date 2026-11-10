<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /** Get print theme config for a specific transaction type, joined with template HTML. */
    /** Look up a print theme config by ModuleUID — avoids string-matching on TransType. */
    public function getPrintThemeByModule(int $orgUID, int $moduleUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'TC.*',
                'PT.HtmlContent   AS TemplateHtmlContent',
                'PT.PreviewImage  AS TemplatePreviewImage',
                'PT.TemplateName  AS TemplateName',
            ]);
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where(['TC.OrgUID' => (int) $orgUID, 'TC.ModuleUID' => (int) $moduleUID, 'TC.IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintThemeByModule', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Paginated list of print theme configs for an org. */
    public function getPrintThemeConfigsPaginated(int $orgUID, int $limit, int $offset, string $search = ''): object {

        $this->EndReturnData = new stdClass();
        try {

            $base = ['TC.OrgUID' => $orgUID, 'TC.IsDeleted' => 0];

            // Count
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where($base);
            $total = $this->ReadDb->count_all_results();

            // Rows
            $this->ReadDb->select([
                'TC.*',
                'PT.TemplateName  AS TemplateName',
                'PT.PreviewImage  AS TemplatePreviewImage',
            ]);
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where($base);
            $this->ReadDb->order_by('TC.ThemeConfigUID', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->rows       = $rows;
            $this->EndReturnData->totalCount = $total;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintThemeConfigsPaginated', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Paginated list of global print templates. */
    public function getPrintTemplatesPaginated(int $limit, int $offset, string $search = ''): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where('IsDeleted', 0);
            if ($search) {
                $this->ReadDb->like('TemplateName', $search);
            }
            $total = $this->ReadDb->count_all_results();

            $this->ReadDb->select(['TemplateUID', 'TemplateKey', 'TemplateName', 'Description', 'Category', 'PreviewImage', 'SortOrder', 'IsActive', 'CreatedOn', 'UpdatedOn']);
            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where('IsDeleted', 0);
            if ($search) {
                $this->ReadDb->like('TemplateName', $search);
            }
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->rows       = $rows;
            $this->EndReturnData->totalCount = $total;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintTemplatesPaginated', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get all active print templates (for carousel / dropdown). */
    public function getPrintTemplatesAll(): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select(['TemplateUID', 'TemplateKey', 'TemplateName', 'Description', 'Category', 'PreviewImage', 'PreviewHtmlContent']);
            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintTemplatesAll', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get a single print template by UID (full row including HtmlContent). */
    public function getPrintTemplateByUID(int $templateUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where(['TemplateUID' => (int)$templateUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintTemplateByUID', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get thermal print config for an org. Returns NULL if not configured yet. */
    public function getThermalPrintConfig(int $orgUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {

            notifyError('Organisation_model::getThermalPrintConfig', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /** Get thermal print config for a specific module (by ModuleUID). */
    public function getThermalPrintConfigByModule(int $orgUID, int $moduleUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {

            notifyError('Organisation_model::getThermalPrintConfigByModule', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    // ── Bank Accounts ────────────────────────────────────────────────────────

    /** Get all active bank accounts (including Cash) for an org. */
    public function getBankAccountList(int $orgUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'BankAccountUID', 'AccountName', 'BankName', 'AccountNumber',
                'IFSC', 'BranchName', 'UPIId', 'UPINumber',
                'OpeningBalance', 'Notes', 'IsDefault', 'IsCash',
                'CreatedOn', 'UpdatedOn',
            ]);
            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('IsCash DESC, IsDefault DESC, BankAccountUID ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getBankAccountList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get a single bank account by UID + OrgUID. */
    public function getBankAccountByUID(int $bankUID, int $orgUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
            $this->ReadDb->where(['BankAccountUID' => (int)$bankUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getBankAccountByUID', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Calculate current balance for a single bank/cash account. */
    public function getBankBalance(int $bankUID, int $orgUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $sql = "
                SELECT
                    BA.OpeningBalance,
                    COALESCE(SUM(CASE WHEN P.PartyType = 'C' AND P.IsDeleted = 0 AND P.IsActive = 1 THEN P.Amount ELSE 0 END), 0) AS TotalIn,
                    COALESCE(SUM(CASE WHEN P.PartyType = 'S' AND P.IsDeleted = 0 AND P.IsActive = 1 THEN P.Amount ELSE 0 END), 0) AS TotalOut,
                    COALESCE((SELECT SUM(FT.Amount) FROM `Transaction`.`FundTransfersTbl` FT WHERE FT.ToBankUID   = BA.BankAccountUID AND FT.IsDeleted = 0), 0) AS TransferIn,
                    COALESCE((SELECT SUM(FT.Amount) FROM `Transaction`.`FundTransfersTbl` FT WHERE FT.FromBankUID = BA.BankAccountUID AND FT.IsDeleted = 0), 0) AS TransferOut
                FROM `Organisation`.`OrgBankAccountsTbl` BA
                LEFT JOIN `Transaction`.`PaymentsTbl` P ON P.BankAccountUID = BA.BankAccountUID
                WHERE BA.BankAccountUID = ? AND BA.OrgUID = ? AND BA.IsDeleted = 0
                GROUP BY BA.BankAccountUID, BA.OpeningBalance
            ";

            $row = $this->ReadDb->query($sql, [(int)$bankUID, (int)$orgUID])->row();

            $balance = 0;
            if ($row) {
                $balance = (float)$row->OpeningBalance
                         + (float)$row->TotalIn
                         - (float)$row->TotalOut
                         + (float)$row->TransferIn
                         - (float)$row->TransferOut;
            }

            $CI = &get_instance();
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Balance = round($balance, 4);
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getBankBalance', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get all message templates for an org, joined with module name. */
    public function getMessageTemplates(int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select(['T.*', 'M.Name AS ModuleName', "CONCAT(U.FirstName,' ',U.LastName) AS UpdatedByName"]);
            $this->ReadDb->from('Settings.MessageTemplatesTbl T');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = T.ModuleUID', 'left');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = T.UpdatedBy', 'left');
            $this->ReadDb->where(['T.OrgUID' => $orgUID, 'T.IsDeleted' => 0]);
            $this->ReadDb->order_by('T.ModuleUID ASC, T.Channel ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Organisation_model::getMessageTemplates', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Fetch all message templates for a module in ONE query, keyed by Channel.
     *  Returns: ['Email' => obj, 'WhatsApp' => obj, 'SMS' => obj]  (only configured channels)
     */
    public function getModuleMessageTemplates(int $orgUID, int $moduleUID): array {
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'IsDeleted' => 0]);
            $rows   = $this->ReadDb->get()->result();
            $result = [];
            foreach ($rows as $row) {
                $result[$row->Channel] = $row;
            }
            return $result;
        } catch (Exception $e) {
            notifyError('Organisation_model::getModuleMessageTemplates', $e);
            return [];
        }
    }

    /** Get a single message template by org + module + channel. */
    public function getMessageTemplate(int $orgUID, int $moduleUID, string $channel): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'Channel' => $channel, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            notifyError('Organisation_model::getMessageTemplate', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    public function getMessageTemplateByUID(int $templateUID, int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['TemplateUID' => (int)$templateUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            notifyError('Organisation_model::getMessageTemplateByUID', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get all thermal print configs for an org (one per module), joined with module name. */
    public function getThermalPrintConfigList(int $orgUID): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select(['TC.*', 'M.Name AS ModuleName', "CONCAT(U.FirstName, ' ', U.LastName) AS UpdatedByName"]);
            $this->ReadDb->from('Settings.ThermalPrintConfigTbl TC');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = TC.ModuleUID', 'left');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = TC.UpdatedBy', 'left');
            $this->ReadDb->where(['TC.OrgUID' => $orgUID, 'TC.IsDeleted' => 0]);
            $this->ReadDb->order_by('TC.ThermalConfigUID', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {

            notifyError('Organisation_model::getThermalPrintConfigList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /** Get all modules that support thermal print (IsThermalPrint = 1). */
    public function getThermalPrintModules(): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('ModuleUID, Name');
            $this->ReadDb->from('Modules.ModuleTbl');
            $this->ReadDb->where(['IsThermalPrint' => 1, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError('Organisation_model::getThermalPrintModules', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    // ── Prefix Configuration ─────────────────────────────────────────────────

    /** Get all modules that support prefix numbering (IsPrefix = 1). */
    public function getPrefixModules(): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('ModuleUID, Name');
            $this->ReadDb->from('Modules.ModuleTbl');
            $this->ReadDb->where(['IsPrefix' => 1, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Organisation_model::getPrefixModules', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get all modules that support print templates (IsPrintTemplate = 1). */
    public function getPrintTemplateModules(): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('ModuleUID, Name, DisplayName');
            $this->ReadDb->from('Modules.ModuleTbl');
            $this->ReadDb->where(['IsPrintTemplate' => 1, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Organisation_model::getPrintTemplateModules', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get all prefix configurations for an org, joined with module name and updated-by user. */
    public function getPrefixConfigList(int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select([
                'P.*',
                'M.Name AS ModuleName',
                "CONCAT(U.FirstName, ' ', IFNULL(U.LastName,'')) AS UpdatedByName",
            ]);
            $this->ReadDb->from('Settings.TransactionPrefixTbl P');
            $this->ReadDb->join('Modules.ModuleTbl M',  'M.ModuleUID = P.ModuleUID AND M.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl U',       'U.UserUID = P.UpdatedBy',                      'left');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $this->ReadDb->order_by('P.IsDefault DESC, M.Sorting ASC, P.PrefixUID ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Organisation_model::getPrefixConfigList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Count active prefix configurations for a specific org + module. */
    public function countPrefixesForModule(int $orgUID, int $moduleUID): int {
        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Settings.TransactionPrefixTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => $moduleUID, 'IsDeleted' => 0]);
        $row = $this->ReadDb->get()->row();
        return (int)($row->cnt ?? 0);
    }

    /** Get a single prefix row by PrefixUID (for validation before delete). */
    public function getPrefixByUID(int $prefixUID, int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('P.*, M.Name AS ModuleName');
            $this->ReadDb->from('Settings.TransactionPrefixTbl P');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = P.ModuleUID AND M.IsDeleted = 0', 'left');
            $this->ReadDb->where(['P.PrefixUID' => $prefixUID, 'P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            notifyError('Organisation_model::getPrefixByUID', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    // ── Additional Charges ────────────────────────────────────────────────────

    /** Get all active additional charges for an org, joined with default tax info. */
    public function getAdditionalChargesList(int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select([
                'C.ChargeUID', 'C.Name', 'C.DisplayName', 'C.DefaultTaxUID',
                'C.Description', 'C.ShowByDefault', 'C.IsSystem', 'C.SortOrder',
                'C.IsActive', 'C.CreatedAt', 'C.UpdatedAt',
                'T.TaxName AS TaxName', 'T.Percentage AS TaxPercentage',
            ]);
            $this->ReadDb->from('Settings.AdditionalChargesTbl C');
            $this->ReadDb->join('Global.TaxDetailsTbl T', 'T.TaxDetailsUID = C.DefaultTaxUID AND T.IsDeleted = 0', 'left');
            $this->ReadDb->where(['C.OrgUID' => $orgUID, 'C.IsDeleted' => 0]);
            $this->ReadDb->order_by('C.IsSystem DESC, C.SortOrder ASC, C.ChargeUID ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Organisation_model::getAdditionalChargesList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = [];
        }
        return $this->EndReturnData;
    }

    /** Get a single additional charge row (for edit/delete validation). */
    public function getAdditionalChargeByUID(int $chargeUID, int $orgUID): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('C.*, T.TaxName, T.Percentage AS TaxPercentage');
            $this->ReadDb->from('Settings.AdditionalChargesTbl C');
            $this->ReadDb->join('Global.TaxDetailsTbl T', 'T.TaxDetailsUID = C.DefaultTaxUID AND T.IsDeleted = 0', 'left');
            $this->ReadDb->where(['C.ChargeUID' => $chargeUID, 'C.OrgUID' => $orgUID, 'C.IsDeleted' => 0]);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            notifyError('Organisation_model::getAdditionalChargeByUID', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = null;
        }
        return $this->EndReturnData;
    }

    /** Count all active charges (system + custom) — used for the per-org limit check. */
    public function getCustomChargesCount(int $orgUID): int {
        try {
            $this->ReadDb->from('Settings.AdditionalChargesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            return (int) $this->ReadDb->count_all_results();
        } catch (Exception $e) {
            notifyError('Organisation_model::getCustomChargesCount', $e);
            return 0;
        }
    }

    /** Check if a charge Name already exists for this org (unique per org). */
    public function additionalChargeNameExists(int $orgUID, string $name, int $excludeUID = 0): bool {
        try {
            $this->ReadDb->from('Settings.AdditionalChargesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'Name' => $name, 'IsDeleted' => 0]);
            if ($excludeUID > 0) {
                $this->ReadDb->where('ChargeUID !=', $excludeUID);
            }
            return $this->ReadDb->count_all_results() > 0;
        } catch (Exception $e) {
            notifyError('Organisation_model::additionalChargeNameExists', $e);
            return false;
        }
    }

    /**
     * Returns org email verification status fields.
     * @param int $orgUID
     * @returns object|null
     */
    public function getOrgVerificationStatus(int $orgUID): ?object {
        try {
            $query = $this->ReadDb->select('IsEmailVerified, EmailAddress')
                ->from('Organisation.OrganisationTbl')
                ->where('OrgUID', $orgUID)
                ->limit(1)
                ->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Organisation_model::getOrgVerificationStatus', $e);
            return null;
        }
    }

    /**
     * Returns the ShortCode for the given org, or empty string if not found.
     * @param int $orgUID
     * @returns string
     */
    public function getOrgShortCode(int $orgUID): string {
        try {
            $query = $this->ReadDb->select('ShortCode')
                ->from('Organisation.OrganisationTbl')
                ->where('OrgUID', $orgUID)
                ->limit(1)
                ->get();
            return $query && $query->num_rows() > 0 ? ($query->row()->ShortCode ?? '') : '';
        } catch (Exception $e) {
            notifyError('Organisation_model::getOrgShortCode', $e);
            return '';
        }
    }

    /**
     * Returns true if the mobile number is already registered to another org.
     * @param string $mobile
     * @param int    $excludeOrgUID  current org to exclude from the check
     * @returns bool
     */
    public function checkOrgMobileExists(string $mobile, int $excludeOrgUID): bool {
        try {
            $this->ReadDb->where('MobileNumber', $mobile);
            $this->ReadDb->where('OrgUID !=',    $excludeOrgUID);
            $this->ReadDb->where('IsDeleted',    0);
            return $this->ReadDb->count_all_results('Organisation.OrganisationTbl') > 0;
        } catch (Exception $e) {
            notifyError('Organisation_model::checkOrgMobileExists', $e);
            return false;
        }
    }

    /**
     * Returns true if the GSTIN is already registered to another org.
     * @param string $gstin
     * @param int    $excludeOrgUID  current org to exclude from the check
     * @returns bool
     */
    public function checkOrgGSTINExists(string $gstin, int $excludeOrgUID): bool {
        try {
            $this->ReadDb->where('GSTIN',     $gstin);
            $this->ReadDb->where('OrgUID !=', $excludeOrgUID);
            $this->ReadDb->where('IsDeleted', 0);
            return $this->ReadDb->count_all_results('Organisation.OrganisationTbl') > 0;
        } catch (Exception $e) {
            notifyError('Organisation_model::checkOrgGSTINExists', $e);
            return false;
        }
    }

}
