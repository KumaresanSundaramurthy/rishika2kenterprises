<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="d-flex mt-2 mt-md-0">
    <a href="javascript: void(0);" class="btn PageRefresh p-2 me-0" data-toggle="tooltip" data-bs-placement="top" title="Refresh Page"><i class="bx bx-refresh fs-4"></i></a>
    <a href="javascript: void(0);" id="btnPageSettings" class="btn p-2" data-toggle="tooltip" data-bs-placement="top" title="Page Column Settings"><i class="bx bx-cog fs-4"></i></a>
    <div class="position-relative me-2">
        <input type="text" class="form-control SearchDetails" name="SearchDetails" id="SearchDetails" placeholder="Search details..." data-toggle="tooltip" title="Please type at least 3 characters to search" />
        <i class="bx bx-x position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer d-none" id="clearSearch"></i>
    </div>
    <div class="btn-group" id="ActionsDD-Div">
        <button class="btn btn-label-secondary dropdown-toggle me-2" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="d-flex align-items-center gap-2">
                <i class="icon-base bx bx-slider-alt icon-xs"></i>
                <span class="d-none d-sm-inline-block"></span>
            </span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
            <li class="d-none" id="CloneOption">
                <a class="dropdown-item" href="javascript: void(0);" id="btnClone">
                    <i class="bx bx-duplicate me-1"></i> Clone
                </a>
            </li>
            <li class="d-none" id="DeleteOption">
                <a class="dropdown-item text-danger" href="javascript: void(0);" id="btnDelete">
                    <i class="bx bx-trash me-1"></i> Delete
                </a>
            </li>
            <li class="dropdown-submenu">
                <a class="dropdown-item" href="javascript: void(0);">
                    <i class="bx bx-export me-1"></i> Export
                </a>
                <ul class="dropdown-menu shadow" style="min-width:260px;font-size:.83rem;">
                    <li>
                        <a class="dropdown-item" href="javascript: void(0);" id="btnExportPrint">
                            <i class="bx bx-printer me-2 text-secondary"></i>Print<small class="text-muted ms-1">(Preview before printing)</small>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item" href="javascript: void(0);" id="btnExportCSV">
                            <i class="bx bx-file me-2 text-success"></i>CSV<small class="text-muted ms-1">(Comma Separated, opens in any spreadsheet)</small>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript: void(0);" id="btnExportExcel">
                            <i class="bx bxs-file-export me-2 text-success"></i>Excel<small class="text-muted ms-1">(Microsoft Excel .xlsx format)</small>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript: void(0);" id="btnExportPDF">
                            <i class="bx bxs-file-pdf me-2 text-danger"></i>PDF<small class="text-muted ms-1">(Fixed layout, best for sharing)</small>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <a href="<?php echo $redirectUrl; ?>" class="btn btn-primary px-3 <?php echo (isset($clsInfo)) ? $clsInfo : ''; ?>"><?php echo $addActionName; ?></a>
</div>