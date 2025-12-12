<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *  Adaptive Login Design - Compact Version - Works with All MIKHMON Themes
 *  Enhanced with Animations by Octobiztech
 */
session_start();

// Detect current theme
$current_theme = $_SESSION['theme'] ?? $theme ?? 'dark';
$is_dark_theme = in_array($current_theme, ['dark', 'black']);
$is_light_theme = in_array($current_theme, ['light', 'white']);

// Check if login was attempted and show notification
$show_notification = false;
$notification_type = '';
$notification_message = '';
$login_attempted = false;

if ($_POST && isset($_POST['login'])) {
    $login_attempted = true;
    if (isset($error) && !empty($error)) {
        $show_notification = true;
        $notification_type = 'error';
        $notification_message = 'Username atau password salah!';
    }
}
?>

<style>
/* Base adaptive styling that works with all themes */
html, html body {
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    background-attachment: fixed !important;
    overflow-x: hidden !important;
}

<?php if ($is_dark_theme): ?>
/* Dark theme specific */
html, html body {
    background: #0f0f23 !important;
    background-image: 
        radial-gradient(at 40% 20%, #1e1b4b 0px, transparent 50%),
        radial-gradient(at 80% 0%, #312e81 0px, transparent 50%),
        radial-gradient(at 0% 50%, #1e1b4b 0px, transparent 50%),
        radial-gradient(at 80% 50%, #312e81 0px, transparent 50%) !important;
    color: #e2e8f0 !important;
}
<?php else: ?>
/* Light themes */
html, html body {
    background: linear-gradient(135deg, 
        <?php 
        switch($current_theme) {
            case 'blue': echo '#3b82f6 0%, #1e40af 100%'; break;
            case 'green': echo '#10b981 0%, #059669 100%'; break;
            case 'pink': echo '#ec4899 0%, #be185d 100%'; break;
            case 'purple': echo '#8b5cf6 0%, #7c3aed 100%'; break;
            default: echo '#6366f1 0%, #4f46e5 100%'; // default light
        }
        ?>
    ) !important;
    color: #1f2937 !important;
}
<?php endif; ?>

/* Animated particles - adaptive colors */
.particles {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

.particle {
    position: absolute !important;
    background: <?= $is_dark_theme ? 'rgba(139, 92, 246, 0.3)' : 'rgba(255, 255, 255, 0.4)' ?> !important;
    border-radius: 50% !important;
    animation: float 6s ease-in-out infinite !important;
}

.particle:nth-child(1) { width: 4px !important; height: 4px !important; top: 20% !important; left: 10% !important; animation-delay: 0s !important; }
.particle:nth-child(2) { width: 6px !important; height: 6px !important; top: 60% !important; left: 80% !important; animation-delay: 2s !important; }
.particle:nth-child(3) { width: 3px !important; height: 3px !important; top: 80% !important; left: 20% !important; animation-delay: 4s !important; }
.particle:nth-child(4) { width: 5px !important; height: 5px !important; top: 30% !important; left: 70% !important; animation-delay: 1s !important; }

@keyframes float {
    0%, 100% { transform: translateY(0px) !important; opacity: 0.3 !important; }
    50% { transform: translateY(-20px) !important; opacity: 0.8 !important; }
}

div.login-box, 
div[style*="padding-top: 5%"].login-box {
    padding-top: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 100vh !important;
    width: 100% !important;
    position: relative !important;
    z-index: 10 !important;
    margin-top: -5vh !important;
}

/* Hide the card header completely */
.login-box .card .card-header,
div.login-box div.card div.card-header {
    display: none !important;
}

/* Adaptive card styling - COMPACT */
.login-box .card,
div.login-box div.card {
    background: <?= $is_dark_theme ? 'rgba(15, 23, 42, 0.8)' : 'rgba(255, 255, 255, 0.95)' ?> !important;
    backdrop-filter: blur(20px) !important;
    border-radius: 20px !important;
    box-shadow: 
        0 20px 40px -12px rgba(0, 0, 0, <?= $is_dark_theme ? '0.25' : '0.15' ?>),
        0 0 0 1px <?= $is_dark_theme ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' ?>,
        inset 0 1px 0 <?= $is_dark_theme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(255, 255, 255, 0.3)' ?> !important;
    border: 1px solid <?= $is_dark_theme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' ?> !important;
    max-width: 350px !important;
    width: 90% !important;
    margin: 0 auto !important;
    overflow: visible !important;
    position: relative !important;
}

.login-box .card::before,
div.login-box div.card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 1px !important;
    background: linear-gradient(90deg, transparent, 
        <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.6)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.6)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.6)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.6)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.6)' : 'rgba(99, 102, 241, 0.6)';
        }
        ?>
        , transparent) !important;
}

.login-box .card .card-body,
div.login-box div.card div.card-body {
    padding: 20px 30px 25px 30px !important;
    background: transparent !important;
    position: relative !important;
}

.login-box .text-center.pd-5,
div.login-box div.text-center.pd-5 {
    padding: 0 !important;
    margin-bottom: 8px !important;
}

.login-box .text-center.pd-5 img,
div.login-box div.text-center.pd-5 img {
    width: 60px !important;
    height: 60px !important;
    border-radius: 16px !important;
    box-shadow: 0 15px 20px -5px rgba(0, 0, 0, 0.1), 0 8px 8px -5px rgba(0, 0, 0, 0.04) !important;
    transition: all 0.3s ease !important;
    border: 2px solid <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.2)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.2)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.2)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.2)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.2)' : 'rgba(99, 102, 241, 0.2)';
        }
    ?> !important;
}

.login-box .text-center.pd-5 img:hover,
div.login-box div.text-center.pd-5 img:hover {
    transform: scale(1.05) !important;
    border-color: <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.4)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.4)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.4)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.4)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.4)' : 'rgba(99, 102, 241, 0.4)';
        }
    ?> !important;
    box-shadow: 0 20px 40px -12px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.25)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.25)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.25)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.25)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.25)' : 'rgba(99, 102, 241, 0.25)';
        }
    ?> !important;
}

/* MIKHMON title styling - COMPACT SPACING */
.login-box span[style*="font-size: 25px"],
div.login-box span[style*="font-size: 25px"] {
    font-size: 20px !important;
    font-weight: 800 !important;
    color: <?= $is_dark_theme ? '#f1f5f9' : '#1f2937' ?> !important;
    display: block !important;
    margin: 8px 0 5px 0 !important;
    letter-spacing: -0.05em !important;
    text-shadow: 0 4px 8px rgba(0, 0, 0, <?= $is_dark_theme ? '0.3' : '0.1' ?>) !important;
}

/* Modified by text styling - COMPACT SPACING */
.login-box .modified-by,
div.login-box .modified-by {
    font-size: 12px !important;
    font-weight: 400 !important;
    color: <?= $is_dark_theme ? 'rgba(148, 163, 184, 0.8)' : 'rgba(107, 114, 128, 0.8)' ?> !important;
    margin-top: 0 !important;
    margin-bottom: 15px !important;
    letter-spacing: 0.5px !important;
    font-style: italic !important;
}

.login-box .table,
div.login-box .table {
    width: 100% !important;
    margin: 0 !important;
}

.login-box .table td,
div.login-box .table td {
    padding: 3px 0 !important;
    text-align: center !important;
    width: 100% !important;
}

/* FORCE center alignment for ALL table cell content */
.login-box .table td *,
div.login-box .table td *,
.login-box .align-middle,
div.login-box .align-middle {
    margin-left: auto !important;
    margin-right: auto !important;
    text-align: center !important;
}

/* Adaptive Input styling - COMPACT SPACING */
.login-box input[type="text"].form-control,
.login-box input[type="password"].form-control,
div.login-box input[type="text"].form-control,
div.login-box input[type="password"].form-control,
.card-body input.form-control[type="text"],
.card-body input.form-control[type="password"] {
    width: 100% !important;
    height: 44px !important;
    padding: 12px 16px !important;
    border: 1px solid <?= $is_dark_theme ? 'rgba(148, 163, 184, 0.2)' : 'rgba(156, 163, 175, 0.3)' ?> !important;
    border-radius: 14px !important;
    font-size: 14px !important;
    background: <?= $is_dark_theme ? 'rgba(15, 23, 42, 0.6)' : 'rgba(255, 255, 255, 0.9)' ?> !important;
    color: <?= $is_dark_theme ? '#f1f5f9' : '#1f2937' ?> !important;
    transition: all 0.3s ease !important;
    box-sizing: border-box !important;
    outline: none !important;
    margin: 3px 0 !important;
    font-weight: 400 !important;
    backdrop-filter: blur(10px) !important;
}

.login-box input[type="text"].form-control:focus,
.login-box input[type="password"].form-control:focus,
div.login-box input[type="text"].form-control:focus,
div.login-box input[type="password"].form-control:focus,
.card-body input.form-control[type="text"]:focus,
.card-body input.form-control[type="password"]:focus {
    border-color: <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.6)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.6)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.6)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.6)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.6)' : 'rgba(99, 102, 241, 0.6)';
        }
    ?> !important;
    background: <?= $is_dark_theme ? 'rgba(15, 23, 42, 0.8)' : 'rgba(255, 255, 255, 1)' ?> !important;
    color: <?= $is_dark_theme ? '#f1f5f9' : '#1f2937' ?> !important;
    box-shadow: 0 0 0 3px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.1)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.1)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.1)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.1)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.1)' : 'rgba(99, 102, 241, 0.1)';
        }
    ?>, 0 6px 20px -6px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.2)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.2)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.2)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.2)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.2)' : 'rgba(99, 102, 241, 0.2)';
        }
    ?> !important;
    transform: translateY(-1px) !important;
}

.login-box input[type="text"].form-control::placeholder,
.login-box input[type="password"].form-control::placeholder,
div.login-box input[type="text"].form-control::placeholder,
div.login-box input[type="password"].form-control::placeholder,
.card-body input.form-control::placeholder {
    color: <?= $is_dark_theme ? '#94a3b8' : '#6b7280' ?> !important;
    opacity: 0.8 !important;
}

/* Enhanced Button styling with Loading Animation */
.login-box input[type="submit"].btn-login,
div.login-box input[type="submit"].btn-login,
.card-body input[type="submit"].btn-login,
.login-box input[type="submit"][name="login"],
div.login-box input[type="submit"][name="login"],
.card-body input[type="submit"][name="login"] {
    width: 100% !important;
    height: 44px !important;
    background: linear-gradient(135deg, 
        <?php 
        switch($current_theme) {
            case 'blue': echo '#3b82f6 0%, #1e40af 100%'; break;
            case 'green': echo '#10b981 0%, #059669 100%'; break;
            case 'pink': echo '#ec4899 0%, #be185d 100%'; break;
            case 'purple': echo '#8b5cf6 0%, #7c3aed 100%'; break;
            default: echo $is_dark_theme ? '#8b5cf6 0%, #3b82f6 100%' : '#6366f1 0%, #4f46e5 100%';
        }
        ?>
    ) !important;
    color: white !important;
    border: none !important;
    border-radius: 14px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    margin-top: 8px !important;
    letter-spacing: 0.025em !important;
    text-shadow: none !important;
    position: relative !important;
    overflow: hidden !important;
    transform: translateY(0) scale(1) !important;
}

.login-box input[type="submit"].btn-login:hover,
div.login-box input[type="submit"].btn-login:hover,
.card-body input[type="submit"].btn-login:hover,
.login-box input[type="submit"][name="login"]:hover,
div.login-box input[type="submit"][name="login"]:hover,
.card-body input[type="submit"][name="login"]:hover {
    background: linear-gradient(135deg, 
        <?php 
        switch($current_theme) {
            case 'blue': echo '#2563eb 0%, #1e3a8a 100%'; break;
            case 'green': echo '#059669 0%, #047857 100%'; break;
            case 'pink': echo '#be185d 0%, #9d174d 100%'; break;
            case 'purple': echo '#7c3aed 0%, #6d28d9 100%'; break;
            default: echo $is_dark_theme ? '#7c3aed 0%, #2563eb 100%' : '#4f46e5 0%, #3730a3 100%';
        }
        ?>
    ) !important;
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 15px 20px -5px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.4)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.4)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.4)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.4)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.4)' : 'rgba(99, 102, 241, 0.4)';
        }
    ?>, 0 8px 8px -5px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.2)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.2)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.2)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.2)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.2)' : 'rgba(99, 102, 241, 0.2)';
        }
    ?> !important;
}

/* Loading state for button */
.login-box input[type="submit"].loading,
div.login-box input[type="submit"].loading {
    pointer-events: none !important;
    opacity: 0.8 !important;
    position: relative !important;
}

.login-box input[type="submit"].loading::after,
div.login-box input[type="submit"].loading::after {
    content: '' !important;
    position: absolute !important;
    width: 16px !important;
    height: 16px !important;
    margin: auto !important;
    border: 2px solid transparent !important;
    border-top-color: white !important;
    border-radius: 50% !important;
    animation: spin 1s linear infinite !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    right: 0 !important;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* CLICK ANIMATION - Button pressed effect */
.login-box input[type="submit"].btn-login:active,
div.login-box input[type="submit"].btn-login:active,
.card-body input[type="submit"].btn-login:active,
.login-box input[type="submit"][name="login"]:active,
div.login-box input[type="submit"][name="login"]:active,
.card-body input[type="submit"][name="login"]:active {
    transform: translateY(1px) scale(0.98) !important;
    box-shadow: 0 5px 10px -3px <?php 
        switch($current_theme) {
            case 'blue': echo 'rgba(59, 130, 246, 0.3)'; break;
            case 'green': echo 'rgba(16, 185, 129, 0.3)'; break;
            case 'pink': echo 'rgba(236, 72, 153, 0.3)'; break;
            case 'purple': echo 'rgba(139, 92, 246, 0.3)'; break;
            default: echo $is_dark_theme ? 'rgba(139, 92, 246, 0.3)' : 'rgba(99, 102, 241, 0.3)';
        }
    ?> !important;
    transition: all 0.1s ease !important;
}

/* Ripple effect animation */
.login-box input[type="submit"].btn-login::before,
div.login-box input[type="submit"].btn-login::before,
.card-body input[type="submit"].btn-login::before,
.login-box input[type="submit"][name="login"]::before,
div.login-box input[type="submit"][name="login"]::before,
.card-body input[type="submit"][name="login"]::before {
    content: '' !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    width: 0 !important;
    height: 0 !important;
    background: rgba(255, 255, 255, 0.3) !important;
    border-radius: 50% !important;
    transform: translate(-50%, -50%) !important;
    transition: width 0.6s ease, height 0.6s ease !important;
}

.login-box input[type="submit"].btn-login:active::before,
div.login-box input[type="submit"].btn-login:active::before,
.card-body input[type="submit"].btn-login:active::before,
.login-box input[type="submit"][name="login"]:active::before,
div.login-box input[type="submit"][name="login"]:active::before,
.card-body input[type="submit"][name="login"]:active::before {
    width: 300px !important;
    height: 300px !important;
    transition: width 0s ease, height 0s ease !important;
}

/* Full Screen Success Overlay */
.success-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.95) !important;
    z-index: 99999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.4s ease !important;
}

.success-overlay.show {
    opacity: 1 !important;
    visibility: visible !important;
}

.success-content {
    text-align: center !important;
    color: white !important;
    transform: scale(0.5) translateY(50px) !important;
    transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
}

.success-overlay.show .success-content {
    transform: scale(1) translateY(0) !important;
}

.success-icon {
    width: 120px !important;
    height: 120px !important;
    background: linear-gradient(135deg, #10b981, #059669) !important;
    border-radius: 50% !important;
    margin: 0 auto 30px auto !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 60px !important;
    box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.3) !important;
    animation: successPulse 2s ease-in-out infinite !important;
}

.success-title {
    font-size: 42px !important;
    font-weight: 800 !important;
    margin-bottom: 20px !important;
    letter-spacing: -0.02em !important;
    background: linear-gradient(135deg, #10b981, #34d399) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

.success-subtitle {
    font-size: 18px !important;
    opacity: 0.8 !important;
    font-weight: 400 !important;
    margin-bottom: 30px !important;
}

.success-loading {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    font-size: 16px !important;
    opacity: 0.7 !important;
}

.success-loading .dot {
    width: 8px !important;
    height: 8px !important;
    background: #10b981 !important;
    border-radius: 50% !important;
    animation: loadingDots 1.5s ease-in-out infinite !important;
}

.success-loading .dot:nth-child(2) { animation-delay: 0.2s !important; }
.success-loading .dot:nth-child(3) { animation-delay: 0.4s !important; }

@keyframes successPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 25px 50px -10px rgba(16, 185, 129, 0.4); }
}

@keyframes loadingDots {
    0%, 100% { transform: translateY(0); opacity: 0.7; }
    50% { transform: translateY(-10px); opacity: 1; }
}

/* Small Notification Styles */
.notification {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
    padding: 16px 20px !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.25) !important;
    backdrop-filter: blur(20px) !important;
    transform: translateX(400px) !important;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    min-width: 300px !important;
    font-weight: 500 !important;
    font-size: 14px !important;
}

.notification.show {
    transform: translateX(0) !important;
}

.notification.success {
    background: rgba(16, 185, 129, 0.1) !important;
    border: 1px solid rgba(16, 185, 129, 0.2) !important;
    color: #10b981 !important;
}

.notification.error {
    background: rgba(239, 68, 68, 0.1) !important;
    border: 1px solid rgba(239, 68, 68, 0.2) !important;
    color: #ef4444 !important;
}

/* Error message styling - PERFECTLY CENTERED - Adaptive - COMPACT */
.login-box .bg-danger,
div.login-box .bg-danger,
.login-box [style*="background-color"],
div.login-box [style*="background-color"] {
    background: <?= $is_dark_theme ? 'rgba(239, 68, 68, 0.1)' : 'rgba(254, 242, 242, 0.9)' ?> !important;
    color: <?= $is_dark_theme ? '#fca5a5' : '#dc2626' ?> !important;
    border-radius: 10px !important;
    border: 1px solid <?= $is_dark_theme ? 'rgba(239, 68, 68, 0.2)' : 'rgba(252, 165, 165, 0.5)' ?> !important;
    padding: 10px 16px !important;
    margin: 8px auto 0 auto !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    backdrop-filter: blur(10px) !important;
    text-align: center !important;
    width: 100% !important;
    box-sizing: border-box !important;
    display: block !important;
}

/* Responsive */
@media (max-width: 480px) {
    .login-box .card .card-body,
    div.login-box div.card div.card-body {
        padding: 18px 20px 22px 20px !important;
    }
    
    .login-box .card,
    div.login-box div.card {
        max-width: 320px !important;
    }
    
    .form-control, .btn-login {
        height: 40px !important;
        font-size: 13px !important;
    }
    
    .login-title {
        font-size: 18px !important;
    }
    
    .login-box .text-center img {
        width: 50px !important;
        height: 50px !important;
    }
    
    .notification {
        right: 10px !important;
        left: 10px !important;
        min-width: auto !important;
        transform: translateY(-100px) !important;
    }
    
    .notification.show {
        transform: translateY(0) !important;
    }
    
    /* Mobile responsive for full screen overlay */
    .success-icon {
        width: 100px !important;
        height: 100px !important;
        font-size: 50px !important;
        margin-bottom: 25px !important;
    }
    
    .success-title {
        font-size: 32px !important;
        margin-bottom: 15px !important;
    }
    
    .success-subtitle {
        font-size: 16px !important;
        margin-bottom: 25px !important;
    }
    
    .success-loading {
        font-size: 14px !important;
    }
}

/* Card animation */
.login-box .card,
div.login-box div.card {
    animation: slideInUp 0.6s ease-out !important;
}

@keyframes slideInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Shake animation for error */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.shake {
    animation: shake 0.5s ease-in-out !important;
}

.text-center { text-align: center !important; }
.login-box input:focus, div.login-box input:focus { outline: none !important; }
</style>

<!-- Animated particles -->
<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<!-- STRUKTUR HTML ASLI DIPERTAHANKAN 100% -->
<div style="padding-top: 5%;" class="login-box">
  <div class="card">
    <div class="card-header">
      <h3><?= $_please_login ?></h3>
    </div>
    <div class="card-body">
      <div class="text-center pd-5">
        <img src="img/favicon.png" alt="MIKHMON Logo">
      </div>
      <div class="text-center">
<span style="font-size: 25px; margin: 10px;">MIKHMON</span>
<div class="modified-by">Modified by Octobiztech</div>
</div>
      <center>
      <form autocomplete="off" action="" method="post" id="loginForm">
      <table class="table" style="width:90%">
        <tr>
          <td class="align-middle text-center">
            <input style="width: 100%; height: 35px; font-size: 16px;" class="form-control" type="text" name="user" id="_username" placeholder="👤 Username" required="1" autofocus>
          </td>
        </tr>
        <tr>
          <td class="align-middle text-center">
            <input style="width: 100%; height: 35px; font-size: 16px;" class="form-control" type="password" name="pass" placeholder="🔒 Password" required="1">
          </td>
        </tr>
        <tr>
          <td class="align-middle text-center">
            <input style="width: 100%; margin-top:20px; height: 35px; font-weight: bold; font-size: 17px;" class="btn-login bg-primary pointer" type="submit" name="login" value="🚀 Sign In" id="loginBtn">
          </td>
        </tr>
        <tr>
          <td class="align-middle text-center">
            <div style="text-align: center; width: 100%;">
              <?= $error; ?>
            </div>
          </td>
        </tr>
      </table>
      </form>
      </center>
    </div>
  </div>
</div>

<script>
// Notification system
function showNotification(message, type = 'success', duration = 3000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = type === 'success' ? '✅' : '❌';
    
    notification.innerHTML = `
        <div style="font-size: 18px;">${icon}</div>
        <div>${message}</div>
        <button onclick="closeNotification(this.parentElement)" style="background: none; border: none; color: inherit; cursor: pointer; opacity: 0.7; font-size: 16px; padding: 0; margin-left: 10px;">✕</button>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto close notification
    setTimeout(() => {
        closeNotification(notification);
    }, duration);
    
    return notification;
}

function closeNotification(notification) {
    if (notification && notification.classList) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 400);
    }
}

// Full Screen Success Overlay
function showFullScreenSuccess() {
    // Remove any existing overlay
    const existingOverlay = document.querySelector('.success-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'success-overlay';
    overlay.innerHTML = `
        <div class="success-content">
            <div class="success-icon">✓</div>
            <div class="success-title">Login Berhasil</div>
            <div class="success-subtitle">Selamat datang kembali!</div>
            <div class="success-loading">
                <span>Mengalihkan ke dashboard</span>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Show with animation
    setTimeout(() => {
        overlay.classList.add('show');
    }, 100);
    
    return overlay;
}

// Small notification system (for errors)
function showNotification(message, type = 'success', duration = 3000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = type === 'success' ? '✅' : '❌';
    
    notification.innerHTML = `
        <div style="font-size: 18px;">${icon}</div>
        <div>${message}</div>
        <button onclick="closeNotification(this.parentElement)" style="background: none; border: none; color: inherit; cursor: pointer; opacity: 0.7; font-size: 16px; padding: 0; margin-left: 10px;">✕</button>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto close notification
    setTimeout(() => {
        closeNotification(notification);
    }, duration);
    
    return notification;
}

function closeNotification(notification) {
    if (notification && notification.classList) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 400);
    }
}

// Simple form handling - no preventDefault to avoid issues
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const loginBtn = document.getElementById('loginBtn');
    const username = document.getElementById('_username').value;
    const password = document.querySelector('input[name="pass"]').value;
    
    // Add loading state
    loginBtn.classList.add('loading');
    loginBtn.style.pointerEvents = 'none';
    
    // Create click particles effect
    const rect = loginBtn.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    createClickParticles(x, y);
    
    if (username && password) {
        // Show full screen success overlay immediately
        setTimeout(() => {
            showFullScreenSuccess();
        }, 400);
        
        // Let the form submit naturally - don't interfere
    } else {
        // No credentials
        setTimeout(() => {
            loginBtn.classList.remove('loading');
            loginBtn.style.pointerEvents = 'auto';
        }, 500);
    }
});

// Show error notification if exists (keeping the original PHP error system)
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($show_notification): ?>
    setTimeout(() => {
        showNotification('<?= $notification_message ?>', '<?= $notification_type ?>', 4000);
        
        // Add shake animation for error
        const card = document.querySelector('.login-box .card');
        if (card) {
            card.classList.add('shake');
            setTimeout(() => {
                card.classList.remove('shake');
            }, 500);
        }
    }, 300);
    <?php endif; ?>
});

// Enhanced input interactions
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.parentElement.style.transform = 'scale(1.02)';
        this.parentElement.parentElement.style.transition = 'transform 0.2s ease';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.parentElement.style.transform = 'scale(1)';
    });
});

// Click particles effect
function createClickParticles(x, y) {
    for (let i = 0; i < 6; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            width: 4px;
            height: 4px;
            background: #8b5cf6;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            left: ${x}px;
            top: ${y}px;
        `;
        
        document.body.appendChild(particle);
        
        const angle = (i / 6) * Math.PI * 2;
        const velocity = 100;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;
        
        particle.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: `translate(${vx}px, ${vy}px) scale(0)`, opacity: 0 }
        ], {
            duration: 600,
            easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
        }).onfinish = () => particle.remove();
    }
}

// Add particle effect on button click
document.addEventListener('click', function(e) {
    if (e.target.type === 'submit' && e.target.name === 'login') {
        createClickParticles(e.clientX, e.clientY);
    }
});
</script>

</body>
</html>