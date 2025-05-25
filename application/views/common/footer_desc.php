<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Footer -->
<div class="card" style="border-radius: 0;">
    <!-- <footer class="content-footer footer bg-footer-theme"> -->
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
        <div class="mb-2 mb-md-0">
            ©
            <script>
                document.write(new Date().getFullYear());
            </script>
            , made with ❤️ by <?php echo strtoupper(getSiteConfiguration()->ShortName); ?>
        </div>
    </div>
    <!-- </footer> -->
</div>
<!-- / Footer -->