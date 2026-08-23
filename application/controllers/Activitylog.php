<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Activitylog extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('activitylog_model');
    }

    // ── Page load ────────────────────────────────────────────────────────────

    public function index(): void {
        $this->_loadPageTitle();

        if (empty($this->pageData['PageTitle'])) {
            $this->pageData['PageTitle']       = 'Activity Log';
            $this->pageData['PageDescription'] = 'Full audit trail of all user actions';
            $this->pageData['PageIcon']        = 'bx-history';
            $this->pageData['PageIconBg']      = '#ede9fe';
            $this->pageData['PageIconColor']   = '#7c3aed';
        }

        $orgUID   = $this->_orgUID();
        $rowLimit = $this->_rowLimit();

        // Default filter: this month
        $filter = ['DateFrom' => date('Y-m-01'), 'DateTo' => date('Y-m-d')];

        $total  = $this->activitylog_model->getAuditLogCount($orgUID, $filter);
        $logs   = $this->activitylog_model->getAuditLogs($orgUID, $filter, $rowLimit, 0);

        $this->pageData['JwtData']       = $this->pageData['JwtData'] ?? null;
        $this->pageData['DataLists']     = $logs;
        $this->pageData['ModAllCount']   = $total;
        $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/settings/activitylog/getPageDetails', $total, 1, $rowLimit);
        $this->pageData['ModRowData']  = $this->_renderRows($logs);
        $this->pageData['Modules']     = $this->activitylog_model->getDistinctModules($orgUID);
        $this->pageData['OrgUsers']    = $this->activitylog_model->getDistinctUsers($orgUID);
        $this->pageData['InitFilter']  = $filter;

        $this->load->view('settings/activitylog/view', $this->pageData);
    }

    // ── AJAX: paginated list ─────────────────────────────────────────────────

    public function getPageDetails(int $pageNo = 1): void {
        $orgUID   = $this->_orgUID();
        $rowLimit = (int)($this->input->post('RowLimit') ?: $this->_rowLimit());
        $pageNo   = max(1, (int)($this->input->post('PageNo') ?: $pageNo));
        $filter   = (array)json_decode($this->input->post('Filter') ?? '{}', true);

        $offset = ($pageNo - 1) * $rowLimit;
        $total  = $this->activitylog_model->getAuditLogCount($orgUID, $filter);
        $logs   = $this->activitylog_model->getAuditLogs($orgUID, $filter, $rowLimit, $offset);

        $this->globalservice->sendJsonResponse([
            'Error'          => false,
            'TotalCount'     => $total,
            'RecordHtmlData' => $this->_renderRows($logs),
            'Pagination'     => $this->globalservice->buildPagePaginationHtml('/settings/activitylog/getPageDetails', $total, $pageNo, $rowLimit),
        ]);
    }

    // ── AJAX: entity history timeline ────────────────────────────────────────

    public function getEntityHistory(): void {
        $orgUID     = $this->_orgUID();
        $entityType = (string)($this->input->post('EntityType') ?? '');
        $entityUID  = (int)($this->input->post('EntityUID') ?? 0);

        if (!$entityType || !$entityUID) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Message' => 'Invalid parameters.']);
            return;
        }

        $logs = $this->activitylog_model->getEntityHistory($orgUID, $entityType, $entityUID, 50);

        $this->globalservice->sendJsonResponse([
            'Error'   => false,
            'History' => $logs,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * @param array $logs
     * @return string
     */
    private function _renderRows(array $logs): string {
        if (empty($logs)) {
            return '<tr><td colspan="12" class="text-center py-4 text-muted">No activity records found.</td></tr>';
        }

        $jwtData     = $this->pageData['JwtData'] ?? null;
        $dateFmt     = $jwtData->GenSettings->ListDateFormat     ?? 'd M Y';
        $dateTimeFmt = $jwtData->GenSettings->ListDateTimeFormat ?? 'd M Y H:i';

        $html = '';
        foreach ($logs as $log) {
            $createdOn  = !empty($log->CreatedOn) ? date($dateFmt, strtotime($log->CreatedOn)) : '—';
            $createdAt  = !empty($log->CreatedOn) ? trim(str_replace($createdOn, '', date($dateTimeFmt, strtotime($log->CreatedOn)))) : '';
            $userName   = htmlspecialchars($log->UserName ?? '—');
            $module     = htmlspecialchars($log->Module ?? $log->EntityType ?? '—');
            $action     = htmlspecialchars($log->Action ?? '—');
            $entityRef  = htmlspecialchars($log->EntityRef ?? '—');
            $summary    = htmlspecialchars($log->Summary ?? '');
            $auditUID     = (int)$log->AuditUID;
            $details      = $log->Details ?? '';
            $auditCatg    = htmlspecialchars($log->AuditCategory ?? '—');
            $result       = htmlspecialchars($log->Result       ?? '');
            $source       = htmlspecialchars($log->Source       ?? '');
            $ipAddress    = htmlspecialchars($log->IPAddress    ?? '—');
            $deviceType   = htmlspecialchars($log->DeviceType   ?? '—');

            // Action badge colour
            $actionLower = strtolower($action);
            if (strpos($actionLower, 'delete') !== false || strpos($actionLower, 'cancel') !== false) {
                $badgeStyle = 'background:#fee2e2;color:#dc2626;';
            } elseif (strpos($actionLower, 'create') !== false || strpos($actionLower, 'add') !== false) {
                $badgeStyle = 'background:#dcfce7;color:#16a34a;';
            } elseif (strpos($actionLower, 'update') !== false || strpos($actionLower, 'edit') !== false || strpos($actionLower, 'save') !== false) {
                $badgeStyle = 'background:#dbeafe;color:#2563eb;';
            } elseif (strpos($actionLower, 'convert') !== false || strpos($actionLower, 'duplicate') !== false) {
                $badgeStyle = 'background:#fef9c3;color:#854d0e;';
            } else {
                $badgeStyle = 'background:#f1f5f9;color:#475569;';
            }

            // Result badge
            $resultStyle = $result === 'FAILED' ? 'background:#fee2e2;color:#dc2626;' : 'background:#dcfce7;color:#16a34a;';

            $html .= '<tr>';
            $html .= '<td style="white-space:nowrap;">';
            $html .= '<div style="font-size:.82rem;font-weight:600;color:#1e293b;">' . $createdOn . '</div>';
            if ($createdAt) {
                $html .= '<div style="font-size:.72rem;color:#94a3b8;">' . $createdAt . '</div>';
            }
            $html .= '</td>';

            $html .= '<td>';
            $html .= '<div style="font-size:.83rem;font-weight:600;color:#374151;">' . $userName . '</div>';
            $html .= '</td>';

            $html .= '<td>';
            $html .= '<span style="font-size:.76rem;font-weight:600;padding:2px 8px;border-radius:20px;' . $badgeStyle . '">';
            $html .= $module;
            $html .= '</span>';
            $html .= '</td>';

            $html .= '<td>';
            $html .= '<span style="font-size:.75rem;font-family:monospace;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 6px;border-radius:4px;color:#475569;">';
            $html .= $action;
            $html .= '</span>';
            $html .= '</td>';

            $html .= '<td style="font-size:.82rem;color:#374151;">' . $entityRef . '</td>';

            $html .= '<td style="font-size:.8rem;color:#64748b;min-width:200px;">';
            if ($summary) {
                $html .= '<span title="' . $summary . '">' . (mb_strlen($summary) > 60 ? mb_substr($summary, 0, 58) . '…' : $summary) . '</span>';
            }
            $html .= '</td>';

            // ── Scrollable extra columns ─────────────────────────────────────
            $html .= '<td style="font-size:.78rem;color:#475569;white-space:nowrap;">' . $auditCatg . '</td>';

            $html .= '<td style="white-space:nowrap;">';
            if ($result) {
                $html .= '<span style="font-size:.72rem;font-weight:600;padding:2px 7px;border-radius:20px;' . $resultStyle . '">' . $result . '</span>';
            } else {
                $html .= '—';
            }
            $html .= '</td>';

            $html .= '<td style="font-size:.78rem;color:#475569;white-space:nowrap;">' . ($source ?: '—') . '</td>';

            $html .= '<td style="font-size:.78rem;color:#475569;white-space:nowrap;font-family:monospace;">' . $ipAddress . '</td>';

            $html .= '<td style="font-size:.78rem;color:#475569;white-space:nowrap;">' . $deviceType . '</td>';

            // ── Sticky last column ───────────────────────────────────────────
            $html .= '<td class="al-sticky-col" style="text-align:center;">';
            if ($details) {
                $html .= '<button type="button" class="btn btn-sm btn-link p-0 alDetailsBtn" ';
                $html .= 'data-uid="' . $auditUID . '" ';
                $html .= 'data-details="' . htmlspecialchars($details) . '" ';
                $html .= 'title="View details">';
                $html .= '<i class="bx bx-code-alt" style="font-size:1rem;color:#7c3aed;"></i>';
                $html .= '</button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

}
