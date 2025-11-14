/**
 * Login Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showError('Username dan password harus diisi');
            return;
        }

        // Show loading state
        const btnText = loginForm.querySelector('.btn-text');
        const btnLoading = loginForm.querySelector('.btn-loading');
        const submitBtn = loginForm.querySelector('button[type="submit"]');

        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        submitBtn.disabled = true;

        try {
            const response = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                showError(data.message || 'Login gagal');
            }
        } catch (error) {
            showError('Terjadi kesalahan. Silakan coba lagi.');
            console.error('Login error:', error);
        } finally {
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';

        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000);
    }
});
