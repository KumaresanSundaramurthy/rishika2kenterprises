<?php defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

Class Globalservice {

    protected $CI;
    private $confData;
    private $EndReturnData;

    public function __construct() {

        $this->CI =& get_instance();

    }

    public function setJwtData($confData) {
        $this->confData = $confData;
    }

    public function refreshUserCache() {
        $GetRedisDetails = $this->CI->cacheservice->get($this->CI->pageData['JwtUserKey']);
        if ($GetRedisDetails->Error === FALSE) {
            $this->CI->load->model('user_model');
            $UserData = $this->CI->user_model->getUserByUserInfo(['User.UserUID' => $this->CI->pageData['JwtData']->User->UserUID]);

            if ($UserData->Error === FALSE && count($UserData->Data) === 1) {
                $this->CI->load->model('login_model');
                $jwtPayload = $this->CI->login_model->formatJWTPayload($UserData->Data[0]);

                if ($jwtPayload->Error === FALSE) {
                    $this->CI->cacheservice->set($GetRedisDetails->Key, json_encode($jwtPayload->JWTData), $GetRedisDetails->TTL);
                }
            }
        }
    }

    public function renderSubMenu($ControllerName, $allSubMenus, $parentUID = null) {

        // Filter only menus under current parent
        $filteredMenus = array_filter($allSubMenus, function($sm) use ($parentUID) {
            return $sm->ParentSubMenuUID == $parentUID;
        });

        // Sort by 'Sorting' property
        usort($filteredMenus, function($a, $b) {
            return $a->Sorting <=> $b->Sorting;
        });

        // Loop through and render
        foreach ($filteredMenus as $subMenu) {
            // Find children
            $childMenus = array_filter($allSubMenus, function($sm) use ($subMenu) {
                return $sm->ParentSubMenuUID == $subMenu->SubMenuUID;
            });

            if (count($childMenus) > 0) {
                echo '<li class="menu-item">';
                echo '<a href="javascript:void(0);" class="menu-link menu-toggle">';
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '<ul class="menu-sub">';
                $this->renderSubMenu($ControllerName, $allSubMenus, $subMenu->SubMenuUID); // Recursive call
                echo '</ul>';
                echo '</li>';
            } else {
                // $activeClass = (strtolower($ControllerName) == strtolower($subMenu->ControllerName)) ? 'active' : '';
                $isActive = (strtolower($ControllerName) == strtolower($subMenu->ControllerName));
                $activeClass = $isActive ? 'active' : '';
                $href = $isActive ? 'javascript: void(0);' : '/' . htmlspecialchars($subMenu->ControllerName);
                echo '<li class="menu-item ' . $activeClass . '">';
                echo '<a href="' . $href . '" class="menu-link">';
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '</li>';
            }
        }
        
    }

    private function getPageTypeConfig($PageType) {
        $map = [
            'MainPage'   => ['where' => 'IsMainPageRequired', 'sort' => 'MainPageOrder', 'disp' => 'IsMainPageApplicable', 'settings' => 'IsMainPageSettingsApplicable'],
            'PageShift'  => ['where' => 'IsMainPageRequired', 'sort' => 'MainPageOrder', 'disp' => 'IsMainPageApplicable'],
            'PrintPage'  => ['where' => 'IsPrintPreviewRequired', 'sort' => 'PrintPreviewOrder', 'disp' => 'IsPrintPreviewApplicable'],
            'CsvPage'    => ['where' => 'IsExportRequired', 'sort' => 'ExportCsvOrder', 'disp' => 'IsExportCsvApplicable'],
            'ExcelPage'  => ['where' => 'IsExportRequired', 'sort' => 'ExportExcelOrder', 'disp' => 'IsExportExcelApplicable'],
            'PdfPage'    => ['where' => 'IsExportRequired', 'sort' => 'ExportPdfOrder', 'disp' => 'IsExportPdfApplicable'],
        ];

        return $map[$PageType] ?? null;
    }

    public function getPaginationInfo($pageNo, $limit, $DataCount) {

        $config['base_url']        = '/globally/getModPageDataDetails';
        $config['use_page_numbers'] = TRUE;
        $config['total_rows']      = $DataCount;
        $config['per_page']        = $limit;
        $config['result_count']    = pageResultCount($pageNo, $limit, $DataCount);

        $this->CI->load->library('pagination');
        $this->CI->pagination->initialize($config);

        return $this->CI->pagination->create_links();

    }

    public function getBaseMainPageTablePagination($ModuleId, $pageNo, $limit, $offset, $Filter, $WhereInCondition = [], $Type = '') {

        $this->EndReturnData = new stdClass();
		try {

            if ($ModuleId <= 0) {
                throw new Exception('Module Information is Missing');
            }
            
            $DataType = '';
            if($Type == 'Index') {
                $DataType = 'MainPage';
            } else if($Type == 'Pagination') {
                $DataType = 'PageShift';
            }

            $DataResp = $this->getModulePageColumnDetails($ModuleId, $DataType, $Filter, $WhereInCondition, $limit, $offset);
            if ($DataResp->Error) {
                throw new Exception($DataResp->Message);
            }

            $this->EndReturnData->TotalRowCount = $DataResp->TotalRowCount;
            $this->EndReturnData->Pagination = $this->getPaginationInfo($pageNo, $limit, $DataResp->TotalRowCount);

            if($Type == 'Index') {

                $this->EndReturnData->ViewAllColumns = $DataResp->ViewAllColumns;
                $this->EndReturnData->DispViewColumns = $DataResp->DispViewColumns;
                if($DataType == 'MainPage') {
                    $this->EndReturnData->DispSettingsViewColumns = $DataResp->DispSettingsViewColumns;
                    $this->EndReturnData->RecordHtmlData = $DataResp->RecordHtmlData;
                }

            } else if($Type == 'Pagination') {
                $this->EndReturnData->RecordHtmlData = $DataResp->RecordHtmlData;
            }

            $this->EndReturnData->Error = FALSE;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

    }

    public function getModulePageColumnDetails($ModuleId, $PageType, $Filter, $WhereInCondition, $Limit, $Offset) {

        $this->EndReturnData = new stdClass();
		try {

            if ($ModuleId <= 0) {
                throw new Exception('Module Information is Missing');
            }

            $this->CI->load->model('global_model');

            $config = $this->getPageTypeConfig($PageType);
            if (!$config) {
                throw new Exception("Invalid PageType: $PageType");
            }

            $VC_WhereCond = $config['where'];
            $VC_SortField = $config['sort'];
            $VC_MPDispAppl = $config['disp'] ?? '';
            $VC_MPSettings = $config['settings'] ?? '';
            
            $ViewAllColumns = $this->getModuleViewColumnDetails($ModuleId, $VC_WhereCond, $VC_SortField);

            $columns = $this->getDispColumns($ViewAllColumns, $PageType, $VC_MPDispAppl, $VC_MPSettings);
            $DispViewColumns = $columns['disp'];
            if($PageType == 'MainPage') {
                $DispSettingsViewColumns = $columns['settings'] ?? [];
            }

            // $ModuleInfoSession = $ModuleId . '-moduleinfo';
            // $ModuleInfo = $this->CI->session->userdata($ModuleInfoSession);
            // if (empty($ModuleInfo)) {
                $ModuleInfo = $this->CI->global_model->getModuleDetails(['Modules.ModuleUID' => $ModuleId]);
            //     $this->CI->session->set_userdata($ModuleId.'-moduleinfo', $ModuleInfo);
            // }
            
            if(count($DispViewColumns) > 0 && count($ModuleInfo) > 0) {

                $ModuleInfoData = $ModuleInfo[0];

                $FilterFormat = $this->buildFilterFormat($ModuleInfoData, $Filter);

                $Aggregates = [];
                // foreach ($DispViewColumns as $index => $column) {
                //     if (!empty($column->AggregationMethod)) {
                //         $Aggregates[$index][$column->AggregationMethod] = 0;
                //     }
                // }
                foreach ($DispViewColumns as $column) {
                    if (!empty($column->AggregationMethod) && !empty($column->DbFieldName)) {
                        $Aggregates[$column->DbFieldName][$column->AggregationMethod] = 0;
                    }
                }

                $WhereInArrayData = [];
                if(!empty($WhereInCondition)) {
                    if (array_key_exists('ExportIds', $WhereInCondition)) {
                        $WhereInArrayData[$ModuleInfoData->TableAliasName.'.'.$ModuleInfoData->TablePrimaryUID] = $WhereInCondition['ExportIds'];
                    }
                }

                // $ViewJoinsSession = $ModuleId . '-viewjoins';
                // $JoinData = $this->CI->session->userdata($ViewJoinsSession);
                // if (empty($JoinData)) {
                    $JoinData = $this->CI->global_model->getModuleViewJoinColumnDetails(['JoinColmn.MainModuleUID' => $ModuleId], true, ['JoinColmn.SortOrder' => 'ASC']);
                //     $this->CI->session->set_userdata($ModuleId.'-viewjoins', $JoinData);
                // }

                $DataLists = $this->CI->global_model->getModuleReportDetails($ModuleInfoData, $ViewAllColumns, $JoinData, $FilterFormat->SearchFilter, $FilterFormat->SearchDirectQuery, 'DESC', $WhereInArrayData, $Limit, $Offset, $FilterFormat->sortOperation);

                if (!in_array($PageType, ['PrintPage', 'CsvPage', 'ExcelPage', 'PdfPage'])) {
                    $TotalRowCount = $this->CI->global_model->getModuleTotalDataRowCount($ModuleInfoData, $ViewAllColumns, $JoinData, $FilterFormat->SearchFilter, $FilterFormat->SearchDirectQuery, $WhereInArrayData);
                }

                $this->EndReturnData->Error = FALSE;
                if (!in_array($PageType, ['PrintPage', 'CsvPage', 'ExcelPage', 'PdfPage'])) {
                    $listDataReq = [
                        'DataLists' => $DataLists,
                        'SerialNumber' => $Offset * $Limit,
                        'DispViewColumns' => $DispViewColumns,
                        'GenSettings' => $this->CI->redis_cache->get('Redis_UserGenSettings')->Value ?? new stdClass(),
                        'JwtData' => $this->CI->pageData['JwtData'],
                    ];
                    if($ModuleInfoData->EditOnPage == 1) {
                        $listDataReq['ViewAllColumns'] = $ViewAllColumns;
                    }
                    $this->EndReturnData->RecordHtmlData = $this->CI->load->view($ModuleInfoData->ListUrl, $listDataReq, TRUE);
                }
                if($PageType == 'MainPage') {
                    $this->EndReturnData->ViewAllColumns = $ViewAllColumns;
                    $this->EndReturnData->DispViewColumns = $DispViewColumns;
                    $this->EndReturnData->DispSettingsViewColumns = $DispSettingsViewColumns;
                } else if($PageType == 'PrintPage') {
                    $this->EndReturnData->DispViewColumns = $DispViewColumns;
                    $this->EndReturnData->DataLists = $DataLists;
                    $this->EndReturnData->Aggregates = $Aggregates;
                } else if(in_array($PageType, ['CsvPage', 'ExcelPage', 'PdfPage'])) {
                    $this->EndReturnData->DispViewColumns = $DispViewColumns;
                    $this->EndReturnData->DataLists = $DataLists;
                    $this->EndReturnData->Aggregates = $Aggregates;
                }
                if (!in_array($PageType, ['PrintPage', 'CsvPage', 'ExcelPage', 'PdfPage'])) {
                    $this->EndReturnData->TotalRowCount = $TotalRowCount;
                }

            } else {
                throw new Exception('Column Information is Missing.!');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

    }

    public function getModuleViewColumnDetails($ModuleId, $VC_WhereCond, $VC_SortField) {

        $this->EndReturnData = new stdClass();
		try {

            // $ViewColumnsSession = $ModuleId . '-viewcolumns';
            // $this->session->unset_userdata($ViewColumnsSession);
            // $ViewAllColumns = $this->CI->session->userdata($ViewColumnsSession);
            // if (empty($ViewAllColumns)) {

                $this->CI->load->model('global_model');
                return $this->CI->global_model->getModuleViewColumnDetails(['ViewColmn.ModuleUID' => $ModuleId, 'ViewColmn.'.$VC_WhereCond => 1], true, ['ViewColmn.'.$VC_SortField => 'ASC']);
            
            //     $this->CI->session->set_userdata($ModuleId.'-viewcolumns', $ViewAllColumns);
            // }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    private function buildFilterFormat($ModuleInfoData, $Filter) {
        $format = (object)['SearchFilter' => [], 'SearchDirectQuery' => '', 'sortOperation' => []];
        if (!empty($ModuleInfoData->ModelName) && !empty($ModuleInfoData->FilterFunctionName)) {
            $this->CI->load->model($ModuleInfoData->ModelName);
            $resp = $this->CI->{$ModuleInfoData->ModelName}->{$ModuleInfoData->FilterFunctionName}($ModuleInfoData, $Filter);
            $format->SearchFilter = $resp->SearchFilter ?? [];
            $format->SearchDirectQuery = $resp->SearchDirectQuery ?? '';
            $format->sortOperation = $resp->sortOperation ?? [];
        }
        return $format;
    }

    private function getDispColumns($ViewAllColumns, $PageType, $VC_MPDispAppl, $VC_MPSettings) {
        if ($PageType === 'MainPage') {
            return [
                'disp' => array_values(array_filter($ViewAllColumns, fn($item) => !empty($item->$VC_MPDispAppl))),
                'settings' => array_values(array_filter($ViewAllColumns, fn($item) => !empty($item->$VC_MPSettings))),
            ];
        }
        return ['disp' => array_values(array_filter($ViewAllColumns, fn($item) => !empty($item->$VC_MPDispAppl)))];
    }

    public function exportCSV($FileName, $ViewColumns, $DataValue, $Aggregates) {

        $this->EndReturnData = new StdClass();
        try {

            if (ob_get_length()) ob_end_clean();

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$FileName.csv\"");
            header("Pragma: no-cache");
            header("Expires: 0");

            $file = fopen('php://output', 'w');

            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            $Columns = array_column($ViewColumns, 'DisplayName');
            $FinalAggregates = $Aggregates;

            fputcsv($file, $Columns);

            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $row) {

                    $colDataVal = [];
                    foreach($ViewColumns as $colKey => $column) {
                        $dbField = $column->DbFieldName;
                        $method  = !empty($column->AggregationMethod) ? strtoupper($column->AggregationMethod) : '';

                        if ($method) {
                            
                            $numericValue = is_numeric($row->{$column->DisplayName} ?? null)  ? (float)($row->{$column->DisplayName})  : 0;

                            switch ($method) {
                                case 'SUM':
                                    if (!isset($FinalAggregates[$dbField]['SUM'])) {
                                        $FinalAggregates[$dbField]['SUM'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['SUM'] += $numericValue;
                                    break;

                                case 'COUNT':
                                    if (!isset($FinalAggregates[$dbField]['COUNT'])) {
                                        $FinalAggregates[$dbField]['COUNT'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['COUNT']++;
                                    break;

                                case 'AVG':
                                    if (!isset($FinalAggregates[$dbField]['_sum'])) {
                                        $FinalAggregates[$dbField]['_sum']   = 0;
                                        $FinalAggregates[$dbField]['_count'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['_sum']   += $numericValue;
                                    $FinalAggregates[$dbField]['_count']++;
                                    break;
                            }
                        }
                        $colDataVal[] = format_disp_column_value('excel', $column, $row, $this->CI->pageData['JwtData'], $this->CI->pageData['JwtData']->GenSettings, $colKey);
                    }

                    fputcsv($file, $colDataVal);

                }

                $Summary = [];
                foreach ($ViewColumns as $column) {
                    $dbField = $column->DbFieldName;
                    $method  = !empty($column->AggregationMethod) ? strtoupper($column->AggregationMethod) : '';
                    $output  = '';

                    if ($method === 'SUM' && isset($FinalAggregates[$dbField]['SUM'])) {
                        $output = $column->IsAmountField == 1 
                                ? smartDecimal($FinalAggregates[$dbField]['SUM']) 
                                : $FinalAggregates[$dbField]['SUM'];

                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$dbField]['COUNT'])) {
                        $output = $FinalAggregates[$dbField]['COUNT'];

                    } elseif ($method === 'AVG' && isset($FinalAggregates[$dbField]['_sum'], $FinalAggregates[$dbField]['_count']) 
                            && $FinalAggregates[$dbField]['_count'] > 0) {
                        $avg = $FinalAggregates[$dbField]['_sum'] / $FinalAggregates[$dbField]['_count'];
                        $output = $column->IsAmountField == 1 ? smartDecimal($avg) : $avg;
                    }

                    $Summary[] = $output;
                }

                fputcsv($file, $Summary);

            }

            fclose($file);
            exit;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function exportExcel($FileName, $SheetName, $ViewColumns, $DataValue, $Aggregates) {

        $this->EndReturnData = new StdClass();
        try {

            if (ob_get_length()) ob_end_clean();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set sheet name
            $sheet->setTitle($SheetName);

            $Columns = array_column($ViewColumns, 'DisplayName');
            $FinalAggregates = $Aggregates;

            // Write headers
            $sheet->fromArray($Columns, null, 'A1');

            // Write data rows
            $rowNum = 2;

            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $row) {

                    $colDataVal = [];
                    foreach($ViewColumns as $colKey => $column) {
                        $dbField = $column->DbFieldName;
                        $method  = !empty($column->AggregationMethod) ? strtoupper($column->AggregationMethod) : '';

                        if ($method) {
                            
                            $numericValue = is_numeric($row->{$column->DisplayName} ?? null)  ? (float)($row->{$column->DisplayName})  : 0;

                            switch ($method) {
                                case 'SUM':
                                    if (!isset($FinalAggregates[$dbField]['SUM'])) {
                                        $FinalAggregates[$dbField]['SUM'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['SUM'] += $numericValue;
                                    break;

                                case 'COUNT':
                                    if (!isset($FinalAggregates[$dbField]['COUNT'])) {
                                        $FinalAggregates[$dbField]['COUNT'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['COUNT']++;
                                    break;

                                case 'AVG':
                                    if (!isset($FinalAggregates[$dbField]['_sum'])) {
                                        $FinalAggregates[$dbField]['_sum']   = 0;
                                        $FinalAggregates[$dbField]['_count'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['_sum']   += $numericValue;
                                    $FinalAggregates[$dbField]['_count']++;
                                    break;
                            }
                        }
                        $colDataVal[] = format_disp_column_value('excel', $column, $row, $this->CI->pageData['JwtData'], $this->CI->pageData['JwtData']->GenSettings, $colKey);
                    }

                    $excelColIndex = 0;
                    foreach ($colDataVal as $cellValue) {
                        $columnLetter = Coordinate::stringFromColumnIndex($excelColIndex + 1);
                        $sheet->setCellValue($columnLetter . $rowNum, $cellValue);
                        $excelColIndex++;
                    }

                    $rowNum++;
                }

                // Summary Section
                if (!empty($ViewColumns)) {
                    foreach ($ViewColumns as $colKey => $column) {
                        $dbField = $column->DbFieldName;
                        $method  = !empty($column->AggregationMethod) ? strtoupper(trim($column->AggregationMethod)) : '';
                        $output  = '';

                        if ($method === 'SUM' && isset($FinalAggregates[$dbField]['SUM'])) {
                            $output = $column->IsAmountField == 1
                                ? ($FinalAggregates[$dbField]['SUM']
                                    ? smartDecimal($FinalAggregates[$dbField]['SUM'])
                                    : 0)
                                : $FinalAggregates[$dbField]['SUM'];

                        } elseif ($method === 'COUNT' && isset($FinalAggregates[$dbField]['COUNT'])) {
                            $output = $FinalAggregates[$dbField]['COUNT'];

                        } elseif ($method === 'AVG'
                            && isset($FinalAggregates[$dbField]['_sum'], $FinalAggregates[$dbField]['_count'])
                            && $FinalAggregates[$dbField]['_count'] > 0) {
                            $avg = $FinalAggregates[$dbField]['_sum'] / $FinalAggregates[$dbField]['_count'];
                            $output = $column->IsAmountField == 1
                                ? ($avg ? smartDecimal($avg) : 0)
                                : $avg;
                        }

                        if ($output !== '') {
                            $columnLetter = Coordinate::stringFromColumnIndex($colKey + 1);
                            $sheet->setCellValue($columnLetter . $rowNum, $output);
                        }
                    }
                }

            }

            // Output headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$FileName.xlsx\"");
            header('Cache-Control: max-age=0');

            // Write file to output
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function exportPdf($FileName, $SheetName, $ViewColumns, $DataValue, $Aggregates) {

        $this->EndReturnData = new StdClass();
        try {

            if (ob_get_length()) ob_end_clean();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($SheetName);

            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
            $spreadsheet->getDefaultStyle()->getAlignment()
                ->setHorizontal('left')
                ->setVertical('center')
                ->setWrapText(true);

            $sheet->getPageMargins()->setTop(0.5);
            $sheet->getPageMargins()->setBottom(0.5);
            $sheet->getPageMargins()->setLeft(0.5);
            $sheet->getPageMargins()->setRight(0.5);

            $Columns = array_column($ViewColumns, 'DisplayName');
            $FinalAggregates = $Aggregates;

            // Write headers
            $sheet->fromArray($Columns, null, 'A1');

            foreach ($ViewColumns as $colKey => $column) {
                $colLetter = Coordinate::stringFromColumnIndex($colKey + 1);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Write data rows
            $rowNum = 2;
            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $row) {

                    $colDataVal = [];
                    foreach ($ViewColumns as $colKey => $column) {

                        $dbField = $column->DbFieldName;
                        $method  = !empty($column->AggregationMethod) ? strtoupper($column->AggregationMethod) : '';
                        $value   = $row->{$column->DisplayName} ?? null;

                        if ($method) {
                            $numericValue = is_numeric($value) ? (float)$value : 0;

                            switch ($method) {
                                case 'SUM':
                                    $FinalAggregates[$dbField]['SUM'] = ($FinalAggregates[$dbField]['SUM'] ?? 0) + $numericValue;
                                    break;

                                case 'COUNT':
                                    $FinalAggregates[$dbField]['COUNT'] = ($FinalAggregates[$dbField]['COUNT'] ?? 0) + 1;
                                    break;

                                case 'AVG':
                                    if (!isset($FinalAggregates[$dbField]['_sum'])) {
                                        $FinalAggregates[$dbField]['_sum'] = 0;
                                        $FinalAggregates[$dbField]['_count'] = 0;
                                    }
                                    $FinalAggregates[$dbField]['_sum'] += $numericValue;
                                    $FinalAggregates[$dbField]['_count']++;
                                    break;
                            }
                        }

                        // Use excel type to avoid currency symbols/HTML
                        $colDataVal[] = format_disp_column_value('excel', $column, $row, $this->CI->pageData['JwtData'], $this->CI->pageData['JwtData']->GenSettings, $colKey);
                    }

                    foreach ($colDataVal as $excelColIndex => $cellValue) {
                        $colLetter = Coordinate::stringFromColumnIndex($excelColIndex + 1);
                        $sheet->setCellValue($colLetter . $rowNum, $cellValue);
                    }
                    $rowNum++;

                }

                foreach ($ViewColumns as $colKey => $column) {
                    $dbField = $column->DbFieldName;
                    $method  = !empty($column->AggregationMethod) ? strtoupper(trim($column->AggregationMethod)) : '';
                    $output  = '';

                    if ($method === 'SUM' && isset($FinalAggregates[$dbField]['SUM'])) {
                        $output = $column->IsAmountField == 1 ? smartDecimal($FinalAggregates[$dbField]['SUM']) : $FinalAggregates[$dbField]['SUM'];

                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$dbField]['COUNT'])) {
                        $output = $FinalAggregates[$dbField]['COUNT'];

                    } elseif ($method === 'AVG' && isset($FinalAggregates[$dbField]['_sum'], $FinalAggregates[$dbField]['_count']) && $FinalAggregates[$dbField]['_count'] > 0) {
                        $avg = $FinalAggregates[$dbField]['_sum'] / $FinalAggregates[$dbField]['_count'];
                        $output = $column->IsAmountField == 1 ? smartDecimal($avg) : $avg;
                    }

                    if ($output !== '') {
                        $colLetter = Coordinate::stringFromColumnIndex($colKey + 1);
                        $sheet->setCellValue($colLetter . $rowNum, $output);
                    }
                }

            }

            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);

            // Output headers
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"$FileName.pdf\"");
            header('Cache-Control: max-age=0');

            // Use Dompdf writer
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Dompdf::class);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function checkImageType() {

        $allowed = array('image/jpeg', 'image/jpg', 'image/png');
        $type_not_match = false;
        if (isset($_FILES['Thumbnail']['name']) && !empty($_FILES['Thumbnail']['name'])) {
            if (!in_array($_FILES['Thumbnail']['type'], $allowed) || $_FILES['Thumbnail']['size'] > 1048576) {
                $type_not_match = true;
            }
        }
        if ($type_not_match) {
            $this->CI->form_validation->set_message('checkImageType', 'Invalid File. Please upload allowed format and size will be below 1MB');
            return false;
        } else {
            return true;
        }

    }

    public function fileUploadService($fileData, $fullPath, $fieldName, $WhereCond) {

        $this->EndReturnData = new stdClass();
		try {

            if(isset($fileData) && $fileData['error'] == 0) {
                if(isset($fileData['tmp_name']) && !empty($fileData['tmp_name'])) {

                    $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
                    $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $fileData['name'])), 0, 50).'_'.uniqid().'.'.$ext;

                    $this->CI->load->library('fileupload');
                    $uploadDetail = $this->CI->fileupload->fileUpload('file', $fullPath.$fileName, $fileData['tmp_name']);

                    if ($uploadDetail->Error === false) {

                        $updateFileData = [
                            $fieldName => '/'.$uploadDetail->Path,
                        ];
                        $UpdateFileResp = $this->CI->dbwrite_model->updateData($WhereCond[0], $WhereCond[1], $updateFileData, $WhereCond[2]);
                        if($UpdateFileResp->Error) {
                            throw new Exception($UpdateFileResp->Message);
                        }

                        $this->EndReturnData->Error = FALSE;
                        $this->EndReturnData->Message = 'Uploaded Successfully';
                        $this->EndReturnData->Path = '/'.$uploadDetail->Path;

                    } else {
                        throw new Exception('File upload failed');
                    }

                } else {
                    $this->EndReturnData->Error = FALSE;
                }

            } else {
                $this->EndReturnData->Error = FALSE;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

    }

    public function softDeleteBankRecords($recordIds, $moduleName, $tableName, $pkField) {

        if (empty($recordIds)) return;

        $recordIds   = (array) $recordIds;
        $currentUser = $this->CI->pageData['JwtData']->User->UserUID ?? null;
        $now         = time();

        $updArray = [];
        foreach ($recordIds as $rid) {
            $updArray[] = [
                $pkField    => (int) $rid,
                'IsDeleted' => 1,
                'UpdatedBy' => $currentUser,
                'UpdatedOn' => $now,
            ];
        }

        if (count($updArray) === 1) {
            $resp = $this->CI->dbwrite_model->updateData($moduleName, $tableName, $updArray[0], [$pkField => $updArray[0][$pkField]]);
        } else {
            $resp = $this->CI->dbwrite_model->updateBatchData($moduleName, $tableName, $updArray, $pkField);
        }

        if ($resp->Error) {
            throw new Exception($resp->Message);
        }

    }

    public function saveBankDetails($entityUID, $detailsJson, $moduleName, $tableName, $extraFields = []) {

        if (!$detailsJson) return;

        $details = is_array($detailsJson) ? $detailsJson : json_decode($detailsJson, true);
        if (!is_array($details) || count($details) === 0) return;

        $insertBatch = [];
        $updateBatch = [];

        $currentUser = $this->CI->pageData['JwtData']->User->UserUID ?? null;
        $now = time();

        foreach ($details as $record) {
            $id = $record['id'] ?? null;

            $dataArray = array_merge([
                $moduleName . 'UID'       => $entityUID,
                'Type'                    => $record['type'] ?? NULL,
                'BankAccountNumber'       => getPostValue($record, 'accNumber') ?? NULL,
                'BankIFSC_Code'           => getPostValue($record, 'ifsc') ?? NULL,
                'BankBranchName'          => getPostValue($record, 'branch') ?? NULL,
                'BankAccountHolderName'   => getPostValue($record, 'holder') ?? NULL,
                'UPI_Id'                  => getPostValue($record, 'upiId') ?? NULL,
                'UpdatedBy'               => $currentUser,
                'UpdatedOn'               => $now,
            ], $extraFields);

            if (is_string($id) && strpos($id, 'New-') === 0) {
                $dataArray['CreatedBy'] = $currentUser;
                $dataArray['CreatedOn'] = $now;
                $insertBatch[] = $dataArray;
            } elseif (is_numeric($id) || (is_string($id) && ctype_digit($id))) {
                $updateBatch[] = [
                    'data'  => $dataArray,
                    'where' => [$tableName . 'UID' => (int) $id],
                ];
            }
        }

        if (count($insertBatch) > 0) {
            $resp = $this->CI->dbwrite_model->insertBatchData($moduleName, $tableName, $insertBatch);
            if ($resp->Error) throw new Exception($resp->Message);
        }

        if (count($updateBatch) > 0) {
            foreach ($updateBatch as $u) {
                $resp = $this->CI->dbwrite_model->updateData($moduleName, $tableName, $u['data'], $u['where']);
                if ($resp->Error) throw new Exception($resp->Message);
            }
        }
    }

    public function softDeleteAddressRecords($recordIds, $moduleName, $tableName, $pkField) {

        if (empty($recordIds)) return;

        $recordIds   = (array) $recordIds;
        $currentUser = $this->CI->pageData['JwtData']->User->UserUID ?? null;
        $now         = time();

        $updArray = [];
        foreach ($recordIds as $rid) {
            $updArray[] = [
                $pkField    => (int) $rid,
                'IsDeleted' => 1,
                'UpdatedBy' => $currentUser,
                'UpdatedOn' => $now,
            ];
        }

        if (count($updArray) === 1) {
            $resp = $this->CI->dbwrite_model->updateData($moduleName, $tableName, $updArray[0], [$pkField => $updArray[0][$pkField]]);
        } else {
            $resp = $this->CI->dbwrite_model->updateBatchData($moduleName, $tableName, $updArray, $pkField);
        }

        if ($resp->Error) {
            throw new Exception($resp->Message);
        }

    }

    public function saveAddressInfo($postData, $entityUID, $typePrefix, $addressType, $moduleName, $tableName, $uidField, $entityField) {

        if (!isset($postData[$typePrefix.'AddrLine1']) || $postData[$typePrefix.'AddrLine1'] === '') {
            return;
        }

        $addressData = [
            $entityField => $entityUID,
            'OrgUID'     => $this->CI->pageData['JwtData']->User->OrgUID,
            'AddressType'=> $addressType,
            'Line1'      => $postData[$typePrefix.'AddrLine1'],
            'Line2'      => getPostValue($postData, $typePrefix.'AddrLine2') ?? NULL,
            'Pincode'    => $postData[$typePrefix.'AddrPincode'],
            'City'       => getPostValue($postData, $typePrefix.'AddrCity') ?? NULL,
            'CityText'   => getPostValue($postData, $typePrefix.'AddrCityText') ?? NULL,
            'State'      => getPostValue($postData, $typePrefix.'AddrState') ?? NULL,
            'StateText'  => getPostValue($postData, $typePrefix.'AddrStateText') ?? NULL,
            'UpdatedBy'  => $this->CI->pageData['JwtData']->User->UserUID,
            'UpdatedOn'  => time(),
        ];

        $addressUIDField = $typePrefix.'AddressUID';

        if (isset($postData[$addressUIDField]) && $postData[$addressUIDField] == 0) {
            $addressData['CreatedBy'] = $this->CI->pageData['JwtData']->User->UserUID;
            $addressData['CreatedOn'] = time();

            $resp = $this->CI->dbwrite_model->insertData($moduleName, $tableName, $addressData);
            if ($resp->Error) throw new Exception($resp->Message);

        } elseif (isset($postData[$addressUIDField]) && $postData[$addressUIDField] > 0) {
            $resp = $this->CI->dbwrite_model->updateData(
                $moduleName,
                $tableName,
                $addressData,
                [$uidField => $postData[$addressUIDField]]
            );
            if ($resp->Error) throw new Exception($resp->Message);
        }

    }

    public function baseDeleteArrayDetails() {

        $deleteData = [
                'IsDeleted' => 1,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedOn' => time(),
            ];
        return $deleteData;

    }

    public function baseTableDataPaginationDetails($pageNo = 0) {

        $limit = $this->CI->input->post('RowLimit');
        $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
        $Filter = $this->CI->input->post('Filter') ?? [];
        $ModuleId = $this->CI->input->post('ModuleId');

        $GeneralSettings = ($this->CI->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
        $this->CI->pageData['JwtData']->GenSettings = $GeneralSettings;

        $ReturnResponse = $this->getBaseMainPageTablePagination($ModuleId, $pageNo, $limit, $offset, $Filter, [], 'Pagination');
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }
        
        return $ReturnResponse;

    }

    public function sendJsonResponse($data) {
        $this->CI->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

}