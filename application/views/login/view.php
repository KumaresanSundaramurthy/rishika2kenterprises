<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('login/header'); ?>

<link href="//fonts.googleapis.com/css?family=Satisfy|Dosis" rel="stylesheet">
<link rel="stylesheet" href="<?php echo base_url();  ?>css/loginstyle.css">

<!-- Pen Title-->
<div class="pen-title">
    <h3 style="font-family: 'Dosis';font-size: 42px; color: #1bcaff; "><?php echo getSiteConfiguration()->ShortName; ?></h3>
    <h4 style="font-family: 'Satisfy';font-size: 39px; color: #666666;">Billing System</h4>
</div>

<!-- Form Module-->
<div class="module form-module">
    <div class="toggle"></div>

    <div class="form">
        <h2>Login Panel</h2>

        <?php $FormAttribute = array('id' => 'doLoginForm', 'name' => 'doLoginForm', 'class' => '', 'autocomplete' => 'off');
        echo form_open('login/doLoginForm', $FormAttribute); ?>

        <?php $this->load->view('login/alerts'); ?>

        <input type="text" id="UserName" name="UserName" required maxlength="20" placeholder="User Name" autocomplete="off" autofocus />
        <input type="password" id="UserPassword" name="UserPassword" required maxlength="20" autocomplete="off" placeholder="Password" />
        <input type="submit" style="margin-top: 30px;margin-bottom: -10px; background-color:rgb(40, 0, 0); color: #ffffff; font-size: 16px;" />

        <?php echo form_close(); ?>

    </div>

    <div class="cta"><a href="#" style="color:grey;">Developed by <span style="color:black;"><?php echo getSiteConfiguration()->ShortName; ?></span></a></div>
</div>

<?php $this->load->view('login/footer'); ?>

<script src="<?= base_url('js/login.js'); ?>"></script>

<script>
$(function() {
    'use strict'
    
    

});
</script>