<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('format_disp_allcolumns')) {
    function format_disp_allcolumns($type, $columns, $list, $JwtData, $GenSettings) {
        $formattedValues = [];
        foreach ($columns as $colKey => $column) {
            $formattedValues[] = format_disp_column_value($type, $column, $list, $JwtData, $GenSettings, $colKey);
        }
        return $formattedValues;
    }
}

if (!function_exists('format_disp_column_value')) {
    function format_disp_column_value($type, $column, $list, $JwtData, $GenSettings, $Key = null) {

        $fieldName = $column->DisplayName;
        $value     = $list->$fieldName ?? '';

        if ($column->IsAmountField) {
            $value = smartDecimal($value);
            if ($column->CurrencySymbol == 1 && $type !== 'excel') {
                $value = $GenSettings->CurrenySymbol . ' ' . $value;
            }
        }

        if ($column->IsDateField && !empty($value)) {
            switch ($column->MPDateFormatType) {
                case 1:
                    $value = changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 1);
                    break;
                case 2:
                    if ($type === 'html' || $type === 'preview') {
                        $lastUpdatedBy = $list->{'Last Updated By'} ?? '';
                        $value  = '<div>'.changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 2).'</div>';
                        $value .= '<div class="text-muted" style="font-size: 0.75rem;">by '.$lastUpdatedBy.'</div>';
                    } else {
                        $value = changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 2);
                    }
                    break;
                default:
                    $value = changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 1);
            }
        }

        if ($type === 'value' || $type === 'excel') {
            return $value;
        }
        
        if ($Key == 1 && $column->MainPageImageDisplay == 1) {
            if (isset($list->Image) && $list->Image) {
                return '<td '.($type != 'preview') ? $column->MainPageDataAddon : ''.'>
                            <div class="d-flex align-items-center">
                                <div class="avatar-wrapper me-3 rounded-2 bg-label-secondary">
                                    <div class="avatar"><img src="'.($list->Image ?? '').'" alt="Image" class="rounded"></div>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <span class="text-heading text-wrap fw-medium">'.$value.'</span>
                                </div>
                            </div>
                        </td>';
            } else {
                return '<td '.($type != 'preview') ? $column->MainPageDataAddon : ''.'>'.$value.'</td>';
            }
        }
        
        return '<td '.(($type != 'preview') ? $column->MainPageDataAddon : '').'>'.$value.'</td>';

    }
}