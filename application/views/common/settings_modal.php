<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="pageSettingsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Customize Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php $FormAttribute = array('id' => 'UpdatePageSettingsForm', 'name' => 'UpdatePageSettingsForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/updatePageSettings', $FormAttribute); ?>
            <div class="modal-body">
                <div class="table-responsive">
                    <input type="hidden" name="InPageAllColumns" id="InPageAllColumns" value="<?php echo implode(',', array_column($ColumnDetails, 'ViewDataUID')); ?>" />
                    <table class="table table-bordered align-middle text-center">
                        <thead>
                            <tr>
                                <th rowspan="2">Field</th>
                                <th rowspan="2">Main Page + Sorting</th>
                                <th colspan="4">Export + Sorting</th>
                            </tr>
                            <tr>
                                <th>Print</th>
                                <th>CSV</th>
                                <th>Excel</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ColumnDetails as $CmKey => $CmVal) { ?>
                                <tr>
                                    <td><?php echo $CmVal->DisplayName; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <input class="me-1" type="checkbox" name="MainPageFld[<?php echo $CmVal->ViewDataUID; ?>]" data-id="<?= $CmVal->ViewDataUID ?>" data-field="IsMainPageApplicable" <?php echo $CmVal->IsMainPageApplicable == 1 ? 'checked' : ''; ?> />
                                            <input type="text" class="form-control" name="MainPageFldSort[<?php echo $CmVal->ViewDataUID; ?>]" id="MainPageFldSort<?php echo $CmVal->ViewDataUID; ?>" min="1000" max="9999" placeholder="Enter Sort Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" onchange="validatePageSettingsMinValue(this);" maxLength="4" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $CmVal->MainPageOrder; ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <input class="me-1" type="checkbox" name="PrintPageFld[<?php echo $CmVal->ViewDataUID; ?>]" data-id="<?= $CmVal->ViewDataUID ?>" data-field="IsPrintPreviewApplicable" <?php echo $CmVal->IsPrintPreviewApplicable == 1 ? 'checked' : ''; ?> />
                                            <input type="text" class="form-control" name="PrintPageFldSort[<?php echo $CmVal->ViewDataUID; ?>]" id="PrintPageFldSort<?php echo $CmVal->ViewDataUID; ?>" min="1000" max="9999" placeholder="Enter Sort Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" onchange="validatePageSettingsMinValue(this);" maxLength="4" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $CmVal->PrintPreviewOrder; ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <input class="me-1" type="checkbox" name="ExpCsvFld[<?php echo $CmVal->ViewDataUID; ?>]" data-id="<?= $CmVal->ViewDataUID ?>" data-field="IsExportCsvApplicable" <?php echo $CmVal->IsExportCsvApplicable == 1 ? 'checked' : ''; ?> />
                                            <input type="text" class="form-control" name="ExpCsvFldSort[<?php echo $CmVal->ViewDataUID; ?>]" id="ExpCsvFldSort<?php echo $CmVal->ViewDataUID; ?>" min="1000" max="9999" placeholder="Enter Sort Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" onchange="validatePageSettingsMinValue(this);" maxLength="4" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $CmVal->ExportCsvOrder; ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <input class="me-1" type="checkbox" name="ExpXlFld[<?php echo $CmVal->ViewDataUID; ?>]" data-id="<?= $CmVal->ViewDataUID ?>" data-field="IsExportExcelApplicable" <?php echo $CmVal->IsExportExcelApplicable == 1 ? 'checked' : ''; ?> />
                                            <input type="text" class="form-control" name="ExpXlFldSort[<?php echo $CmVal->ViewDataUID; ?>]" id="ExpXlFldSort<?php echo $CmVal->ViewDataUID; ?>" min="1000" max="9999" placeholder="Enter Sort Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" onchange="validatePageSettingsMinValue(this);" maxLength="4" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $CmVal->ExportExcelOrder; ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <input class="me-1" type="checkbox" name="ExpPdfFld[<?php echo $CmVal->ViewDataUID; ?>]" data-id="<?= $CmVal->ViewDataUID ?>" data-field="IsExportPdfApplicable" <?php echo $CmVal->IsExportPdfApplicable == 1 ? 'checked' : ''; ?> />
                                            <input type="text" class="form-control" name="ExpPdfFldSort[<?php echo $CmVal->ViewDataUID; ?>]" id="ExpPdfFldSort<?php echo $CmVal->ViewDataUID; ?>" min="1000" max="9999" placeholder="Enter Sort Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" onchange="validatePageSettingsMinValue(this);" maxLength="4" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $CmVal->ExportPdfOrder; ?>" />
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="updatePageSettingsBtn">Update Settings</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>