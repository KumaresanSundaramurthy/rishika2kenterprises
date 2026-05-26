<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public $pageData = [];

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
            $this->pageData['PageTitle'] = '';
            $this->pageData['PageIcon']  = '';
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

        if (!$found) return false;

        $this->pageData['PageTitle']       = !empty($found->DisplayName) ? $found->DisplayName : ($found->Name ?? '');
        $this->pageData['PageIcon']        = $found->Icon ?? '';
        $this->pageData['PageDescription'] = $found->Description ?? '';
        return true;
    }

    // ── Shared export helpers (used by Inventory, Customers, etc.) ────────────

    protected function _sendExport($type, $fileName, $sheetName, $previewName, $headers, $rows, $org = null, $timezone = 'UTC', $colWidths = null) {
        if ($type === 'Print') {
            $html = $this->_buildPrintHtml($previewName, $headers, $rows, $org, $timezone, $colWidths);
            header('Content-Type: application/json');
            echo json_encode(['Error' => false, 'HtmlData' => $html]);
            exit;
        }

        if ($type === 'Pdf') {
            if (ob_get_length()) ob_end_clean();
            $html = $this->_buildPrintHtml($previewName, $headers, $rows, $org, $timezone, $colWidths);
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"{$fileName}.pdf\"");
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream("{$fileName}.pdf", ['Attachment' => true]);
            exit;
        }

        if (ob_get_length()) ob_end_clean();

        $str = function($v) { $s = trim((string)$v); return ($v !== null && $s !== '' && $s !== 'null') ? $s : ''; };
        $orgName    = $org ? ($str($org->BrandName ?? '') ?: $str($org->Name ?? '')) : '';
        $addrParts  = [];
        if ($org) {
            if ($str($org->Line1 ?? ''))   $addrParts[] = $str($org->Line1 ?? '');
            if ($str($org->Line2 ?? ''))   $addrParts[] = $str($org->Line2 ?? '');
            $ct = $str($org->CityText ?? '');
            $st = $str($org->StateText ?? '');
            $cityState = trim($ct . ($st ? ' - ' . $st : ''));
            if ($cityState)                $addrParts[] = $cityState;
            if ($str($org->Pincode ?? '')) $addrParts[] = 'PIN: ' . $str($org->Pincode ?? '');
        }
        $addrLine   = implode(', ', $addrParts);
        $contactLine = implode('  |  ', array_filter([
            !empty($org->MobileNumber) ? 'Ph: ' . $org->MobileNumber  : '',
            !empty($org->EmailAddress) ? 'Email: ' . $org->EmailAddress : '',
            !empty($org->GSTIN)        ? 'GSTIN: ' . $org->GSTIN       : '',
        ]));

        if ($type === 'CSV') {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
            header('Pragma: no-cache');
            $f = fopen('php://output', 'w');
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            if ($orgName)    fputcsv($f, [$orgName]);
            if ($addrLine)   fputcsv($f, [$addrLine]);
            if ($contactLine) fputcsv($f, [$contactLine]);
            $genDate = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');
            fputcsv($f, [$previewName . ' — Generated: ' . $genDate]);
            fputcsv($f, []);
            fputcsv($f, $headers);
            foreach ($rows as $row) { fputcsv($f, $row); }
            fclose($f);
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $r = 1;
        $logoOffset = 0;
        if ($org && !empty($org->Logo)) {
            try {
                $tmpLogo = tempnam(sys_get_temp_dir(), 'exp_logo_');
                $imgData = @file_get_contents($org->Logo);
                if ($imgData) {
                    file_put_contents($tmpLogo, $imgData);
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setPath($tmpLogo);
                    $drawing->setHeight(48);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                    $logoOffset = 4;
                    $r = $logoOffset + 1;
                }
            } catch (\Exception $e) { /* skip logo */ }
        }

        $sheet->setCellValue("A{$r}", $orgName);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $r++;

        if ($addrLine) {
            $sheet->setCellValue("A{$r}", $addrLine);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $r++;
        }
        if ($contactLine) {
            $sheet->setCellValue("A{$r}", $contactLine);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $r++;
        }

        $genDate = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');
        $sheet->setCellValue("A{$r}", $previewName . '   |   Generated: ' . $genDate);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setColor(
            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setARGB('FF334155')
        );
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $r++;
        $r++;

        $headerRow = $r;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $r++;

        foreach ($rows as $row) {
            $sheet->fromArray($row, null, "A{$r}");
            $r++;
        }

        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        if ($type === 'Excel') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$fileName}.xlsx\"");
            header('Pragma: no-cache');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
    }

    protected function _buildPrintHtml($previewName, $headers, $rows, $org = null, $timezone = 'UTC', $colWidths = null) {
        $str = function($v) { $s = trim((string)$v); return ($v !== null && $s !== '' && $s !== 'null') ? $s : ''; };
        $orgName   = $org ? ($str($org->BrandName ?? '') ?: $str($org->Name ?? '')) : '';
        $addrParts = [];
        if ($org) {
            if ($str($org->Line1 ?? ''))   $addrParts[] = htmlspecialchars($str($org->Line1 ?? ''));
            if ($str($org->Line2 ?? ''))   $addrParts[] = htmlspecialchars($str($org->Line2 ?? ''));
            $ct = $str($org->CityText ?? '');
            $st = $str($org->StateText ?? '');
            $cs = trim($ct . ($st ? ' - ' . $st : ''));
            if ($cs)                       $addrParts[] = htmlspecialchars($cs);
            if ($str($org->Pincode ?? '')) $addrParts[] = 'PIN: ' . htmlspecialchars($str($org->Pincode ?? ''));
        }
        $logoHtml = ($org && !empty($org->Logo))
            ? '<img src="' . htmlspecialchars($org->Logo) . '" style="height:48px;object-fit:contain;" alt="Logo">'
            : '';
        $contactParts = array_filter([
            !empty($org->MobileNumber) ? 'Ph: ' . htmlspecialchars($org->MobileNumber)    : '',
            !empty($org->EmailAddress) ? 'Email: ' . htmlspecialchars($org->EmailAddress) : '',
            !empty($org->GSTIN)        ? 'GSTIN: ' . htmlspecialchars($org->GSTIN)        : '',
        ]);
        $date = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');

        $colGroup    = '';
        $tableLayout = 'auto';
        if (!empty($colWidths)) {
            $tableLayout = 'fixed';
            $colGroup    = '<colgroup>';
            foreach ($colWidths as $w) {
                $colGroup .= '<col style="width:' . htmlspecialchars($w) . '">';
            }
            $colGroup .= '</colgroup>';
        }

        $th = '';
        foreach ($headers as $h) {
            $th .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $tb = '';
        foreach ($rows as $row) {
            $tb .= '<tr>';
            foreach ($row as $cell) {
                $tb .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $tb .= '</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>' . htmlspecialchars($orgName ?: $previewName) . '</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
@page{size:A4 landscape;margin:10mm 15mm;}
body{font-family:Arial,Helvetica,sans-serif;font-size:9px;}
.org-header{text-align:center;border-bottom:2px solid #334155;padding-bottom:8px;margin-bottom:8px;}
.org-logo{margin-bottom:4px;}
.org-name{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:2px;}
.org-addr{color:#475569;font-size:8px;margin-bottom:2px;}
.org-contact{color:#475569;font-size:8px;}
.report-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.report-bar h4{margin:0;font-size:11px;font-weight:700;color:#1e293b;}
.report-bar .gen-date{font-size:8px;color:#64748b;}
table{width:100%;border-collapse:collapse;table-layout:' . $tableLayout . ';}
th{background:#e2e8f0;padding:5px 6px;text-align:left;border:1px solid #cbd5e1;font-size:8.5px;color:#1e293b;font-weight:700;white-space:nowrap;overflow:hidden;}
td{padding:4px 6px;border:1px solid #e2e8f0;font-size:8px;color:#334155;word-break:break-word;overflow-wrap:break-word;}
tr:nth-child(even) td{background:#f8fafc;}
@media print{@page{size:A4 landscape;margin:10mm 15mm;}}
</style>
</head><body>
<div class="org-header">
  ' . ($logoHtml ? '<div class="org-logo">' . $logoHtml . '</div>' : '') . '
  <div class="org-name">' . htmlspecialchars($orgName) . '</div>
  ' . (!empty($addrParts) ? '<div class="org-addr">' . implode(', ', $addrParts) . '</div>' : '') . '
  ' . (!empty($contactParts) ? '<div class="org-contact">' . implode(' &nbsp;|&nbsp; ', $contactParts) . '</div>' : '') . '
</div>
<div class="report-bar">
  <h4>' . htmlspecialchars($previewName) . '</h4>
  <span class="gen-date">Generated: ' . $date . '</span>
</div>
<table>' . $colGroup . '<thead><tr>' . $th . '</tr></thead><tbody>' . $tb . '</tbody></table>
</body></html>';
    }

}
