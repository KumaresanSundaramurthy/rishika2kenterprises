<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

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
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="javascript: void(0);" id="btnExportPrint">
                        <i class="bx bx-printer me-1"></i> Print
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript: void(0);" id="btnExportCSV">
                        <i class="bx bx-file me-1"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript: void(0);" id="btnExportExcel">
                        <i class="bx bxs-file-export me-1"></i> Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript: void(0);" id="btnExportPDF">
                        <i class="bx bxs-file-pdf me-1"></i> PDF
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>