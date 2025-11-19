/**
 * Dimsum Umami POS - Main JavaScript
 */

// Theme Management
const ThemeManager = {
    init() {
        this.updateToggleButton();
    },

    toggle() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-bs-theme') === 'dark';
        const newTheme = isDark ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
        this.updateToggleButton();
    },

    updateToggleButton() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        btn.innerHTML = isDark
            ? '<i class="bi bi-sun"></i>'
            : '<i class="bi bi-moon-stars"></i>';
        btn.title = isDark ? 'Mode Terang' : 'Mode Gelap';
    }
};

// Sidebar Management
const SidebarManager = {
    init() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => this.toggle());
        }

        if (overlay) {
            overlay.addEventListener('click', () => this.hide());
        }

        // Close sidebar on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hide();
        });
    },

    toggle() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    },

    hide() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
};

// Format Helpers
const Format = {
    rupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    },

    number(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    },

    date(dateString) {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    },

    datetime(dateString) {
        return new Date(dateString).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

// Toast Notifications
const Toast = {
    show(message, type = 'info') {
        const container = document.getElementById('toastContainer') || this.createContainer();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    },

    createContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'danger'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// Confirm Dialog
const Confirm = {
    show(options) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${options.title || 'Konfirmasi'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${options.message || 'Apakah Anda yakin?'}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-${options.type || 'primary'}" id="confirmBtn">
                                ${options.confirmText || 'Ya'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            modal.querySelector('#confirmBtn').addEventListener('click', () => {
                bsModal.hide();
                resolve(true);
            });

            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
                resolve(false);
            });
        });
    },

    delete(message) {
        return this.show({
            title: 'Hapus Data',
            message: message || 'Data yang dihapus tidak dapat dikembalikan. Lanjutkan?',
            type: 'danger',
            confirmText: 'Hapus'
        });
    }
};

// AJAX Helper
const Ajax = {
    async request(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaults, ...options };

        if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
            config.body = JSON.stringify(config.body);
        }

        if (config.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan');
            }

            return data;
        } catch (error) {
            Toast.error(error.message);
            throw error;
        }
    },

    get(url) {
        return this.request(url);
    },

    post(url, data) {
        return this.request(url, { method: 'POST', body: data });
    },

    put(url, data) {
        return this.request(url, { method: 'PUT', body: data });
    },

    delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
};

// DataTable Helper (if using DataTables)
const DataTableConfig = {
    language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(filter dari _MAX_ data)",
        zeroRecords: "Data tidak ditemukan",
        paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
        }
    },
    pageLength: 10,
    responsive: true
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    ThemeManager.init();
    SidebarManager.init();

    // Auto-hide alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Tooltip initialization
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});

// Expose to global
window.ThemeManager = ThemeManager;
window.Format = Format;
window.Toast = Toast;
window.Confirm = Confirm;
window.Ajax = Ajax;
