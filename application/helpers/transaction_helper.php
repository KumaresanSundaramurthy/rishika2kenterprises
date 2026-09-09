<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Render a party name element with the global hover-card trigger.
 * The popup JS/CSS is defined globally in footer_script.php + apex-theme.css.
 * Call this once per list row wherever a customer/vendor name is displayed.
 *
 * @param string $name    Full party name
 * @param string $mobile  Mobile number (digits only, no country code)
 * @param string $code    Country dial code e.g. "+91"
 * @param string $area    Area / location string (optional)
 * @param string $img     CDN URL of party photo (optional)
 */
if (!function_exists('r2k_party_name')) {
    function r2k_party_name(string $name, string $mobile = '', string $code = '', string $area = '', string $img = ''): string {
        $attr  = ' class="chc-trigger" style="cursor:default;"';
        $attr .= ' data-name="'   . htmlspecialchars($name,   ENT_QUOTES) . '"';
        $attr .= ' data-mobile="' . htmlspecialchars($mobile, ENT_QUOTES) . '"';
        $attr .= ' data-code="'   . htmlspecialchars($code,   ENT_QUOTES) . '"';
        if ($area)  $attr .= ' data-area="'  . htmlspecialchars($area,  ENT_QUOTES) . '"';
        if ($img)   $attr .= ' data-img="'   . htmlspecialchars($img,   ENT_QUOTES) . '"';
        return '<span' . $attr . '>' . htmlspecialchars($name, ENT_QUOTES) . '</span>';
    }
}

if (!function_exists('smart_dec_amount')) {
    /**
     * Format a decimal amount, stripping trailing zeros.
     */
    function smart_dec_amount(mixed $value): string {
        if ($value === null || $value === '') return '0.00';
        return smartDecimal((float)$value);
    }
}

if (!function_exists('trans_build_close_url')) {
    function trans_build_close_url(string $basePath, string $returnTab, int $returnPage): string {
        $params = [];
        if ($returnTab)      $params[] = 'tab=' . urlencode($returnTab);
        if ($returnPage > 1) $params[] = 'page=' . $returnPage;
        return $basePath . ($params ? '?' . implode('&', $params) : '');
    }
}

// ── Transaction form preamble helpers ────────────────────────────────────────

if (!function_exists('_buildPrefixSegmentFromComponentConfig')) {
    /**
     * Build the form display prefix segment from a ComponentConfig array.
     * Returns everything before the number slot, including the trailing separator.
     * e.g. for order=[prefix,fiscal,number] with seps=['-','/'] → "INV-26-27/"
     *
     * @param array  $pcfg Decoded ComponentConfig JSON
     * @param object $cfg  Prefix DB row
     * @return string
     */
    function _buildPrefixSegmentFromComponentConfig(array $pcfg, object $cfg): string {
        $order     = $pcfg['order']     ?? ['prefix', 'shortname', 'fiscal', 'number'];
        $seps      = $pcfg['seps']      ?? [];
        $active    = $pcfg['active']    ?? [];
        $shortname = strtoupper($pcfg['shortname'] ?? '');
        $fiscalFmt = $pcfg['fiscalFmt'] ?? ($cfg->FiscalYearFormat ?? 'SHORT');
        $alwaysOn  = ['prefix' => true, 'number' => true];

        $m   = (int)date('m');
        $yr  = (int)date('Y');
        $fy  = $m >= 4 ? $yr : $yr - 1;
        $fyStr = $fiscalFmt === 'LONG'
            ? $fy . '-' . ($fy + 1)
            : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);

        /* Collect active parts in order, including 'number' as a sentinel */
        $activeParts = [];
        foreach ($order as $idx => $key) {
            $isActive = !empty($alwaysOn[$key]) || !empty($active[$key]);
            if (!$isActive) continue;
            switch ($key) {
                case 'prefix':    $val = strtoupper($cfg->Name ?? ''); break;
                case 'shortname': $val = $shortname;                    break;
                case 'fiscal':    $val = $fyStr;                        break;
                case 'number':    $val = null;                          break; /* sentinel */
                default:          $val = '';
            }
            $activeParts[] = ['val' => $val, 'idx' => (int)$idx, 'key' => $key];
        }

        /* Build segment: all parts before 'number', then append the separator that precedes it */
        $result = '';
        foreach ($activeParts as $i => $part) {
            if ($part['key'] === 'number') {
                /* Append the separator between the last non-number part and the number */
                if ($i > 0) {
                    $prevIdx = $activeParts[$i - 1]['idx'];
                    $result .= $seps[$prevIdx] ?? '-';
                }
                break;
            }
            if ((string)$part['val'] === '') continue;
            $result .= $part['val'];
            /* Separator after this part only if the next part is also not 'number' */
            if (isset($activeParts[$i + 1]) && $activeParts[$i + 1]['key'] !== 'number') {
                $result .= $seps[$part['idx']] ?? '-';
            }
        }
        return $result;
    }
}

if (!function_exists('buildTransPrefixSegment')) {
    /**
     * Build the display prefix segment string (e.g. "INV-FY24-") from a prefix config object.
     * Uses ComponentConfig (new builder format) when present; falls back to old columns.
     *
     * @param object|null $cfg
     * @return string
     */
    function buildTransPrefixSegment(?object $cfg): string {
        if (!$cfg) return '';

        if (!empty($cfg->ComponentConfig)) {
            $pcfg = @json_decode($cfg->ComponentConfig, true);
            if (is_array($pcfg) && !empty($pcfg['order'])) {
                return _buildPrefixSegmentFromComponentConfig($pcfg, $cfg);
            }
        }

        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) {
            $parts[] = strtoupper($cfg->ShortName);
        }
        if (!empty($cfg->IncludeFiscalYear)) {
            $m   = (int)date('m');
            $yr  = (int)date('Y');
            $fy  = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}

if (!function_exists('buildTransactionUniqueNumber')) {
    /**
     * Assemble a full transaction number string from a prefix config row.
     * Uses ComponentConfig (drag-order + per-gap separators) when present;
     * falls back to old individual columns for rows saved before the builder.
     *
     * @param object $prefix      Prefix DB row (must have Name, NumberPadding, etc.)
     * @param int    $transNumber The sequential number to embed
     * @param string $date        Transaction date (Y-m-d)
     * @return string
     */
    function buildTransactionUniqueNumber(object $prefix, int $transNumber, string $date): string {
        if (!empty($prefix->ComponentConfig)) {
            $cfg = @json_decode($prefix->ComponentConfig, true);
            if (is_array($cfg) && !empty($cfg['order'])) {
                $order     = $cfg['order'];
                $seps      = $cfg['seps']      ?? [];
                $active    = $cfg['active']    ?? [];
                $shortname = strtoupper($cfg['shortname'] ?? '');
                $fiscalFmt = $cfg['fiscalFmt'] ?? ($prefix->FiscalYearFormat ?? 'SHORT');
                $alwaysOn  = ['prefix' => true, 'number' => true];

                $txMonth = (int)date('m', strtotime($date));
                $txYear  = (int)date('Y', strtotime($date));
                $fyStart = $txMonth >= 4 ? $txYear : $txYear - 1;
                $fyStr   = $fiscalFmt === 'LONG'
                    ? $fyStart . '-' . ($fyStart + 1)
                    : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);

                $pad    = (int)($prefix->NumberPadding ?? 1);
                $numStr = $pad > 1 ? str_pad($transNumber, $pad, '0', STR_PAD_LEFT) : (string)$transNumber;

                $activeParts = [];
                foreach ($order as $idx => $key) {
                    $isActive = !empty($alwaysOn[$key]) || !empty($active[$key]);
                    if (!$isActive) continue;
                    switch ($key) {
                        case 'prefix':    $val = strtoupper($prefix->Name ?? ''); break;
                        case 'shortname': $val = $shortname;                       break;
                        case 'fiscal':    $val = $fyStr;                           break;
                        case 'number':    $val = $numStr;                          break;
                        default:          $val = '';
                    }
                    if ($val === '') continue;
                    $activeParts[] = ['val' => $val, 'idx' => (int)$idx];
                }

                $result = '';
                foreach ($activeParts as $i => $part) {
                    $result .= $part['val'];
                    if ($i < count($activeParts) - 1) {
                        $result .= $seps[$part['idx']] ?? '-';
                    }
                }
                return $result;
            }
        }

        /* Old-style fallback */
        $sep   = $prefix->Separator ?? '-';
        $parts = [strtoupper($prefix->Name ?? '')];
        if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
            $parts[] = strtoupper($prefix->ShortName);
        }
        if (!empty($prefix->IncludeFiscalYear)) {
            $txMonth = (int)date('m', strtotime($date));
            $txYear  = (int)date('Y', strtotime($date));
            $fyStart = $txMonth >= 4 ? $txYear : $txYear - 1;
            $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fyStart . '-' . ($fyStart + 1)
                : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        $pad     = (int)($prefix->NumberPadding ?? 1);
        $parts[] = $pad > 1 ? str_pad($transNumber, $pad, '0', STR_PAD_LEFT) : (string)$transNumber;
        return implode($sep, $parts);
    }
}

if (!function_exists('resolveTransPrefix')) {
    /**
     * Resolve prefix config, edit transaction number, and prefix display segment.
     * Replaces the ~10-line prefix config loop + $editTransNumber/$editPrefixSeg lines in all 9 forms.
     * @param bool   $isEdit
     * @param bool   $isDraft
     * @param array  $prefixData
     * @param int    $prefixUID      Pass 0 when !$isEdit
     * @param int    $transNumber    Pass 0 when !$isEdit
     * @param array  $nextNumberMap
     * @return array{config:object|null, transNumber:int, seg:string}
     */
    function resolveTransPrefix(bool $isEdit, bool $isDraft, array $prefixData, int $prefixUID, int $transNumber, array $nextNumberMap): array {
        $config = null;
        if ($isEdit && !empty($prefixData)) {
            foreach ($prefixData as $_pd) {
                if ((int)$_pd->PrefixUID === $prefixUID) { $config = $_pd; break; }
            }
            if (!$config) $config = $prefixData[0];
        }
        $resolvedNumber = 0;
        if ($isEdit) {
            $resolvedNumber = $isDraft
                ? (int)($nextNumberMap[(int)($config->PrefixUID ?? 0)] ?? 1)
                : $transNumber;
        }
        return [
            'config'      => $config,
            'transNumber' => $resolvedNumber,
            'seg'         => ($isEdit && $isDraft) ? buildTransPrefixSegment($config) : '',
        ];
    }
}

if (!function_exists('buildDispatchAddressLines')) {
    /**
     * Format the org's dispatch address into an array of display lines.
     * Replaces the identical ~9-line $_addrLines block in 6 of the 9 form views.
     * @param object|null $addr
     * @return array<string>
     */
    function buildDispatchAddressLines(?object $addr): array {
        if (empty($addr)) return [];
        $lines    = array_values(array_filter([
            htmlspecialchars($addr->Line1 ?? ''),
            htmlspecialchars($addr->Line2 ?? ''),
        ]));
        $cityPin  = trim(implode(' - ', array_filter([
            htmlspecialchars($addr->CityText ?? ''),
            htmlspecialchars($addr->Pincode  ?? ''),
        ])));
        if ($cityPin)             $lines[] = $cityPin;
        if (!empty($addr->StateText)) $lines[] = htmlspecialchars($addr->StateText);
        return $lines;
    }
}

if (!function_exists('calcTransStatusBadge')) {
    /**
     * Compute all header-badge display values from a transaction record.
     * Replaces the ~8-line $hXxx block in INV, PUR, SR, PR form views.
     * @param object $transData
     * @param array  $statusMap   e.g. ['Issued'=>'primary','Paid'=>'success', ...]
     * @param object $jwtData
     * @return array{netAmt:float, paidAmt:float, balAmt:float, decimals:int, currency:string, status:string, statusClr:string}
     */
    function calcTransStatusBadge(object $transData, array $statusMap, object $jwtData): array {
        $decimals = 2;
        $netAmt   = (float)($transData->NetAmount  ?? 0);
        $paidAmt  = (float)($transData->PaidAmount ?? 0);
        $status   = $transData->DocStatus ?? '';
        return [
            'netAmt'    => $netAmt,
            'paidAmt'   => $paidAmt,
            'balAmt'    => max(0, round($netAmt - $paidAmt, $decimals)),
            'decimals'  => $decimals,
            'currency'  => htmlspecialchars($jwtData->GenSettings->CurrenySymbol ?? '₹'),
            'status'    => $status,
            'statusClr' => $statusMap[$status] ?? 'secondary',
        ];
    }
}

if (!function_exists('resolveTransNotesTerms')) {
    /**
     * Resolve notes and terms-and-conditions values for a transaction form.
     * In edit mode reads from $transData. In create mode checks $sources in order
     * (first non-empty Notes wins, first non-empty TermsConditions wins over JWT default).
     * Replaces the ~10-line $_notesVal/$_termsVal block in INV, SO, DC, PF, QT.
     * @param bool        $isEdit
     * @param object|null $transData
     * @param object      $jwtData
     * @param array       $sources   Ordered list of source doc objects (null entries skipped)
     * @return array{notesVal:string, termsVal:string}
     */
    function resolveTransNotesTerms(bool $isEdit, ?object $transData, object $jwtData, array $sources = []): array {
        $jwtTerms = $jwtData->TransSettings->TermsAndConditions ?? '';
        if ($isEdit) {
            return [
                'notesVal' => $transData->Notes           ?? '',
                'termsVal' => $transData->TermsConditions ?? '',
            ];
        }
        $notesVal = '';
        $termsVal = $jwtTerms;
        foreach ($sources as $src) {
            if ($src === null) continue;
            if ($notesVal === '' && !empty($src->Notes))            $notesVal = $src->Notes;
            if ($termsVal === $jwtTerms && !empty($src->TermsConditions)) $termsVal = $src->TermsConditions;
        }
        return ['notesVal' => $notesVal, 'termsVal' => $termsVal];
    }
}

if (!function_exists('initTransFormCommon')) {
    /**
     * Compute the 5 common preamble variables shared by all 9 transaction forms.
     * Use with extract(): extract(initTransFormCommon($isEdit, $TransData, '/invoices', $JwtData));
     * Sets: $_posCode, $_posName, $_returnTab, $_returnPage, $_closeUrl.
     * @param bool        $isEdit
     * @param object|null $transData
     * @param string      $closeBase  e.g. '/invoices'
     * @param object      $jwtData
     * @return array
     */
    function initTransFormCommon(bool $isEdit, ?object $transData, string $closeBase, object $jwtData): array {
        $CI          = &get_instance();
        $returnTab   = $CI->input->get('returnTab')  ?: 'All';
        $returnPage  = (int)($CI->input->get('returnPage') ?: 1);
        return [
            '_posCode'      => $isEdit ? ($transData->PlaceOfSupplyCode ?? '') : ($jwtData->Org->StateCode ?? ''),
            '_posName'      => $isEdit ? ($transData->PlaceOfSupplyName ?? '') : ($jwtData->Org->StateName ?? ''),
            '_isInterState' => ($isEdit && isset($transData->IsInterState)) ? (int)$transData->IsInterState : '',
            '_returnTab'  => $returnTab,
            '_returnPage' => $returnPage,
            '_closeUrl'   => trans_build_close_url($closeBase, $returnTab, $returnPage),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('format_datedisplay')) {
    function format_datedisplay($getDate, $format = null, $default = '', $timezone = null, $adjustDays = 0) {
        // When no format supplied, read ListDateFormat from JWT TransSettings via CI instance
        if ($format === null) {
            try {
                $CI     = &get_instance();
                $format = $CI->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd-m-Y';
            } catch (Exception $_) {
                $format = 'd-m-Y';
            }
        }
        if (empty($getDate)) {
            return $default;
        }
        try {
            if (is_numeric($getDate)) {
                $dt = new DateTime('@' . $getDate);
            } else {
                $dt = new DateTime($getDate);
            }
            if ($timezone) {
                $dt->setTimezone(new DateTimeZone($timezone));
            }
            if (!empty($adjustDays) && is_numeric($adjustDays)) {
                $dt->modify(($adjustDays >= 0 ? '+' : '') . $adjustDays . ' days');
            }
            return $dt->format($format);
        } catch (Exception $e) {
            notifyError('transaction_helper::format_datedisplay', $e);
            return $default;
        }
    }
}