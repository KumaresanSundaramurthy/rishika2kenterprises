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
                                            <?php if (sizeof($Columns) > 0) {
                                                foreach ($Columns as $Col) { ?>
                                                    <th><?php echo $Col; ?></th>
                                            <?php }
                                            } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($List) > 0) {
                                            foreach ($List as $List) { ?>
                                                <tr>
                                                    <td><?php echo $List->ProductUID; ?></td>
                                                    <td><?php echo $List->ItemName; ?></td>
                                                </tr>
                                        <?php }
                                        } ?>
                                    </tbody>
                                    <tfoot class="table-border-bottom-0">
                                        <tr>
                                            <th>Project</th>
                                            <th>Client</th>
                                            <th>Users</th>
                                            <th>Status</th>
                                            <th>Actions</th>
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