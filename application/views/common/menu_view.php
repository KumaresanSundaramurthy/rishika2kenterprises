<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container">
    <!-- <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top"> -->
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">

            <ul class="nav navbar-nav navbar-right">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="javascript: void(0);" role="button" data-bs-toggle="dropdown" style="padding-bottom: 0 !important;">
                        <img src="/images/logo/avathar_user.png" alt="Avatar Logo" width="40px" height="35px" class="rounded-pill"> <?php echo strtoupper($JwtData->User->FirstName); ?> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" id="ChangePasswordBtn" href="javascript: void(0);">Change Password</a></li>
                        <li><a class="dropdown-item" href="/login/logout">Logout</a></li>
                    </ul>
                </li>                
            </ul>

            <div>
                <div class="collapse navbar-collapse">
                    <a class="navbar-brand" href="/dashboard"><i class="fas fa-house-user"></i></a>
                    <ul class="nav navbar-nav">

                        <?php if (sizeof($JwtData->UserMainModule) > 0) {
                            foreach ($JwtData->UserMainModule as $MMKey => $MMVal) { ?>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="javascript: void(0);" role="button" data-bs-toggle="dropdown"><?php echo $MMVal->MainMenuName; ?> <span class="caret"></span></a>
                                    <ul class="dropdown-menu">
                                        <?php if (sizeof($JwtData->UserSubModule) > 0) {
                                            $SubMenuData = filterByMainMenuUID($JwtData->UserSubModule, $MMVal->MainMenuUID);
                                            if (sizeof($SubMenuData) > 0) {
                                                foreach ($SubMenuData as $SMKey => $SMVal) { ?>

                                                    <li><a class="dropdown-item" href="/<?php echo $SMVal->ControllerName; ?>"><?php echo $SMVal->SubMenuName; ?></a></li>

                                        <?php }
                                            }
                                        } ?>
                                    </ul>
                                </li>

                        <?php }
                        } ?>

                    </ul>

                    <!-- <ul class="nav navbar-nav navbar-right" style="padding-right:25px;">
                        <li class="dropdown">
                            <input type="text" class="form-control" style="margin-top: 10px; width: 80px;" id='txtMenuEvalResult' disabled="yes">
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right" style="padding-right:25px;">
                        <li class="dropdown">
                            <input type="text" class="form-control" style="margin-top: 10px;" id='txtMenuEval' maxlength=100 placeholder="Calculator">
                        </li>
                    </ul>

                    <ul class="nav navbar-nav navbar-right" style="padding-right:25px;">
                        <li class="dropdown">
                        <a  tabindex="0" href="<?php echo base_url();  ?>index.php/Sale_Controller"><span style="padding: 5px 10px;" class="label label-primary">SV</span></a>
                    </li>
                    <li class="dropdown" >
                        <a tabindex="0" href="<?php echo base_url();  ?>index.php/Purchase_Controller"><span style="padding: 5px 10px;" class="label label-success">PV</span></a>
                    </li>
                        <li class="dropdown">
                            <a tabindex="0" href="<?php echo base_url();  ?>index.php/PaymentReceipt_Controller"><span style="padding: 5px 10px;" class="label label-success">Payment Receipt</span></a>
                        </li>
                        <li class="dropdown">
                            <a tabindex="0" href="<?php echo base_url();  ?>index.php/RptDues_Controller"><span style="padding: 5px 10px;" class="label label-default">Dues</span></a>
                        </li>
                        <li class="dropdown">
                            <a tabindex="0" href="<?php echo base_url();  ?>index.php/DailyCash_Controller"><span style="padding: 5px 10px;" class="label label-primary">DailyCash</span></a>
                        </li>
                    </ul> -->

                    <ul class="nav navbar-nav navbar-right" style="padding-right:5px;">
                        <li class="dropdown">
                            <a href="javascript: void(0);"><span style="padding: 5px 10px;" id="spanNotificationAsli" class="label label-danger glyphicon glyphicon-bell" onclick="notificationPadhLiya();"> 0</span></a>
                        </li>
                    </ul>

                </div>
            </div>
            
        </div>
    </nav>
</div>