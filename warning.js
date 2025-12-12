// warning.js - Gabungan Redirect (dari file user) + Fix Bug Warning (dari artifact 12)
(function() {
    'use strict';
    
    // Check for warning when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkExpiry, 1000);
    });
    
    function checkExpiry() {
        fetch('/warning_ajax.php', {
            method: 'GET',
            cache: 'no-cache'
        })
        .then(response => response.json())
        .then(data => {
            if (data.show_warning) {
                // Check limit hanya untuk warning
                if (shouldShowWarning()) {
                    showWarningBar(data);
                    recordWarningShown();
                }
            } else if (data.reason === 'already_expired') {
                return;
            }
        })
        .catch(error => {
            // Try alternative path if main path fails
            fetch('./warning_ajax.php', { method: 'GET', cache: 'no-cache' })
                .then(response => response.json())
                .then(data => {
                    if (data.show_warning) {
                        if (shouldShowWarning()) {
                            showWarningBar(data);
                            recordWarningShown();
                        }
                    } else if (data.reason === 'already_expired') {
                        return;
                    }
                })
                .catch(() => {}); // Silent fail
        });
    }
    
    // Redirect function ke expired.html yang elegant
    
    function shouldShowWarning() {
        try {
            const today = new Date().toDateString(); // Format: "Fri Jun 07 2025"
            const warningData = localStorage.getItem('mikhmon_warning_tracker');
            
            if (!warningData) {
                return true; // First time, show warning
            }
            
            const data = JSON.parse(warningData);
            
            // If different day, reset counter
            if (data.date !== today) {
                return true;
            }
            
            // If same day, check if already shown 2x
            return data.count < 2;
            
        } catch (e) {
            // If localStorage error, always show (fallback)
            return true;
        }
    }
    
    function recordWarningShown() {
        try {
            const today = new Date().toDateString();
            const warningData = localStorage.getItem('mikhmon_warning_tracker');
            
            let data = { date: today, count: 1 };
            
            if (warningData) {
                const existing = JSON.parse(warningData);
                if (existing.date === today) {
                    data.count = existing.count + 1;
                }
            }
            
            localStorage.setItem('mikhmon_warning_tracker', JSON.stringify(data));
            
        } catch (e) {
            // Silent fail if localStorage not available
        }
    }
    
    function showWarningBar(data) {
        // Remove existing warning if any
        var existing = document.getElementById('mikhmon-expiry-warning');
        if (existing) {
            existing.remove();
        }
        
        // Determine colors based on warning level - ELEGANT VERSION
        let bgGradient, borderColor, iconEmoji;
        switch(data.level) {
            case 'critical':
                bgGradient = 'linear-gradient(135deg, rgba(40, 50, 60, 0.95) 0%, rgba(60, 40, 50, 0.95) 100%)';
                borderColor = 'rgba(255, 87, 87, 0.6)';
                iconEmoji = '🚨';
                break;
            case 'warning':
                bgGradient = 'linear-gradient(135deg, rgba(45, 55, 65, 0.95) 0%, rgba(65, 45, 55, 0.95) 100%)';
                borderColor = 'rgba(255, 165, 2, 0.6)';
                iconEmoji = '⚠️';
                break;
            case 'info':
                bgGradient = 'linear-gradient(135deg, rgba(50, 60, 70, 0.95) 0%, rgba(70, 50, 60, 0.95) 100%)';
                borderColor = 'rgba(99, 180, 255, 0.6)';
                iconEmoji = '📅';
                break;
            default:
                bgGradient = 'linear-gradient(135deg, rgba(55, 65, 75, 0.95) 0%, rgba(75, 55, 65, 0.95) 100%)';
                borderColor = 'rgba(102, 126, 234, 0.6)';
                iconEmoji = '💡';
        }
        
        // Create elegant warning bar
        var warning = document.createElement('div');
        warning.id = 'mikhmon-expiry-warning';
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: ${bgGradient};
            color: white;
            padding: 18px 25px;
            text-align: center;
            z-index: 999999;
            border-bottom: 3px solid ${borderColor};
            border-top: 1px solid rgba(255,255,255,0.1);
            font-family: 'Segoe UI', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 15px;
            font-weight: 400;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.1);
            backdrop-filter: blur(25px);
            animation: slideDownElastic 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            letter-spacing: 0.2px;
        `;
        
        // Add CSS animations
        if (!document.getElementById('warning-animations')) {
            var style = document.createElement('style');
            style.id = 'warning-animations';
            style.textContent = `
                @keyframes slideDownElastic {
                    0% { 
                        transform: translateY(-100%) scale(0.95); 
                        opacity: 0; 
                    }
                    60% { 
                        transform: translateY(10px) scale(1.02); 
                        opacity: 0.9; 
                    }
                    100% { 
                        transform: translateY(0) scale(1); 
                        opacity: 1; 
                    }
                }
                
                @keyframes slideUpSmooth {
                    0% { 
                        transform: translateY(0) scale(1); 
                        opacity: 1; 
                    }
                    100% { 
                        transform: translateY(-100%) scale(0.95); 
                        opacity: 0; 
                    }
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.8; }
                }
                
                @keyframes countdown {
                    0% { width: 100%; }
                    100% { width: 0%; }
                }
                
                #mikhmon-expiry-warning .warning-icon {
                    animation: pulse 2s ease-in-out infinite;
                    display: inline-block;
                    margin-right: 8px;
                    font-size: 18px;
                    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
                }
                
                #mikhmon-expiry-warning .time-count {
                    background: rgba(255,255,255,0.15);
                    padding: 6px 14px;
                    border-radius: 25px;
                    font-weight: 600;
                    font-size: 14px;
                    margin: 0 6px;
                    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2), 0 2px 8px rgba(0,0,0,0.1);
                    border: 1px solid rgba(255,255,255,0.15);
                    backdrop-filter: blur(10px);
                }
                
                #mikhmon-expiry-warning .renewal-btn {
                    background: linear-gradient(45deg, rgba(255,255,255,0.12), rgba(255,255,255,0.08)) !important;
                    color: white !important;
                    font-weight: 500 !important;
                    text-decoration: none !important;
                    margin-left: 15px !important;
                    padding: 12px 24px !important;
                    border: 1px solid rgba(255,255,255,0.2) !important;
                    border-radius: 30px !important;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    backdrop-filter: blur(15px) !important;
                    position: relative !important;
                    overflow: hidden !important;
                    font-size: 14px !important;
                }
                
                #mikhmon-expiry-warning .renewal-btn::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.6s ease;
                }
                
                #mikhmon-expiry-warning .renewal-btn:hover::before {
                    left: 100%;
                }
                
                #mikhmon-expiry-warning .renewal-btn:hover {
                    background: rgba(255,255,255,0.2) !important;
                    border-color: rgba(255,255,255,0.35) !important;
                    transform: translateY(-1px) scale(1.02) !important;
                    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
                }
                
                #mikhmon-expiry-warning .close-btn {
                    background: rgba(255,255,255,0.1) !important;
                    border: 1px solid rgba(255,255,255,0.15) !important;
                    color: rgba(255,255,255,0.8) !important;
                    font-size: 14px !important;
                    margin-left: 15px !important;
                    cursor: pointer !important;
                    padding: 8px 10px !important;
                    border-radius: 50% !important;
                    transition: all 0.3s ease !important;
                    width: 32px !important;
                    height: 32px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    backdrop-filter: blur(10px) !important;
                }
                
                #mikhmon-expiry-warning .close-btn:hover {
                    background: rgba(255,255,255,0.2) !important;
                    transform: rotate(90deg) scale(1.05) !important;
                    border-color: rgba(255,255,255,0.25) !important;
                    color: white !important;
                }
                
                #mikhmon-expiry-warning .countdown-bar {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 2px;
                    background: ${borderColor};
                    animation: countdown 5s linear forwards;
                    border-radius: 0 0 0 2px;
                }
                
                ${data.level === 'critical' ? `
                #mikhmon-expiry-warning {
                    animation: slideDownElastic 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), 
                               pulseRed 1.5s ease-in-out infinite !important;
                }
                @keyframes pulseRed {
                    0%, 100% { box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.1); }
                    50% { box-shadow: 0 8px 32px rgba(255,87,87,0.4), 0 4px 16px rgba(255,87,87,0.2); }
                }
                ` : ''}
                
                @media (max-width: 768px) {
                    #mikhmon-expiry-warning {
                        padding: 15px 20px !important;
                        font-size: 14px !important;
                    }
                    #mikhmon-expiry-warning .renewal-btn {
                        margin: 10px 0 5px 0 !important;
                        display: block !important;
                        width: fit-content !important;
                        margin-left: auto !important;
                        margin-right: auto !important;
                    }
                    #mikhmon-expiry-warning .time-count {
                        font-size: 14px !important;
                        padding: 3px 10px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Create elegant warning content with countdown bar
        warning.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; justify-content: center;">
                    <span class="warning-icon">${iconEmoji}</span>
                    <strong style="margin-right: 8px;">${data.level === 'critical' ? 'URGENT!' : 'Peringatan:'}</strong> 
                    Halo&nbsp;<strong>${data.nama}</strong>,&nbsp;layanan akan berakhir dalam 
                    <span class="time-count">${data.time_display}</span>
                    <span style="opacity: 0.85; margin-left: 5px; font-size: 13px;">(${data.date})</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="${data.renewal_url}" class="renewal-btn">
                        <span style="font-size: 14px;">🔄</span>
                        <span>Perpanjang Sekarang</span>
                    </a>
                    <button onclick="hideWarning()" class="close-btn">
                        ✕
                    </button>
                </div>
            </div>
            <div class="countdown-bar"></div>
        `;
        
        // Add to page with smooth transition
        document.body.insertBefore(warning, document.body.firstChild);
        
        // Smooth body padding adjustment
        document.body.style.transition = 'padding-top 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => {
            document.body.style.paddingTop = '80px';
        }, 100);
        
        // Auto-hide after exactly 5 seconds
        setTimeout(function() {
            hideWarning();
        }, 5000);
    }
    
    // Global function untuk hide warning
    window.hideWarning = function() {
        var warning = document.getElementById('mikhmon-expiry-warning');
        if (warning && warning.parentElement) {
            warning.style.animation = 'slideUpSmooth 0.6s ease-out forwards';
            setTimeout(function() {
                if (warning.parentElement) {
                    warning.remove();
                    document.body.style.paddingTop = '0';
                }
            }, 600);
        }
    };
    
})();