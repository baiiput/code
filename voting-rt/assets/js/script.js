/**
 * Voting RT - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Form validation for NIK (16 digits)
    const nikInputs = document.querySelectorAll('input[name="nik"], input[name="no_kk"]');
    nikInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 16);
        });
    });

    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[name="no_hp"]');
    phoneInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15);
        });
    });

    // Password confirmation validation
    const confirmPassword = document.querySelector('input[name="konfirmasi_password"]');
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            const password = document.querySelector('input[name="password"]');
            if (this.value !== password.value) {
                this.setCustomValidity('Password tidak cocok');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
});

// Confirm before dangerous actions
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin?');
}

// Format number with thousand separator
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
