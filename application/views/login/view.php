<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('login/header'); ?>

<style>
    /* Agriculture Luxury Login Page Styles */
    .auth-wrapper-agriculture {
        min-height: 100vh;
        max-height: 100vh;
        position: relative;
        overflow: hidden;
        background: #0a0e27;
    }

    /* Animated Background */
    .agriculture-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background-image: url('https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/landing%20page/rice_straw.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    .agriculture-bg::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(10, 14, 39, 0.75) 0%, rgba(16, 37, 60, 0.70) 50%, rgba(10, 14, 39, 0.75) 100%);
        z-index: 2;
    }

    .agriculture-bg-image {
        display: none;
    }

    /* Rain Canvas */
    #rainCanvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 3;
        pointer-events: none;
    }

    /* Login Container */
    .login-container {
        position: relative;
        z-index: 10;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
        overflow: hidden;
    }

    /* Glass Card */
    .glass-card {
        background: rgba(10, 20, 40, 0.75);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border-radius: 24px;
        border: 1px solid rgba(139, 195, 74, 0.35);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(139, 195, 74, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        padding: 40px 40px;
        width: 100%;
        max-width: 450px;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.8s ease-out;
    }

    /* Hide scrollbar but keep functionality */
    .glass-card::-webkit-scrollbar {
        width: 0;
        display: none;
    }

    .glass-card {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(50px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Logo */
    .auth-logo {
        text-align: center;
        margin-bottom: 20px;
    }

    .auth-logo img {
        width: 70px;
        height: 70px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(139, 195, 74, 0.3);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 4px 20px rgba(139, 195, 74, 0.3); }
        50% { transform: scale(1.05); box-shadow: 0 6px 30px rgba(139, 195, 74, 0.5); }
    }

    .auth-logo h3 {
        color: #fff;
        font-size: 24px;
        font-weight: 700;
        margin-top: 12px;
        margin-bottom: 3px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .auth-logo p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 13px;
        margin: 0;
    }

    /* Welcome Text */
    .welcome-text {
        text-align: center;
        margin-bottom: 25px;
    }

    .welcome-text h4 {
        color: #fff;
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .welcome-text p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        margin: 0;
    }

    /* Form Styles */
    .glass-card .form-label {
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .glass-card .form-control {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .glass-card .form-control:focus {
        background: rgba(255, 255, 255, 0.12);
        border-color: #8bc34a;
        box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.1);
        color: #fff;
    }

    .glass-card .form-control::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .glass-card .input-group-text {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-left: none;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .glass-card .input-group-text:hover {
        color: #8bc34a;
    }

    /* Checkbox & Links */
    .glass-card .form-check-input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .glass-card .form-check-input:checked {
        background-color: #8bc34a;
        border-color: #8bc34a;
    }

    .glass-card .form-check-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
    }

    .glass-card a {
        color: #8bc34a;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .glass-card a:hover {
        color: #9ccc65;
        text-shadow: 0 0 10px rgba(139, 195, 74, 0.5);
    }

    /* Button */
    .btn-agriculture {
        background: linear-gradient(135deg, #8bc34a 0%, #689f38 100%);
        border: none;
        color: #fff;
        padding: 12px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(139, 195, 74, 0.3);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-agriculture::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-agriculture:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-agriculture:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(139, 195, 74, 0.5);
    }

    .btn-agriculture:active {
        transform: translateY(0);
    }

    /* Alert Styles */
    .glass-card .alert {
        background: rgba(244, 67, 54, 0.15);
        border: 1px solid rgba(244, 67, 54, 0.3);
        color: #ff5252;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
    }

    .glass-card .alert-success {
        background: rgba(139, 195, 74, 0.15);
        border-color: rgba(139, 195, 74, 0.3);
        color: #8bc34a;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .agriculture-bg {
            background-attachment: scroll;
            background-position: center center;
        }

        .glass-card {
            padding: 30px 25px;
            max-width: 100%;
            margin: 0 10px;
        }

        .auth-logo img {
            width: 60px;
            height: 60px;
        }

        .auth-logo h3 {
            font-size: 20px;
        }

        .welcome-text h4 {
            font-size: 18px;
        }

        .btn-agriculture {
            padding: 11px;
            font-size: 14px;
        }

        .agriculture-bg-image {
            object-position: center;
        }

        .login-container {
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        .glass-card {
            padding: 25px 20px;
            border-radius: 20px;
        }

        .auth-logo img {
            width: 50px;
            height: 50px;
        }

        .auth-logo h3 {
            font-size: 18px;
        }

        .welcome-text {
            margin-bottom: 20px;
        }

        .welcome-text h4 {
            font-size: 16px;
        }

        .welcome-text p {
            font-size: 12px;
        }

        .login-container {
            padding: 8px;
        }
    }

    @media (max-height: 700px) {
        .glass-card {
            padding: 25px 35px;
        }

        .auth-logo {
            margin-bottom: 15px;
        }

        .auth-logo img {
            width: 55px;
            height: 55px;
        }

        .auth-logo h3 {
            font-size: 20px;
            margin-top: 8px;
        }

        .welcome-text {
            margin-bottom: 18px;
        }

        .welcome-text h4 {
            font-size: 18px;
        }

        .mb-4 {
            margin-bottom: 0.8rem !important;
        }
    }
</style>

<div class="auth-wrapper-agriculture">
    
    <!-- Rice Straw Background -->
    <div class="agriculture-bg"></div>

    <!-- Rain Canvas -->
    <canvas id="rainCanvas"></canvas>

    <!-- Login Container -->
    <div class="login-container">
        <div class="glass-card">
            
            <!-- Logo -->
            <div class="auth-logo">
                <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                <h3><?php echo getSiteConfiguration()->ShortName; ?></h3>
                <p>Billing Management System</p>
            </div>

            <!-- Welcome Text -->
            <div class="welcome-text">
                <h4>Welcome Back! 🌾</h4>
                <p>Sign in to manage your billing operations</p>
            </div>

            <!-- Login Form -->
            <?php $FormAttribute = array('id' => 'doLoginForm', 'name' => 'doLoginForm', 'autocomplete' => 'off');
            echo form_open('login/doLoginForm', $FormAttribute); ?>

            <?php $this->load->view('login/alerts'); ?>

            <div class="mb-4">
                <label for="UserName" class="form-label">Email or Username</label>
                <input type="text" class="form-control" id="UserName" name="UserName" placeholder="Enter your email or username" autocomplete="off" required />
            </div>

            <div class="mb-4">
                <label class="form-label" for="UserPassword">Password</label>
                <div class="input-group">
                    <input type="password" id="UserPassword" class="form-control" name="UserPassword" placeholder="Enter your password" autocomplete="new-password" required />
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="bx bx-hide" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember-me">
                        <label class="form-check-label" for="remember-me">Remember Me</label>
                    </div>
                    <a href="javascript: void(0);">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="btn btn-agriculture d-grid w-100">
                <span style="position: relative; z-index: 1;">Sign In</span>
            </button>

            <?php echo form_close(); ?>

        </div>
    </div>

</div>

<?php $this->load->view('login/footer'); ?>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('UserPassword');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bx-hide');
            toggleIcon.classList.add('bx-show');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bx-show');
            toggleIcon.classList.add('bx-hide');
        }
    }

    // Rain Animation
    (function() {
        const canvas = document.getElementById('rainCanvas');
        const ctx = canvas.getContext('2d');

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        const drops = Array.from({ length: 120 }, () => ({
            x: Math.random() * window.innerWidth,
            y: Math.random() * window.innerHeight,
            length: Math.random() * 20 + 10,
            speed: Math.random() * 4 + 3,
            opacity: Math.random() * 0.4 + 0.1,
            width: Math.random() * 1 + 0.5
        }));

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drops.forEach(drop => {
                ctx.beginPath();
                ctx.moveTo(drop.x, drop.y);
                ctx.lineTo(drop.x - drop.length * 0.2, drop.y + drop.length);
                ctx.strokeStyle = `rgba(174, 214, 241, ${drop.opacity})`;
                ctx.lineWidth = drop.width;
                ctx.lineCap = 'round';
                ctx.stroke();

                drop.y += drop.speed;
                drop.x -= drop.speed * 0.2;

                if (drop.y > canvas.height) {
                    drop.y = -drop.length;
                    drop.x = Math.random() * canvas.width;
                }
            });
            requestAnimationFrame(draw);
        }
        draw();
    })();

    // Wait for DOM and jQuery to load
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined') {
            $('#template-customizer').addClass('d-none');
        } else {
            // Fallback if jQuery not loaded
            const customizer = document.getElementById('template-customizer');
            if (customizer) {
                customizer.classList.add('d-none');
            }
        }
    });
</script>