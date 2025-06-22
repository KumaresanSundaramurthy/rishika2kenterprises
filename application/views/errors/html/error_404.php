<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!DOCTYPE html>
<html lang="en" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="/assets/" data-template="vertical-menu-template-free">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
	<title>R2K Enterprises :: 404 Page Not Found</title>
	<meta name="description" content="" />

	<!-- Favicon -->
	<link rel="shortcut icon" href="/images/logo/favicon_io/favicon-32x32.png" type="image/png">

	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link
		href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
		rel="stylesheet" />

	<!-- Icons. Uncomment required icon fonts -->
	<link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css" />

	<!-- Core CSS -->
	<link rel="stylesheet" href="/assets/vendor/libs/pickr/pickr-themes.css">
	<link rel="stylesheet" href="/assets/vendor/css/core.css" class="template-customizer-core-css" />
	<link rel="stylesheet" href="/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
	<link rel="stylesheet" href="/assets/css/demo.css" />

	<!-- Vendors CSS -->
	<link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

	<!-- Page CSS -->
	<!-- Page -->
	<link rel="stylesheet" href="/assets/vendor/css/pages/page-misc.css" />

	<!-- Helpers -->
	<script src="/assets/vendor/js/helpers.js"></script>

	<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
	<script src="/assets/vendor/js/template-customizer.js"></script>

	<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
	<script src="/assets/js/config.js"></script>

</head>

</head>

<body>
	<!-- <div id="container">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message; ?>
	</div> -->

	<!-- Error -->
	<div class="container-xxl container-p-y">
		<div class="misc-wrapper">
			<h2 class="mb-2 mx-2">Page Not Found :(</h2>
			<p class="mb-4 mx-2">Oops! 😖 <?php echo $message; ?></p>
			<a href="/dashboard" class="btn btn-primary">Back to Dashboard</a>
			<div class="mt-3">
				<img src="../assets/img/illustrations/page-misc-error-light.png" alt="page-misc-error-light" width="500" class="img-fluid" data-app-dark-img="illustrations/page-misc-error-dark.png" data-app-light-img="illustrations/page-misc-error-light.png" />
			</div>
		</div>
	</div>
	<!-- /Error -->

	<!-- Core JS -->
	<!-- build:js assets/vendor/js/core.js -->
	<script type="text/javascript" src="/assets/vendor/libs/jquery/jquery.js"></script>
	<script type="text/javascript" src="/assets/vendor/libs/popper/popper.js"></script>
	<script type="text/javascript" src="/assets/vendor/js/bootstrap.js"></script>
	<script type="text/javascript" src="/assets/vendor/libs/pickr/pickr.js"></script>
	<script type="text/javascript" src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

	<script src="/assets/vendor/js/menu.js"></script>
	<!-- endbuild -->

	<!-- Main JS -->
	<script src="/assets/js/main.js"></script>
	<script>
		$(function() {
			'use strict'

			$('#template-customizer').addClass('d-none');

		});
	</script>

</body>

</html>