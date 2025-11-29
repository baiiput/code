/**
 * Form Submission Protection
 * Prevents duplicate/concurrent form submissions
 *
 * Features:
 * - Prevents double-click submissions
 * - Disables form during processing
 * - Shows loading state
 * - Auto re-enables on error
 * - Session-based duplicate prevention
 */

class FormProtection {
    constructor() {
        this.submittingForms = new Set();
        this.submissionTokens = new Map();
        this.init();
    }

    init() {
        // Auto-protect all forms on page load
        document.addEventListener('DOMContentLoaded', () => {
            this.protectAllForms();
        });

        // Handle browser back/forward
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.resetAllForms();
            }
        });
    }

    /**
     * Protect all forms on the page automatically
     */
    protectAllForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.hasAttribute('data-no-protection')) {
                this.protectForm(form);
            }
        });
    }

    /**
     * Protect a specific form from duplicate submissions
     */
    protectForm(form) {
        const formId = form.id || this.generateFormId(form);
        form.setAttribute('data-form-id', formId);

        // Check if already protected
        if (form.hasAttribute('data-protection-applied')) {
            return;
        }
        form.setAttribute('data-protection-applied', 'true');

        // Add submit event listener in capture phase (runs before other listeners)
        form.addEventListener('submit', (e) => this.handleSubmit(e, formId), true);
    }

    /**
     * Handle form submission
     */
    handleSubmit(event, formId) {
        const form = event.target;

        // Check if form is already being submitted
        if (this.submittingForms.has(formId)) {
            event.preventDefault();
            event.stopPropagation();
            this.showWarning('Mohon tunggu, form sedang diproses...');
            return false;
        }

        // Mark form as submitting
        this.submittingForms.add(formId);

        // Disable form
        this.disableForm(form);

        // Set timeout to re-enable form if submission takes too long (30 seconds)
        setTimeout(() => {
            if (this.submittingForms.has(formId)) {
                this.enableForm(form);
                this.submittingForms.delete(formId);
            }
        }, 30000);

        return true;
    }

    /**
     * Disable form to prevent re-submission
     */
    disableForm(form) {
        // Only disable buttons, NOT inputs (disabled inputs don't send values!)
        const buttons = form.querySelectorAll('button');
        buttons.forEach(button => {
            button.disabled = true;
            button.setAttribute('data-was-disabled', button.disabled);
        });

        // Change submit button text to show loading
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.setAttribute('data-original-text', originalText);
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.classList.add('btn-loading');
        }

        // Add visual indicator to form
        form.classList.add('form-submitting');
        form.style.opacity = '0.8';
        form.style.pointerEvents = 'none';
    }

    /**
     * Re-enable form (in case of error)
     */
    enableForm(form) {
        // Re-enable buttons
        const buttons = form.querySelectorAll('button');
        buttons.forEach(button => {
            const wasDisabled = button.getAttribute('data-was-disabled') === 'true';
            if (!wasDisabled) {
                button.disabled = false;
            }
            button.removeAttribute('data-was-disabled');
        });

        // Restore submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.getAttribute('data-original-text');
            if (originalText) {
                submitBtn.innerHTML = originalText;
            }
            submitBtn.classList.remove('btn-loading');
        }

        // Remove visual indicator
        form.classList.remove('form-submitting');
        form.style.opacity = '1';
        form.style.pointerEvents = 'auto';
    }

    /**
     * Reset all forms on the page
     */
    resetAllForms() {
        this.submittingForms.clear();
        const forms = document.querySelectorAll('form[data-form-id]');
        forms.forEach(form => {
            this.enableForm(form);
            const formId = form.getAttribute('data-form-id');
            this.submittingForms.delete(formId);
        });
    }

    /**
     * Generate a unique form ID
     */
    generateFormId(form) {
        return 'form_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Generate a unique token
     */
    generateToken() {
        return 'token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Show warning message
     */
    showWarning(message) {
        // Check if there's a toast/notification system
        if (typeof showNotification === 'function') {
            showNotification(message, 'warning');
        } else {
            // Fallback to alert (can be customized)
            console.warn(message);

            // Create a temporary toast notification
            const toast = document.createElement('div');
            toast.className = 'form-protection-toast';
            toast.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => toast.classList.add('show'), 10);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }

    /**
     * Manually reset a specific form (for AJAX forms)
     */
    resetForm(formId) {
        const form = document.querySelector(`form[data-form-id="${formId}"]`);
        if (form) {
            this.enableForm(form);
            this.submittingForms.delete(formId);

            // Regenerate token
            const token = this.generateToken();
            this.submissionTokens.set(formId, token);
            const tokenInput = form.querySelector('input[name="__form_token"]');
            if (tokenInput) {
                tokenInput.value = token;
            }
        }
    }

    /**
     * Get form ID from form element
     */
    getFormId(form) {
        return form.getAttribute('data-form-id');
    }
}

// Initialize global instance
const formProtection = new FormProtection();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormProtection;
}

/**
 * Button Click Protection
 * Prevents rapid multiple clicks on buttons
 */
class ButtonProtection {
    constructor() {
        this.clickedButtons = new Set();
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.protectButtons();
        });
    }

    protectButtons() {
        // Protect all buttons with data-protect attribute
        const buttons = document.querySelectorAll('button[data-protect], .btn[data-protect]');
        buttons.forEach(button => {
            this.protectButton(button);
        });
    }

    protectButton(button) {
        button.addEventListener('click', (e) => {
            const buttonId = button.id || this.generateButtonId();

            if (this.clickedButtons.has(buttonId)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            this.clickedButtons.add(buttonId);

            // Reset after 2 seconds
            setTimeout(() => {
                this.clickedButtons.delete(buttonId);
            }, 2000);
        });
    }

    generateButtonId() {
        return 'btn_' + Math.random().toString(36).substr(2, 9);
    }
}

// Initialize button protection
const buttonProtection = new ButtonProtection();

/**
 * AJAX Request Protection
 * Prevents duplicate AJAX requests
 */
class AjaxProtection {
    constructor() {
        this.activeRequests = new Map();
    }

    /**
     * Check if a request with this key is already in progress
     */
    isRequestActive(key) {
        return this.activeRequests.has(key);
    }

    /**
     * Mark request as started
     */
    startRequest(key, abortController = null) {
        this.activeRequests.set(key, {
            startTime: Date.now(),
            abortController: abortController
        });
    }

    /**
     * Mark request as completed
     */
    endRequest(key) {
        this.activeRequests.delete(key);
    }

    /**
     * Cancel a request
     */
    cancelRequest(key) {
        const request = this.activeRequests.get(key);
        if (request && request.abortController) {
            request.abortController.abort();
        }
        this.endRequest(key);
    }

    /**
     * Generate request key from URL and data
     */
    generateKey(url, data = {}) {
        const dataString = JSON.stringify(data);
        return `${url}_${dataString}`;
    }
}

// Initialize global AJAX protection
const ajaxProtection = new AjaxProtection();

/**
 * Protected Fetch Wrapper
 */
async function protectedFetch(url, options = {}, requestKey = null) {
    const key = requestKey || ajaxProtection.generateKey(url, options.body);

    // Check if request is already active
    if (ajaxProtection.isRequestActive(key)) {
        console.warn(`Request to ${url} is already in progress`);
        throw new Error('DUPLICATE_REQUEST');
    }

    // Create abort controller
    const abortController = new AbortController();
    options.signal = abortController.signal;

    // Mark request as started
    ajaxProtection.startRequest(key, abortController);

    try {
        const response = await fetch(url, options);
        ajaxProtection.endRequest(key);
        return response;
    } catch (error) {
        ajaxProtection.endRequest(key);
        throw error;
    }
}

// Add CSS for toast notifications
const style = document.createElement('style');
style.textContent = `
    .form-protection-toast {
        position: fixed;
        top: 80px;
        right: 20px;
        background: #f59e0b;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 600;
        z-index: 10000;
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .form-protection-toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .form-protection-toast i {
        font-size: 18px;
    }

    .btn-loading {
        position: relative;
        cursor: not-allowed !important;
    }

    .form-submitting {
        position: relative;
    }

    .form-submitting::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(1px);
        z-index: 1;
        border-radius: inherit;
    }

    /* Loading spinner animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .fa-spinner.fa-spin {
        animation: spin 1s linear infinite;
    }

    /* Prevent text selection during form submission */
    .form-submitting * {
        user-select: none;
    }
`;
document.head.appendChild(style);
