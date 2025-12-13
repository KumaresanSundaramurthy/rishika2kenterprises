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
    $AggregationMethods = array_column($ViewColumns, 'AggregationMethod');
    $FinalAggregates = $Aggregates;

    ?>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <div class="layout-page">

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="card">
                            <h5 class="card-header"><?php echo $previewName; ?></h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <?php foreach ($Columns as $colName): ?>
                                            <th><?php echo $colName; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($List)) {
                                        foreach ($List as $row) { ?>
                                        <tr>
                                        <?php
                                            foreach ($ViewColumns as $column) {
                                                $dbField = $column->DbFieldName;
                                                $method  = !empty($column->AggregationMethod) ? strtoupper($column->AggregationMethod) : '';
                                                $value   = $row->{$column->DisplayName} ?? null;

                                                if ($method) {
                                                    $numericValue = is_numeric($value) ? (float)$value : 0;
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
                                            }
                                            
                                            $getData = format_disp_allcolumns('preview', $ViewColumns, $row, $this->pageData['JwtData'], $this->pageData['JwtData']->GenSettings);
                                            if (!empty($getData) && is_array($getData)) {
                                                echo implode('', $getData);
                                            } ?>
                                        </tr>
                                    <?php } } ?>
                                    </tbody>
                                    <tfoot class="table-border-bottom-0">
                                        <tr>
                                            <?php foreach ($ViewColumns as $column) { ?>
                                            <td>
                                            <?php
                                                $dbField = $column->DbFieldName;
                                                $method  = !empty($column->AggregationMethod) ? strtoupper(trim($column->AggregationMethod)) : '';
                                                $output  = '';

                                                if ($method === 'SUM' && isset($FinalAggregates[$dbField]['SUM'])) {
                                                    $output = $column->IsAmountField == 1
                                                        ? ($FinalAggregates[$dbField]['SUM']
                                                        ? $this->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($FinalAggregates[$dbField]['SUM'])
                                                        : 0)
                                                        : $FinalAggregates[$dbField]['SUM'];

                                                } elseif ($method === 'COUNT' && isset($FinalAggregates[$dbField]['COUNT'])) {
                                                    $output = $FinalAggregates[$dbField]['COUNT'];

                                                } elseif ($method === 'AVG' && isset($FinalAggregates[$dbField]['_sum'], $FinalAggregates[$dbField]['_count']) && $FinalAggregates[$dbField]['_count'] > 0) {
                                                    $avg = $FinalAggregates[$dbField]['_sum'] / $FinalAggregates[$dbField]['_count'];
                                                    $output = $column->IsAmountField == 1
                                                        ? ($avg ? $this->pageData['JwtData']->GenSettings->CurrenySymbol . smartDecimal($avg) : 0)
                                                        : $avg;
                                                }

                                                echo $output;
                                            ?>
                                            </td>
                                            <?php } ?>
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