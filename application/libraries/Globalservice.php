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
                $activeClass = (strtolower($ControllerName) == strtolower($subMenu->ControllerName)) ? 'active' : '';
                echo '<li class="menu-item ' . $activeClass . '">';
                echo '<a href="/' . htmlspecialchars($subMenu->ControllerName) . '" class="menu-link">';
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '</li>';
            }
        }
        
    }

    public function getBaseMainPageTablePagination($ModuleId, $CallingUrl, $ViewPath, $pageNo, $limit, $offset, $Filter, $WhereInCondition = []) {

        $this->EndReturnData = new stdClass();
		try {

            if($ModuleId > 0) {
                
                $DataResp = $this->getModulePageColumnDetails($ModuleId, 'MainPage', $Filter, $WhereInCondition, $limit, $offset, 1);
                if($DataResp->Error === FALSE) {

                    $this->CI->pageData['ModuleInfo'] = $DataResp->ModuleInfo;
                    $this->CI->pageData['ViewColumns'] = filterViewDataColumns($DataResp->ViewColumns, 'IsMainPageApplicable');
                    
                    $this->CI->pageData['DataLists'] = $DataResp->DataLists;
                    $this->CI->pageData['GenSettings'] = $this->confData['JwtData']->GenSettings;
                    $DataCount = sizeof($DataResp->DataPrimaryUIDs);

                    $this->CI->pageData['SerialNumber'] = $offset;

                    $config['base_url'] = $CallingUrl;
                    $config['use_page_numbers'] = TRUE;
                    $config['total_rows'] = $DataCount;
                    $config['per_page'] = $limit;

                    $config['result_count'] = pageResultCount($pageNo, $limit, $DataCount);
                    $this->CI->load->library('pagination');
                    $this->CI->pagination->initialize($config);

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->List = $this->CI->load->view($ViewPath, $this->CI->pageData, TRUE);
                    $this->EndReturnData->UIDs = $DataCount > 0 ? array_map('intval', array_column($DataResp->DataPrimaryUIDs, $DataResp->ModuleInfo->TablePrimaryUID)) : [];
                    $this->EndReturnData->Count = $DataCount;
                    $this->EndReturnData->Pagination = $this->CI->pagination->create_links();

                } else {
                    throw new Exception($DataResp->Message);
                }

            } else {
                throw new Exception('Module Information is Missing');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

    }

    public function getModulePageColumnDetails($ModuleId, $PageType, $Filter, $WhereInCondition, $Limit, $Offset, $UniqueFlag = 0) {

        $this->EndReturnData = new stdClass();
		try {

            if($ModuleId > 0) {

                $this->CI->load->model('global_model');

                $ViewColumnWhere = '';
                $ViewColumnSort = '';
                if($PageType == 'MainPage') {
                    $ViewColumnWhere = 'IsMainPageRequired';
                    $ViewColumnSort = 'MainPageOrder';
                } else if($PageType == 'PrintPage') {
                    $ViewColumnWhere = 'IsPrintPreviewApplicable';
                    $ViewColumnSort = 'PrintPreviewOrder';
                } else if($PageType == 'CsvPage') {
                    $ViewColumnWhere = 'IsExportCsvApplicable';
                    $ViewColumnSort = 'ExportCsvOrder';
                } else if($PageType == 'ExcelPage') {
                    $ViewColumnWhere = 'IsExportExcelApplicable';
                    $ViewColumnSort = 'ExportExcelOrder';
                } else if($PageType == 'PdfPage') {
                    $ViewColumnWhere = 'IsExportPdfApplicable';
                    $ViewColumnSort = 'ExportPdfOrder';
                }

                // $DataInfo = $this->CI->global_model->getModuleViewColumnDetails(['ViewColmn.ModuleUID' => $ModuleId, $ViewColumnWhere => 1], true, [$ViewColumnSort => 'ASC']);
                
                $AllDataInfo = $this->CI->global_model->getModuleViewColumnDetails(['ViewColmn.ModuleUID' => $ModuleId]);
                $DataInfo = array_filter($AllDataInfo, function($item) use ($ViewColumnWhere) {
                    return isset($item->$ViewColumnWhere) && $item->$ViewColumnWhere == 1;
                });

                usort($DataInfo, function($a, $b) use ($ViewColumnSort) {
                    return $a->$ViewColumnSort <=> $b->$ViewColumnSort;
                });

                $ModuleInfo = $this->CI->global_model->getModuleDetails(['Modules.ModuleUID' => $ModuleId]);
                if(sizeof($DataInfo) > 0 && sizeof($ModuleInfo) > 0) {

                    $ModuleInfoData = $ModuleInfo[0];

                    $FilterFormat = new stdClass();
                    $FilterFormat->SearchFilter = [];
                    $FilterFormat->SearchDirectQuery = '';

                    $ModelName = $ModuleInfoData->ModelName;
                    $FltFuncName = $ModuleInfoData->FilterFunctionName;
                    if(!empty($ModelName) && !empty($FltFuncName)) {
                        $this->CI->load->model($ModuleInfoData->ModelName);
                        $FilterResp = $this->CI->$ModelName->$FltFuncName($ModuleInfoData, $Filter);
                        $FilterFormat->SearchFilter = $FilterResp->SearchFilter;
                        $FilterFormat->SearchDirectQuery = $FilterResp->SearchDirectQuery;
                    }

                    $Aggregates = [];
                    foreach ($DataInfo as $index => $column) {
                        if (!empty($column->AggregationMethod)) {
                            $Aggregates[$index][$column->AggregationMethod] = 0;
                        }
                    }

                    $WhereInArrayData = [];
                    if(!empty($WhereInCondition)) {
                        if (array_key_exists('ExportIds', $WhereInCondition)) {
                            $WhereInArrayData[$ModuleInfoData->TableAliasName.'.'.$ModuleInfoData->TablePrimaryUID] = $WhereInCondition['ExportIds'];
                        }
                    }

                    $JoinData = $this->CI->global_model->getModuleViewJoinColumnDetails(['JoinColmn.MainModuleUID' => $ModuleId], true, ['JoinColmn.SortOrder' => 'ASC']);

                    $DataLists = $this->CI->global_model->getModuleReportDetails($ModuleInfoData, $DataInfo, $JoinData, $FilterFormat->SearchFilter, $FilterFormat->SearchDirectQuery, 'DESC', $WhereInArrayData, $Limit, $Offset, 0);
                    if($UniqueFlag == 1) {
                        $DataPrimaryUIDs = $this->CI->global_model->getModuleReportDetails($ModuleInfoData, $DataInfo, $JoinData, $FilterFormat->SearchFilter, $FilterFormat->SearchDirectQuery, 'DESC', $WhereInArrayData, $Limit, $Offset, 1);
                    }

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->AllViewColumns = $AllDataInfo;
                    $this->EndReturnData->ModuleInfo = $ModuleInfoData;
                    $this->EndReturnData->ViewColumns = $DataInfo;
                    $this->EndReturnData->DataLists = $DataLists;
                    $this->EndReturnData->Aggregates = $Aggregates;
                    if($UniqueFlag == 1) {
                        $this->EndReturnData->DataPrimaryUIDs = $DataPrimaryUIDs;
                    }

                } else {
                    throw new Exception('Column Information is Missing.!');
                }

            } else {
                throw new Exception('Oops! Missing Module Information.');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

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
            $AmountField = array_column($ViewColumns, 'IsAmountField');
            $DateField = array_column($ViewColumns, 'IsDateField');
            $AggregationMethods = array_column($ViewColumns, 'AggregationMethod');
            $FinalAggregates = $Aggregates;

            fputcsv($file, $Columns);

            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $Ind => $row) {

                    if (isset($row->TablePrimaryUID)) {
                        unset($row->TablePrimaryUID);
                    }

                    $colIndex = 0;

                    $colDataVal = [];
                    foreach (get_object_vars($row) as $key => $value) {

                        if (!empty($AggregationMethods[$colIndex])) {
                            $method = strtoupper($AggregationMethods[$colIndex]);
                            switch ($method) {
                                case 'SUM':
                                    $FinalAggregates[$colIndex]['SUM'] += $value;
                                    break;
                                case 'COUNT':
                                    $FinalAggregates[$colIndex]['COUNT']++;
                                    break;
                                case 'AVG':
                                    if (!isset($FinalAggregates[$colIndex]['_sum'])) {
                                        $FinalAggregates[$colIndex]['_sum'] = 0;
                                        $FinalAggregates[$colIndex]['_count'] = 0;
                                    }
                                    $FinalAggregates[$colIndex]['_sum'] += $value;
                                    $FinalAggregates[$colIndex]['_count']++;
                                    break;
                            }
                        }

                        $value = $value ?? '';
                        if($AmountField[$colIndex] == 1) {
                            if($value) {
                                $value = $this->CI->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($value);
                            }
                        } else if($DateField[$colIndex] == 1) {
                            $value = changeTimeZomeDateFormat($value, $this->CI->pageData['JwtData']->User->Timezone, 2);
                        } else if($value) {
                            $value = htmlspecialchars($value);
                        }

                        $colDataVal[] = $value;

                        $colIndex++;
                    }

                    fputcsv($file, $colDataVal);
                }

                // Summary Section
                $Summary = [];
                foreach ($AggregationMethods as $colIndex => $method):
                    $output = '';
                    $method = $method ? strtoupper(trim($method)) : '';
                    // Only output if we have aggregation for this column
                    if ($method === 'SUM' && isset($FinalAggregates[$colIndex]['SUM'])) {
                        $output = $AmountField[$colIndex] == 1 ? ($FinalAggregates[$colIndex]['SUM'] ?  smartDecimal($FinalAggregates[$colIndex]['SUM']) : 0) : $FinalAggregates[$colIndex]['SUM'];
                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$colIndex]['COUNT'])) {
                        $output = $FinalAggregates[$colIndex]['COUNT'];
                    } elseif ($method === 'AVG' && isset($FinalAggregates[$colIndex]['_sum'], $FinalAggregates[$colIndex]['_count']) && $FinalAggregates[$colIndex]['_count'] > 0) {
                        $avg = $FinalAggregates[$colIndex]['_sum'] / $FinalAggregates[$colIndex]['_count'];
                        $output = $AmountField[$colIndex] == 1 ? ($avg ?  smartDecimal($avg) : 0) : $avg;
                    }
                    $Summary[] = $output;
                endforeach;

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
            $AmountField = array_column($ViewColumns, 'IsAmountField');
            $DateField = array_column($ViewColumns, 'IsDateField');
            $AggregationMethods = array_column($ViewColumns, 'AggregationMethod');
            $FinalAggregates = $Aggregates;

            // Write headers
            $sheet->fromArray($Columns, null, 'A1');

            // Write data rows
            $rowNum = 2;

            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $Ind => $row) {

                    if (isset($row->TablePrimaryUID)) {
                        unset($row->TablePrimaryUID);
                    }

                    $colIndex = 0;

                    $colDataVal = [];
                    foreach (get_object_vars($row) as $key => $value) {

                        if (!empty($AggregationMethods[$colIndex])) {
                            $method = strtoupper($AggregationMethods[$colIndex]);
                            switch ($method) {
                                case 'SUM':
                                    $FinalAggregates[$colIndex]['SUM'] += $value;
                                    break;
                                case 'COUNT':
                                    $FinalAggregates[$colIndex]['COUNT']++;
                                    break;
                                case 'AVG':
                                    if (!isset($FinalAggregates[$colIndex]['_sum'])) {
                                        $FinalAggregates[$colIndex]['_sum'] = 0;
                                        $FinalAggregates[$colIndex]['_count'] = 0;
                                    }
                                    $FinalAggregates[$colIndex]['_sum'] += $value;
                                    $FinalAggregates[$colIndex]['_count']++;
                                    break;
                            }
                        }

                        $value = $value ?? '';
                        if($AmountField[$colIndex] == 1) {
                            if($value) {
                                $value = $this->CI->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($value);
                            }
                        } else if($DateField[$colIndex] == 1) {
                            $value = changeTimeZomeDateFormat($value, $this->CI->pageData['JwtData']->User->Timezone, 2);
                        } else if($value) {
                            $value = htmlspecialchars($value);
                        }

                        $colDataVal[] = $value;

                        $colIndex++;
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
                $Summary = [];
                $summaryColIndex = 0;
                foreach ($AggregationMethods as $colIndex => $method):
                    $output = '';
                    $method = $method ? strtoupper(trim($method)) : '';
                    // Only output if we have aggregation for this column
                    if ($method === 'SUM' && isset($FinalAggregates[$colIndex]['SUM'])) {
                        $output = $AmountField[$colIndex] == 1 ? ($FinalAggregates[$colIndex]['SUM'] ?  smartDecimal($FinalAggregates[$colIndex]['SUM']) : 0) : $FinalAggregates[$colIndex]['SUM'];
                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$colIndex]['COUNT'])) {
                        $output = $FinalAggregates[$colIndex]['COUNT'];
                    } elseif ($method === 'AVG' && isset($FinalAggregates[$colIndex]['_sum'], $FinalAggregates[$colIndex]['_count']) && $FinalAggregates[$colIndex]['_count'] > 0) {
                        $avg = $FinalAggregates[$colIndex]['_sum'] / $FinalAggregates[$colIndex]['_count'];
                        $output = $AmountField[$colIndex] == 1 ? ($avg ?  smartDecimal($avg) : 0) : $avg;
                    }
                    if ($output !== '') {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                        $sheet->setCellValue($columnLetter . $rowNum, $output);
                    }
                    $summaryColIndex++;
                endforeach;
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

            // Set sheet name
            $sheet->setTitle($SheetName);

            $Columns = array_column($ViewColumns, 'DisplayName');
            $AmountField = array_column($ViewColumns, 'IsAmountField');
            $DateField = array_column($ViewColumns, 'IsDateField');
            $AggregationMethods = array_column($ViewColumns, 'AggregationMethod');
            $FinalAggregates = $Aggregates;

            // Write headers
            $sheet->fromArray($Columns, null, 'A1');

            // Write data rows
            $rowNum = 2;

            if (sizeof($DataValue) > 0) {
                foreach ($DataValue as $Ind => $row) {

                    if (isset($row->TablePrimaryUID)) {
                        unset($row->TablePrimaryUID);
                    }

                    $colIndex = 0;

                    $colDataVal = [];
                    foreach (get_object_vars($row) as $key => $value) {

                        if (!empty($AggregationMethods[$colIndex])) {
                            $method = strtoupper($AggregationMethods[$colIndex]);
                            switch ($method) {
                                case 'SUM':
                                    $FinalAggregates[$colIndex]['SUM'] += $value;
                                    break;
                                case 'COUNT':
                                    $FinalAggregates[$colIndex]['COUNT']++;
                                    break;
                                case 'AVG':
                                    if (!isset($FinalAggregates[$colIndex]['_sum'])) {
                                        $FinalAggregates[$colIndex]['_sum'] = 0;
                                        $FinalAggregates[$colIndex]['_count'] = 0;
                                    }
                                    $FinalAggregates[$colIndex]['_sum'] += $value;
                                    $FinalAggregates[$colIndex]['_count']++;
                                    break;
                            }
                        }

                        $value = $value ?? '';
                        if($AmountField[$colIndex] == 1) {
                            if($value) {
                                $value = $this->CI->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($value);
                            }
                        } else if($DateField[$colIndex] == 1) {
                            $value = changeTimeZomeDateFormat($value, $this->CI->pageData['JwtData']->User->Timezone, 2);
                        } else if($value) {
                            $value = htmlspecialchars($value);
                        }

                        $colDataVal[] = $value;

                        $colIndex++;
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
                $Summary = [];
                $summaryColIndex = 0;
                foreach ($AggregationMethods as $colIndex => $method):
                    $output = '';
                    $method = $method ? strtoupper(trim($method)) : '';
                    // Only output if we have aggregation for this column
                    if ($method === 'SUM' && isset($FinalAggregates[$colIndex]['SUM'])) {
                        $output = $AmountField[$colIndex] == 1 ? ($FinalAggregates[$colIndex]['SUM'] ?  smartDecimal($FinalAggregates[$colIndex]['SUM']) : 0) : $FinalAggregates[$colIndex]['SUM'];
                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$colIndex]['COUNT'])) {
                        $output = $FinalAggregates[$colIndex]['COUNT'];
                    } elseif ($method === 'AVG' && isset($FinalAggregates[$colIndex]['_sum'], $FinalAggregates[$colIndex]['_count']) && $FinalAggregates[$colIndex]['_count'] > 0) {
                        $avg = $FinalAggregates[$colIndex]['_sum'] / $FinalAggregates[$colIndex]['_count'];
                        $output = $AmountField[$colIndex] == 1 ? ($avg ?  smartDecimal($avg) : 0) : $avg;
                    }
                    if ($output !== '') {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                        $sheet->setCellValue($columnLetter . $rowNum, $output);
                    }
                    $summaryColIndex++;
                endforeach;
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

}