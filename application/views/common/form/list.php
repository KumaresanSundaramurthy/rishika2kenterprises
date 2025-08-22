<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php foreach ($ViewColumns as $Key => $column) {

    $fieldName = $column->DisplayName;
    $value = $list->$fieldName ?? '';
    
    // Formatting Amount
    if ($column->IsAmountField) {
        $value = smartDecimal($value);
        if($column->CurrencySymbol == 1) {
            $value = $JwtData->GenSettings->CurrenySymbol .' '. $value;
        }
    }

    // Formatting Date
    if ($column->IsDateField && !empty($value)) {
        if($column->MPDateFormatType == 1) {
            $value = changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 1);
        } else if($column->MPDateFormatType == 2) {
            $LastUpdatedBy = 'Last Updated By';
            $value = '<div>'.changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 2).'</div>';
            $value .= '<div class="text-muted" style="font-size: 0.75rem;">by '.$list->$LastUpdatedBy.'</div>';
        } else {
            $value = changeTimeZomeDateFormat($value, $JwtData->User->Timezone, 1);
        }
    }

    if($Key == 1 && $column->MainPageImageDisplay == 1) { ?>

        <td <?php echo $column->MainPageDataAddon; ?>>
        <?php if(isset($list->Image) && $list->Image) { ?>
            <div class="d-flex align-items-center">
                <div class="avatar-wrapper me-3 rounded-2 bg-label-secondary">
                    <div class="avatar"><img src="<?php // echo getenv('CDN_URL').$list->Image ?? ''; 
                    echo $list->Image ?? ''; ?>" alt="Image" class="rounded"></div>
                </div>
                <div class="d-flex flex-column justify-content-center">
                    <span class="text-heading text-wrap fw-medium"><?php echo $value; ?></span>
                </div>
            </div>
        <?php } else {
            echo $value;
            } ?>
        </td>

<?php } else { ?>

        <td <?php echo $column->MainPageDataAddon; ?>><?php echo $value; ?></td>

<?php } } ?>