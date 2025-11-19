/**
 * NetStock Pro - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme
    initTheme();

    // Initialize sidebar toggle
    initSidebar();

    // Initialize dropdowns
    initDropdowns();

    // Initialize alerts auto-dismiss
    initAlerts();

    // Initialize confirmations
    initConfirmations();

    // Initialize form validations
    initFormValidations();
});

/**
 * Theme Toggle (Dark/Light Mode)
 */
function initTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('theme') || 'light';

    // Apply saved theme
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
}

/**
 * Sidebar Toggle
 */
function initSidebar() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
}

/**
 * Dropdown Menus
 */
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');

    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;

            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                if (openMenu !== menu) {
                    openMenu.classList.remove('show');
                }
            });

            menu.classList.toggle('show');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
        });
    });
}

/**
 * Auto-dismiss Alerts
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function(alert) {
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);

        // Manual dismiss
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alert.remove();
            });
        }
    });
}

/**
 * Confirmation Dialogs
 */
function initConfirmations() {
    const confirmLinks = document.querySelectorAll('[data-confirm]');

    confirmLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Form Validations
 */
function initFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('danger', 'Mohon lengkapi semua field yang wajib diisi');
            }
        });
    });
}

/**
 * Show Alert
 */
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close">&times;</button>
        </div>
    `;

    const container = document.querySelector('.content') || document.body;
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Re-init alerts for the new one
    initAlerts();
}

/**
 * Format Currency
 */
function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Format Number Input
 */
function formatNumberInput(input) {
    let value = input.value.replace(/\D/g, '');
    input.value = new Intl.NumberFormat('id-ID').format(value);
    return parseInt(value) || 0;
}

/**
 * Calculate Totals (for forms)
 */
function calculateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.item-row');

    rows.forEach(function(row) {
        const qty = parseInt(row.querySelector('.item-qty')?.value) || 0;
        const price = parseInt(row.querySelector('.item-price')?.value.replace(/\D/g, '')) || 0;
        const subtotal = qty * price;

        const subtotalField = row.querySelector('.item-subtotal');
        if (subtotalField) {
            subtotalField.textContent = formatCurrency(subtotal);
        }

        total += subtotal;
    });

    const totalField = document.getElementById('grandTotal');
    if (totalField) {
        totalField.textContent = formatCurrency(total);
    }

    return total;
}

/**
 * Add Item Row (for dynamic forms)
 */
function addItemRow(template) {
    const container = document.getElementById('itemsContainer');
    if (container && template) {
        const newRow = template.cloneNode(true);
        newRow.style.display = '';
        newRow.classList.add('item-row');

        // Clear values
        newRow.querySelectorAll('input, select').forEach(function(input) {
            input.value = '';
        });

        container.appendChild(newRow);
        calculateTotal();
    }
}

/**
 * Remove Item Row
 */
function removeItemRow(button) {
    const row = button.closest('.item-row');
    if (row) {
        row.remove();
        calculateTotal();
    }
}

/**
 * Print Element
 */
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                    th { background: #f5f5f5; }
                    .text-end { text-align: right; }
                    .fw-bold { font-weight: bold; }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

/**
 * Export Table to Excel
 */
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (table) {
        const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(wb, filename + '.xlsx');
    }
}

/**
 * Search/Filter Table
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (input && table) {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

/**
 * Modal Functions
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    const backdrop = document.querySelector('.modal-backdrop') || createBackdrop();

    if (modal) {
        modal.style.display = 'block';
        backdrop.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const backdrop = document.querySelector('.modal-backdrop');

    if (modal) {
        modal.style.display = 'none';
    }
    if (backdrop) {
        backdrop.style.display = 'none';
    }
    document.body.style.overflow = '';
}

function createBackdrop() {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.addEventListener('click', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.style.display = 'none';
        });
        this.style.display = 'none';
        document.body.style.overflow = '';
    });
    document.body.appendChild(backdrop);
    return backdrop;
}

/**
 * Debounce Function
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
 * AJAX Helper
 */
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showAlert('danger', 'Terjadi kesalahan saat memuat data');
        return null;
    }
}

/**
 * Barcode Scanner (using camera)
 */
function initBarcodeScanner(inputId, videoId) {
    // This is a placeholder for barcode scanning functionality
    // You would need to include a library like QuaggaJS or ZXing for actual implementation
    console.log('Barcode scanner initialized for', inputId);
}

/**
 * Chart Initialization Helper
 */
function createChart(canvasId, type, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    return new Chart(canvas, {
        type: type,
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...options
        }
    });
}
