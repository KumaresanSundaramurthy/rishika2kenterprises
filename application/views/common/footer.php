    

    <?php $this->load->view('common/common_form'); ?>

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script type="text/javascript" src="/assets/vendor/libs/jquery/jquery.js"></script>
    <script type="text/javascript" src="/assets/vendor/libs/popper/popper.js"></script>
    <script type="text/javascript" src="/assets/vendor/js/bootstrap.js"></script>
    <script type="text/javascript" src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script type="text/javascript" src="/assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="/assets/vendor/libs/quill/katex.js"></script>
    <script src="/assets/vendor/libs/quill/quill.js"></script>

    <!-- Select2 JS -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
    <script src="/assets/vendor/libs/select2/select2.js"></script>

    <script src="/assets/vendor/libs/dropzone/dropzone.js"></script>

    <!-- Main JS -->
    <script type="text/javascript" src="<?php echo _assetV('/assets/js/main.js'); ?>"></script>

    <script type="text/javascript" src="/assets/vendor/libs/flatpickr/flatpickr.js"></script>

    <!-- Vendors JS -->
    
    <script src="/assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    
    <script type="text/javascript" src="/bootstrap/js/jquery.blockUI.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <script type="text/javascript" src="<?php echo _assetV('/assets/js/services/upstash-service.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo _assetV('/js/common/categoryappend.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo _assetV('/js/common/dropdown_cache.js'); ?>"></script>
    <script>var R2K_STATS_DEFAULT_OPEN = <?php echo json_encode(isset($JwtData->GenSettings->StatsDefaultOpen) ? (bool)$JwtData->GenSettings->StatsDefaultOpen : true); ?>;</script>
    <script type="text/javascript" src="<?php echo _assetV('/js/common/default.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo _assetV('/js/transactions/customer_search.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo _assetV('/js/internet_monitor.js'); ?>"></script>

    <?php $this->load->view('common/footer_script'); ?>

</body>

</html>