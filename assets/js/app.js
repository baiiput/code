/**
 * Main Application JavaScript
 * Handles theme, sidebar, user menu, and common functions
 */

// Theme Management
const themeToggle = document.getElementById('themeToggle');
const htmlElement = document.documentElement;

// Load saved theme or default to light
const savedTheme = localStorage.getItem('theme') || 'light';
htmlElement.setAttribute('data-theme', savedTheme);

if (themeToggle) {
    themeToggle.addEventListener('click', function() {
        const currentTheme = htmlElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        htmlElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
}

// Sidebar Toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        sidebar.classList.toggle('active');
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// User Menu Dropdown
const userMenuBtn = document.querySelector('.user-menu-btn');
const userMenu = document.querySelector('.user-menu');

if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        userMenu.classList.remove('active');
    });

    userMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

// Logout Handler
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async function(e) {
        e.preventDefault();

        if (confirm('Apakah Anda yakin ingin logout?')) {
            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = 'login.php';
            }
        }
    });
}

// Utility Functions

/**
 * Format number as Indonesian Rupiah
 */
function formatCurrency(number) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

/**
 * Format date to Indonesian format
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Format datetime to Indonesian format
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Show notification/alert
 */
function showNotification(message, type = 'info') {
    // You can implement a custom notification system here
    // For now, we'll use a simple alert
    alert(message);
}

/**
 * Get payment status badge HTML
 */
function getPaymentStatusBadge(status) {
    const badges = {
        'paid': '<span class="badge badge-success">Lunas</span>',
        'partial': '<span class="badge badge-warning">Sebagian</span>',
        'unpaid': '<span class="badge badge-danger">Belum Bayar</span>'
    };

    return badges[status] || status;
}

/**
 * Fetch API with error handling
 */
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Set active menu item
 */
function setActiveMenuItem() {
    const currentPath = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.menu-item');

    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPath) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// Set active menu on page load
setActiveMenuItem();

// Export functions for use in other scripts
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.showNotification = showNotification;
window.getPaymentStatusBadge = getPaymentStatusBadge;
window.apiRequest = apiRequest;
window.debounce = debounce;
