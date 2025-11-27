// 📅 INDONESIAN DATE FORMATTER
function formatDateIndonesian(dateString) {
    const date = new Date(dateString);
    
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    const dayName = days[date.getDay()];
    const day = date.getDate();
    const monthName = months[date.getMonth()];
    const year = date.getFullYear();
    
    return `${dayName}, ${day} ${monthName} ${year}`;
}

// 📅 LOCAL DATE TO ISO STRING (Timezone-safe)
function dateToLocalISOString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// 🔧 CUSTOM DATE PICKER SETUP
function setupCustomDatePicker() {
    const hiddenInput = document.getElementById('tanggalPembayaran');
    const dateDisplay = document.getElementById('dateDisplay');
    const dateText = document.getElementById('dateText');
    const indonesiaDisplay = document.getElementById('tanggalIndonesia');
    const modal = document.getElementById('datePickerModal');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarGrid = document.getElementById('calendarGrid');
    const todayBtn = document.getElementById('datePickerToday');
    const cancelBtn = document.getElementById('datePickerCancel');
    
    if (!hiddenInput || !dateDisplay || !modal) {
        CONFIG.log('⚠️ Custom date picker elements not found');
        return;
    }
    
    let currentDate = new Date();
    let selectedDate = new Date();
    
    // Populate year selector
    const currentYear = new Date().getFullYear();
    for (let year = currentYear - 5; year <= currentYear + 5; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) option.selected = true;
        yearSelect.appendChild(option);
    }
    
    // Set default values
    monthSelect.value = currentDate.getMonth();
    yearSelect.value = currentDate.getFullYear();
    
    // Set today as default with proper local timezone
    const today = new Date();
    const todayIsoString = dateToLocalISOString(today);
    
    console.log('📅 Today ISO string (local):', todayIsoString);
    
    selectedDate = today;
    hiddenInput.value = todayIsoString;
    dateText.textContent = formatDateIndonesian(todayIsoString);
    indonesiaDisplay.textContent = formatDateIndonesian(todayIsoString);
    
    // Generate calendar
    function generateCalendar(month, year) {
        calendarGrid.innerHTML = '';
        
        console.log('📅 Generating calendar for:', year, month, 'Selected date:', selectedDate);
        
        // Day headers
        const dayHeaders = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        dayHeaders.forEach(day => {
            const header = document.createElement('div');
            header.textContent = day;
            header.style.cssText = 'padding: 8px; text-align: center; font-weight: bold; color: #94a3b8; font-size: 12px;';
            calendarGrid.appendChild(header);
        });
        
        // Calendar days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        console.log('📅 Calendar info - First day:', firstDay, 'Days in month:', daysInMonth);
        
        // Empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            calendarGrid.appendChild(emptyCell);
        }
        
        // Month days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.textContent = day;
            dayCell.style.cssText = `
                padding: 8px; 
                text-align: center; 
                cursor: pointer; 
                border-radius: 4px; 
                color: #f1f5f9;
                transition: all 0.2s ease;
            `;
            
            // Store day value in data attribute to avoid closure issues
            dayCell.setAttribute('data-day', day);
            dayCell.setAttribute('data-month', month);
            dayCell.setAttribute('data-year', year);
            
            // Check if this is selected date
            const cellDate = new Date(year, month, day);
            if (cellDate.toDateString() === selectedDate.toDateString()) {
                dayCell.style.background = '#3b82f6';
                dayCell.style.color = 'white';
                dayCell.classList.add('selected');
            }
            
            // Check if this is today
            if (cellDate.toDateString() === new Date().toDateString()) {
                dayCell.style.border = '2px solid #10b981';
                dayCell.classList.add('today');
            }
            
            // Fix closure issue by using data attributes
            dayCell.addEventListener('click', function() {
                const clickedDay = parseInt(this.getAttribute('data-day'));
                const clickedMonth = parseInt(this.getAttribute('data-month'));
                const clickedYear = parseInt(this.getAttribute('data-year'));
                
                console.log('📅 Clicked date components:', clickedYear, clickedMonth, clickedDay);
                console.log('📅 Creating new Date:', new Date(clickedYear, clickedMonth, clickedDay));
                
                // Clear all selected styles first
                document.querySelectorAll('#calendarGrid > div').forEach(cell => {
                    cell.classList.remove('selected');
                    if (!cell.classList.contains('today')) {
                        cell.style.background = 'transparent';
                        cell.style.color = '#f1f5f9';
                    }
                });
                
                // Add selected style to clicked cell
                this.classList.add('selected');
                this.style.background = '#3b82f6';
                this.style.color = 'white';
                
                setSelectedDate(new Date(clickedYear, clickedMonth, clickedDay));
                hideModal();
                window.selectedDate = selectedDate;
            });
            
            dayCell.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = '#475569';
                }
            });
            
            dayCell.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = 'transparent';
                }
            });
            
            calendarGrid.appendChild(dayCell);
        }
    }
    
    function setSelectedDate(date) {
        selectedDate = new Date(date);
        
        // Use timezone-safe local ISO string
        const isoString = dateToLocalISOString(selectedDate);
        const indonesianFormat = formatDateIndonesian(isoString);
        
        console.log('📅 setSelectedDate called with:', date);
        console.log('📅 selectedDate object:', selectedDate);
        console.log('📅 ISO string (local):', isoString);
        console.log('📅 Indonesian format:', indonesianFormat);
        
        hiddenInput.value = isoString;
        dateText.textContent = indonesianFormat;
        indonesiaDisplay.textContent = indonesianFormat;
        
        // Trigger change events for form validation
        hiddenInput.dispatchEvent(new Event('change'));
        dateDisplay.dispatchEvent(new Event('change'));
        
        CONFIG.log('📅 Date selected:', isoString, '→', indonesianFormat);
    }
    
    function showModal() {
        modal.style.display = 'flex';
        // Update month/year selectors to match selected date
        monthSelect.value = selectedDate.getMonth();
        yearSelect.value = selectedDate.getFullYear();
        generateCalendar(selectedDate.getMonth(), selectedDate.getFullYear());
    }
    
    function hideModal() {
        modal.style.display = 'none';
    }
    
    // Event listeners
    dateDisplay.addEventListener('click', showModal);
    
    monthSelect.addEventListener('change', function() {
        generateCalendar(parseInt(this.value), parseInt(yearSelect.value));
    });
    
    yearSelect.addEventListener('change', function() {
        generateCalendar(parseInt(monthSelect.value), parseInt(this.value));
    });
    
    todayBtn.addEventListener('click', function() {
        const today = new Date();
        console.log('📅 Today button clicked, today is:', today);
        console.log('📅 Today ISO (local):', dateToLocalISOString(today));
        setSelectedDate(today);
        hideModal();
    });
    
    cancelBtn.addEventListener('click', hideModal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) hideModal();
    });
    
    // Keyboard support
    dateDisplay.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            showModal();
        }
    });
    
    // Visual feedback
    dateDisplay.addEventListener('mouseenter', function() {
        this.style.borderColor = '#3b82f6';
        this.style.transform = 'translateY(-1px)';
        this.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.15)';
    });
    
    dateDisplay.addEventListener('mouseleave', function() {
        this.style.borderColor = '#475569';
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = 'none';
    });
    
    CONFIG.log('✅ Custom date picker initialized successfully');
}

// 🔧 GET FORM DATE VALUE
function getFormDateValue() {
    const hiddenInput = document.getElementById('tanggalPembayaran');
    
    if (hiddenInput && hiddenInput.value) {
        return hiddenInput.value; // Format ISO untuk backend
    }
    
    return '';
}

// 🔧 FORMAT DATE FOR DISPLAY (DD/MM/YYYY)
function formatDateForDisplay(isoDateString) {
    if (!isoDateString) return '-';
    
    const date = new Date(isoDateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

// 🌐 ENHANCED APPS SCRIPT API CLASS
class AppsScriptAPI {
    constructor() {
        this.baseURL = CONFIG.getApiUrl('payment');
        this.timeout = CONFIG.API_TIMEOUT;
        this.retryAttempts = CONFIG.RETRY_ATTEMPTS;
        this.pendingRequests = new Map(); // Track pending requests
    }

    addCacheBuster(params) {
        params._cb = Date.now();
        params._r = Math.random().toString(36).substr(2, 9);
        return params;
    }

    // Request deduplication key
    getRequestKey(params, method) {
        const keyData = {
            action: params.action,
            method: method,
            requestId: params.formData?.requestId || params.requestId
        };
        return JSON.stringify(keyData);
    }

    // Enhanced API call with deduplication
    async apiCall(params, method = 'GET', retryCount = 0) {
        try {
            // Check for duplicate requests (only for submit)
            if (params.action === 'submitPaymentMultiKit' && params.formData?.requestId) {
                const requestKey = this.getRequestKey(params, method);
                
                if (this.pendingRequests.has(requestKey)) {
                    console.log('🚫 Duplicate request detected, ignoring:', requestKey);
                    throw new Error('Duplicate request - submission already in progress');
                }
                
                this.pendingRequests.set(requestKey, true);
                
                try {
                    const result = await this.performApiCall(params, method, retryCount);
                    this.pendingRequests.delete(requestKey);
                    return result;
                } catch (error) {
                    this.pendingRequests.delete(requestKey);
                    throw error;
                }
            } else {
                return await this.performApiCall(params, method, retryCount);
            }
            
        } catch (error) {
            CONFIG.error(`API Call failed (attempt ${retryCount + 1}):`, error);
            
            if (error.message.includes('Duplicate request')) {
                throw error;
            }
            
            if (retryCount < this.retryAttempts) {
                CONFIG.log(`Retrying in 2 seconds... (${retryCount + 1}/${this.retryAttempts})`);
                await new Promise(resolve => setTimeout(resolve, 2000));
                return this.apiCall(params, method, retryCount + 1);
            }
            
            throw error;
        }
    }

    async performApiCall(params, method, retryCount) {
        CONFIG.log(`API Call [${method}] (attempt ${retryCount + 1}):`, params);
        
        if (CONFIG.VALIDATE_ORIGIN) {
            params.origin = CONFIG.getCurrentOrigin();
        }

        params = this.addCacheBuster(params);
        CONFIG.log('🚀 Using JSONP method directly...');
        
        if (method === 'POST') {
            CONFIG.log('Converting POST to GET for CORS bypass...');
            const getParams = {
                action: params.action,
                data: JSON.stringify(params.formData || params),
                method: 'POST',
                origin: params.origin,
                _cb: params._cb,
                _r: params._r
            };
            
            const result = await this.jsonpRequest(getParams);
            CONFIG.log('✅ POST-to-GET success:', result);
            return result;
        } else {
            const result = await this.jsonpRequest(params);
            CONFIG.log('✅ JSONP success:', result);
            return result;
        }
    }

    async jsonpRequest(params) {
        return new Promise((resolve, reject) => {
            const callbackName = 'jsonp_callback_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            CONFIG.log('🌐 JSONP Request:', params, 'Callback:', callbackName);
            
            params.callback = callbackName;
            
            const script = document.createElement('script');
            const urlParams = new URLSearchParams(params);
            script.src = this.baseURL + '?' + urlParams.toString();
            
            CONFIG.log('🔗 JSONP URL:', script.src);
            
            const timeoutId = setTimeout(() => {
                cleanup();
                CONFIG.error('❌ JSONP timeout after', this.timeout + 'ms');
                reject(new Error('JSONP request timeout - server might be slow'));
            }, this.timeout + 5000);
            
            const cleanup = () => {
                clearTimeout(timeoutId);
                if (script.parentNode) {
                    script.parentNode.removeChild(script);
                }
                delete window[callbackName];
                CONFIG.log('🧹 JSONP cleanup completed for:', callbackName);
            };
            
            window[callbackName] = (data) => {
                CONFIG.log('📥 JSONP Response received:', data);
                cleanup();
                
                if (data) {
                    resolve(data);
                } else {
                    resolve({ status: 'success', message: 'Request completed' });
                }
            };
            
            script.onerror = (error) => {
                CONFIG.error('❌ JSONP script error:', error);
                cleanup();
                
                resolve({ 
                    status: 'success', 
                    message: 'Request sent but response unclear',
                    warning: 'Script load error but data may have been processed'
                });
            };
            
            document.head.appendChild(script);
            CONFIG.log('📤 JSONP script added to DOM');
        });
    }

    async getClientsBySheet(sheetName) {
        let params = {
            action: 'getClientsBySheet',
            sheetName: sheetName
        };
        params = this.addCacheBuster(params);
        return await this.apiCall(params, 'GET');
    }

    async validateClientByName(clientName) {
        const { month, year } = getSelectedMonthYear();
        
        console.log('🐛 DEBUG API validateClientByName with month/year:', month, year);
        
        let params = {
            action: 'validateClientByName',
            clientName: clientName,
            month: month,
            year: year
        };
        
        params = this.addCacheBuster(params);
        
        console.log('📤 API params with cache buster:', params);
        
        const result = await this.apiCall(params, 'GET');
        
        console.log('📥 RAW validateClientByName response:', JSON.stringify(result, null, 2));
        console.log('📥 Response timestamp:', result?.timestamp);
        console.log('📥 Response duplicate status:', result?.duplicate?.status);
        console.log('📥 Response duplicate hasDuplicate:', result?.duplicate?.hasDuplicate);
        console.log('📥 Response duplicate duplicateKits:', result?.duplicate?.duplicateKits);
        
        return result;
    }

    async validateKitMulti(kitNumber, selectedMonth = null, selectedYear = null) {
        let params = {
            action: 'validateKitMulti',
            kit: kitNumber
        };
        
        console.log('🐛 DEBUG validateKitMulti called with:');
        console.log('  - kitNumber:', kitNumber);
        console.log('  - selectedMonth:', selectedMonth);
        console.log('  - selectedYear:', selectedYear);
        
        if (selectedMonth && selectedYear) {
            params.month = selectedMonth;
            params.year = selectedYear;
            console.log('🐛 DEBUG Adding month/year to params');
        } else {
            console.log('🐛 DEBUG No month/year provided, will use current month');
        }
        
        params = this.addCacheBuster(params);
        
        console.log('🐛 DEBUG Final API params:', params);
        
        const result = await this.apiCall(params, 'GET');
        console.log('🐛 DEBUG API response:', result);
        
        return result;
    }

    // ENHANCED: Submit Payment with unique request ID
    async submitPayment(formData) {
        console.log('📤 API submitPayment called with Request ID:', formData.requestId);
        
        let params = {
            action: 'submitPaymentMultiKit',
            formData: formData
        };
        
        const result = await this.apiCall(params, 'POST');
        
        console.log('📥 API submitPayment result:', result);
        return result;
    }

    async checkDuplicates(month, year) {
        let params = {
            action: 'checkDuplicates',
            month: month,
            year: year
        };
        params = this.addCacheBuster(params);
        return await this.apiCall(params, 'GET');
    }

    async testConnection() {
        try {
            let params = {
                action: 'validateKitMulti',
                kit: 'TESTCONNECTION'
            };
            params = this.addCacheBuster(params);
            
            const result = await this.apiCall(params, 'GET');
            
            if (result && (result.status || result.validation)) {
                return { status: 'connected', result };
            } else {
                return { status: 'disconnected', error: 'Invalid response format' };
            }
            
        } catch (error) {
            return { status: 'disconnected', error: error.message };
        }
    }
}

// Initialize API instance
const api = new AppsScriptAPI();

// 🎯 GLOBAL VARIABLES
let elements = {};
let validationResult = null;
let isKitValid = false;
let availableKits = [];
let selectedKits = [];
let validationTimeout;
let allClientNames = [];
let filteredClientNames = [];
let selectedClientIndex = -1;

// 🚫 SUBMISSION STATE MANAGEMENT - Key untuk fix double POST
let isSubmitting = false;
let submitTimeout = null;
let submitStartTime = null;
let loadingProgressInterval = null;

// 🔒 TRACK ACTIVE SUBMISSION REQUEST - Prevent double POST
let activeSubmissionRequest = null;
let currentRequestId = null;

// 🎯 TRACK SUBMITTED REQUEST IDs - Prevent duplicate submissions even on retry
const submittedRequestIds = new Set();
const REQUEST_ID_CACHE_DURATION = 300000; // 5 minutes

// 🚀 INITIALIZE FORM
function initializeForm() {
    // Get all elements
    elements = {
        nomorKitInput: document.getElementById('nomorKit'),
        namaClientDisplay: document.getElementById('namaClient'),
        clientNameText: document.getElementById('clientNameText'),
        kitLoading: document.getElementById('kitLoading'),
        kitValidation: document.getElementById('kitValidation'),
        kitSelectionSection: document.getElementById('kitSelectionSection'),
        kitList: document.getElementById('kitList'),
        kitSummary: document.getElementById('kitSummary'),
        selectAllBtn: document.getElementById('selectAllBtn'),
        deselectAllBtn: document.getElementById('deselectAllBtn'),
        previewBtn: document.getElementById('previewBtn'),
        submitBtn: document.getElementById('submitBtn'),
        previewSection: document.getElementById('previewSection'),
        form: document.getElementById('paymentForm'),
        warningBanner: document.getElementById('warningBanner'),
        errorBanner: document.getElementById('errorBanner'),
        successBanner: document.getElementById('successBanner'),
        connectionStatus: document.getElementById('connectionStatus'),
        clientNameSearch: document.getElementById('clientNameSearch'),
        clientSearchResults: document.getElementById('clientSearchResults'),
        searchResultsList: document.getElementById('searchResultsList'),
        resultsCount: document.getElementById('resultsCount'),
        searchNoResults: document.getElementById('searchNoResults'),
        searchMode: document.getElementById('searchMode'),
        kitNumberSection: document.getElementById('kitNumberSection'),
        clientNameSection: document.getElementById('clientNameSection'),
        clientNameSelect: document.getElementById('clientNameSelect'),
        clientNamesLoading: document.getElementById('clientNamesLoading'),
        duplicateCheckBtn: document.getElementById('duplicateCheckBtn'),
        duplicateModal: document.getElementById('duplicateModal'),
        modalCloseBtn: document.getElementById('modalCloseBtn'),
        duplicateMonth: document.getElementById('duplicateMonth'),
        duplicateYear: document.getElementById('duplicateYear'),
        checkDuplicateBtn: document.getElementById('checkDuplicateBtn'),
        modalLoading: document.getElementById('modalLoading'),
        duplicateResults: document.getElementById('duplicateResults'),
        duplicateSummary: document.getElementById('duplicateSummary'),
        duplicateTable: document.getElementById('duplicateTable')
    };

    // Set tanggal hari ini sebagai default
    document.getElementById('tanggalPembayaran').value = new Date().toISOString().split('T')[0];

    // Initialize modern dropdowns
    initializeModernSearchDropdown();
    initializeModernPaymentDropdown();
        
    // Setup event listeners
    setupEventListeners();
    
    // Setup custom date picker
    setupCustomDatePicker();
    
    // Apply mobile responsive
    forceMobileLayout();
    
    // Initialize duplicate checker
    initializeDuplicateChecker();
    
    CONFIG.log('✅ Form initialized successfully');
}

// 🆕 INITIALIZE DUPLICATE CHECKER
function initializeDuplicateChecker() {
    if (!elements.duplicateYear || !elements.duplicateMonth) {
        CONFIG.log('⚠️ Duplicate checker elements not found, skipping initialization');
        return;
    }
    
    const currentYear = new Date().getFullYear();
    const yearSelect = elements.duplicateYear;
    
    for (let year = 2020; year <= currentYear + 2; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) {
            option.selected = true;
        }
        yearSelect.appendChild(option);
    }
    
    const currentMonth = new Date().getMonth() + 1;
    elements.duplicateMonth.value = currentMonth;
    
    CONFIG.log('✅ Duplicate checker initialized');
}

// 🔗 ENHANCED TEST CONNECTION
async function testConnection() {
    const statusEl = elements.connectionStatus;
    
    try {
        statusEl.style.opacity = '0';
        statusEl.style.transition = 'all 0.5s ease-in-out';
        statusEl.textContent = '🔄 Testing Connection...';
        statusEl.className = 'connection-status testing';
        statusEl.style.display = 'block';
        
        setTimeout(() => {
            statusEl.style.opacity = '1';
        }, 100);
        
        if (!CONFIG.API_URL || CONFIG.API_URL.includes('YOUR_SCRIPT_ID')) {
            throw new Error('Apps Script URL belum dikonfigurasi dengan benar');
        }
        
        CONFIG.log('Testing connection to:', CONFIG.getApiUrl('payment'));
        
        const result = await api.testConnection();
        
        if (result.status === 'connected') {
            statusEl.style.opacity = '0';
            
            setTimeout(() => {
                statusEl.textContent = '✅ Connected to Google Sheets (Separate File)';
                statusEl.className = 'connection-status connected';
                statusEl.style.opacity = '1';
                
                CONFIG.log('✅ Connection test successful');
                
                setTimeout(() => {
                    statusEl.style.opacity = '0';
                    statusEl.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        statusEl.style.display = 'none';
                        statusEl.style.transform = 'translateY(0)';
                        statusEl.style.opacity = '1';
                    }, 500);
                }, 4000);
                
            }, 300);
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        statusEl.style.opacity = '0';
        
        setTimeout(() => {
            statusEl.textContent = '❌ Connection Failed: ' + error.message;
            statusEl.className = 'connection-status disconnected';
            statusEl.style.opacity = '1';
            
            CONFIG.error('Connection test failed:', error);
            
            showBanner(`⚠️ Koneksi gagal: ${error.message}. 
                       Kemungkinan: 1) Apps Script URL salah, 2) Web App belum di-deploy, 3) Permission belum diset 'Anyone'.
                       Cek konfigurasi di config.js dan re-deploy Web App.`, 'error');
                       
            setTimeout(() => {
                statusEl.style.opacity = '0';
                statusEl.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    statusEl.style.display = 'none';
                    statusEl.style.transform = 'translateY(0)';
                    statusEl.style.opacity = '1';
                }, 500);
            }, 8000);
            
        }, 300);
    }
}

// 🎧 ENHANCED SETUP EVENT LISTENERS
function setupEventListeners() {
    // Search mode change listener
    elements.searchMode.addEventListener('change', handleSearchModeChange);

    // 🆕 NEW: Add KIT button - one KIT at a time
    const addKitBtn = document.getElementById('addKitBtn');
    if (addKitBtn) {
        addKitBtn.addEventListener('click', async function() {
            const kitInput = elements.nomorKitInput;
            const kitNumber = kitInput.value.trim();

            if (kitNumber.length < 3) {
                showBanner('❌ Nomor KIT/SN minimal 3 karakter', 'error');
                return;
            }

            // Show loading
            const loadingEl = document.getElementById('addKitLoading');
            if (loadingEl) loadingEl.style.display = 'block';
            addKitBtn.disabled = true;

            try {
                // Validate this single KIT
                const selectedDate = getFormDateValue();
                let selectedMonth = null;
                let selectedYear = null;

                if (selectedDate) {
                    const dateParts = selectedDate.split('-');
                    selectedMonth = parseInt(dateParts[1]);
                    selectedYear = parseInt(dateParts[0]);
                }

                const data = await api.validateKitMulti(kitNumber, selectedMonth, selectedYear);

                if (data && data.validation && data.validation.status === 'found') {
                    // 🔧 FIX: Store client name from current validation
                    const currentClientName = data.validation.data.nama;

                    // Add KITs from validation to availableKits
                    if (data.validation.data.allKits) {
                        data.validation.data.allKits.forEach(kit => {
                            // Check if KIT already exists
                            const exists = availableKits.some(k => k.kitNumber === kit.kitNumber);
                            if (!exists) {
                                availableKits.push({
                                    ...kit,
                                    clientName: currentClientName, // 🆕 FIX: Store client name per-KIT
                                    isSelected: false,
                                    nominal: 0,
                                    tipePembayaran: '', // 🆕 NEW: Per-KIT payment type
                                    isDuplicate: data.duplicate?.duplicateKits?.includes(kit.kitNumber) || false
                                });
                            }
                        });
                    }

                    // Update validation result for client name (first time only for backward compat)
                    if (!validationResult) {
                        validationResult = data;
                        isKitValid = true;
                        elements.clientNameText.textContent = currentClientName;
                    }

                    // Clear input and show success
                    kitInput.value = '';
                    showBanner(`✅ ${data.validation.data.allKits.length} KIT berhasil ditambahkan!`, 'success');

                    // Update display
                    showKitSelection();
                    updateButtonStates();

                } else {
                    showBanner('❌ KIT/SN tidak ditemukan dalam database', 'error');
                }

            } catch (error) {
                CONFIG.error('Error validating KIT:', error);
                showBanner('❌ Error validasi: ' + error.message, 'error');
            } finally {
                // Hide loading
                if (loadingEl) loadingEl.style.display = 'none';
                addKitBtn.disabled = false;
            }
        });
    }

    // Enter key on KIT input to add
    elements.nomorKitInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const addKitBtn = document.getElementById('addKitBtn');
            if (addKitBtn) addKitBtn.click();
        }
    });   

    // Format rupiah input
    const nominalInput = document.getElementById('nominal');
    nominalInput.addEventListener('input', handleNominalInput);
    nominalInput.addEventListener('focus', handleNominalFocus);
    nominalInput.addEventListener('blur', handleNominalBlur);

    // Select All / Deselect All buttons
    elements.selectAllBtn.addEventListener('click', selectAllKits);
    elements.deselectAllBtn.addEventListener('click', deselectAllKits);

    // Form field changes for custom date picker
    const dateDisplay = document.getElementById('dateDisplay');
    if (dateDisplay) {
        dateDisplay.addEventListener('change', updatePreview);
    }
    
    // Other form field changes
    document.querySelectorAll('#tipePembayaran, #nominal').forEach(field => {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
    });

    // Preview button with double-click protection
    elements.previewBtn.addEventListener('click', function(e) {
        if (this.disabled || isSubmitting) return;
        showPreview();
    });

    // 🚫 ENHANCED: Form submit with protection against double submission
    elements.form.addEventListener('submit', handleFormSubmit);
    
    // Additional: Prevent multiple clicks on submit button
    elements.submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (this.disabled || isSubmitting) {
            console.log('🚫 Submit button clicked but form is submitting or disabled');
            showSlowLoadingMessage();
            return false;
        }
        
        // Trigger form submit
        elements.form.dispatchEvent(new Event('submit'));
        return false;
    });

    // Duplicate checker event listeners
    if (elements.duplicateCheckBtn) {
        elements.duplicateCheckBtn.addEventListener('click', openDuplicateModal);
    }
    if (elements.modalCloseBtn) {
        elements.modalCloseBtn.addEventListener('click', closeDuplicateModal);
    }
    if (elements.checkDuplicateBtn) {
        elements.checkDuplicateBtn.addEventListener('click', performDuplicateCheck);
    }
    
    // Modal confirmation event listeners
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmationModalClose = document.getElementById('confirmationModalClose');
    
    if (confirmSubmitBtn) {
        confirmSubmitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Only check disabled status, not isSubmitting
            // isSubmitting will be set AFTER this click, inside confirmAndSubmit()
            if (this.disabled) {
                console.log('🚫 Confirm button is disabled');
                return false;
            }
            
            // Disable button immediately to prevent double click
            this.disabled = true;
            this.style.opacity = '0.6';
            this.style.cursor = 'not-allowed';
            
            console.log('✅ Confirm button clicked, starting submission...');
            confirmAndSubmit();
            return false;
        });
    }
    
    if (cancelSubmitBtn) {
        cancelSubmitBtn.addEventListener('click', closeConfirmationModal);
    }
    if (confirmationModalClose) {
        confirmationModalClose.addEventListener('click', closeConfirmationModal);
    }

    // Close modals by clicking outside (but not during submission)
    if (elements.duplicateModal) {
        elements.duplicateModal.addEventListener('click', function(e) {
            if (e.target === elements.duplicateModal && !isSubmitting) {
                closeDuplicateModal();
            }
        });
    }

    if (confirmationModal) {
        confirmationModal.addEventListener('click', function(e) {
            if (e.target.id === 'confirmationModal' && !isSubmitting) {
                closeConfirmationModal();
            }
        });
    }

    // ESC key handling (disabled during submission)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !isSubmitting) {
            if (elements.duplicateModal && elements.duplicateModal.style.display === 'flex') {
                closeDuplicateModal();
            }
            const confirmationModal = document.getElementById('confirmationModal');
            if (confirmationModal && confirmationModal.style.display === 'flex') {
                closeConfirmationModal();
            }
        }
    });

    // Mobile responsive
    setupMobileResponsive();
}

// 🚫 ENHANCED: Form submit handler dengan protection double POST
async function handleFormSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Double submission protection - check if ACTUALLY submitting (after modal confirmation)
    if (isSubmitting) {
        console.log('🚫 Submission already in progress, showing user feedback...');
        showSlowLoadingMessage();
        return false;
    }
    
    // Clear any existing timeout
    if (submitTimeout) {
        clearTimeout(submitTimeout);
        submitTimeout = null;
    }
    
    if (!isFormValid() || !isKitValid || selectedKits.length === 0) {
        showBanner('❌ Form belum valid, KIT belum tervalidasi, atau belum ada KIT yang dipilih!', 'error');
        return false;
    }
    
    // DON'T set isSubmitting here - only set after user confirms in modal
    // This allows the confirm button in modal to be clickable
    
    // Show confirmation modal
    showConfirmationModal();
    
    return false;
}

// 🌟 ENHANCED: Confirm and submit dengan loading lambat handling
async function confirmAndSubmit() {
    // Double check if already submitting
    if (isSubmitting && document.getElementById('confirmSubmitBtn').disabled) {
        console.log('🚫 Already processing submission, aborting...');
        showSlowLoadingMessage();
        return;
    }

    // 🚫 CRITICAL: Check if there's already an active submission with same data
    const tempRequestId = Date.now() + '_' + btoa(selectedKits.map(k => k.kitNumber).join()).slice(-8);
    if (currentRequestId === tempRequestId) {
        console.log('🚫 Duplicate submission attempt detected (same requestId), blocking...');
        showSlowLoadingMessage();
        return;
    }

    // 🚫 CRITICAL: Cancel any previous request if still pending
    if (activeSubmissionRequest) {
        console.log('⚠️ Cancelling previous pending request...');
        try {
            activeSubmissionRequest.abort();
        } catch (e) {
            console.log('Previous request already completed or failed');
        }
        activeSubmissionRequest = null;
    }

    console.log('🚀 Starting confirmAndSubmit...');

    // 🔧 CRITICAL FIX: Setup success detection for this submission
    // This ensures detection is ready when success banner appears
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.setupSuccessDetection === 'function') {
        window.stepperNav.setupSuccessDetection();
        console.log('👁️ Success detection initialized for this submission');
    }

    isSubmitting = true;
    submitStartTime = Date.now();
    
    // Disable all controls immediately
    disableAllSubmitControls();
    
    // Show progressive loading UI
    showProgressiveLoadingUI();
    
    // Start monitoring loading progress
    startLoadingProgressMonitor();
    
    try {
        // 🆕 NEW: Validate that all selected KITs have nominal AND payment type
        const kitsWithoutNominal = selectedKits.filter(kit => !kit.nominal || kit.nominal < 1);
        if (kitsWithoutNominal.length > 0) {
            const missingKits = kitsWithoutNominal.map(kit => kit.kitNumber).join(', ');
            throw new Error(`Nominal belum diisi atau kurang dari Rp 1 untuk KIT: ${missingKits}`);
        }

        const kitsWithoutPaymentType = selectedKits.filter(kit => !kit.tipePembayaran || kit.tipePembayaran === '');
        if (kitsWithoutPaymentType.length > 0) {
            const missingKits = kitsWithoutPaymentType.map(kit => kit.kitNumber).join(', ');
            throw new Error(`Tipe pembayaran belum dipilih untuk KIT: ${missingKits}`);
        }

        // Calculate total nominal from all selected KITs
        const totalNominal = selectedKits.reduce((sum, kit) => sum + (kit.nominal || 0), 0);
        const kitNumbers = selectedKits.map(kit => kit.kitNumber).join('\n');
        const kitPackages = selectedKits.map(kit => kit.paket).join('\n');

        // UNIQUE REQUEST ID untuk mencegah duplikasi - gunakan timestamp + hash KIT numbers
        const requestId = Date.now() + '_' + btoa(kitNumbers).slice(-8);

        // Store current requestId to prevent duplicate
        currentRequestId = requestId;

        // 🐛 DEBUG: Check values before creating formData
        const tanggalInput = document.getElementById('tanggalPembayaran');
        const tanggalValue = getFormDateValue();
        let namaValue = elements.clientNameText.textContent.trim();

        console.log('🐛 DEBUG - Values before submit:');
        console.log('  - tanggalInput element exists:', !!tanggalInput);
        console.log('  - tanggalInput.value:', tanggalInput ? tanggalInput.value : 'N/A');
        console.log('  - tanggalValue (from getFormDateValue):', tanggalValue);
        console.log('  - nama:', namaValue);
        console.log('  - selectedKits count:', selectedKits.length);

        if (!tanggalValue) {
            console.error('❌ Tanggal validation failed!');
            console.error('  - Element exists:', !!tanggalInput);
            console.error('  - Element value:', tanggalInput ? tanggalInput.value : 'element not found');
            console.error('  - Element display:', tanggalInput ? getComputedStyle(tanggalInput).display : 'N/A');
            throw new Error('Tanggal pembayaran tidak valid. Silakan pilih tanggal terlebih dahulu.');
        }

        // 🔧 FIX: If client name is placeholder, get it from validationResult
        if (!namaValue || namaValue === 'Akan terisi otomatis setelah nomor KIT valid') {
            console.log('⚠️ Client name is placeholder, checking validationResult...');

            const clientNameFromValidation = validationResult?.validation?.data?.nama;
            console.log('  - validationResult nama:', clientNameFromValidation);

            if (clientNameFromValidation) {
                console.log('  ✅ Found client name in validationResult:', clientNameFromValidation);
                // Update clientNameText with correct value
                elements.clientNameText.textContent = clientNameFromValidation;
                namaValue = clientNameFromValidation;
                console.log('  - Using client name:', namaValue);
            } else {
                console.error('❌ Nama client validation failed!');
                console.error('  - namaValue:', namaValue);
                console.error('  - validationResult:', validationResult);
                throw new Error('Nama client tidak valid. Silakan tambahkan KIT terlebih dahulu.');
            }
        }

        const formData = {
            requestId: requestId,
            submissionTime: submitStartTime,
            tanggal: tanggalValue,
            nama: namaValue,
            // 🆕 REMOVED: tipe - no longer global, each KIT has its own
            nominal: totalNominal, // Total for summary purposes
            kitNumbers: kitNumbers,
            kitPackages: kitPackages,
            kitCount: selectedKits.length,
            isMultipleKit: selectedKits.length > 1,
            submitAsMultipleEntries: true, // NEW: flag to indicate separate entries
            selectedKits: selectedKits.map(kit => ({
                kitNumber: kit.kitNumber,
                serialNumber: kit.serialNumber || '',
                paket: kit.paket,
                nominal: kit.nominal, // Per-KIT nominal
                tipePembayaran: kit.tipePembayaran, // 🆕 NEW: Per-KIT payment type
                clientName: kit.clientName || namaValue // 🔧 FIX: Per-KIT client name
            }))
        };

        console.log('📤 Submitting with unique Request ID:', requestId);
        console.log('📤 Total KITs:', selectedKits.length, '| Total Nominal:', totalNominal);

        // 🔍 DEBUG: Log each KIT's payment type before submitting
        console.log('📤 Per-KIT Payment Types:');
        selectedKits.forEach((kit, i) => {
            console.log(`  ${i+1}. ${kit.kitNumber} -> Tipe: ${kit.tipePembayaran || '(EMPTY!)'} | Nominal: ${kit.nominal}`);
        });

        console.log('📤 Form Data being sent:', JSON.stringify(formData, null, 2));
        CONFIG.log('Submitting form data:', formData);

        // Submit dengan enhanced retry handling
        const result = await submitWithRetry(formData);

        console.log('📥 Submit result received:', result);
        CONFIG.log('Submit result:', result);

        handleSubmitSuccess(result);

    } catch (error) {
        console.error('❌ Submit error:', error);
        CONFIG.error('Submit error:', error);
        handleSubmitError(error);
    } finally {
        // Cleanup progress monitoring
        stopLoadingProgressMonitor();
        // Clear current requestId after submission complete
        currentRequestId = null;
    }
}

// 📊 PROGRESSIVE LOADING UI untuk feedback visual yang lebih baik
function showProgressiveLoadingUI() {
    const confirmationDetails = document.getElementById('confirmationDetails');
    if (!confirmationDetails) return;
    
    confirmationDetails.innerHTML = `
        <div id="progressiveLoading" style="text-align: center; padding: 40px;">
            <div class="loading-dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            <p id="loadingMessage" style="color: #94a3b8; font-size: 16px; margin: 10px 0;">Mengirim data pembayaran...</p>
            <p id="loadingSubtext" style="color: #64748b; font-size: 13px; margin: 0;">Mohon tunggu dan jangan tutup halaman ini</p>
            <div id="progressBar" style="width: 100%; height: 4px; background: #475569; border-radius: 2px; margin: 20px 0; overflow: hidden;">
                <div id="progressFill" style="height: 100%; background: linear-gradient(90deg, #3b82f6, #06b6d4); width: 0%; transition: width 0.5s ease; border-radius: 2px;"></div>
            </div>
            <div id="timeElapsed" style="color: #94a3b8; font-size: 12px; margin-top: 10px;">Waktu berlalu: 0 detik</div>
        </div>
    `;
}

// ⏱️ LOADING PROGRESS MONITOR untuk update UI selama proses lambat
function startLoadingProgressMonitor() {
    let elapsed = 0;
    const progressFill = document.getElementById('progressFill');
    const timeElapsed = document.getElementById('timeElapsed');
    const loadingMessage = document.getElementById('loadingMessage');
    const loadingSubtext = document.getElementById('loadingSubtext');
    
    loadingProgressInterval = setInterval(() => {
        elapsed += 1;
        
        // Update time elapsed
        if (timeElapsed) {
            timeElapsed.textContent = `Waktu berlalu: ${elapsed} detik`;
        }
        
        // Update progress bar (fake progress untuk user feedback)
        if (progressFill) {
            let progress = Math.min(90, (elapsed / 30) * 100); // Max 90% sampai selesai
            progressFill.style.width = progress + '%';
        }
        
        // Update messages berdasarkan waktu - KEY UNTUK MENCEGAH USER ANXIETY
        if (elapsed === 5 && loadingMessage) {
            loadingMessage.textContent = 'Memproses data di server...';
            loadingSubtext.textContent = 'Sistem sedang menyimpan ke Google Sheets';
        } else if (elapsed === 10 && loadingMessage) {
            loadingMessage.textContent = 'Menunggu respons dari server...';
            loadingSubtext.textContent = 'Proses bisa memakan waktu beberapa detik';
        } else if (elapsed === 15 && loadingMessage) {
            loadingMessage.textContent = 'Hampir selesai...';
            loadingSubtext.textContent = 'Jangan refresh atau tutup halaman ini';
            // Tambah peringatan visual
            const progressiveLoading = document.getElementById('progressiveLoading');
            if (progressiveLoading) {
                progressiveLoading.style.background = 'rgba(245, 158, 11, 0.05)';
                progressiveLoading.style.border = '1px solid rgba(245, 158, 11, 0.2)';
                progressiveLoading.style.borderRadius = '8px';
            }
        } else if (elapsed >= 20 && loadingMessage) {
            loadingMessage.textContent = 'Server membutuhkan waktu lebih lama...';
            loadingSubtext.innerHTML = `
                <strong style="color: #fde68a;">JANGAN KLIK TOMBOL LAIN!</strong><br>
                Proses tetap berjalan di background
            `;
        }
        
        // Show warning jika sudah terlalu lama (30+ detik)
        if (elapsed >= 30) {
            showSlowConnectionWarning();
        }
        
    }, 1000);
}

function stopLoadingProgressMonitor() {
    if (loadingProgressInterval) {
        clearInterval(loadingProgressInterval);
        loadingProgressInterval = null;
    }
    
    // Complete progress bar
    const progressFill = document.getElementById('progressFill');
    if (progressFill) {
        progressFill.style.width = '100%';
        progressFill.style.background = '#10b981'; // Green when complete
    }
}

// ⚠️ SLOW CONNECTION WARNING untuk loading yang sangat lambat
function showSlowConnectionWarning() {
    const progressiveLoading = document.getElementById('progressiveLoading');
    if (!progressiveLoading) return;
    
    const existingWarning = document.getElementById('slowConnectionWarning');
    if (existingWarning) return; // Sudah ada warning
    
    const warningDiv = document.createElement('div');
    warningDiv.id = 'slowConnectionWarning';
    warningDiv.innerHTML = `
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 15px; margin-top: 20px;">
            <p style="color: #fca5a5; font-weight: 600; margin: 0 0 8px 0;">⚠️ Koneksi Lambat Terdeteksi</p>
            <p style="color: #fca5a5; font-size: 12px; margin: 0;">
                Server Google Sheets sedang sibuk. Proses tetap berjalan, mohon bersabar dan 
                <strong>JANGAN REFRESH HALAMAN</strong> atau klik tombol lain.
            </p>
        </div>
    `;
    
    progressiveLoading.appendChild(warningDiv);
}

// 🚫 DISABLE ALL CONTROLS dengan feedback yang jelas
function disableAllSubmitControls() {
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const confirmationModal = document.getElementById('confirmationModal');
    const modalClose = confirmationModal?.querySelector('.modal-close');
    
    // Disable modal controls
    if (confirmSubmitBtn) {
        confirmSubmitBtn.disabled = true;
        confirmSubmitBtn.innerHTML = '⏳ Memproses...';
        confirmSubmitBtn.style.pointerEvents = 'none';
        confirmSubmitBtn.style.opacity = '0.6';
    }
    
    if (cancelSubmitBtn) {
        cancelSubmitBtn.disabled = true;
        cancelSubmitBtn.innerHTML = '🚫 Tidak Bisa Dibatalkan';
        cancelSubmitBtn.style.opacity = '0.4';
        cancelSubmitBtn.style.pointerEvents = 'none';
    }
    
    if (modalClose) {
        modalClose.disabled = true;
        modalClose.style.opacity = '0.3';
        modalClose.style.pointerEvents = 'none';
    }
    
    // Disable main form controls
    if (elements.submitBtn) {
        elements.submitBtn.disabled = true;
        elements.submitBtn.textContent = '⏳ Sedang Memproses...';
        elements.submitBtn.style.pointerEvents = 'none';
        elements.submitBtn.style.opacity = '0.6';
    }
    
    // Disable form inputs
    const allInputs = document.querySelectorAll('input, select, button');
    allInputs.forEach(input => {
        if (input !== confirmSubmitBtn && input !== cancelSubmitBtn) {
            input.disabled = true;
            input.style.opacity = '0.5';
        }
    });
    
    // Prevent modal close pada outside click
    if (confirmationModal) {
        confirmationModal.style.pointerEvents = 'none';
        confirmationModal.querySelector('.modal-content').style.pointerEvents = 'auto';
    }
}

// 🔄 SUBMIT WITH RETRY dan enhanced error handling
async function submitWithRetry(formData, retryCount = 0) {
    const maxRetries = 2;
    const retryDelay = 3000; // 3 detik
    
    // 🚫 CRITICAL: Check if this requestId has already been submitted successfully
    if (submittedRequestIds.has(formData.requestId)) {
        console.log('🚫 RequestId already submitted successfully, blocking duplicate:', formData.requestId);
        throw new Error('Duplicate submission blocked - this request was already processed successfully');
    }
    
    try {
        // Update UI untuk retry attempt
        if (retryCount > 0) {
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingSubtext = document.getElementById('loadingSubtext');
            
            if (loadingMessage) {
                loadingMessage.textContent = `Mencoba lagi... (Percobaan ${retryCount + 1})`;
            }
            if (loadingSubtext) {
                loadingSubtext.textContent = 'Koneksi terputus, sistem mencoba mengirim ulang';
            }
        }
        
        const result = await api.submitPayment(formData);
        
        // Jika berhasil, return result
        if (result && (result.status === 'success' || result.status === 'ok')) {
            // 🎯 MARK requestId as submitted successfully
            submittedRequestIds.add(formData.requestId);
            console.log('✅ RequestId marked as submitted:', formData.requestId);
            
            // Auto-cleanup after cache duration
            setTimeout(() => {
                submittedRequestIds.delete(formData.requestId);
                console.log('🧹 RequestId removed from cache:', formData.requestId);
            }, REQUEST_ID_CACHE_DURATION);
            
            return result;
        } else {
            throw new Error(result?.message || 'Unknown error from server');
        }
        
    } catch (error) {
        console.error(`Submit attempt ${retryCount + 1} failed:`, error);
        
        // 🚫 If duplicate error detected, mark as submitted to prevent further retries
        if (error.message.includes('duplicate') || error.message.includes('sudah ada') || error.message.includes('already')) {
            submittedRequestIds.add(formData.requestId);
            console.log('⚠️ Duplicate detected, marking requestId to prevent retries:', formData.requestId);
        }
        
        // Jika masih ada retry attempts dan error bukan karena duplikasi
        if (retryCount < maxRetries && !error.message.includes('duplicate') && !error.message.includes('sudah ada') && !error.message.includes('already')) {
            
            console.log(`Retrying in ${retryDelay}ms... (${retryCount + 1}/${maxRetries})`);
            
            // Update UI untuk countdown
            await showRetryCountdown(retryDelay);
            
            // Retry
            return submitWithRetry(formData, retryCount + 1);
        } else {
            // Jika sudah habis retry attempts atau duplicate error
            throw error;
        }
    }
}

// ⏳ RETRY COUNTDOWN untuk user feedback
async function showRetryCountdown(delay) {
    return new Promise(resolve => {
        const loadingSubtext = document.getElementById('loadingSubtext');
        let remaining = Math.ceil(delay / 1000);
        
        const countdownInterval = setInterval(() => {
            if (loadingSubtext) {
                loadingSubtext.innerHTML = `Mencoba lagi dalam ${remaining} detik... <strong>Jangan tutup halaman!</strong>`;
            }
            
            remaining--;
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                resolve();
            }
        }, 1000);
    });
}

// 💬 SHOW MESSAGE saat user coba submit lagi selama loading
function showSlowLoadingMessage() {
    // Update existing loading message
    const loadingMessage = document.getElementById('loadingMessage');
    const loadingSubtext = document.getElementById('loadingSubtext');
    
    if (loadingMessage) {
        loadingMessage.textContent = 'Data sedang diproses, mohon tunggu...';
        loadingMessage.style.color = '#fde68a';
        loadingMessage.style.fontWeight = '600';
    }
    
    if (loadingSubtext) {
        loadingSubtext.innerHTML = `
            <strong style="color: #ef4444;">JANGAN KLIK TOMBOL SUBMIT LAGI!</strong><br>
            Sistem sedang memproses submission Anda
        `;
    }
    
    // Shake animation untuk attract attention
    const progressiveLoading = document.getElementById('progressiveLoading');
    if (progressiveLoading) {
        progressiveLoading.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            progressiveLoading.style.animation = '';
        }, 500);
    }
    
    // Inject shake keyframes jika belum ada
    if (!document.querySelector('#shakeKeyframes')) {
        const style = document.createElement('style');
        style.id = 'shakeKeyframes';
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    }
}

// ✅ ENHANCED handleSubmitSuccess untuk reset semua state
function handleSubmitSuccess(result) {
    CONFIG.log('🎉 handleSubmitSuccess called with:', result);
    
    // Complete progress bar first
    const progressFill = document.getElementById('progressFill');
    if (progressFill) {
        progressFill.style.width = '100%';
        progressFill.style.background = '#10b981';
    }
    
    // Show success in modal briefly
    const loadingMessage = document.getElementById('loadingMessage');
    const loadingSubtext = document.getElementById('loadingSubtext');
    
    if (loadingMessage) {
        loadingMessage.textContent = '✅ Data berhasil disimpan!';
        loadingMessage.style.color = '#a7f3d0';
    }
    if (loadingSubtext) {
        loadingSubtext.textContent = 'Menutup dialog dalam 2 detik...';
        loadingSubtext.style.color = '#a7f3d0';
    }
    
    // Wait 2 seconds untuk user melihat success message
    setTimeout(() => {
        // Reset global state
        isSubmitting = false;
        submitStartTime = null;
        
        // Close modal
        closeConfirmationModal();
        
        // Re-enable all controls
        enableAllControls();
        
        // Show success banner
        if (result && result.status === 'success') {
            let successMessage = `✅ Berhasil menyimpan ${result.kitCount || selectedKits.length} KIT`;
            if (result.targetFile) {
                successMessage += ` ke file terpisah: ${result.targetFile}`;
            }
            if (result.sheetName) {
                successMessage += ` (Sheet: ${result.sheetName})`;
            }
            if (result.dailySummaryUpdated) {
                successMessage += ` 📊 Rekap harian diperbarui otomatis.`;
            }
            
            showBanner(successMessage, 'success');
            
            // Auto-hide success banner
            setTimeout(() => {
                if (elements.successBanner && elements.successBanner.style.display === 'block') {
                    elements.successBanner.style.transition = 'opacity 0.5s ease-out';
                    elements.successBanner.style.opacity = '0';
                    
                    setTimeout(() => {
                        elements.successBanner.style.display = 'none';
                        elements.successBanner.style.opacity = '1';
                        elements.successBanner.style.transition = '';
                    }, 500);
                }
            }, 6000);
            
            // Reset form
            resetFormAfterSuccess();
            
        } else {
            showBanner('❌ Error: ' + (result ? result.message : 'Unknown error'), 'error');
        }
        
    }, 2000);
    
    CONFIG.log('✅ handleSubmitSuccess completed');
}

// ❌ ENHANCED handleSubmitError dengan better user feedback
function handleSubmitError(error) {
    CONFIG.error('🚨 handleSubmitError called with:', error);
    
    // Update UI untuk show error
    const loadingMessage = document.getElementById('loadingMessage');
    const loadingSubtext = document.getElementById('loadingSubtext');
    const progressFill = document.getElementById('progressFill');
    
    if (progressFill) {
        progressFill.style.background = '#ef4444'; // Red untuk error
    }
    
    if (loadingMessage) {
        loadingMessage.textContent = '❌ Gagal menyimpan data';
        loadingMessage.style.color = '#fca5a5';
    }
    
    let errorDetail = '';
    if (error.message.includes('duplicate') || error.message.includes('sudah ada')) {
        errorDetail = 'Data mungkin sudah tersimpan sebelumnya. Cek laporan untuk memastikan.';
    } else if (error.message.includes('timeout')) {
        errorDetail = 'Koneksi timeout. Data mungkin tersimpan, cek laporan dulu sebelum submit ulang.';
    } else {
        errorDetail = 'Terjadi kesalahan sistem. Silakan coba lagi dalam beberapa saat.';
    }
    
    if (loadingSubtext) {
        loadingSubtext.innerHTML = `<strong style="color: #fca5a5;">${errorDetail}</strong>`;
    }
    
    // Wait 3 seconds untuk user baca error message
    setTimeout(() => {
        // Reset state
        isSubmitting = false;
        submitStartTime = null;
        
        // Close modal
        closeConfirmationModal();
        
        // Re-enable controls
        enableAllControls();
        
        // Show error banner
        let errorMessage = 'Error submit: ' + error.message;
        if (error.message.includes('duplicate')) {
            errorMessage += ' (Data mungkin sudah tersimpan, cek laporan)';
        }
        
        showBanner(errorMessage, 'error');
        
    }, 3000);
    
    CONFIG.log('✅ handleSubmitError completed');
}

// 🔓 RE-ENABLE ALL CONTROLS setelah selesai processing
function enableAllControls() {
    // Re-enable main submit button
    if (elements.submitBtn) {
        elements.submitBtn.disabled = false;
        elements.submitBtn.textContent = '✅ Submit';
        elements.submitBtn.style.pointerEvents = 'auto';
        elements.submitBtn.style.opacity = '1';
    }
    
    // Re-enable all form inputs
    const allInputs = document.querySelectorAll('input, select, button');
    allInputs.forEach(input => {
        input.disabled = false;
        input.style.opacity = '1';
    });
    
    // Update button states properly
    updateButtonStates();
}

// 🔄 Form reset helper function
function resetFormAfterSuccess() {
    // Reset form
    elements.form.reset();
    clearClientData();
    hideKitSelection();
    elements.previewSection.style.display = 'none';

    // 🔧 CRITICAL FIX: Reset ALL state variables
    isKitValid = false;
    validationResult = null; // Added to fix Next button staying disabled
    availableKits = [];
    selectedKits = [];

    // Reset payment type dropdown to empty state
    const paymentTypeSelected = document.getElementById('paymentTypeSelected');
    if (paymentTypeSelected) {
        paymentTypeSelected.classList.add('empty');
        paymentTypeSelected.querySelector('.payment-title').textContent = 'Select payment type';
        paymentTypeSelected.querySelector('.payment-subtitle').textContent = 'Choose activation or extension';
    }

    updateButtonStates();

    // Reset custom date picker to today
    setupCustomDatePicker();

    // Clear any lingering validation messages
    clearValidation();
    hideAllBanners();

    console.log('✅ All state variables reset - Form ready for new entry');
}

// [CONTINUING WITH REST OF THE FUNCTIONS...]
// Due to length limits, I'll continue with the remaining functions in the same structure

async function handleSearchModeChange() {
    const selectedMode = elements.searchMode.value;
    
    // Reset state
    resetValidationState();
    
    if (selectedMode === 'name') {
        elements.kitNumberSection.style.display = 'none';
        elements.clientNameSection.style.display = 'block';
        elements.nomorKitInput.required = false;
        document.getElementById('clientNameSearch').required = true;
        
        if (allClientNames.length === 0) {
            await loadClientNames();
        }
    } else {
        elements.kitNumberSection.style.display = 'block';
        elements.clientNameSection.style.display = 'none';
        elements.nomorKitInput.required = true;
        document.getElementById('clientNameSearch').required = false;
    }
    
    updateButtonStates();
}

async function loadClientNames() {
    try {
        showClientNamesLoading();
        
        const result = await api.getClientsBySheet('Client Aktif');
        
        if (result.status === 'success') {
            allClientNames = result.data;
            filteredClientNames = [...allClientNames];
            setupSearchableClientDropdown();
        } else {
            showBanner('❌ Error loading client names: ' + result.message, 'error');
        }
    } catch (error) {
        showBanner('❌ Error loading client names: ' + error.message, 'error');
    } finally {
        hideClientNamesLoading();
    }
}

function setupSearchableClientDropdown() {
    const searchInput = elements.clientNameSearch;
    const resultsContainer = elements.clientSearchResults;
    const resultsList = elements.searchResultsList;
    const resultsCount = elements.resultsCount;
    const noResults = elements.searchNoResults;
    
    if (!searchInput || !resultsContainer || !resultsList) {
        CONFIG.warn('⚠️ Client search elements not found');
        return;
    }
    
    let isOpen = false;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        filterClients(query);
        showResults();
    });
    
    searchInput.addEventListener('focus', function() {
        if (allClientNames.length > 0) {
            showResults();
        }
    });
    
    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            hideResults();
        }, 200);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (!isOpen) return;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedClientIndex = Math.min(selectedClientIndex + 1, filteredClientNames.length - 1);
                updateHighlight();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                selectedClientIndex = Math.max(selectedClientIndex - 1, -1);
                updateHighlight();
                break;
                
            case 'Enter':
                e.preventDefault();
                if (selectedClientIndex >= 0) {
                    selectClient(filteredClientNames[selectedClientIndex]);
                } else if (this.value.trim()) {
                    selectClientByName(this.value.trim());
                }
                break;
                
            case 'Escape':
                hideResults();
                this.blur();
                break;
        }
    });
    
    function filterClients(query) {
        if (!query) {
            filteredClientNames = [...allClientNames];
        } else {
            filteredClientNames = allClientNames.filter(client => 
                client.nama.toLowerCase().includes(query)
            );
        }
        selectedClientIndex = -1;
        renderResults(query);
    }
    
    function renderResults(query = '') {
        resultsList.innerHTML = '';
        
        if (filteredClientNames.length === 0) {
            noResults.classList.add('show');
            resultsCount.textContent = '0 clients found';
            return;
        } else {
            noResults.classList.remove('show');
            resultsCount.textContent = `${filteredClientNames.length} client${filteredClientNames.length > 1 ? 's' : ''} found`;
        }
        
        filteredClientNames.forEach((client, index) => {
            const item = document.createElement('div');
            item.className = 'search-result-item';
            item.setAttribute('data-index', index);
            
            let highlightedName = client.nama;
            if (query) {
                const regex = new RegExp(`(${query})`, 'gi');
                highlightedName = client.nama.replace(regex, '<span class="highlight-match">$1</span>');
            }
            
            item.innerHTML = `
                <span class="client-result-icon">👤</span>
                <div class="client-result-text">
                    <span class="client-result-name">${highlightedName}</span>
                    <span class="client-result-info">Row ${client.rowNumber} • Client Aktif</span>
                </div>
            `;
            
            item.addEventListener('click', function() {
                selectClient(client);
            });
            
            item.addEventListener('mouseenter', function() {
                selectedClientIndex = index;
                updateHighlight();
            });
            
            resultsList.appendChild(item);
        });
    }
    
    function updateHighlight() {
        const items = resultsList.querySelectorAll('.search-result-item');
        items.forEach((item, index) => {
            if (index === selectedClientIndex) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('highlighted');
            }
        });
    }
    
    function showResults() {
        isOpen = true;
        resultsContainer.classList.add('show');
    }
    
    function hideResults() {
        isOpen = false;
        resultsContainer.classList.remove('show');
        selectedClientIndex = -1;
    }
    
    function selectClient(client) {
        searchInput.value = client.nama;
        hideResults();
        selectClientByName(client.nama);
    }
    
    async function selectClientByName(clientName) {
        showLoading();
        hideAllBanners();
        
        try {
            const result = await api.validateClientByName(clientName);
            
            console.log('📥 selectClientByName full result:', result);
            console.log('📥 Validation data:', result?.validation?.data);
            console.log('📥 Duplicate info:', result?.duplicate);
            console.log('📥 Duplicate status:', result?.duplicate?.status);
            console.log('📥 Has duplicate:', result?.duplicate?.hasDuplicate);
            console.log('📥 Duplicate kits:', result?.duplicate?.duplicateKits);
            
            if (result.validation && result.validation.status === 'found') {
                const selectedDate = getFormDateValue();
                let selectedMonth = null;
                let selectedYear = null;
                
                if (selectedDate) {
                    const dateParts = selectedDate.split('-');
                    selectedMonth = parseInt(dateParts[1]);
                    selectedYear = parseInt(dateParts[0]);
                }
                
                if (result.validation.data && result.validation.data.allKits) {
                    const allKitNumbers = result.validation.data.allKits.map(kit => kit.kitNumber);
                    
                    try {
                        const duplicateResult = await api.apiCall({
                            action: 'checkDuplicates',
                            kitNumbers: allKitNumbers.join(','),
                            month: selectedMonth,
                            year: selectedYear
                        }, 'GET');
                        
                        result.duplicate = duplicateResult || { 
                            status: 'no_duplicate', 
                            hasDuplicate: false, 
                            duplicateData: [], 
                            duplicateKits: [] 
                        };
                        
                        CONFIG.log('✅ Client name validation with duplicate check completed');
                        
                    } catch (duplicateError) {
                        CONFIG.warn('⚠️ Duplicate check failed for client search:', duplicateError.message);
                        result.duplicate = { 
                            status: 'no_duplicate', 
                            hasDuplicate: false, 
                            duplicateData: [], 
                            duplicateKits: [] 
                        };
                    }
                }
            }
            
            handleValidationSuccess(result);
        } catch (error) {
            handleValidationError(error);
        }
    }
    
    CONFIG.log(`✅ Searchable client dropdown setup with ${allClientNames.length} clients`);
}

function showClientNamesLoading() {
    elements.clientNamesLoading.style.display = 'flex';
}

function hideClientNamesLoading() {
    elements.clientNamesLoading.style.display = 'none';
}

function resetValidationState() {
    isKitValid = false;
    validationResult = null;
    availableKits = [];
    selectedKits = [];
    hideKitSelection();
    hideLoading();
    clearValidation();
    updateButtonStates();
    hideAllBanners();
}

// 🎨 MODERN DROPDOWN FUNCTIONALITY
function initializeModernSearchDropdown() {
    const selectedElement = document.getElementById('searchModeSelected');
    const optionsElement = document.getElementById('searchModeOptions');
    const hiddenInput = document.getElementById('searchMode');
    const options = optionsElement.querySelectorAll('.mode-option');
    
    let isOpen = false;
    
    selectedElement.addEventListener('click', function() {
        isOpen = !isOpen;
        
        if (isOpen) {
            selectedElement.classList.add('active');
            optionsElement.classList.add('show');
        } else {
            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
        }
    });
    
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const icon = this.querySelector('.mode-icon').textContent;
            const title = this.querySelector('.mode-title').textContent;
            const subtitle = this.querySelector('.mode-subtitle').textContent;
            
            selectedElement.querySelector('.mode-icon').textContent = icon;
            selectedElement.querySelector('.mode-title').textContent = title;
            selectedElement.querySelector('.mode-subtitle').textContent = subtitle;
            
            hiddenInput.value = value;
            
            options.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
            isOpen = false;
            
            hiddenInput.dispatchEvent(new Event('change'));
        });
        
        option.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
            this.style.paddingLeft = '22px';
        });
        
        option.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.paddingLeft = '18px';
        });
    });
    
    document.addEventListener('click', function(e) {
        if (!selectedElement.contains(e.target) && !optionsElement.contains(e.target)) {
            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
            isOpen = false;
        }
    });
    
    selectedElement.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
    
    options[0].classList.add('selected');
    
    CONFIG.log('✅ Modern search dropdown initialized');
}

function initializeModernPaymentDropdown() {
    const selectedElement = document.getElementById('paymentTypeSelected');
    const optionsElement = document.getElementById('paymentTypeOptions');
    const hiddenInput = document.getElementById('tipePembayaran');
    const options = optionsElement.querySelectorAll('.payment-option');
    
    let isOpen = false;
    
    selectedElement.addEventListener('click', function() {
        isOpen = !isOpen;
        
        if (isOpen) {
            selectedElement.classList.add('active');
            optionsElement.classList.add('show');
        } else {
            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
        }
    });
    
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const icon = this.querySelector('.payment-icon').textContent;
            const title = this.querySelector('.payment-title').textContent;
            const subtitle = this.querySelector('.payment-subtitle').textContent;

            selectedElement.querySelector('.payment-icon').textContent = icon;
            selectedElement.querySelector('.payment-title').textContent = title;
            selectedElement.querySelector('.payment-subtitle').textContent = subtitle;

            selectedElement.classList.remove('empty');

            hiddenInput.value = value;

            options.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');

            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
            isOpen = false;

            hiddenInput.dispatchEvent(new Event('change'));
            updatePreview();

            // 🔍 DEBUG: Trigger stepper navigation update
            if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
                console.log('🔄 Payment type selected, triggering stepper validation:', value);
                window.stepperNav.updateNavigationButtons();
            }

            CONFIG.log('Payment type selected:', value);
        });
        
        option.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768) {
                this.style.transform = 'translateX(4px)';
                this.style.paddingLeft = '22px';
            }
        });
        
        option.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768) {
                this.style.transform = 'translateX(0)';
                this.style.paddingLeft = '18px';
            }
        });
    });
    
    document.addEventListener('click', function(e) {
        if (!selectedElement.contains(e.target) && !optionsElement.contains(e.target)) {
            selectedElement.classList.remove('active');
            optionsElement.classList.remove('show');
            isOpen = false;
        }
    });
    
    selectedElement.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
        
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) {
                this.click();
            }
        }
    });
    
    selectedElement.classList.add('empty');
    
    CONFIG.log('✅ Modern payment type dropdown initialized');
}

// DUPLICATE CHECKER FUNCTIONS
function openDuplicateModal() {
    if (!elements.duplicateModal) {
        CONFIG.warn('⚠️ Duplicate modal not found');
        return;
    }
    
    elements.duplicateModal.style.display = 'flex';
    resetDuplicateModal();
    
    const now = new Date();
    if (elements.duplicateMonth) elements.duplicateMonth.value = now.getMonth() + 1;
    if (elements.duplicateYear) elements.duplicateYear.value = now.getFullYear();
    
    CONFIG.log('Duplicate modal opened');
}

function closeDuplicateModal() {
    if (!elements.duplicateModal) return;
    
    elements.duplicateModal.style.display = 'none';
    resetDuplicateModal();
    CONFIG.log('Duplicate modal closed');
}

function resetDuplicateModal() {
    if (elements.modalLoading) elements.modalLoading.style.display = 'none';
    if (elements.duplicateResults) elements.duplicateResults.style.display = 'none';
    if (elements.duplicateTable) elements.duplicateTable.style.display = 'none';
    if (elements.checkDuplicateBtn) {
        elements.checkDuplicateBtn.disabled = false;
        elements.checkDuplicateBtn.textContent = '🔍 Mulai Cek Duplikasi';
    }
}

async function performDuplicateCheck() {
    if (!elements.duplicateMonth || !elements.duplicateYear) {
        CONFIG.warn('⚠️ Duplicate checker elements not found');
        return;
    }
    
    const month = parseInt(elements.duplicateMonth.value);
    const year = parseInt(elements.duplicateYear.value);
    
    if (!month || !year) {
        showBanner('❌ Pilih bulan dan tahun terlebih dahulu!', 'error');
        return;
    }
    
    try {
        if (elements.modalLoading) elements.modalLoading.style.display = 'block';
        if (elements.duplicateResults) elements.duplicateResults.style.display = 'none';
        if (elements.checkDuplicateBtn) {
            elements.checkDuplicateBtn.disabled = true;
            elements.checkDuplicateBtn.textContent = '⏳ Mengecek...';
        }
        
        CONFIG.log(`Checking duplicates for ${month}/${year}...`);
        
        const result = await api.checkDuplicates(month, year);
        
        CONFIG.log('Duplicate check result:', result);
        
        if (elements.modalLoading) elements.modalLoading.style.display = 'none';
        if (elements.checkDuplicateBtn) {
            elements.checkDuplicateBtn.disabled = false;
            elements.checkDuplicateBtn.textContent = '🔍 Mulai Cek Duplikasi';
        }
        
        displayDuplicateResults(result, month, year);
        
    } catch (error) {
        CONFIG.error('Duplicate check error:', error);
        
        if (elements.modalLoading) elements.modalLoading.style.display = 'none';
        if (elements.checkDuplicateBtn) {
            elements.checkDuplicateBtn.disabled = false;
            elements.checkDuplicateBtn.textContent = '🔍 Mulai Cek Duplikasi';
        }
        
        if (elements.duplicateResults && elements.duplicateSummary) {
            elements.duplicateResults.style.display = 'block';
            elements.duplicateSummary.className = 'duplicate-summary warning';
            elements.duplicateSummary.innerHTML = `
                <h4 style="color: #fde68a; margin-bottom: 10px;">❌ Error</h4>
                <p style="color: #fde68a; margin: 0;">Gagal mengecek duplikasi: ${error.message}</p>
            `;
        }
    }
}

function displayDuplicateResults(result, month, year) {
    if (!elements.duplicateResults || !elements.duplicateSummary) {
        CONFIG.warn('⚠️ Duplicate results elements not found');
        return;
    }
    
    elements.duplicateResults.style.display = 'block';
    
    const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const monthName = monthNames[month];
    
    if (result.status === 'error') {
        elements.duplicateSummary.className = 'duplicate-summary warning';
        elements.duplicateSummary.innerHTML = `
            <h4 style="color: #fde68a; margin-bottom: 10px;">❌ Error</h4>
            <p style="color: #fde68a; margin: 0;">${result.message}</p>
        `;
        return;
    }
    
    if (!result.hasDuplicates) {
        elements.duplicateSummary.className = 'duplicate-summary safe';
        elements.duplicateSummary.innerHTML = `
            <h4 style="color: #a7f3d0; margin-bottom: 10px;">✅ ${result.message}</h4>
            <p style="color: #a7f3d0; margin: 5px 0 0 0;">
                📊 <strong>${monthName} ${year}</strong><br>
                📋 Sheet: ${result.sheetName || 'N/A'}<br>
                📝 Total Baris: ${result.totalRows || 0}<br>
                🛰️ Total KIT: ${result.checkedKits || 0}
            </p>
        `;
        if (elements.duplicateTable) elements.duplicateTable.style.display = 'none';
        return;
    }
    
    elements.duplicateSummary.className = 'duplicate-summary warning';
    elements.duplicateSummary.innerHTML = `
        <h4 style="color: #fde68a; margin-bottom: 10px;">⚠️ ${result.message}</h4>
        <p style="color: #fde68a; margin: 5px 0 0 0;">
            📊 <strong>${monthName} ${year}</strong><br>
            📋 Sheet: ${result.sheetName}<br>
            📝 Total Baris: ${result.totalRows}<br>
            🛰️ Total KIT: ${result.checkedKits}<br>
            🔍 KIT Duplikasi: ${result.duplicates.length}
        </p>
    `;
    
    if (!elements.duplicateTable) return;
    
    let tableHTML = `
        <table>
            <thead>
                <tr>
                    <th>KIT Number</th>
                    <th>Duplikasi</th>
                    <th>Detail Occurrences</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    result.duplicates.forEach(duplicate => {
        tableHTML += `
            <tr>
                <td style="font-weight: 600;">${duplicate.kitNumber}</td>
                <td><span class="kit-duplicate-count">${duplicate.count}x</span></td>
                <td>
        `;
        
        duplicate.occurrences.forEach((occurrence, index) => {
            const transactionId = occurrence.transactionID || 'No ID';
            const namaClient = occurrence.namaClient || 'No Name';
            const tanggal = occurrence.tanggal || 'No Date';
            const tipe = occurrence.tipe || 'No Type';
            
            tableHTML += `
                <div style="margin-bottom: 5px; font-size: 11px;">
                    <strong>Row ${occurrence.rowNumber}:</strong> ${transactionId} - ${namaClient} | ${tanggal} | ${tipe}
                </div>
            `;
        });
        
        tableHTML += `</td></tr>`;
    });
    
    tableHTML += `</tbody></table>`;
    
    elements.duplicateTable.innerHTML = tableHTML;
    elements.duplicateTable.style.display = 'block';
}

// 🆕 PARSE MULTIPLE KIT INPUT
function parseMultipleKitInput(inputValue) {
    if (!inputValue || inputValue.trim() === '') {
        return [];
    }

    // Split by newline, comma, atau semicolon
    const separators = /[\n,;]+/;
    const rawItems = inputValue.split(separators);

    // Clean up and filter empty values
    const kitNumbers = rawItems
        .map(item => item.trim())
        .filter(item => item.length > 0)
        .filter((item, index, self) => self.indexOf(item) === index); // Remove duplicates

    CONFIG.log('📋 Parsed KIT/SN numbers:', kitNumbers);
    return kitNumbers;
}

// 🆕 VALIDATE MULTIPLE KITS
async function validateMultipleKits(inputValue) {
    try {
        const kitNumbers = parseMultipleKitInput(inputValue);

        if (kitNumbers.length === 0) {
            hideLoading();
            clearValidation();
            return;
        }

        CONFIG.log(`🔍 Validating ${kitNumbers.length} KIT/SN numbers...`);
        showLoading();

        // Get selected date for duplicate check
        const selectedDate = getFormDateValue();
        let selectedMonth = null;
        let selectedYear = null;

        if (selectedDate) {
            const dateParts = selectedDate.split('-');
            selectedMonth = parseInt(dateParts[1]);
            selectedYear = parseInt(dateParts[0]);
        }

        // Validate all KITs in parallel
        const validationPromises = kitNumbers.map(kitNumber =>
            api.validateKitMulti(kitNumber, selectedMonth, selectedYear)
                .then(result => ({
                    kitNumber,
                    success: true,
                    data: result
                }))
                .catch(error => ({
                    kitNumber,
                    success: false,
                    error: error.message
                }))
        );

        const results = await Promise.all(validationPromises);

        CONFIG.log('📊 Validation results:', results);

        // Process results and aggregate
        handleMultipleValidationResults(results);

    } catch (error) {
        CONFIG.error('Error validating multiple KITs:', error);
        handleValidationError(error);
    }
}

// 🆕 HANDLE MULTIPLE VALIDATION RESULTS
function handleMultipleValidationResults(results) {
    hideLoading();

    const successfulResults = results.filter(r => r.success && r.data?.validation?.status === 'found');
    const failedResults = results.filter(r => !r.success || r.data?.validation?.status !== 'found');

    if (successfulResults.length === 0) {
        const errorMessages = failedResults.map(r => `❌ ${r.kitNumber}: ${r.error || 'Not found'}`).join('\n');
        showValidationMessage(`Tidak ada KIT/SN yang valid:\n${errorMessages}`, 'error');
        return;
    }

    // Aggregate all KITs from successful validations
    const allKits = [];
    const clientNames = new Set();
    const duplicateInfo = [];

    successfulResults.forEach(result => {
        const data = result.data;

        if (data.validation?.data?.allKits) {
            data.validation.data.allKits.forEach(kit => {
                allKits.push(kit);
            });

            if (data.validation.data.nama) {
                clientNames.add(data.validation.data.nama);
            }
        }

        // Collect duplicate info
        if (data.duplicate?.hasDuplicate) {
            duplicateInfo.push({
                kitNumber: result.kitNumber,
                duplicates: data.duplicate.duplicateKits || []
            });
        }
    });

    // Remove duplicate KITs based on kitNumber
    const uniqueKits = [];
    const seenKitNumbers = new Set();

    allKits.forEach(kit => {
        if (!seenKitNumbers.has(kit.kitNumber)) {
            uniqueKits.push(kit);
            seenKitNumbers.add(kit.kitNumber);
        }
    });

    // Update validation result
    validationResult = {
        validation: {
            status: 'found',
            data: {
                nama: Array.from(clientNames).join(', '),
                allKits: uniqueKits
            }
        },
        duplicate: duplicateInfo.length > 0 ? {
            hasDuplicate: true,
            duplicateKits: duplicateInfo.flatMap(d => d.duplicates)
        } : {
            hasDuplicate: false,
            duplicateKits: []
        }
    };

    CONFIG.log('✅ Aggregated validation result:', validationResult);

    // Show validation success with aggregated data
    handleValidationSuccess(validationResult);

    // Show summary message
    let summaryMessage = `✅ ${successfulResults.length} KIT/SN berhasil divalidasi, total ${uniqueKits.length} KIT ditemukan`;

    if (failedResults.length > 0) {
        summaryMessage += `\n⚠️ ${failedResults.length} KIT/SN tidak ditemukan: ${failedResults.map(r => r.kitNumber).join(', ')}`;
    }

    if (duplicateInfo.length > 0) {
        summaryMessage += `\n⚠️ ${duplicateInfo.length} KIT memiliki duplikasi`;
    }

    showValidationMessage(summaryMessage, successfulResults.length > 0 ? 'success' : 'warning');
}

// VALIDATE KIT (original function - kept for backward compatibility)
async function validateKit(kitNumber) {
    try {
        CONFIG.log('Validating KIT:', kitNumber);

        const selectedDate = getFormDateValue();
        console.log('🐛 DEBUG selectedDate:', selectedDate);
        console.log('🐛 DEBUG selectedDate type:', typeof selectedDate);

        let selectedMonth = null;
        let selectedYear = null;

        if (selectedDate) {
            const dateParts = selectedDate.split('-');
            console.log('🐛 DEBUG dateParts:', dateParts);
            selectedMonth = parseInt(dateParts[1]);
            selectedYear = parseInt(dateParts[0]);
            console.log('🐛 DEBUG extracted month:', selectedMonth);
            console.log('🐛 DEBUG extracted year:', selectedYear);
        }

        console.log('🐛 DEBUG Final params - month:', selectedMonth, 'year:', selectedYear);

        const data = await api.validateKitMulti(kitNumber, selectedMonth, selectedYear);
        handleValidationSuccess(data);

    } catch (error) {
        handleValidationError(error);
    }
}

function handleValidationSuccess(data) {
    console.log('🎯 handleValidationSuccess RAW input:', JSON.stringify(data, null, 2));
    
    hideLoading();
    
    if (!data || !data.validation) {
        CONFIG.error('Invalid validation response:', data);
        showValidationMessage('❌ Response tidak valid dari server. Coba lagi.', 'error');
        return;
    }
    
    const originalDuplicate = JSON.parse(JSON.stringify(data.duplicate));
    console.log('📋 ORIGINAL duplicate data:', originalDuplicate);
    
    validationResult = data;
    
    if (data.validation.status === 'found') {
        isKitValid = true;
        availableKits = data.validation.data.allKits || [];
        
        elements.clientNameText.textContent = data.validation.data.nama;
        elements.namaClientDisplay.classList.add('filled');
        
        let successMsg = `✅ Client ditemukan! ${data.validation.data.totalKits} KIT tersedia untuk ${data.validation.data.nama}`;
        if (data.targetFile) {
            successMsg += ` (Laporan akan disimpan ke: ${data.targetFile})`;
        }
        
        console.log('📦 Detailed KIT Information:');
        availableKits.forEach((kit, index) => {
            console.log(`  ${index + 1}. KIT: ${kit.kitNumber} | Serial: ${kit.serialNumber || 'N/A'} | Paket: ${kit.paket}`);
        });
        
        showValidationMessage(successMsg, 'success');

        setTimeout(() => {
            if (elements.kitValidation.classList.contains('validation-success')) {
                elements.kitValidation.style.transition = 'opacity 0.5s ease-out';
                elements.kitValidation.style.opacity = '0';
                
                setTimeout(() => {
                    elements.kitValidation.style.display = 'none';
                    elements.kitValidation.style.opacity = '1';
                    elements.kitValidation.style.transition = '';
                }, 500);
            }
        }, 5000);
        
        showKitSelection();
        
        // Update stepper navigation buttons after client name is set
        if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
            setTimeout(() => {
                window.stepperNav.updateNavigationButtons();
                console.log('🔄 Stepper navigation buttons updated after client name set');
            }, 100);
        }
        
        if (data.validation.warning) {
            console.log('⚠️ Validation warning:', data.validation.warning);
            showBanner(data.validation.warning, 'warning');
        }
        
        console.log('🔍 CHECKING DUPLICATES...');
        if (data.duplicate && 
            data.duplicate.hasDuplicate && 
            data.duplicate.duplicateKits && 
            data.duplicate.duplicateKits.length > 0) {
            
            const duplicateList = data.duplicate.duplicateKits.join(', ');
            const warningMessage = `⚠️ DUPLICATE WARNING: KIT berikut sudah ada di laporan: ${duplicateList}. Anda masih bisa melanjutkan jika ini pembayaran tambahan/cicilan.`;
            
            console.log('🚨 SHOWING DUPLICATE WARNING:', warningMessage);
            showBanner(warningMessage, 'warning');
            
            availableKits.forEach(kit => {
                if (data.duplicate.duplicateKits.includes(kit.kitNumber)) {
                    kit.isDuplicate = true;
                    console.log('🔴 Marked as duplicate:', kit.kitNumber);
                }
            });
        }
        
        selectedKits = availableKits.filter(kit => kit.isSelected);
        
    } else if (data.validation.status === 'not_found') {
        isKitValid = false;
        clearClientData();
        showValidationMessage('❌ Nomor KIT tidak ditemukan dalam database!', 'error');
        
    } else {
        isKitValid = false;
        clearClientData();
        showValidationMessage('❌ Error dalam validasi: ' + (data.validation.warning || 'Unknown error'), 'error');
    }
    
    updateButtonStates();
    updatePreview();
}

function handleValidationError(error) {
    hideLoading();
    isKitValid = false;
    clearClientData();
    hideKitSelection();
    
    let errorMessage = 'Error koneksi: ' + error.message;
    
    if (error.message.includes('Failed to fetch')) {
        errorMessage = 'Tidak dapat terhubung ke server. Mencoba metode alternatif...';
    } else if (error.message.includes('404')) {
        errorMessage = 'Apps Script Web App tidak ditemukan. URL salah atau belum di-deploy.';
    } else if (error.message.includes('timeout')) {
        errorMessage = 'Koneksi timeout. Server lambat atau tidak merespon.';
    }
    
    showValidationMessage('❌ ' + errorMessage, 'error');
    updateButtonStates();
    updatePreview();
}

// 🔧 ENHANCED CONFIRMATION MODAL
function showConfirmationModal() {
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmationDetails = document.getElementById('confirmationDetails');
    
    if (!confirmationModal || !confirmationDetails) {
        CONFIG.warn('⚠️ Confirmation modal not found, submitting directly...');
        confirmAndSubmit();
        return;
    }
    
    const tanggal = getFormDateValue();
    const nama = elements.clientNameText.textContent.trim();

    // Calculate total nominal from selectedKits
    const totalNominal = selectedKits.reduce((sum, kit) => sum + (kit.nominal || 0), 0);

    const formattedDate = formatDateForDisplay(tanggal);
    const formattedNominal = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(totalNominal);
    
    const kitDetails = selectedKits.map((kit, index) => {
        const serialInfo = kit.serialNumber ? ` | SN: ${kit.serialNumber}` : '';
        return `<li><strong>🛰️ ${kit.kitNumber}${serialInfo}</strong> (${kit.paket})</li>`;
    }).join('');
    
    confirmationDetails.innerHTML = `
        <div style="background: #0f172a; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p><strong>📅 Tanggal:</strong> ${formattedDate}</p>
            <p><strong>💰 Nominal:</strong> ${formattedNominal}</p>
            <p><strong>🛰️ Total KIT:</strong> ${selectedKits.length}</p>
        </div>
        <div style="background: #334155; padding: 12px; border-radius: 8px;">
            <p style="margin-bottom: 8px;"><strong>📦 Detail KIT:</strong></p>
            <ul style="margin-left: 20px; color: #cbd5e1;">
                ${kitDetails}
            </ul>
        </div>
    `;
    
    resetModalState();
    confirmationModal.style.display = 'flex';
    
    CONFIG.log('✅ Confirmation modal opened with enhanced KIT details');
}

function resetModalState() {
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const confirmationModalClose = document.getElementById('confirmationModalClose');
    
    if (confirmSubmitBtn) {
        confirmSubmitBtn.disabled = false;
        confirmSubmitBtn.innerHTML = '✅ Ya, Submit Data';
        confirmSubmitBtn.style.opacity = '1';
        confirmSubmitBtn.style.cursor = 'pointer';
        confirmSubmitBtn.style.pointerEvents = 'auto';
    }
    if (cancelSubmitBtn) {
        cancelSubmitBtn.disabled = false;
        cancelSubmitBtn.innerHTML = '❌ Batal';
        cancelSubmitBtn.style.opacity = '1';
        cancelSubmitBtn.style.cursor = 'pointer';
        cancelSubmitBtn.style.pointerEvents = 'auto';
    }
    if (confirmationModalClose) {
        confirmationModalClose.disabled = false;
        confirmationModalClose.style.opacity = '1';
        confirmationModalClose.style.pointerEvents = 'auto';
    }
}

function closeConfirmationModal() {
    CONFIG.log('🔒 closeConfirmationModal called');
    
    const confirmationModal = document.getElementById('confirmationModal');
    if (confirmationModal) {
        confirmationModal.style.display = 'none';
        confirmationModal.style.pointerEvents = 'auto';
    }
    
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const confirmationDetails = document.getElementById('confirmationDetails');
    const modalClose = confirmationModal?.querySelector('.modal-close');
    
    if (confirmSubmitBtn) {
        confirmSubmitBtn.disabled = false;
        confirmSubmitBtn.innerHTML = '✅ Ya, Submit Data';
        confirmSubmitBtn.style.display = 'inline-block';
        confirmSubmitBtn.style.pointerEvents = 'auto';
    }
    if (cancelSubmitBtn) {
        cancelSubmitBtn.disabled = false;
        cancelSubmitBtn.innerHTML = '❌ Batal';
        cancelSubmitBtn.style.opacity = '1';
        cancelSubmitBtn.style.pointerEvents = 'auto';
    }
    if (modalClose) {
        modalClose.disabled = false;
        modalClose.style.opacity = '1';
        modalClose.style.pointerEvents = 'auto';
    }
    if (confirmationDetails) {
        confirmationDetails.innerHTML = '';
    }
    
    isSubmitting = false;
    
    CONFIG.log('✅ Modal state reset completed');
}

// HELPER FUNCTION: Extract month/year dari selected date
function getSelectedMonthYear() {
    try {
        let selectedDate = window.selectedDate;
        
        console.log('🐛 DEBUG window.selectedDate:', window.selectedDate);
        
        if (!selectedDate) {
            selectedDate = getFormDateValue();
        }
        
        console.log('🐛 DEBUG final selectedDate:', selectedDate);
        
        let month, year;
        
        if (selectedDate instanceof Date) {
            month = selectedDate.getMonth() + 1;
            year = selectedDate.getFullYear();
        } else if (typeof selectedDate === 'string') {
            const dateParts = selectedDate.split('-');
            if (dateParts.length === 3) {
                year = parseInt(dateParts[0]);
                month = parseInt(dateParts[1]);
            }
        }
        
        if (!month || !year) {
            const currentDate = new Date();
            month = currentDate.getMonth() + 1;
            year = currentDate.getFullYear();
        }
        
        console.log('🐛 FINAL month/year:', month, year);
        return { month, year };
        
    } catch (error) {
        console.error('❌ Error getting month/year:', error);
        const currentDate = new Date();
        return {
            month: currentDate.getMonth() + 1,
            year: currentDate.getFullYear()
        };
    }
}

// 📱 MOBILE RESPONSIVE FUNCTIONS
function setupMobileResponsive() {
    forceMobileLayout();
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(forceMobileLayout, 100);
    });
    
    window.addEventListener('orientationchange', function() {
        setTimeout(forceMobileLayout, 200);
    });
}

function forceMobileLayout() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile && CONFIG.FORCE_MOBILE_LAYOUT) {
        document.body.classList.add('mobile-view');
        CONFIG.log('Mobile styles applied');
    } else {
        document.body.classList.remove('mobile-view');
        CONFIG.log('Desktop styles applied');
    }
}

// 💰 RUPIAH INPUT HANDLERS
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function unformatNumber(str) {
    return str.replace(/\./g, '');
}

function handleNominalInput() {
    let value = this.value.replace(/[^0-9]/g, '');
    if (value) {
        this.value = formatNumber(value);
    } else {
        this.value = '';
    }
    updatePreview();
}

function handleNominalFocus() {
    let rawValue = unformatNumber(this.value);
    this.value = rawValue;
}

function handleNominalBlur() {
    let value = this.value.replace(/[^0-9]/g, '');
    if (value) {
        this.value = formatNumber(value);
    }
}

// 🎯 KIT SELECTION FUNCTIONS
function selectAllKits() {
    availableKits.forEach(kit => kit.isSelected = true);
    selectedKits = [...availableKits];
    updateKitDisplay();
    updateKitSummary();
    updateButtonStates();
    
    // Update stepper navigation buttons
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
        window.stepperNav.updateNavigationButtons();
    }
}

function deselectAllKits() {
    availableKits.forEach(kit => kit.isSelected = false);
    selectedKits = [];
    updateKitDisplay();
    updateKitSummary();
    updateButtonStates();
    
    // Update stepper navigation buttons
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
        window.stepperNav.updateNavigationButtons();
    }
}

function showKitSelection() {
    if (availableKits.length > 0) {
        updateKitDisplay();
        updateKitSummary();
        elements.kitSelectionSection.style.display = 'block';
    }
}

function hideKitSelection() {
    elements.kitSelectionSection.style.display = 'none';
}

function updateKitDisplay() {
    elements.kitList.innerHTML = '';

    availableKits.forEach((kit, index) => {
        const kitItem = document.createElement('div');

        // 🔧 FIX: Get client name from individual KIT
        const clientName = kit.clientName || 'Unknown Client';

        // Format nominal jika sudah diisi
        const nominalValue = kit.nominal ? formatRupiahInput(kit.nominal) : '';

        const serialInfo = kit.serialNumber ? `<div style="color: #94a3b8; font-size: 13px; margin-top: 2px;">📟 SN: ${kit.serialNumber}</div>` : '';
        const duplicateLabel = kit.isDuplicate ? '<div style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block; margin-top: 5px;">⚠️ DUPLICATE</div>' : '';

        // 🆕 NEW: Each KIT in its own box with clear borders
        kitItem.innerHTML = `
            <div style="background: ${kit.isSelected ? '#1e3a8a' : '#1e293b'}; border: 2px solid ${kit.isSelected ? '#3b82f6' : '#475569'}; border-radius: 10px; padding: 0; margin-bottom: 12px; transition: all 0.3s ease; box-shadow: ${kit.isSelected ? '0 4px 12px rgba(59, 130, 246, 0.3)' : '0 2px 6px rgba(0, 0, 0, 0.2)'}; overflow: hidden;">

                <!-- Client Name Header -->
                <div style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); padding: 10px 15px; border-bottom: 2px solid #134e4a;">
                    <div style="color: #f0fdfa; font-size: 13px; font-weight: 600;">
                        👤 ${clientName}
                    </div>
                </div>

                <!-- KIT Content -->
                <div style="padding: 15px;">
                    <!-- Checkbox & KIT Info Row -->
                <div style="display: flex; align-items: start; gap: 12px; margin-bottom: ${kit.isSelected ? '12px' : '0'};">
                    <input type="checkbox"
                           class="kit-checkbox"
                           id="kit-${index}"
                           data-kit="${kit.kitNumber}"
                           ${kit.isSelected ? 'checked' : ''}
                           style="margin-top: 4px; width: 20px; height: 20px; cursor: pointer; accent-color: #3b82f6;">

                    <div style="flex: 1;">
                        <div style="color: #f1f5f9; font-size: 15px; font-weight: 600; margin-bottom: 2px;">
                            🛰️ ${kit.kitNumber}
                        </div>
                        ${serialInfo}
                        <div style="color: #60a5fa; font-size: 13px; margin-top: 4px; font-weight: 500;">
                            📦 ${kit.paket}
                        </div>
                        ${duplicateLabel}
                    </div>
                </div>

                <!-- Nominal Input (shown when selected) -->
                <div class="kit-nominal-input" style="display: ${kit.isSelected ? 'block' : 'none'}; padding: 12px; background: #0f172a; border-radius: 8px; border: 2px solid #60a5fa; margin-bottom: 12px;">
                    <label style="display: block; color: #e0f2fe; font-size: 13px; font-weight: 600; margin-bottom: 8px;">
                        💰 Nominal Pembayaran:
                    </label>
                    <input type="text"
                           class="nominal-input-per-kit"
                           data-kit-index="${index}"
                           placeholder="Contoh: 100000 atau 100.000"
                           value="${nominalValue}"
                           style="width: 100%; padding: 10px 14px; background: #1e293b; border: 2px solid #475569; border-radius: 6px; color: #f1f5f9; font-size: 15px; font-family: 'Roboto Mono', monospace; font-weight: 600; transition: all 0.3s ease;">
                    <small style="color: #94a3b8; font-size: 11px; display: block; margin-top: 6px;">
                        ℹ️ Minimal Rp 1 - Wajib diisi
                    </small>
                </div>

                <!-- 🆕 NEW: Payment Type Dropdown (shown when selected) -->
                <div class="kit-payment-type" style="display: ${kit.isSelected ? 'block' : 'none'}; padding: 12px; background: #0f172a; border-radius: 8px; border: 2px solid #f59e0b;">
                    <label style="display: block; color: #fef3c7; font-size: 13px; font-weight: 600; margin-bottom: 8px;">
                        💳 Tipe Pembayaran:
                    </label>
                    <select class="payment-type-per-kit"
                            data-kit-index="${index}"
                            style="width: 100%; padding: 10px 14px; background: #1e293b; border: 2px solid #475569; border-radius: 6px; color: #f1f5f9; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <option value="">-- Pilih Tipe --</option>
                        <option value="Aktivasi" ${kit.tipePembayaran === 'Aktivasi' ? 'selected' : ''}>🚀 Aktivasi</option>
                        <option value="Perpanjangan" ${kit.tipePembayaran === 'Perpanjangan' ? 'selected' : ''}>🔄 Perpanjangan</option>
                        <option value="Migrasi" ${kit.tipePembayaran === 'Migrasi' ? 'selected' : ''}>🔀 Migrasi</option>
                    </select>
                    <small style="color: #94a3b8; font-size: 11px; display: block; margin-top: 6px;">
                        ℹ️ Pilih tipe pembayaran - Wajib diisi
                    </small>
                </div>
                </div>
            </div>
        `;

        // Checkbox click handler
        const checkbox = kitItem.querySelector('.kit-checkbox');
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            toggleKitSelection(index);
        });

        // 🔧 FIX: REMOVED - Click on box to toggle
        // User only wants checkbox itself to be clickable, not the whole box
        // kitItem.addEventListener('click', function(e) {
        //     // Don't toggle if clicking on input field or dropdown
        //     if (e.target.classList.contains('nominal-input-per-kit') ||
        //         e.target.classList.contains('payment-type-per-kit')) {
        //         return;
        //     }
        //     toggleKitSelection(index);
        // });

        // Nominal input handler
        const nominalInput = kitItem.querySelector('.nominal-input-per-kit');
        if (nominalInput) {
            nominalInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            nominalInput.addEventListener('input', function(e) {
                handleKitNominalInput(index, this.value);
            });

            nominalInput.addEventListener('focus', function() {
                this.value = kit.nominal || '';
                this.style.borderColor = '#3b82f6';
                this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
            });

            nominalInput.addEventListener('blur', function() {
                if (kit.nominal) {
                    this.value = formatRupiahInput(kit.nominal);
                }
                this.style.borderColor = '#475569';
                this.style.boxShadow = 'none';
            });
        }

        // 🆕 NEW: Payment type dropdown handler
        const paymentTypeSelect = kitItem.querySelector('.payment-type-per-kit');
        if (paymentTypeSelect) {
            paymentTypeSelect.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            paymentTypeSelect.addEventListener('change', function(e) {
                handleKitPaymentTypeChange(index, this.value);
            });

            paymentTypeSelect.addEventListener('focus', function() {
                this.style.borderColor = '#f59e0b';
                this.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.1)';
            });

            paymentTypeSelect.addEventListener('blur', function() {
                this.style.borderColor = '#475569';
                this.style.boxShadow = 'none';
            });
        }

        elements.kitList.appendChild(kitItem);
    });

    // 🔍 DEBUG: Export to window scope for stepper validation
    window.selectedKits = selectedKits;
}

// 🆕 Handle nominal input per KIT
function handleKitNominalInput(kitIndex, value) {
    // Remove non-numeric characters except digits
    const cleaned = value.replace(/[^\d]/g, '');
    const numericValue = parseInt(cleaned) || 0;

    // Update kit nominal
    availableKits[kitIndex].nominal = numericValue;

    // Update selectedKits if this kit is selected
    if (availableKits[kitIndex].isSelected) {
        const selectedIndex = selectedKits.findIndex(kit => kit.kitNumber === availableKits[kitIndex].kitNumber);
        if (selectedIndex !== -1) {
            selectedKits[selectedIndex].nominal = numericValue;
        }
    }

    // 🔍 DEBUG: Export to window scope for stepper validation
    window.selectedKits = selectedKits;

    console.log('💰 Nominal input updated:', {
        kitIndex: kitIndex,
        kitNumber: availableKits[kitIndex].kitNumber,
        nominal: numericValue,
        isSelected: availableKits[kitIndex].isSelected,
        selectedKitsCount: selectedKits.length,
        allSelectedKitsWithNominal: selectedKits.every(kit => kit.nominal && kit.nominal >= 1)
    });

    updateKitSummary();
    updateButtonStates();
    updateStep3Summary(); // Update Step 3 summary display

    // 🔍 DEBUG: Trigger stepper validation update
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
        console.log('🔄 Triggering stepper navigation update...');
        window.stepperNav.updateNavigationButtons();
    }
}

// 🆕 NEW: Handle payment type change per KIT
function handleKitPaymentTypeChange(kitIndex, value) {
    // Update kit payment type
    availableKits[kitIndex].tipePembayaran = value;

    // Update selectedKits if this kit is selected
    if (availableKits[kitIndex].isSelected) {
        const selectedIndex = selectedKits.findIndex(kit => kit.kitNumber === availableKits[kitIndex].kitNumber);
        if (selectedIndex !== -1) {
            selectedKits[selectedIndex].tipePembayaran = value;
        }
    }

    // 🔍 DEBUG: Export to window scope for stepper validation
    window.selectedKits = selectedKits;

    console.log('💳 Payment type updated:', {
        kitIndex: kitIndex,
        kitNumber: availableKits[kitIndex].kitNumber,
        tipePembayaran: value,
        isSelected: availableKits[kitIndex].isSelected,
        selectedKitsCount: selectedKits.length,
        allSelectedKitsHavePaymentType: selectedKits.every(kit => kit.tipePembayaran && kit.tipePembayaran !== '')
    });

    updateKitSummary();
    updateButtonStates();
    updateStep3Summary();

    // 🔍 DEBUG: Trigger stepper validation update
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
        console.log('🔄 Triggering stepper navigation update after payment type change...');
        window.stepperNav.updateNavigationButtons();
    }
}

// 🆕 Update Step 3 Summary Display
function updateStep3Summary() {
    const totalKitsCountEl = document.getElementById('totalKitsCount');
    const totalNominalDisplayEl = document.getElementById('totalNominalDisplay');

    if (!totalKitsCountEl || !totalNominalDisplayEl) return;

    const selectedCount = selectedKits.length;
    const totalNominal = selectedKits.reduce((sum, kit) => sum + (kit.nominal || 0), 0);

    totalKitsCountEl.textContent = selectedCount;
    totalNominalDisplayEl.textContent = formatRupiahInput(totalNominal);
}

// 🆕 Format rupiah for input display
function formatRupiahInput(amount) {
    if (!amount) return '';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

function toggleKitSelection(index) {
    availableKits[index].isSelected = !availableKits[index].isSelected;
    selectedKits = availableKits.filter(kit => kit.isSelected);

    // 🔍 DEBUG: Export to window scope for stepper validation
    window.selectedKits = selectedKits;

    console.log('✅ KIT selection toggled:', {
        kitIndex: index,
        kitNumber: availableKits[index].kitNumber,
        isSelected: availableKits[index].isSelected,
        selectedKitsCount: selectedKits.length,
        allSelectedKitsWithNominal: selectedKits.every(kit => kit.nominal && kit.nominal >= 1)
    });

    // Re-render to show/hide nominal input
    updateKitDisplay();
    updateKitSummary();
    updateButtonStates();
    updateStep3Summary(); // NEW: Update Step 3 summary when selection changes
    updatePreview();

    // Update stepper navigation buttons
    if (typeof window.stepperNav !== 'undefined' && typeof window.stepperNav.updateNavigationButtons === 'function') {
        console.log('🔄 Triggering stepper navigation update after KIT toggle...');
        window.stepperNav.updateNavigationButtons();
    }
}

function updateKitSummary() {
    const selectedCount = selectedKits.length;
    const totalCount = availableKits.length;
    const duplicateCount = availableKits.filter(kit => kit.isDuplicate).length;

    // Calculate total nominal from selected KITs
    const totalNominal = selectedKits.reduce((sum, kit) => sum + (kit.nominal || 0), 0);
    const formattedTotal = totalNominal > 0 ? formatRupiahInput(totalNominal) : 'Rp 0';

    let summaryText = `<strong>Dipilih: ${selectedCount} dari ${totalCount} KIT</strong>`;
    if (selectedCount > 0) {
        summaryText += `<br><small style="color: #3b82f6;">💰 Total Nominal: ${formattedTotal}</small>`;
    }
    if (duplicateCount > 0) {
        summaryText += `<br><small style="color: #fde68a;">⚠️ ${duplicateCount} KIT duplicate (tetap bisa dipilih)</small>`;
    }

    elements.kitSummary.innerHTML = summaryText;
}

// 👀 PREVIEW FUNCTIONS
function showPreview() {
    if (!isFormValid() || selectedKits.length === 0) return;
    
    const tanggal = getFormDateValue();
    const nama = elements.clientNameText.textContent.trim();
    const tipe = document.getElementById('tipePembayaran').value;
    const nominalInput = document.getElementById('nominal').value;
    const totalNominal = Number(unformatNumber(nominalInput));
    
    const formattedDate = formatDateForDisplay(tanggal);
    const formattedTotalNominal = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(totalNominal);
    
    document.getElementById('previewTanggal').textContent = formattedDate;
    document.getElementById('previewNama').textContent = nama;
    document.getElementById('previewKitCount').textContent = `${selectedKits.length} KIT`;
    document.getElementById('previewTipe').textContent = tipe;
    document.getElementById('previewNominal').textContent = formattedTotalNominal;
    document.getElementById('previewTotalNominal').textContent = formattedTotalNominal;
    
    const kitDetails = selectedKits.map((kit, index) => {
        const serialInfo = kit.serialNumber ? ` | SN: ${kit.serialNumber}` : '';
        return `<div style="padding: 5px 0; border-bottom: 1px solid #475569;">
            <strong>${index + 1}. 🛰️ ${kit.kitNumber}${serialInfo}</strong> (${kit.paket})
        </div>`;
    }).join('');
    
    document.getElementById('previewKitDetails').innerHTML = 
        `<h4 style="margin-bottom: 10px; color: #f1f5f9;">📦 Detail KIT yang Dipilih:</h4>${kitDetails}`;
    
    elements.previewSection.style.display = 'block';
    elements.previewSection.scrollIntoView({ behavior: 'smooth' });
}

// 🛠️ UTILITY FUNCTIONS
function showLoading() {
    elements.kitLoading.style.display = 'flex';
    elements.kitValidation.style.display = 'none';
}

function hideLoading() {
    elements.kitLoading.style.display = 'none';
}

function showValidationMessage(message, type) {
    elements.kitValidation.textContent = message;
    elements.kitValidation.className = `validation-message validation-${type}`;
    elements.kitValidation.style.display = 'block';
}

function clearValidation() {
    elements.kitValidation.style.display = 'none';
    clearClientData();
}

function clearClientData() {
    elements.clientNameText.textContent = 'Akan terisi otomatis setelah nomor KIT valid';
    elements.namaClientDisplay.classList.remove('filled');
}

function showBanner(message, type) {
    hideAllBanners();
    const banner = elements[`${type}Banner`];
    banner.textContent = message;
    banner.style.display = 'block';
}

function hideAllBanners() {
    elements.warningBanner.style.display = 'none';
    elements.errorBanner.style.display = 'none';
    elements.successBanner.style.display = 'none';
}

function updateButtonStates() {
    const allFieldsFilled = isFormValid();
    const hasSelectedKits = selectedKits.length > 0;
    
    elements.previewBtn.disabled = !allFieldsFilled || !hasSelectedKits;
    elements.submitBtn.disabled = !allFieldsFilled || !isKitValid || !hasSelectedKits;
}

function isFormValid() {
    const tanggal = getFormDateValue();
    const nama = elements.clientNameText.textContent.trim();

    // 🆕 NEW: Check if all selected KITs have nominal >= 10000 AND payment type
    let allKitsHaveValidNominal = true;
    let allKitsHaveValidPaymentType = true;
    if (selectedKits.length > 0) {
        allKitsHaveValidNominal = selectedKits.every(kit => kit.nominal && kit.nominal >= 10000);
        allKitsHaveValidPaymentType = selectedKits.every(kit => kit.tipePembayaran && kit.tipePembayaran !== '');
    }

    // 🔧 FIX: Check search field only if no KITs selected yet
    // If KITs already selected, we don't need to check the input field (which is cleared after adding)
    const searchMode = document.getElementById('searchMode').value;
    let searchFieldValid = false;

    if (selectedKits.length > 0) {
        // If we have selected KITs, search field is valid (KITs were already validated when added)
        searchFieldValid = true;
    } else if (searchMode === 'kit') {
        // Mode KIT: Cek input KIT (only if no KITs selected yet)
        const kit = document.getElementById('nomorKit').value.trim();
        searchFieldValid = kit.length > 0;
    } else {
        // Mode Client Name: Cek apakah nama sudah tervalidasi
        searchFieldValid = isKitValid;
    }

    const isValid = tanggal && searchFieldValid && nama && nama !== 'Akan terisi otomatis setelah nomor KIT valid' && allKitsHaveValidNominal && allKitsHaveValidPaymentType;

    // 🔍 DEBUG: Log validation details
    console.log('🔍 isFormValid() check:', {
        tanggal: !!tanggal,
        searchFieldValid: searchFieldValid,
        nama: nama,
        namaValid: nama && nama !== 'Akan terisi otomatis setelah nomor KIT valid',
        allKitsHaveValidNominal: allKitsHaveValidNominal,
        allKitsHaveValidPaymentType: allKitsHaveValidPaymentType,
        selectedKitsCount: selectedKits.length,
        isValid: isValid
    });

    // 🆕 UPDATED: Check per-KIT nominal AND payment type instead of global payment type
    return isValid;
}

function updatePreview() {
    updateButtonStates();
}

// 🔧 INITIALIZE ON DOM READY
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Starlink Form initialized with Enhanced Loading & Double POST Protection');
    initializeForm();
    testConnection();
});

// 🌍 EXPORT selectedKits to window scope for stepper.js validation
window.selectedKits = selectedKits;
window.availableKits = availableKits;

// 🌍 EXPORT resetFormAfterSuccess for stepper.js to call after auto-reset
window.resetFormAfterSuccess = resetFormAfterSuccess;

// 🔧 GLOBAL ERROR HANDLER
window.addEventListener('error', function(e) {
    CONFIG.error('Global error:', e.error);
});

window.addEventListener('unhandledrejection', function(e) {
    CONFIG.error('Unhandled promise rejection:', e.reason);
});

// ✅ EXPORT FOR DEBUGGING
if (CONFIG.DEBUG_MODE) {
    window.STARLINK_FORM_DEBUG = {
        api,
        elements,
        validationResult,
        availableKits,
        selectedKits,
        isSubmitting,
        submitStartTime,
        CONFIG: CONFIG,
        // Debug functions
        testDuplicateCheck: () => performDuplicateCheck(),
        openDuplicateModal: () => openDuplicateModal(),
        closeDuplicateModal: () => closeDuplicateModal(),
        showConfirmationModal: () => showConfirmationModal(),
        closeConfirmationModal: () => closeConfirmationModal(),
        // Custom date picker debug functions
        formatDateIndonesian,
        dateToLocalISOString,
        setupCustomDatePicker,
        getFormDateValue,
        formatDateForDisplay,
        // Submit testing functions
        handleSubmitSuccess: (result) => handleSubmitSuccess(result),
        handleSubmitError: (error) => handleSubmitError(error),
        confirmAndSubmit: () => confirmAndSubmit(),
        // Loading state functions
        showProgressiveLoadingUI: () => showProgressiveLoadingUI(),
        startLoadingProgressMonitor: () => startLoadingProgressMonitor(),
        stopLoadingProgressMonitor: () => stopLoadingProgressMonitor(),
        showSlowLoadingMessage: () => showSlowLoadingMessage(),
        showSlowConnectionWarning: () => showSlowConnectionWarning(),
        disableAllSubmitControls: () => disableAllSubmitControls(),
        enableAllControls: () => enableAllControls(),
        submitWithRetry: (formData) => submitWithRetry(formData),
        
        // Test loading animations
        testLoadingAnimations: () => {
            const modal = document.getElementById('confirmationModal');
            const details = document.getElementById('confirmationDetails');
            
            if (!modal || !details) {
                console.log('❌ Modal not found');
                return;
            }
            
            modal.style.display = 'flex';
            
            console.log('🎨 Testing Loading Animations...');
            
            // Test 1: Bouncing Balls
            setTimeout(() => {
                console.log('1️⃣ Bouncing Balls');
                details.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <h4 style="color: #f1f5f9; margin-bottom: 20px;">🎾 Bouncing Balls</h4>
                        <div class="loading-bounce">
                            <div class="ball"></div>
                            <div class="ball"></div>
                            <div class="ball"></div>
                            <div class="ball"></div>
                        </div>
                        <p style="color: #94a3b8;">Testing bouncing animation...</p>
                    </div>
                `;
            }, 1000);
            
            // Test 2: Pulsing Dots
            setTimeout(() => {
                console.log('2️⃣ Pulsing Dots');
                details.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <h4 style="color: #f1f5f9; margin-bottom: 20px;">⚪ Pulsing Dots</h4>
                        <div class="loading-dots">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                        <p style="color: #94a3b8;">Testing pulsing animation...</p>
                    </div>
                `;
            }, 3000);
            
            // Test 3: Progress Bar
            setTimeout(() => {
                console.log('3️⃣ Progress Bar');
                showProgressiveLoadingUI();
                startLoadingProgressMonitor();
            }, 5000);
            
            // Close after test
            setTimeout(() => {
                stopLoadingProgressMonitor();
                modal.style.display = 'none';
                console.log('✅ Animation test completed!');
            }, 11000);
        },
        
        // Debug function untuk melihat KIT structure
        logKitStructure: () => {
            if (availableKits.length > 0) {
                console.log('📦 CURRENT KIT STRUCTURE:');
                availableKits.forEach((kit, index) => {
                    console.log(`${index + 1}. KIT Number: "${kit.kitNumber}"`);
                    console.log(`   Serial Number: "${kit.serialNumber || 'N/A'}"`);
                    console.log(`   Paket: "${kit.paket}"`);
                    console.log(`   Selected: ${kit.isSelected}`);
                    console.log(`   Duplicate: ${kit.isDuplicate || false}`);
                });
            } else {
                console.log('📦 No KITs available');
            }
        },
        
        // Test enhanced display
        testEnhancedDisplay: () => {
            const mockKits = [
                {
                    kitNumber: 'KIT304096716',
                    serialNumber: 'SN123456789',
                    paket: 'Standard',
                    isSelected: true,
                    isDuplicate: false
                },
                {
                    kitNumber: 'KIT304096717',
                    serialNumber: 'SN987654321',
                    paket: 'Premium',
                    isSelected: false,
                    isDuplicate: true
                }
            ];
            
            console.log('🧪 Testing enhanced display with mock data:');
            mockKits.forEach(kit => {
                const serialInfo = kit.serialNumber ? ` | SN: ${kit.serialNumber}` : '';
                console.log(`🛰️ ${kit.kitNumber}${serialInfo} (${kit.paket}) - Selected: ${kit.isSelected}, Duplicate: ${kit.isDuplicate}`);
            });
        }
    };
}