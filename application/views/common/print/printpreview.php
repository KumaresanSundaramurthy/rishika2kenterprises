<html>

<head>
    <title><?php echo getSiteConfiguration()->ShortName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/images/logo/favicon_io/favicon-32x32.png" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css" />

    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
</head>

<body>
    <?php

    $Columns = array_column($ViewColumns, 'DisplayName');
    $AmountField = array_column($ViewColumns, 'IsAmountField');
    $DateField = array_column($ViewColumns, 'IsDateField');
    $AggregationMethods = array_column($ViewColumns, 'AggregationMethod');
    $FinalAggregates = $Aggregates;

    ?>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <div class="layout-page">

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="card">
                            <h5 class="card-header">Product Details</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <?php
                                            if (sizeof($Columns) > 0) {
                                                foreach ($Columns as $Col) { ?>
                                                    <th><?php echo $Col; ?></th>
                                            <?php }
                                            } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($List) > 0) {
                                            foreach ($List as $Ind => $row) {
                                                if (isset($row->TablePrimaryUID)) {
                                                    unset($row->TablePrimaryUID);
                                                } ?>
                                                <tr>
                                                    <?php
                                                    $colIndex = 0;
                                                    foreach (get_object_vars($row) as $key => $value) {

                                                        if (!empty($AggregationMethods[$colIndex])) {
                                                            $method = strtoupper($AggregationMethods[$colIndex]);
                                                            switch ($method) {
                                                                case 'SUM':
                                                                    $FinalAggregates[$colIndex]['SUM'] += is_numeric($value) ? (float)$value : 0;
                                                                    break;
                                                                case 'COUNT':
                                                                    $FinalAggregates[$colIndex]['COUNT']++;
                                                                    break;
                                                                case 'AVG':
                                                                    if (!isset($FinalAggregates[$colIndex]['_sum'])) {
                                                                        $FinalAggregates[$colIndex]['_sum'] = 0;
                                                                        $FinalAggregates[$colIndex]['_count'] = 0;
                                                                    }
                                                                    $FinalAggregates[$colIndex]['_sum'] += (float)$value;
                                                                    $FinalAggregates[$colIndex]['_count']++;
                                                                    break;
                                                            }
                                                        }

                                                    ?>
                                                        <td>
                                                            <?php 
                                                                $value = $value ?? '';
                                                                if($AmountField[$colIndex] == 1) {
                                                                    if($value) {
                                                                        $value = $this->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($value);
                                                                    }
                                                                } else if($DateField[$colIndex] == 1) {
                                                                    $value = changeTimeZomeDateFormat($value, $this->pageData['JwtData']->User->Timezone);
                                                                } else if($value) {
                                                                    $value = htmlspecialchars($value);
                                                                }
                                                            ?>
                                                            <?php echo $value; ?>
                                                        </td>
                                                    <?php $colIndex++;
                                                    } ?>
                                                </tr>
                                        <?php }
                                        } ?>
                                    </tbody>
                                    <tfoot class="table-border-bottom-0">
                                        <tr>
                                            <?php
                                            foreach ($AggregationMethods as $colIndex => $method): ?>
                                                <td>
                                                    <?php
                                                    $output = '';
                                                    $method = $method ? strtoupper(trim($method)) : '';

                                                    // Only output if we have aggregation for this column
                                                    if ($method === 'SUM' && isset($FinalAggregates[$colIndex]['SUM'])) {
                                                        $output = $AmountField[$colIndex] == 1 ? ($FinalAggregates[$colIndex]['SUM'] ?  $this->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($FinalAggregates[$colIndex]['SUM']) : 0) : $FinalAggregates[$colIndex]['SUM'];
                                                    } elseif ($method === 'COUNT' && isset($FinalAggregates[$colIndex]['COUNT'])) {
                                                        $output = $FinalAggregates[$colIndex]['COUNT'];
                                                    } elseif ($method === 'AVG' && isset($FinalAggregates[$colIndex]['_sum'], $FinalAggregates[$colIndex]['_count']) && $FinalAggregates[$colIndex]['_count'] > 0) {
                                                        $avg = $FinalAggregates[$colIndex]['_sum'] / $FinalAggregates[$colIndex]['_count'];
                                                        $output = $AmountField[$colIndex] == 1 ? ($avg ?  $this->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($avg) : 0) : $avg;
                                                    }

                                                    echo $output;
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>