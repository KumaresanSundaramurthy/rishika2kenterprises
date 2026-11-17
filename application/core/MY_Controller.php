<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/ValidationException.php';

class MY_Controller extends CI_Controller {

    public    $pageData      = [];
    protected $pageModuleUID = 0;

    public function __construct() {
        parent::__construct();
    }

    // ── JWT shorthand accessors (available in every controller) ──────────────

    protected function _orgUID(): int        { return (int)($this->pageData['JwtData']->Org->OrgUID              ?? 0); }
    protected function _userUID(): int       { return (int)($this->pageData['JwtData']->User->UserUID             ?? 0); }
    protected function _branchUID(): int     { return (int)($this->pageData['JwtData']->Org->BranchUID            ?? 1); }
    protected function _rowLimit(): int      { return (int)($this->pageData['JwtData']->GenSettings->RowLimit      ?? 25); }
    protected function _dateFormat(): string { return       $this->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd M Y'; }
    protected function _currency(): string   { return       $this->pageData['JwtData']->GenSettings->CurrenySymbol  ?? 'â‚¹'; }
    protected function _decimals(): int      { return 2; }
    protected function _uiLang(): string     { return       $this->pageData['JwtData']->User->UILanguage            ?? 'en'; }

    /**
     * Looks up the module record from the Redis module cache and sets:
     *   $this->pageData['PageTitle']  — DisplayName (falls back to Name)
     *   $this->pageData['PageIcon']   — Icon class string
     *
     * @param int|null $moduleUID  Pass a UID to search by ID; omit to search by controller name.
     * @return bool  true = found, false = module not configured in ModuleTbl
     */
    protected function _loadPageTitle($moduleUID = null) {

        $modules = (array)($this->redisservice->getUserCache('modules') ?? []);

        // Cache miss — don't block; title stays empty
        if (empty($modules)) {
            $this->pageData['PageTitle']       = '';
            $this->pageData['PageIcon']        = '';
            $this->pageData['PageIconBg']      = '';
            $this->pageData['PageIconColor']   = '';
            $this->pageData['PageDescription'] = '';
            return true;
        }

        $found = null;
        if ($moduleUID !== null) {
            foreach ($modules as $m) {
                if ((int)$m->ModuleUID === (int)$moduleUID) { $found = $m; break; }
            }
        } else {
            $controllerName = strtolower($this->router->fetch_class());
            foreach ($modules as $m) {
                if ($m->ControllerName === $controllerName) { $found = $m; break; }
            }
        }

        // Cache stale — module added after last login; fall back to direct DB query
        if (!$found) {
            $this->load->model('login_model');
            $dbResult = $this->login_model->getModuleDetails();
            if ($dbResult->Error === FALSE && !empty($dbResult->Data)) {
                if ($moduleUID !== null) {
                    foreach ($dbResult->Data as $m) {
                        if ((int)$m->ModuleUID === (int)$moduleUID) { $found = $m; break; }
                    }
                } else {
                    $controllerName = strtolower($this->router->fetch_class());
                    foreach ($dbResult->Data as $m) {
                        if ($m->ControllerName === $controllerName) { $found = $m; break; }
                    }
                }
            }
            if (!$found) return false;
        }

        $this->pageData['PageTitle']       = !empty($found->DisplayName) ? $found->DisplayName : ($found->Name ?? '');
        $this->pageData['PageIcon']        = $found->Icon        ?? '';
        $this->pageData['PageIconBg']      = $found->IconBg      ?? '';
        $this->pageData['PageIconColor']   = $found->IconColor   ?? '';
        $this->pageData['PageDescription'] = $found->Description ?? '';

        // Load attachment config for all slots — 1-year Upstash cache, negligible overhead
        $this->pageData['AttachCfg'] = $this->_loadAttachCfg();

        return true;

    }

    /* Fetches org receipt header from services. Returns {Error, Data} — use ->Data ?? null. */
    protected function _getOrgInfo(int $orgUID): object {
        return get_org_info($orgUID);
    }

    // ── Upstash client config ────────────────────────────────────────────────
    protected function _loadUpstashConfig() {
        $this->pageData['UpstashReadUrl']   = rtrim(getenv('UPSTASH_REDIS_REST_URL') ?: '', '/');
        $this->pageData['UpstashReadToken'] = getenv('UPSTASH_REDIS_REST_READONLY_TOKEN')
                                            ?: getenv('UPSTASH_REDIS_REST_TOKEN')
                                            ?: '';
        $this->pageData['CustomerCacheKey'] = $this->redisservice->orgKey('customers');
        $this->pageData['VendorCacheKey']   = $this->redisservice->orgKey('vendors');
        $hasPLFlag = $this->upstashservice->get($this->redisservice->orgKey('has-price-lists'));
        $this->pageData['HasPriceLists']    = ($hasPLFlag === true);
    }

    /*
     * Cache guard — returns the cached value if present, otherwise attempts a
     * DB rebuild for known key types. Falls back to the error page only when
     * the rebuild also yields nothing, so new-org users never hit
     * ERR_SESSION_CACHE_MISS after onboarding.
     */
    protected function _requireCache($cacheKey) {
        $value = $this->redisservice->getCache($cacheKey)->Value;
        if ($value !== null && (!is_array($value) || !empty($value))) {
            return $value;
        }

        /* Auto-rebuild: org-users ─────────────────────────────────────────── */
        if (strpos($cacheKey, 'org-users') !== false) {
            $orgUID = $this->_orgUID();
            if ($orgUID > 0) {
                $resp     = services_get('api/users/getOrgUsers?orgId=' . $orgUID, withJwt: true);
                $value    = (!empty($resp) && empty($resp->Error)) ? (array)($resp->Users ?? []) : [];
                $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                $this->redisservice->setCache($cacheKey, $value, $loginExpiry);
                if (!empty($value)) {
                    return $value;
                }
            }
        }

        $this->load->view('common/cache_refresh', $this->pageData);
        return null;
    }

    // ── Attachment config loader ──────────────────────────────────────────────

    /**
     * Loads attachment config for all slots from Modules.AttachmentSlotConfigTbl.
     * Result is cached in Upstash under 'global:attach_cfg' with a 1-year TTL.
     * To force reload after a DB change: DEL the key from Upstash.
     * Returns array keyed by SlotKey.
     */
    protected function _loadAttachCfg(): array {
        // AttachCfg is loaded at login and stored in the JWT/Redis session.
        // It's already in JwtData — no extra DB or cache query needed.
        $cfg = $this->pageData['JwtData']->AttachCfg ?? null;
        if (!empty($cfg)) return (array)$cfg;
        return [];
    }

    // ── Date filter preference helper ─────────────────────────────────────────

    /**
     * Reads the saved date filter preference for a page from user_preferences,
     * falling back to $default ('this_month') if none is saved.
     *
     * Returns: ['range'=>string, 'from'=>string, 'to'=>string, 'label'=>string]
     */
    protected function getDateFilterPreference($pageKey, $default = 'this_month') {

        $jwtData   = $this->pageData['JwtData'] ?? null;
        $orgUID    = (int)($jwtData->Org->OrgUID    ?? 0);
        $branchUID = (int)($jwtData->Org->BranchUID ?? 0);
        $userUID   = (int)($jwtData->User->UserUID  ?? 0);

        $range = $default;
        if ($orgUID && $userUID) {
            $resp     = services_get(
                'api/userpreferences/getUserPreference?orgId=' . $orgUID . '&userId=' . $userUID . '&branchId=' . $branchUID . '&PreferenceKey=df_' . rawurlencode($pageKey),
                withJwt: true
            );
            $saved = ($resp && !$resp->Error) ? ($resp->PreferenceValue ?? null) : null;
            if ($saved !== null && $saved !== '') $range = $saved;
        }

        $result = $this->_buildDateRange($range);
        $this->pageData['SavedDateRange'] = $result['range'];
        $this->pageData['SavedDateLabel'] = $result['label'];
        $this->pageData['SavedDateFrom']  = $result['from'];
        $this->pageData['SavedDateTo']    = $result['to'];

        $fmt = $jwtData->GenSettings->ListDateFormat ?? 'd M Y';
        $this->pageData['SavedDateFromDisplay'] = $result['from'] ? date($fmt, strtotime($result['from'])) : '';
        $this->pageData['SavedDateToDisplay']   = $result['to']   ? date($fmt, strtotime($result['to']))   : '';

        return $result;

    }

    protected function _buildDateRange(string $range): array {
        $today = date('Y-m-d');
        $y     = (int)date('Y');
        $m     = (int)date('m');
        $from  = '';
        $to    = $today;
        $label = 'This Month';

        // Handle custom range stored as 'custom|YYYY-MM-DD|YYYY-MM-DD'
        if (strncmp($range, 'custom|', 7) === 0) {
            $parts = explode('|', $range);
            if (count($parts) === 3 && $parts[1] && $parts[2]) {
                return [
                    'range' => $range,
                    'from'  => $parts[1],
                    'to'    => $parts[2],
                    'label' => 'Custom',
                ];
            }
        }

        switch ($range) {
            case 'today':
                $from = $to = $today; $label = 'Today'; break;
            case 'yesterday':
                $d = date('Y-m-d', strtotime('-1 day'));
                $from = $to = $d; $label = 'Yesterday'; break;
            case 'this_week':
                $dow = (int)date('w');
                $from = date('Y-m-d', strtotime('-' . $dow . ' days')); $label = 'This Week'; break;
            case 'last_week':
                $dow  = (int)date('w');
                $from = date('Y-m-d', strtotime('-' . ($dow + 7) . ' days'));
                $to   = date('Y-m-d', strtotime('-' . ($dow + 1) . ' days'));
                $label = 'Last Week'; break;
            case 'last_7_days':
                $from = date('Y-m-d', strtotime('-6 days')); $label = 'Last 7 Days'; break;
            case 'this_month':
            default:
                $from = date('Y-m-01'); $label = 'This Month'; $range = 'this_month'; break;
            case 'previous_month':
                $from  = date('Y-m-01', strtotime('first day of last month'));
                $to    = date('Y-m-t',  strtotime('last month'));
                $label = 'Previous Month'; break;
            case 'last_30_days':
                $from = date('Y-m-d', strtotime('-29 days')); $label = 'Last 30 Days'; break;
            case 'this_quarter':
                $qStart = (int)(floor(($m - 1) / 3) * 3) + 1;
                $from   = $y . '-' . str_pad($qStart, 2, '0', STR_PAD_LEFT) . '-01';
                $label  = 'This Quarter'; break;
            case 'previous_quarter':
                $pqStart = (int)(floor(($m - 1) / 3) * 3) - 3;
                $pqYear  = $y;
                if ($pqStart < 1) { $pqStart += 12; $pqYear--; }
                $from   = $pqYear . '-' . str_pad($pqStart, 2, '0', STR_PAD_LEFT) . '-01';
                $to     = date('Y-m-t', strtotime($from . ' +2 months'));
                $label  = 'Previous Quarter'; break;
            case 'this_year':
                $from = $y . '-01-01'; $label = 'This Year'; break;
            case 'last_year':
                $from = ($y - 1) . '-01-01'; $to = ($y - 1) . '-12-31'; $label = 'Last Year'; break;
            case 'last_365_days':
                $from = date('Y-m-d', strtotime('-364 days')); $label = 'Last 365 Days'; break;
            case 'current_fy':
                $fyYear = ($m >= 4) ? $y : $y - 1;
                $from   = $fyYear . '-04-01';
                $to     = ($fyYear + 1) . '-03-31';
                $label  = 'Current FY'; break;
            case 'previous_fy':
                $fyYear = (($m >= 4) ? $y : $y - 1) - 1;
                $from   = $fyYear . '-04-01';
                $to     = ($fyYear + 1) . '-03-31';
                $label  = 'Previous FY'; break;
            case 'custom':
                // Stored as 'custom' without dates — treat as no range
                $from = ''; $to = ''; $label = 'This Month'; $range = 'this_month'; break;
            case '':
            case 'all':
                $from = ''; $to = ''; $label = 'All Dates'; $range = ''; break;
        }

        return ['range' => $range, 'from' => $from, 'to' => $to, 'label' => $label];
    }

}
