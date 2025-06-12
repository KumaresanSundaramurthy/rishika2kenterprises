<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('login/header'); ?>

<div class="authentication-wrapper authentication-cover">

    <!-- <a href="javascript: void(0);" class="app-brand auth-cover-brand gap-2">
        <span class="app-brand-logo demo">
            <span class="text-primary">
                <img src="<?php // echo getenv('CDN_URL'); ?>/global/images/logo/favicon_io/favicon_32x32.png" alt="Logo" width="25" height="38">
            </span>
        </span>
        <span class="app-brand-text demo text-heading fw-bold"><?php // echo getSiteConfiguration()->ShortName; ?></span>
    </a> -->

    <div class="authentication-inner row m-0">
        
        <!-- /Left Text -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
            <div class="w-100 d-flex justify-content-center">
                <img src="/images/login_page.png" class="img-fluid" alt="Login image" width="700" data-app-dark-img="illustrations/boy-with-rocket-dark.png" data-app-light-img="illustrations/boy-with-rocket-light.png" style="visibility: visible;">
            </div>
        </div>
        <!-- /Left Text -->

        <!-- Login -->
        <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
            <div class="w-px-400 mx-auto mt-sm-12 mt-8">
                <h4 class="mb-1">Welcome to <?php echo getSiteConfiguration()->ShortName; ?>! 👋</h4>
                <p class="mb-6">Please sign-in to your account and start the adventure</p>

                <?php $FormAttribute = array('id' => 'doLoginForm', 'name' => 'doLoginForm', 'class' => 'mb-6 fv-plugins-bootstrap5 fv-plugins-framework', 'autocomplete' => 'off');
                    echo form_open('login/doLoginForm', $FormAttribute); ?>

                    <?php $this->load->view('login/alerts'); ?>

                    <div class="mb-6 form-control-validation fv-plugins-icon-container">
                        <label for="UserName" class="form-label">Email or Username</label>
                        <input type="text" class="form-control" id="UserName" name="UserName" placeholder="Enter your email or username">
                        <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                    </div>
                    <div class="form-password-toggle form-control-validation fv-plugins-icon-container">
                        <label class="form-label" for="UserPassword">Password</label>
                        <div class="input-group input-group-merge has-validation">
                            <input type="password" id="UserPassword" class="form-control" name="UserPassword" placeholder="············" aria-describedby="password">
                            <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                        </div>
                        <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                    </div>
                    <div class="my-7">
                        <div class="d-flex justify-content-between">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="remember-me">
                                <label class="form-check-label" for="remember-me">Remember Me</label>
                            </div>
                            <a href="javascript: void(0);">
                                <p class="mb-0">Forgot Password?</p>
                            </a>
                        </div>
                    </div>

                    <button class="btn btn-primary d-grid w-100">Sign in</button>
                    
                <?php echo form_close(); ?>

                <p class="text-center d-none">
                    <span>New on our platform?</span>
                    <a href="auth-register-cover.html">
                        <span>Create an account</span>
                    </a>
                </p>

                <div class="divider my-6 d-none">
                    <div class="divider-text">or</div>
                </div>

                <div class="d-flex justify-content-center d-none">
                    <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-facebook me-1_5">
                        <i class="icon-base bx bxl-facebook-circle icon-20px"></i>
                    </a>

                    <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-twitter me-1_5">
                        <i class="icon-base bx bxl-twitter icon-20px"></i>
                    </a>

                    <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-github me-1_5">
                        <i class="icon-base bx bxl-github icon-20px"></i>
                    </a>

                    <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-google-plus">
                        <i class="icon-base bx bxl-google icon-20px"></i>
                    </a>
                </div>

            </div>
        </div>
        <!-- /Login -->

    </div>

</div>

<?php $this->load->view('login/footer'); ?>