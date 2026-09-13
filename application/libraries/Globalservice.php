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
        $GetRedisDetails = $this->CI->redisservice->getCache($this->CI->pageData['JwtUserKey']);
        if ($GetRedisDetails->Error === FALSE) {
            $this->CI->load->model('user_model');
            $UserData = $this->CI->user_model->getUserByUserInfo(['User.UserUID' => $this->CI->pageData['JwtData']->User->UserUID]);

            if ($UserData->Error === FALSE && count($UserData->Data) === 1) {
                $this->CI->load->model('login_model');
                $jwtPayload = $this->CI->login_model->formatJWTPayload($UserData->Data[0]);

                if ($jwtPayload->Error === FALSE) {
                    // Preserve the session token so single-session enforcement is not broken by a cache refresh
                    $existingToken = $GetRedisDetails->Value->User->SessionToken ?? null;
                    if ($existingToken) {
                        $jwtPayload->JWTData['User']['SessionToken'] = $existingToken;
                    }
                    $this->CI->redisservice->setCache($GetRedisDetails->Key, $jwtPayload->JWTData, $GetRedisDetails->TTL);
                }
            }
        }
    }

    public function renderSubMenu($ControllerName, $allSubMenus, $parentUID = null, $currentUrlPath = null) {

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

            $iconHtml = !empty($subMenu->SubMenuIcon)
                ? '<i class="menu-icon tf-icons ' . htmlspecialchars($subMenu->SubMenuIcon) . '"></i>'
                : '';

            if (!empty($subMenu->IsParent)) {
                // Group item — check if any descendant is active to open the group
                $hasActiveChild = $this->_hasActiveDescendant($allSubMenus, $subMenu->SubMenuUID, $ControllerName, $currentUrlPath);
                $openClass = $hasActiveChild ? ' open active' : '';
                echo '<li class="menu-item' . $openClass . '">';
                echo '<a href="javascript:void(0);" class="menu-link menu-toggle">';
                echo $iconHtml;
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '<ul class="menu-sub">';
                $this->renderSubMenu($ControllerName, $allSubMenus, $subMenu->SubMenuUID, $currentUrlPath);
                echo '</ul>';
                echo '</li>';
            } else {
                $urlPath = '/' . ltrim($subMenu->UrlPath ?? $subMenu->ControllerName, '/');
                if ($currentUrlPath !== null) {
                    $itemPath = strtolower(ltrim($subMenu->UrlPath ?? $subMenu->ControllerName, '/'));
                    $isActive = ($itemPath === strtolower(ltrim($currentUrlPath, '/')));
                } else {
                    $isActive = (strtolower($ControllerName) == strtolower($subMenu->ControllerName));
                }
                $activeClass = $isActive ? 'active' : '';
                $href = $isActive ? 'javascript: void(0);' : htmlspecialchars($urlPath);
                echo '<li class="menu-item ' . $activeClass . '">';
                echo '<a href="' . $href . '" class="menu-link">';
                echo $iconHtml;
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '</li>';
            }
        }

    }

    public function hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath = null) {
        return $this->_hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath);
    }

    private function _hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath = null) {
        foreach ($allSubMenus as $sm) {
            if ($sm->ParentSubMenuUID != $parentUID) continue;
            if (!empty($sm->IsParent)) {
                if ($this->_hasActiveDescendant($allSubMenus, $sm->SubMenuUID, $ControllerName, $currentUrlPath)) return true;
            } else {
                if ($currentUrlPath !== null) {
                    $itemPath = strtolower(ltrim($sm->UrlPath ?? $sm->ControllerName, '/'));
                    if ($itemPath === strtolower(ltrim($currentUrlPath, '/'))) return true;
                } else {
                    if (strtolower($ControllerName) == strtolower($sm->ControllerName)) return true;
                }
            }
        }
        return false;
    }

    public function buildPagePaginationHtml($pageUrl, $totalCount, $pageNo, $limit) {

        $config['base_url']        = $pageUrl;
        $config['first_url']       = rtrim($pageUrl, '/') . '/1';
        $config['use_page_numbers'] = TRUE;
        $config['total_rows']      = $totalCount;
        $config['per_page']        = $limit;
        $config['cur_page']        = (int) $pageNo;
        $config['result_count']    = pageResultCount($pageNo, $limit, $totalCount);

        $this->CI->load->library('pagination');
        $this->CI->pagination->initialize($config);

        return $this->CI->pagination->create_links();

        // if ($totalCount <= 0 || $limit <= 0) return '';
        // $totalPages = (int) ceil($totalCount / $limit);
        // $from = (($pageNo - 1) * $limit) + 1;
        // $to   = min($pageNo * $limit, $totalCount);

        // $html = '<div class="col-12 col-sm-6 d-flex align-items-center text-muted small">'
        //       . 'Showing <strong class="mx-1">' . $from . '</strong> - <strong class="mx-1">' . $to . '</strong>'
        //       . ' of <strong class="mx-1">' . $totalCount . '</strong></div>'
        //       . '<div class="col-12 col-sm-6 d-flex justify-content-sm-end mt-2 mt-sm-0">';

        // if ($totalPages > 1) {
        //     $html .= '<nav><ul class="pagination pagination-sm mb-0">';
        //     $html .= '<li class="page-item' . ($pageNo <= 1 ? ' disabled' : '') . '">'
        //            .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . max(1, $pageNo - 1) . '">&#8249;</a>'
        //            . '</li>';

        //     $start = max(1, $pageNo - 2);
        //     $end   = min($totalPages, $start + 4);
        //     $start = max(1, $end - 4);
        //     for ($p = $start; $p <= $end; $p++) {
        //         $html .= '<li class="page-item' . ($p === $pageNo ? ' active' : '') . '">'
        //                .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . $p . '">' . $p . '</a>'
        //                . '</li>';
        //     }

        //     $html .= '<li class="page-item' . ($pageNo >= $totalPages ? ' disabled' : '') . '">'
        //            .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . min($totalPages, $pageNo + 1) . '">&#8250;</a>'
        //            . '</li>';
        //     $html .= '</ul></nav>';
        // }
        // $html .= '</div>';
        // return $html;

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
            notifyError('Globalservice::exportCSV', $e);
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
            notifyError('Globalservice::exportExcel', $e);
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
            notifyError('Globalservice::exportPdf', $e);
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
            notifyError('Globalservice::fileUploadService', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		return $this->EndReturnData;

    }

    public function softDeleteBankRecords($recordIds, $moduleName, $tableName, $pkField) {

        if (empty($recordIds)) return;

        $recordIds   = (array) $recordIds;
        $currentUser = $this->CI->pageData['JwtData']->User->UserUID ?? null;

        $updArray = [];
        foreach ($recordIds as $rid) {
            $updArray[] = [
                $pkField    => (int) $rid,
                'IsDeleted' => 1,
                'UpdatedBy' => $currentUser,
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

    public function saveBankDetails($entityUID, $detailsJson, $moduleName, $tableName, $extraFields = [], $pkField = '') {

        if (!$detailsJson) return;

        $details = is_array($detailsJson) ? $detailsJson : json_decode($detailsJson, true);
        if (!is_array($details) || count($details) === 0) return;

        $insertBatch = [];
        $updateBatch = [];

        $currentUser = $this->CI->pageData['JwtData']->User->UserUID ?? null;

        foreach ($details as $record) {
            $id = $record['id'] ?? null;

            $dataArray = array_merge([
                rtrim($moduleName, 's') . 'UID' => $entityUID,
                'Type'                    => $record['type'] ?? NULL,
                'BankAccountNumber'       => getPostValue($record, 'accNumber') ?? NULL,
                'BankIFSC_Code'           => getPostValue($record, 'ifsc') ?? NULL,
                'BankBranchName'          => getPostValue($record, 'branch') ?? NULL,
                'BankAccountHolderName'   => getPostValue($record, 'holder') ?? NULL,
                'UPI_Id'                  => getPostValue($record, 'upiId') ?? NULL,
                'UpdatedBy'               => $currentUser,
            ], $extraFields);

            if (is_string($id) && strpos($id, 'New-') === 0) {
                $dataArray['CreatedBy'] = $currentUser;
                $insertBatch[] = $dataArray;
            } elseif (is_numeric($id) || (is_string($id) && ctype_digit($id))) {
                $updateBatch[] = [
                    'data'  => $dataArray,
                    'where' => [($pkField ?: $tableName . 'UID') => (int) $id],
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

        $updArray = [];
        foreach ($recordIds as $rid) {
            $updArray[] = [
                $pkField    => (int) $rid,
                'IsDeleted' => 1,
                'UpdatedBy' => $currentUser,
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
            'OrgUID'     => $this->CI->pageData['JwtData']->Org->OrgUID,
            'AddressType'=> $addressType,
            'Line1'      => $postData[$typePrefix.'AddrLine1'],
            'Line2'      => getPostValue($postData, $typePrefix.'AddrLine2') ?? NULL,
            'Pincode'    => $postData[$typePrefix.'AddrPincode'],
            'City'       => getPostValue($postData, $typePrefix.'AddrCity') ?? NULL,
            'CityText'   => getPostValue($postData, $typePrefix.'AddrCityText') ?? NULL,
            'State'      => getPostValue($postData, $typePrefix.'AddrState') ?? NULL,
            'StateText'  => getPostValue($postData, $typePrefix.'AddrStateText') ?? NULL,
            'UpdatedBy'  => $this->CI->pageData['JwtData']->User->UserUID,
        ];

        $addressUIDField = $typePrefix.'AddressUID';

        if (isset($postData[$addressUIDField]) && $postData[$addressUIDField] == 0) {
            $addressData['CreatedBy'] = $this->CI->pageData['JwtData']->User->UserUID;

            $resp = $this->CI->dbwrite_model->insertData($moduleName, $tableName, $addressData);
            if ($resp->Error) throw new Exception($resp->Message);

        } elseif (isset($postData[$addressUIDField]) && $postData[$addressUIDField] > 0) {
            $resp = $this->CI->dbwrite_model->updateData($moduleName, $tableName, $addressData, [$uidField => $postData[$addressUIDField]]);
            if ($resp->Error) throw new Exception($resp->Message);
        }

    }

    public function baseDeleteArrayDetails() {
        $deleteData = [
                'IsDeleted' => 1,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
            ];
        return $deleteData;
    }

    public function sendJsonResponse($data): void {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $json = json_encode(['Error' => true, 'Message' => 'Response encoding failed: ' . json_last_error_msg()]);
        }
        // Discard any stray output (BOM, whitespace) buffered from included PHP files
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo $json;
        exit;
    }

}