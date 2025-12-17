// 🌟 SMART CENTRALIZED CONFIGURATION untuk semua halaman Starlink
// File: /www/wwwroot/octonet.my.id/starlink/config.js

const CONFIG = {
    // 🚀 MAIN APPS SCRIPT URL - HANYA PERLU UPDATE DI SINI!
    // Jika semua halaman (dashboard, checker, payment) pakai Apps Script yang SAMA:
    API_URL: 'https://script.google.com/macros/s/AKfycbwNSbTUCNmBdmSOUUdnmSjGG2YiZzkbWmSJN4nxFVT5HdIwkQRai7PgrMCVyCN0vpRk/exec',
    
    // 🎯 OPTIONAL: Apps Script URLs yang BERBEDA (jika ada)
    // Hanya isi ini jika ada halaman yang pakai Apps Script berbeda
    CUSTOM_APIS: {
        // Approval system uses separate URL (if different deployment)
        'approval': 'https://script.google.com/macros/s/AKfycbyqA10mgTuwccs_At5EXt2AE8kGeuzaZhsYoXwTckGQwHB4nzzg_-ZaIN5FlELyz3CL/exec'
        // dashboard: 'https://script.google.com/macros/s/DIFFERENT_DASHBOARD_ID/exec',  // Uncomment jika beda
        // payment: 'https://script.google.com/macros/s/DIFFERENT_PAYMENT_ID/exec',     // Uncomment jika beda
        // admin: 'https://script.google.com/macros/s/DIFFERENT_ADMIN_ID/exec',         // Uncomment jika beda
    },
    
 // 🌐 DOMAIN & SECURITY SETTINGS
    WEBSITE_DOMAIN: 'https://octolink.id',
    ALLOWED_ORIGINS: [
        'https://octolink.id',
        'https://www.octolink.id',
        'http://localhost',
        'http://127.0.0.1'
    ],
    
    // ⚙️ API SETTINGS
    API_TIMEOUT: 30000,        // 30 detik timeout
    RETRY_ATTEMPTS: 3,         // 3x retry jika gagal
    VALIDATE_ORIGIN: true,     // Validasi origin untuk security
    FORCE_MOBILE_LAYOUT: true, // Force mobile layout untuk screen < 768px
    
    // 🐛 DEBUG & ANALYTICS
    DEBUG_MODE: false,         // Set true untuk development, false untuk production
    TRACK_EVENTS: false,       // Set true jika mau track form submissions
    ENABLE_LOGGING: true,      // Enable/disable console logging
    
    // 📊 FEATURE FLAGS
    FEATURES: {
        MULTI_KIT_SUPPORT: true,      // Enable multi-kit selection
        DUPLICATE_WARNING: true,      // Show duplicate warnings
        AUTO_VALIDATION: true,        // Auto-validate KIT on input
        MOBILE_OPTIMIZATION: true,    // Enable mobile-specific features
        OFFLINE_MODE: false,          // Enable offline capabilities (future)
        ADVANCED_SEARCH: true,        // Enable advanced search features
        EXPORT_FUNCTIONS: false       // Enable data export features
    },
    
    // 🎨 UI THEMES & CUSTOMIZATION
    THEME: {
        PRIMARY_COLOR: '#3b82f6',     // Blue primary color
        SUCCESS_COLOR: '#10b981',     // Green success color
        WARNING_COLOR: '#f59e0b',     // Yellow warning color
        ERROR_COLOR: '#ef4444',       // Red error color
        DARK_MODE: true               // Enable dark theme
    },
    
    // 📄 PAGE-SPECIFIC SETTINGS
    PAGES: {
        DASHBOARD: {
            REFRESH_INTERVAL: 30000,  // Auto-refresh setiap 30 detik
            SHOW_STATISTICS: true,
            ENABLE_CHARTS: true
        },
        CHECKER: {
            SEARCH_DELAY: 800,        // Debounce delay untuk search
            MAX_RESULTS: 50,          // Maksimal hasil search
            ENABLE_EXPORT: false
        },
        PAYMENT_FORM: {
            VALIDATION_DELAY: 800,    // Debounce delay untuk KIT validation
            AUTO_SAVE_DRAFT: false,   // Auto-save form draft
            CONFIRMATION_REQUIRED: true, // Require confirmation before submit
            EMAIL_NOTIFICATIONS: true    // Send email notifications
        },
        ADMIN: {
            BULK_OPERATIONS: true,    // Enable bulk operations
            ADVANCED_FILTERS: true,   // Enable advanced filtering
            DATA_BACKUP: true         // Enable data backup features
        }
    },
    
    // 🔧 HELPER FUNCTIONS
    log: function(message, data = null) {
        if (this.DEBUG_MODE && this.ENABLE_LOGGING) {
            console.log('[STARLINK]', message, data || '');
        }
    },
    
    error: function(message, error = null) {
        if (this.ENABLE_LOGGING) {
            console.error('[STARLINK ERROR]', message, error || '');
        }
    },
    
    warn: function(message, data = null) {
        if (this.ENABLE_LOGGING) {
            console.warn('[STARLINK WARNING]', message, data || '');
        }
    },
    
    // 🌐 GET CURRENT ORIGIN
    getCurrentOrigin: function() {
        return window.location.origin;
    },
    
    // 🎯 SMART API URL GETTER
    getApiUrl: function(context = 'default') {
        // 1. Cek apakah ada custom API untuk context ini
        if (this.CUSTOM_APIS[context]) {
            this.log(`Using custom API for ${context}:`, this.CUSTOM_APIS[context]);
            return this.CUSTOM_APIS[context];
        }
        
        // 2. Fallback ke main API_URL (99% case akan ke sini)
        this.log(`Using main API for ${context}:`, this.API_URL);
        return this.API_URL;
    },
    
    // 🔍 GET PAGE SETTINGS
    getPageSettings: function(pageName) {
        return this.PAGES[pageName.toUpperCase()] || {};
    },
    
    // 🎨 GET THEME COLOR
    getThemeColor: function(colorType) {
        return this.THEME[colorType.toUpperCase() + '_COLOR'] || this.THEME.PRIMARY_COLOR;
    },
    
    // ✅ CHECK FEATURE FLAG
    isFeatureEnabled: function(featureName) {
        return this.FEATURES[featureName.toUpperCase()] || false;
    },
    
    // 🔧 VALIDATE CONFIGURATION
    validate: function() {
        const issues = [];
        
        // Check main API URL
        if (!this.API_URL || this.API_URL.includes('YOUR_SCRIPT_ID')) {
            issues.push('⚠️ API_URL belum diset dengan benar');
        }
        
        // Check domain
        if (!this.WEBSITE_DOMAIN || this.WEBSITE_DOMAIN.includes('yourdomain.com')) {
            issues.push('⚠️ WEBSITE_DOMAIN belum diset dengan benar');
        }
        
        // Check critical features
        if (!this.ALLOWED_ORIGINS || this.ALLOWED_ORIGINS.length === 0) {
            issues.push('⚠️ ALLOWED_ORIGINS tidak dikonfigurasi');
        }
        
        // Validate custom APIs if any
        Object.keys(this.CUSTOM_APIS).forEach(context => {
            const url = this.CUSTOM_APIS[context];
            if (url && url.includes('YOUR_SCRIPT_ID')) {
                issues.push(`⚠️ CUSTOM_APIS.${context} belum diset dengan benar`);
            }
        });
        
        if (issues.length > 0) {
            console.warn('🔧 KONFIGURASI ISSUES:', issues);
            return false;
        }
        
        this.log('✅ Konfigurasi valid');
        return true;
    },
    
    // 🚀 INITIALIZE CONFIG
    init: function() {
        this.log('🚀 Initializing Starlink configuration...');
        
        // Validate config
        const isValid = this.validate();
        
        // Set global reference
        if (typeof window !== 'undefined') {
            window.STARLINK_CONFIG = this;
            window.CONFIG = this; // Backward compatibility
        }
        
        // Auto-detect page context
        const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
        if (currentPath.includes('/report/') || currentPath.includes('/payment/')) {
            this.currentContext = 'payment';
        } else if (currentPath.includes('/dashboard/')) {
            this.currentContext = 'dashboard';
        } else if (currentPath.includes('/checker/')) {
            this.currentContext = 'checker';
        } else if (currentPath.includes('/admin/')) {
            this.currentContext = 'admin';
        } else {
            this.currentContext = 'default';
        }
        
        this.log(`📍 Detected context: ${this.currentContext}`);
        this.log(`🔗 API URL for this context: ${this.getApiUrl(this.currentContext)}`);
        this.log('✅ Configuration initialized');
        
        return isValid;
    },
    
    // 🎯 UTILITY: Add custom API (for future use)
    setCustomApi: function(context, url) {
        this.CUSTOM_APIS[context] = url;
        this.log(`🔧 Custom API set for ${context}:`, url);
    },
    
    // 📊 UTILITY: Get all API URLs (for debugging)
    getAllApiUrls: function() {
        const urls = {
            main: this.API_URL,
            custom: this.CUSTOM_APIS
        };
        
        // Add context-specific URLs
        ['dashboard', 'checker', 'payment', 'admin'].forEach(context => {
            urls[context] = this.getApiUrl(context);
        });
        
        return urls;
    }
};

// 🏃‍♂️ AUTO-INITIALIZE ON LOAD
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            CONFIG.init();
        });
    } else {
        CONFIG.init();
    }
} else {
    // Node.js environment atau server-side
    CONFIG.init();
}

// 🌐 EXPORT FOR DIFFERENT ENVIRONMENTS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
}

if (typeof window !== 'undefined') {
    window.CONFIG = CONFIG; // Backward compatibility
}

/* 
🎯 USAGE EXAMPLES:

// ✅ NORMAL USE (99% cases):
CONFIG.getApiUrl('payment')   // Returns main API_URL
CONFIG.getApiUrl('dashboard') // Returns main API_URL  
CONFIG.getApiUrl('checker')   // Returns main API_URL

// ✅ CUSTOM API (jika ada yang beda):
CONFIG.CUSTOM_APIS.payment = 'https://script.google.com/macros/s/DIFFERENT_ID/exec';
CONFIG.getApiUrl('payment')   // Returns custom payment URL

// ✅ UPDATE MAIN API (untuk semua halaman):
CONFIG.API_URL = 'https://script.google.com/macros/s/NEW_SCRIPT_ID/exec';

// ✅ DEBUG:
console.log(CONFIG.getAllApiUrls());

*/