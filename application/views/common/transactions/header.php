<?php defined('BASEPATH') or exit('No direct script access allowed'); ?><!doctype html>
<html lang="en" class="layout-compact layout-menu-fixed layout-menu-collapsed" data-layout="horizontal" data-topbar="dark" data-sidebar-size="lg" data-sidebar="light" data-sidebar-image="none" data-preloader="disable" dir="ltr" data-skin="default" data-assets-path="/assets/" data-template="vertical-menu-template" data-bs-theme="light">
<head>

    <meta charset="utf-8">
    <title><?php echo getSiteConfiguration()->ShortName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
<?php
    $_jwt_user  = isset($JwtData) ? ($JwtData->Org ?? null) : null;
    $_sc        = strtolower($_jwt_user->OrgShortCode ?? '');
    $_tk        = strtolower($_jwt_user->OrgToken     ?? '');
    $_env       = defined('ENVIRONMENT') ? ENVIRONMENT : 'production';
    $_envMap    = ['development' => 'dev', 'staging' => 'stg', 'production' => 'prod'];
    $_orgPrefix = ($_sc && $_tk) ? $_sc . ':' . $_tk . ':' . ($_envMap[$_env] ?? $_env) : '';
    unset($_jwt_user, $_sc, $_tk, $_env, $_envMap);
?>
    <meta name="upstash-url"    content="<?= htmlspecialchars(getenv('UPSTASH_REDIS_REST_URL')   ?: '') ?>">
    <meta name="upstash-token"  content="<?= htmlspecialchars(getenv('UPSTASH_REDIS_REST_TOKEN') ?: '') ?>">
    <meta name="app-org-prefix" content="<?= htmlspecialchars($_orgPrefix) ?>">

<?php unset($_orgPrefix); ?>

    <link rel="icon" href="/images/logo/favicon_io/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/images/logo/favicon_io/android-chrome-512x512-1.png" type="image/png">

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
    <link rel="stylesheet" href="/assets/vendor/css/transactions.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/flatpickr/flatpickr.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/sweetalert2/sweetalert2.css">

    <link rel="stylesheet" href="/assets/vendor/libs/quill/typography.css">
    <link rel="stylesheet" href="/assets/vendor/libs/quill/katex.css">
    <link rel="stylesheet" href="/assets/vendor/libs/quill/editor.css">

    <!-- Select2 CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
    <link rel="stylesheet" href="/assets/vendor/libs/select2/select2.css">

    <link rel="stylesheet" href="/assets/vendor/libs/dropzone/dropzone.css">

    <!-- Project core overrides (profile, select2, etc.) -->
    <link rel="stylesheet" href="/assets/css/core.css" />

    <!-- Transaction theme -->
    <link rel="stylesheet" href="/css/transactions-theme.css">

    <!-- Helpers -->
    <script src="/assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <script src="/assets/vendor/js/template-customizer.js"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="/assets/js/config.js"></script>
    
</head>
<body>
<?php
$_hideNavOnTransForm = (int)(isset($JwtData) ? ($JwtData->TransGenSettings->HideNavOnTransForm ?? 0) : 0);
// Only apply on create/edit form pages, not on view/detail pages.
if ($_hideNavOnTransForm) {
    $_ci     = &get_instance();
    $_method = strtolower($_ci->router->fetch_method());
    $_hideNavOnTransForm = (
        strpos($_method, 'create') === 0 ||
        strpos($_method, 'edit')   === 0 ||
        strpos($_method, 'add')    === 0 ||
        strpos($_method, 'update') === 0
    ) ? 1 : 0;
    unset($_ci, $_method);
}
?>
<?php if ($_hideNavOnTransForm): ?>
<style>
#layout-menu { display: none !important; }
.layout-page  { margin-left: 0 !important; padding-left: 0 !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var headerInfo = document.getElementById('transHeaderInfo');
    if (!headerInfo) return;
    var btn = document.createElement('button');
    btn.type      = 'button';
    btn.className = 'btn btn-outline-secondary btn-sm me-1';
    btn.style.cssText = 'display:inline-flex;align-items:center;gap:3px;flex-shrink:0;';
    btn.innerHTML = '<i class="bx bx-undo" style="font-size:1rem;"></i>Back';
    btn.onclick   = function () { history.back(); };
    headerInfo.insertBefore(btn, headerInfo.firstChild);
});
</script>
<?php endif; unset($_hideNavOnTransForm); ?>