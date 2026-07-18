<?php
$_flashSuccess = $this->session->flashdata('trans_success');
if ($_flashSuccess): ?>
<script>$(function() { showToastNotification(<?php echo json_encode($_flashSuccess); ?>, 'success'); });</script>
<?php endif; ?>
