/**
 * ============================================
 * 🎯 STEPPER NAVIGATION SYSTEM
 * Modern Multi-Step Form with Validation
 * ============================================
 */

// Global State
let currentStep = 1;
const totalSteps = 4;
let formData = {
    searchMode: 'kit',
    tanggalPembayaran: '',
    nomorKit: '',
    clientName: '',
    selectedKits: [],
    tipePembayaran: '',
    nominal: '',
    transactionId: ''
};

// Initialize on page load - WAIT for app.js to finish
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for app.js to initialize first
    setTimeout(() => {
        console.log('🚀 Stepper initialized');
        initializeStepper();
        setupStepNavigation();
        setupFormValidation();
    }, 100);
    
    // Keep existing functionality from original app.js
    // This assumes app.js handles KIT validation, date picker, etc.
});

/**
 * Initialize Stepper UI
 */
function initializeStepper() {
    updateStepperUI();
    updateNavigationButtons();
}

/**
 * Setup Step Navigation Buttons
 */
function setupStepNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Safety check
    if (!prevBtn || !nextBtn || !submitBtn) {
        console.error('❌ Stepper navigation buttons not found');
        return;
    }
    
    // Next Button
    nextBtn.addEventListener('click', function() {
        if (validateCurrentStep()) {
            goToNextStep();
        } else {
            showError('Mohon lengkapi semua field yang required sebelum melanjutkan');
        }
    });
    
    // Previous Button
    prevBtn.addEventListener('click', function() {
        goToPreviousStep();
    });
    
    // Submit Button (handled by existing form submit logic)
    submitBtn.addEventListener('click', function(e) {
        // Let the original form submission logic handle this
        console.log('📝 Submit clicked');
    });
    
    // Allow clicking on completed steps to go back
    document.querySelectorAll('.step-item').forEach(stepItem => {
        stepItem.addEventListener('click', function() {
            const targetStep = parseInt(this.dataset.step);
            if (targetStep < currentStep) {
                goToStep(targetStep);
            }
        });
    });
}

/**
 * Go to Next Step
 */
function goToNextStep() {
    if (currentStep < totalSteps) {
        // Mark current step as completed
        markStepCompleted(currentStep);
        
        currentStep++;
        updateStepperUI();
        updateNavigationButtons();
        scrollToTop();
        
        // Auto-preview on step 4 with multiple retry attempts
        if (currentStep === 4) {
            console.log('📋 Step 4 reached - Starting preview load sequence...');
            
            // Immediate attempt
            updateLocalPreview();
            
            // Retry with increasing delays
            setTimeout(() => {
                console.log('📋 Preview retry #1');
                updateLocalPreview();
            }, 100);
            
            setTimeout(() => {
                console.log('📋 Preview retry #2');
                updateLocalPreview();
            }, 300);
            
            setTimeout(() => {
                console.log('📋 Preview retry #3 (final)');
                updateLocalPreview();
            }, 600);
        }
    }
}

/**
 * Go to Previous Step
 */
function goToPreviousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepperUI();
        updateNavigationButtons();
        scrollToTop();
    }
}

/**
 * Go to Specific Step
 */
function goToStep(stepNumber) {
    if (stepNumber >= 1 && stepNumber <= totalSteps && stepNumber < currentStep) {
        currentStep = stepNumber;
        updateStepperUI();
        updateNavigationButtons();
        scrollToTop();
    }
}

/**
 * Update Stepper UI
 */
function updateStepperUI() {
    // Update step items
    document.querySelectorAll('.step-item').forEach(stepItem => {
        const stepNumber = parseInt(stepItem.dataset.step);
        
        // Remove all states
        stepItem.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            stepItem.classList.add('active');
        } else if (stepNumber < currentStep) {
            stepItem.classList.add('completed');
        }
    });
    
    // Update step content
    document.querySelectorAll('.step-content').forEach(content => {
        const stepNumber = parseInt(content.dataset.stepContent);
        content.classList.toggle('active', stepNumber === currentStep);
    });
    
    // Update lines
    updateStepLines();
}

/**
 * Update Step Connection Lines
 */
function updateStepLines() {
    const lines = document.querySelectorAll('.step-line');
    lines.forEach((line, index) => {
        const stepBefore = index + 1;
        if (stepBefore < currentStep) {
            line.classList.add('completed');
        } else {
            line.classList.remove('completed');
        }
    });
}

/**
 * Mark Step as Completed
 */
function markStepCompleted(stepNumber) {
    const stepItem = document.querySelector(`.step-item[data-step="${stepNumber}"]`);
    if (stepItem) {
        stepItem.classList.add('completed');
    }
}

/**
 * Update Navigation Buttons
 */
function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Show/Hide Previous button
    if (prevBtn) {
        prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
    }
    
    // Show/Hide Next vs Submit button
    if (currentStep === totalSteps) {
        if (nextBtn) nextBtn.style.display = 'none';
        if (submitBtn) {
            submitBtn.style.display = 'flex';
            // Enable submit button on step 4
            submitBtn.disabled = false;
        }
    } else {
        if (nextBtn) {
            nextBtn.style.display = 'flex';
            // Enable/Disable Next button based on validation
            nextBtn.disabled = !validateCurrentStep();
        }
        if (submitBtn) submitBtn.style.display = 'none';
    }
}

/**
 * Validate Current Step
 */
function validateCurrentStep() {
    switch(currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        case 4:
            return true; // Preview step, always valid
        default:
            return false;
    }
}

/**
 * Validate Step 1: Search Mode & Date
 */
function validateStep1() {
    const searchMode = document.getElementById('searchMode')?.value;
    const tanggalPembayaran = document.getElementById('tanggalPembayaran')?.value;
    
    // Valid if search mode selected AND date is filled
    const isValid = searchMode && tanggalPembayaran && tanggalPembayaran.trim() !== '';
    
    console.log('🔍 Step 1 Validation:', {
        searchMode: searchMode,
        tanggalPembayaran: tanggalPembayaran,
        isValid: isValid
    });
    
    if (isValid) {
        formData.searchMode = searchMode;
        formData.tanggalPembayaran = tanggalPembayaran;
    }
    
    return isValid;
}

/**
 * Validate Step 2: KIT Selection & Client
 */
function validateStep2() {
    const selectedKits = document.querySelectorAll('.kit-checkbox:checked');
    const clientNameText = document.getElementById('clientNameText');
    const clientName = clientNameText ? clientNameText.textContent.trim() : '';
    
    // Check if client name is filled (not the default placeholder text)
    const isClientNameValid = clientName && 
                              clientName !== 'Akan terisi otomatis setelah nomor KIT valid' &&
                              clientName !== '';
    
    // Valid if at least 1 KIT selected AND client name is filled
    const isValid = selectedKits.length > 0 && isClientNameValid;
    
    console.log('🔍 Step 2 Validation:', {
        selectedKitsCount: selectedKits.length,
        clientName: clientName,
        isClientNameValid: isClientNameValid,
        isValid: isValid
    });
    
    if (isValid) {
        formData.selectedKits = Array.from(selectedKits).map(cb => ({
            kitNumber: cb.dataset.kit,
            package: cb.dataset.package
        }));
        formData.clientName = clientName;
    }
    
    return isValid;
}

/**
 * Validate Step 3: Payment Details
 * UPDATED: Check per-KIT nominal AND payment type validation
 */
function validateStep3() {
    // 🆕 NEW: No longer need global payment type
    // Each KIT has its own payment type now

    // Access from window scope (defined in app.js)
    const selectedKits = window.selectedKits || [];

    console.log('🔍 Step 3 Validation - START:', {
        currentStep: currentStep,
        windowSelectedKitsExists: !!window.selectedKits,
        selectedKitsCount: selectedKits.length
    });

    // 🆕 NEW: Check if all selected KITs have valid nominal AND payment type
    let allKitsHaveValidNominal = selectedKits.length > 0 &&
                                  selectedKits.every(kit => kit.nominal && kit.nominal >= 1);

    let allKitsHaveValidPaymentType = selectedKits.length > 0 &&
                                      selectedKits.every(kit => kit.tipePembayaran && kit.tipePembayaran !== '');

    // Detailed per-KIT validation check
    const kitValidationDetails = selectedKits.map(kit => ({
        kitNumber: kit.kitNumber,
        nominal: kit.nominal,
        hasNominal: !!kit.nominal,
        nominalValid: kit.nominal && kit.nominal >= 1,
        nominalFormatted: kit.nominal ? `Rp ${kit.nominal.toLocaleString('id-ID')}` : 'NOT SET',
        tipePembayaran: kit.tipePembayaran || 'NOT SET',
        paymentTypeValid: kit.tipePembayaran && kit.tipePembayaran !== ''
    }));

    console.log('🔍 Step 3 Validation - KIT Details:', kitValidationDetails);

    // Check individual validations
    const hasSelectedKits = selectedKits.length > 0;
    const allKitsHaveNominal = allKitsHaveValidNominal;
    const allKitsHavePaymentType = allKitsHaveValidPaymentType;

    const isValid = hasSelectedKits && allKitsHaveNominal && allKitsHavePaymentType;

    console.log('🔍 Step 3 Validation - RESULT:', {
        hasSelectedKits: hasSelectedKits,
        selectedKitsCount: selectedKits.length,
        allKitsHaveValidNominal: allKitsHaveValidNominal,
        allKitsHaveValidPaymentType: allKitsHaveValidPaymentType,
        isValid: isValid,
        failureReason: !isValid ? (
            !hasSelectedKits ? 'No KITs selected' :
            !allKitsHaveNominal ? 'Some KITs have invalid nominal' :
            !allKitsHavePaymentType ? 'Some KITs missing payment type' :
            'Unknown'
        ) : 'All valid'
    });

    if (isValid) {
        // No need to set global payment type anymore
        console.log('✅ Step 3 validation PASSED - All KITs have valid nominal and payment type');
    } else {
        console.error('❌ Step 3 validation FAILED');
    }

    return isValid;
}

/**
 * Setup Form Validation (Real-time)
 */
function setupFormValidation() {
    // Listen to form changes for real-time validation
    const form = document.getElementById('paymentForm');
    if (!form) return;
    
    form.addEventListener('change', function(e) {
        console.log('📝 Form change detected:', e.target.id || e.target.className);
        updateNavigationButtons();
    });
    
    form.addEventListener('input', function(e) {
        updateNavigationButtons();
    });
    
    // Listen to KIT checkbox changes with event delegation
    document.addEventListener('change', function(e) {
        // Update navigation buttons when KIT checkbox is changed
        if (e.target && e.target.classList.contains('kit-checkbox')) {
            console.log('📦 KIT checkbox changed, updating navigation buttons');
            setTimeout(() => {
                updateNavigationButtons();
            }, 100);
        }
    });

    // 🔍 DEBUG: Listen to nominal input changes with event delegation
    document.addEventListener('input', function(e) {
        // Update navigation buttons when nominal input is changed
        if (e.target && e.target.classList.contains('nominal-input-per-kit')) {
            console.log('💰 Nominal input detected in stepper.js:', {
                kitIndex: e.target.dataset.kitIndex,
                value: e.target.value,
                currentStep: currentStep
            });
            setTimeout(() => {
                console.log('🔄 Updating navigation buttons after nominal input...');
                updateNavigationButtons();
            }, 100);
        }
    });
    
    // Listen to client name changes (filled by app.js)
    const clientNameObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                console.log('👤 Client name changed');
                setTimeout(() => {
                    updateNavigationButtons();
                }, 100);
            }
        });
    });
    
    const clientNameText = document.getElementById('clientNameText');
    if (clientNameText) {
        clientNameObserver.observe(clientNameText, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }
    
    // Listen to date changes (from date picker)
    const tanggalPembayaran = document.getElementById('tanggalPembayaran');
    if (tanggalPembayaran) {
        tanggalPembayaran.addEventListener('change', function() {
            console.log('📅 Date changed:', this.value);
            setTimeout(() => {
                updateNavigationButtons();
            }, 100);
        });
    }
    
    // Listen to date display changes (when date is selected)
    const dateDisplay = document.getElementById('dateDisplay');
    if (dateDisplay) {
        dateDisplay.addEventListener('click', function() {
            // After date picker closes, check validation
            setTimeout(() => {
                updateNavigationButtons();
            }, 500);
        });
    }
    
    // Observe tanggalIndonesia changes (updated by date picker)
    const tanggalIndonesiaObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                console.log('📅 Tanggal Indonesia display updated');
                setTimeout(() => {
                    updateNavigationButtons();
                }, 100);
            }
        });
    });
    
    const tanggalIndonesia = document.getElementById('tanggalIndonesia');
    if (tanggalIndonesia) {
        tanggalIndonesiaObserver.observe(tanggalIndonesia, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }
    
    // Prevent Enter key from submitting form when in Step 3
    // User should click Next button to go to Step 4 (preview)
    const formElement = document.getElementById('paymentForm');
    if (formElement) {
        formElement.addEventListener('keydown', function(e) {
            // If Enter key is pressed
            if (e.key === 'Enter') {
                // Check if we're in Step 3
                if (currentStep === 3) {
                    e.preventDefault();
                    console.log('⚠️ Enter key blocked in Step 3 - use Next button to proceed');
                    
                    // If form is valid, trigger Next button instead
                    if (validateCurrentStep()) {
                        const nextBtn = document.getElementById('nextBtn');
                        if (nextBtn && !nextBtn.disabled) {
                            nextBtn.click();
                        }
                    }
                    return false;
                }
                
                // In other steps, allow Enter key if needed
                // But generally prevent form submission until Step 4
                if (currentStep < 4) {
                    e.preventDefault();
                    console.log('⚠️ Enter key blocked - use navigation buttons');
                    return false;
                }
            }
        });
    }
    
    // Also specifically prevent Enter on nominal input
    const nominalInput = document.getElementById('nominal');
    if (nominalInput) {
        nominalInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                console.log('⚠️ Enter key blocked on nominal input');
                
                // If we're in step 3 and form is valid, trigger Next button
                if (currentStep === 3 && validateCurrentStep()) {
                    const nextBtn = document.getElementById('nextBtn');
                    if (nextBtn && !nextBtn.disabled) {
                        nextBtn.click();
                    }
                }
                return false;
            }
        });
    }
}

/**
 * Generate Transaction ID
 */
function generateTransactionId() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    
    return `TRX${year}${month}${day}${random}`;
}

/**
 * Update Transaction ID Display
 */
function updateTransactionIdDisplay() {
    const display = document.getElementById('transactionIdDisplay');
    const text = document.getElementById('transactionIdText');
    
    if (display && text && formData.transactionId) {
        text.textContent = `Transaction ID: ${formData.transactionId}`;
        display.classList.remove('hidden');
    }
}

/**
 * Update Preview Section (Local version for stepper)
 */
function updateLocalPreview() {
    console.log('📋 updateLocalPreview() called');
    
    const previewSection = document.getElementById('previewSection');
    if (!previewSection) {
        console.error('❌ previewSection element not found!');
        return;
    }
    
    // Get current form values directly from elements (real-time data)
    const tanggalIndonesiaEl = document.getElementById('tanggalIndonesia');
    const clientNameTextEl = document.getElementById('clientNameText');
    const tipePembayaranEl = document.getElementById('tipePembayaran');

    // 🆕 NEW: Get selected KITs from window scope (set by app.js)
    const selectedKitsData = window.selectedKits || [];

    console.log('📋 Elements found:', {
        tanggalIndonesia: !!tanggalIndonesiaEl,
        clientNameText: !!clientNameTextEl,
        tipePembayaran: !!tipePembayaranEl,
        selectedKitsData: selectedKitsData.length
    });

    const tanggalIndonesia = tanggalIndonesiaEl?.textContent || '-';
    const clientNameText = clientNameTextEl?.textContent || '-';
    const tipePembayaran = tipePembayaranEl?.value || '-';

    // 🆕 NEW: Calculate total nominal from selectedKits
    const totalNominal = selectedKitsData.reduce((sum, kit) => sum + (kit.nominal || 0), 0);
    const nominalFormatted = totalNominal > 0 ? `Rp ${totalNominal.toLocaleString('id-ID')}` : 'Rp 0';

    console.log('📋 Data retrieved:', {
        tanggal: tanggalIndonesia,
        client: clientNameText,
        tipe: tipePembayaran,
        nominal: nominalFormatted,
        totalNominal: totalNominal,
        selectedKitsCount: selectedKitsData.length
    });

    const kitCount = selectedKitsData.length;
    
    console.log('📋 Selected KITs:', kitCount);
    
    // Check if we have minimum required data
    if (tanggalIndonesia === '-' || clientNameText === '-' || kitCount === 0) {
        console.warn('⚠️ Some data is missing, showing loading state');
        previewSection.innerHTML = `
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <div style="font-size:48px;margin-bottom:15px;">⏳</div>
                <p style="font-size:16px;font-weight:600;color:#f1f5f9;margin-bottom:8px;">Loading Preview...</p>
                <p style="font-size:13px;">Data sedang dimuat, mohon tunggu sebentar...</p>
            </div>
        `;
        return;
    }
    
    // 🆕 NEW: Build KIT details HTML from window.selectedKits data with client name and payment type
    let kitDetailsHTML = '';
    if (kitCount > 0) {
        kitDetailsHTML = '<div style="background:#0f172a;padding:15px;border-radius:8px;border:1px solid #475569;margin-top:15px;"><h4 style="color:#94a3b8;font-size:14px;margin-bottom:12px;">📦 Detail KIT yang Dipilih:</h4><div style="display:flex;flex-direction:column;gap:10px;">';

        selectedKitsData.forEach((kit, index) => {
            const kitNumber = kit.kitNumber || 'Unknown';
            const kitPackage = kit.paket || 'Unknown';
            const kitNominal = kit.nominal ? `Rp ${kit.nominal.toLocaleString('id-ID')}` : 'Rp 0';
            const kitPaymentType = kit.tipePembayaran || 'Not Set';
            const paymentIcon = kit.tipePembayaran === 'Aktivasi' ? '🚀' : kit.tipePembayaran === 'Perpanjangan' ? '🔄' : kit.tipePembayaran === 'Migrasi' ? '🔀' : '❓';
            // 🔧 FIX: Get client name from individual KIT
            const kitClientName = kit.clientName || 'Unknown Client';

            console.log(`📦 KIT ${index + 1}:`, {kitNumber, kitPackage, clientName: kitClientName, nominal: kitNominal, paymentType: kitPaymentType});

            kitDetailsHTML += `
                <div style="background:#1e293b;border:2px solid #3b82f6;border-radius:8px;overflow:hidden;">
                    <!-- Client Name Header -->
                    <div style="background:linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);padding:8px 12px;">
                        <span style="color:#f0fdfa;font-size:12px;font-weight:600;">👤 ${kitClientName}</span>
                    </div>

                    <!-- KIT Info -->
                    <div style="padding:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                            <div style="flex:1;">
                                <div style="color:#f1f5f9;font-size:14px;font-weight:600;margin-bottom:4px;">
                                    ${index + 1}. 🛰️ ${kitNumber}
                                </div>
                                <div style="color:#94a3b8;font-size:11px;margin-bottom:4px;">
                                    📦 ${kitPackage}
                                </div>
                                <div style="color:#fbbf24;font-size:12px;font-weight:600;">
                                    ${paymentIcon} ${kitPaymentType}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="color:#10b981;font-size:14px;font-weight:700;background:#064e3b;padding:6px 10px;border-radius:6px;">
                                    ${kitNominal}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        kitDetailsHTML += '</div></div>';
    }
    
    // Build full preview HTML (without Transaction ID - it will be generated on submit)
    const previewHTML = `
        <h3 style="color:#3b82f6;margin-bottom:20px;font-size:18px;text-align:center;border-bottom:2px solid #334155;padding-bottom:12px;">📋 Preview Data Pembayaran</h3>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="preview-item" style="display:flex;justify-content:space-between;padding:12px 15px;background:#334155;border-radius:8px;border:1px solid #475569;">
                <span style="font-weight:600;color:#94a3b8;font-size:14px;">📅 Tanggal Pembayaran:</span>
                <span style="font-weight:700;color:#f1f5f9;font-size:14px;">${tanggalIndonesia}</span>
            </div>

            <div class="preview-item" style="display:flex;justify-content:space-between;padding:12px 15px;background:#334155;border-radius:8px;border:1px solid #475569;">
                <span style="font-weight:600;color:#94a3b8;font-size:14px;">📦 Jumlah KIT:</span>
                <span style="font-weight:700;color:#f1f5f9;font-size:14px;">${kitCount} KIT</span>
            </div>

            <div class="preview-item" style="display:flex;justify-content:space-between;padding:12px 15px;background:#334155;border-radius:8px;border:1px solid #475569;">
                <span style="font-weight:600;color:#94a3b8;font-size:14px;">💰 Nominal Total:</span>
                <span style="font-weight:700;color:#10b981;font-size:16px;">${nominalFormatted}</span>
            </div>
        </div>

        ${kitDetailsHTML}

        <div style="margin-top:20px;padding:15px;background:linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);border-radius:8px;text-align:center;color:white;border:2px solid #3b82f6;box-shadow:0 4px 15px rgba(59, 130, 246, 0.3);">
            <p style="font-size:13px;margin-bottom:5px;opacity:0.9;">📊 Total Pembayaran</p>
            <p style="font-size:24px;font-weight:700;margin:0;">${nominalFormatted}</p>
        </div>
        
        <div style="margin-top:15px;padding:12px;background:rgba(16, 185, 129, 0.1);border-radius:8px;border:1px solid #10b981;text-align:center;">
            <p style="color:#10b981;font-size:13px;margin:0;">✅ Silakan periksa kembali data di atas sebelum submit</p>
            <p style="color:#94a3b8;font-size:12px;margin:5px 0 0 0;">Transaction ID akan di-generate otomatis setelah submit</p>
        </div>
    `;
    
    previewSection.innerHTML = previewHTML;
    
    // Force display the preview section (override CSS display: none)
    previewSection.style.display = 'block';
    
    console.log('✅ Preview updated successfully with data');
}

/**
 * Show Error Message
 */
function showError(message) {
    const errorBanner = document.getElementById('errorBanner');
    if (errorBanner) {
        errorBanner.textContent = `⚠️ ${message}`;
        errorBanner.style.display = 'block';
        errorBanner.style.background = 'rgba(239, 68, 68, 0.1)';
        errorBanner.style.color = '#fca5a5';
        errorBanner.style.padding = '12px 15px';
        errorBanner.style.borderRadius = '8px';
        errorBanner.style.marginBottom = '15px';
        errorBanner.style.borderLeft = '4px solid #ef4444';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            errorBanner.style.display = 'none';
        }, 5000);
        
        scrollToTop();
    }
}

/**
 * Scroll to Top of Form
 */
function scrollToTop() {
    const container = document.querySelector('.container');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Enhanced Form Submit Handler
 */
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
    if (currentStep !== totalSteps) {
        e.preventDefault();
        showError('Mohon selesaikan semua step terlebih dahulu');
        return;
    }

    // Let the original app.js handle the actual submission
    console.log('📤 Form submitted with data:', formData);

    // 🔧 REMOVED: setupSuccessDetection() - dipanggil terlalu awal
    // Detection akan dipanggil di confirmAndSubmit() setelah user klik "Ya, Submit Data" di modal
    // setupSuccessDetection();
});

/**
 * Setup Success Detection (Multiple Strategies)
 */
// Global variable to track if reset is already scheduled
let resetScheduled = false;
let pollingIntervalId = null;
let successDetectionActive = false; // Track if detection should be active

function setupSuccessDetection() {
    console.log('🔍 Setting up success detection - will auto-reset to step 1 after success');

    // Reset flag when setting up detection
    resetScheduled = false;
    successDetectionActive = true; // Enable detection

    // 🔧 RE-ENABLED: Automatic form reset after success
    // Wait for success banner, then reset form to step 1 for new entry

    // Strategy 1: Watch for successBanner to appear
    const successBanner = document.getElementById('successBanner');
    if (successBanner) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const display = successBanner.style.display;

                    // 🔧 CRITICAL: Only trigger if we're on step 4 AND detection is active AND not already scheduled
                    const onSubmitStep = currentStep === 4;

                    if (display === 'block' &&
                        successBanner.textContent.trim() !== '' &&
                        !resetScheduled &&
                        successDetectionActive &&
                        onSubmitStep) {

                        console.log('✅ Success banner detected on step 4, will reset form in 3 seconds...');
                        resetScheduled = true; // Prevent multiple resets
                        successDetectionActive = false; // Disable further detection
                        observer.disconnect();

                        // Clear polling interval to prevent double trigger
                        if (pollingIntervalId) {
                            clearInterval(pollingIntervalId);
                            pollingIntervalId = null;
                            console.log('⏹️ Polling stopped - reset already scheduled');
                        }

                        setTimeout(() => {
                            resetFormAndStepper();
                        }, 3000); // Wait 3s for user to see success message
                    }
                }
            });
        });

        observer.observe(successBanner, {
            attributes: true,
            attributeFilter: ['style']
        });

        console.log('👁️ MutationObserver watching successBanner');
    }

    // Strategy 2: Polling check (backup method)
    let checkCount = 0;
    const maxChecks = 15; // Check for 15 seconds

    pollingIntervalId = setInterval(() => {
        checkCount++;

        const successBanner = document.getElementById('successBanner');

        // 🔧 CRITICAL: Only trigger if we're on step 4 AND detection is active AND not already scheduled
        const onSubmitStep = currentStep === 4;

        if (successBanner &&
            successBanner.style.display === 'block' &&
            successBanner.textContent.trim() !== '' &&
            !resetScheduled &&
            successDetectionActive &&
            onSubmitStep) {

            console.log('✅ Success detected via polling on step 4, resetting form...');
            resetScheduled = true; // Prevent multiple resets
            successDetectionActive = false; // Disable further detection
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;

            setTimeout(() => {
                resetFormAndStepper();
            }, 3000);
        }

        // Stop checking after maxChecks
        if (checkCount >= maxChecks) {
            console.log('⏹️ Stopped checking for success (timeout)');
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
        }
    }, 1000);

    console.log('🔄 Polling check started');
}

/**
 * Reset Form and Stepper to Step 1
 */
function resetFormAndStepper() {
    console.log('🔄 Resetting form and stepper to Step 1...');

    // 🔧 CRITICAL FIX: Clear polling interval to prevent double reset
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
        console.log('⏹️ Polling interval cleared during reset');
    }

    // 🔧 CRITICAL FIX: Hide success banner to prevent false positive detection
    const successBanner = document.getElementById('successBanner');
    if (successBanner) {
        successBanner.style.display = 'none';
        successBanner.textContent = '';
        console.log('👁️ Success banner hidden to prevent re-detection');
    }

    // 🔧 CRITICAL FIX: Call app.js resetFormAfterSuccess to properly reset all state
    // This ensures isKitValid, availableKits, selectedKits, and other states are cleared
    if (typeof window.resetFormAfterSuccess === 'function') {
        console.log('✅ Calling app.js resetFormAfterSuccess to reset all state variables...');
        window.resetFormAfterSuccess();
    } else {
        console.warn('⚠️ window.resetFormAfterSuccess not found - some states may not reset properly');
    }

    // 🔧 RE-ENABLED: Reset stepper to step 1 after success
    // User wants form to auto-reset for new entry
    currentStep = 1;

    // Reset formData
    formData = {
        searchMode: 'kit',
        tanggalPembayaran: '',
        nomorKit: '',
        clientName: '',
        selectedKits: [],
        tipePembayaran: '',
        nominal: '',
        transactionId: ''
    };

    // Clear form fields
    const nomorKitInput = document.getElementById('nomorKit');
    if (nomorKitInput) nomorKitInput.value = '';

    const clientNameText = document.getElementById('clientNameText');
    if (clientNameText) clientNameText.textContent = 'Akan terisi otomatis setelah nomor KIT valid';

    const tipePembayaran = document.getElementById('tipePembayaran');
    if (tipePembayaran) tipePembayaran.value = '';

    const nominal = document.getElementById('nominal');
    if (nominal) nominal.value = '';

    const tanggalIndonesia = document.getElementById('tanggalIndonesia');
    const tanggalPembayaran = document.getElementById('tanggalPembayaran');

    // Reset date to today instead of empty
    const today = new Date();
    const todayISO = today.toISOString().split('T')[0];

    if (tanggalPembayaran) {
        tanggalPembayaran.value = todayISO;
        console.log('📅 Reset tanggal to today:', todayISO);
    }

    // Update display texts - use formatDateIndonesian if available (from app.js)
    if (typeof formatDateIndonesian === 'function') {
        const indonesianDate = formatDateIndonesian(todayISO);

        if (tanggalIndonesia) {
            tanggalIndonesia.textContent = indonesianDate;
        }

        const dateText = document.getElementById('dateText');
        if (dateText) {
            dateText.textContent = indonesianDate;
        }
    } else {
        // Fallback: show simple format
        if (tanggalIndonesia) {
            tanggalIndonesia.textContent = todayISO;
        }
        const dateText = document.getElementById('dateText');
        if (dateText) {
            dateText.textContent = 'Klik untuk pilih tanggal';
        }
    }

    // Uncheck all KIT checkboxes
    document.querySelectorAll('.kit-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });

    // 🔧 RE-ENABLED: Reset step markers to show step 1
    // Reset all step markers
    document.querySelectorAll('.step-item').forEach(stepItem => {
        stepItem.classList.remove('active', 'completed');
    });

    // Mark step 1 as active
    const step1 = document.querySelector('.step-item[data-step="1"]');
    if (step1) step1.classList.add('active');

    // Update UI
    updateStepperUI();
    updateNavigationButtons();

    // Scroll to top
    scrollToTop();

    // 🔧 CRITICAL FIX: Reset the flag to allow detection for next submission
    resetScheduled = false;
    successDetectionActive = false; // Keep detection disabled until next submission
    console.log('🔄 Reset flags cleared - Detection will re-enable on next submission');

    // 🔧 REMOVED: Don't auto re-initialize success detection
    // It will be initialized when user actually submits next time
    // This prevents false positive detection from lingering success banners

    console.log('✅ Form reset complete - Ready for new entry');
}

// Export functions for use by app.js
window.stepperNav = {
    getCurrentStep: () => currentStep,
    goToStep: goToStep,
    updateNavigationButtons: updateNavigationButtons,
    setupSuccessDetection: setupSuccessDetection, // Export for app.js to call on submit
    formData: formData
};