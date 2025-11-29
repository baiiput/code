/**
 * Custom Date & DateTime Picker
 * Beautiful, responsive, and consistent across all devices
 *
 * Features:
 * - Works on all mobile devices consistently
 * - Touch-friendly interface
 * - Supports date and datetime-local
 * - Auto-formats display
 * - Beautiful animations
 * - Lightweight (no dependencies)
 */

class CustomDatePicker {
    constructor() {
        this.activeInput = null;
        this.pickerElement = null;
        this.selectedDate = null;
        this.selectedTime = { hour: '00', minute: '00' };
        this.includeTime = false;
        this.monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        this.dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        this.init();
    }

    init() {
        // Create picker element
        this.createPickerElement();

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeInputs());
        } else {
            this.initializeInputs();
        }

        // Handle clicks outside picker
        document.addEventListener('click', (e) => {
            if (this.pickerElement && !this.pickerElement.contains(e.target) &&
                !e.target.classList.contains('custom-date-input')) {
                this.hidePicker();
            }
        });
    }

    createPickerElement() {
        const picker = document.createElement('div');
        picker.className = 'custom-datepicker';
        picker.innerHTML = `
            <div class="datepicker-header">
                <button type="button" class="dp-prev-month" aria-label="Bulan Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="dp-current-month"></div>
                <button type="button" class="dp-next-month" aria-label="Bulan Berikutnya">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="datepicker-days-header"></div>
            <div class="datepicker-days"></div>
            <div class="datepicker-time" style="display: none;">
                <div class="time-inputs">
                    <div class="time-group">
                        <label>Jam</label>
                        <input type="number" class="dp-hour" min="0" max="23" value="00">
                    </div>
                    <div class="time-separator">:</div>
                    <div class="time-group">
                        <label>Menit</label>
                        <input type="number" class="dp-minute" min="0" max="59" value="00">
                    </div>
                </div>
            </div>
            <div class="datepicker-actions">
                <button type="button" class="dp-cancel">Batal</button>
                <button type="button" class="dp-today">Hari Ini</button>
                <button type="button" class="dp-confirm">OK</button>
            </div>
        `;

        document.body.appendChild(picker);
        this.pickerElement = picker;

        // Bind events
        this.bindPickerEvents();
    }

    bindPickerEvents() {
        const picker = this.pickerElement;

        // Month navigation
        picker.querySelector('.dp-prev-month').addEventListener('click', () => this.changeMonth(-1));
        picker.querySelector('.dp-next-month').addEventListener('click', () => this.changeMonth(1));

        // Action buttons
        picker.querySelector('.dp-cancel').addEventListener('click', () => this.hidePicker());
        picker.querySelector('.dp-today').addEventListener('click', () => this.selectToday());
        picker.querySelector('.dp-confirm').addEventListener('click', () => this.confirmSelection());

        // Time inputs
        const hourInput = picker.querySelector('.dp-hour');
        const minuteInput = picker.querySelector('.dp-minute');

        hourInput.addEventListener('input', (e) => {
            let val = parseInt(e.target.value) || 0;
            if (val > 23) val = 23;
            if (val < 0) val = 0;
            e.target.value = val.toString().padStart(2, '0');
            this.selectedTime.hour = e.target.value;
        });

        minuteInput.addEventListener('input', (e) => {
            let val = parseInt(e.target.value) || 0;
            if (val > 59) val = 59;
            if (val < 0) val = 0;
            e.target.value = val.toString().padStart(2, '0');
            this.selectedTime.minute = e.target.value;
        });
    }

    initializeInputs() {
        // Find all date and datetime-local inputs
        const inputs = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');

        inputs.forEach(input => {
            // Hide native input
            input.style.display = 'none';

            // Create custom display input
            const displayInput = document.createElement('input');
            displayInput.type = 'text';
            displayInput.className = 'custom-date-input';
            displayInput.placeholder = input.type === 'datetime-local' ? 'Pilih tanggal & waktu' : 'Pilih tanggal';
            displayInput.readOnly = true;

            // Copy attributes
            if (input.id) displayInput.setAttribute('data-for', input.id);
            if (input.required) displayInput.required = true;
            if (input.classList.length > 0) {
                input.classList.forEach(cls => displayInput.classList.add(cls));
            }

            // Set initial value if exists
            if (input.value) {
                displayInput.value = this.formatDisplayValue(input.value, input.type === 'datetime-local');
            }

            // Insert after native input
            input.parentNode.insertBefore(displayInput, input.nextSibling);

            // Bind click event
            displayInput.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showPicker(input, displayInput);
            });

            // Add icon
            const icon = document.createElement('i');
            icon.className = 'fas fa-calendar-alt custom-date-icon';
            icon.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showPicker(input, displayInput);
            });
            displayInput.parentNode.insertBefore(icon, displayInput.nextSibling);
        });
    }

    showPicker(nativeInput, displayInput) {
        this.activeInput = { native: nativeInput, display: displayInput };
        this.includeTime = nativeInput.type === 'datetime-local';

        // Parse current value
        if (nativeInput.value) {
            const date = new Date(nativeInput.value);
            this.selectedDate = date;
            if (this.includeTime) {
                this.selectedTime.hour = date.getHours().toString().padStart(2, '0');
                this.selectedTime.minute = date.getMinutes().toString().padStart(2, '0');
            }
        } else {
            this.selectedDate = new Date();
            if (this.includeTime) {
                this.selectedTime.hour = this.selectedDate.getHours().toString().padStart(2, '0');
                this.selectedTime.minute = this.selectedDate.getMinutes().toString().padStart(2, '0');
            }
        }

        // Show/hide time section
        const timeSection = this.pickerElement.querySelector('.datepicker-time');
        if (this.includeTime) {
            timeSection.style.display = 'block';
            this.pickerElement.querySelector('.dp-hour').value = this.selectedTime.hour;
            this.pickerElement.querySelector('.dp-minute').value = this.selectedTime.minute;
        } else {
            timeSection.style.display = 'none';
        }

        // Render calendar
        this.renderCalendar();

        // Position picker
        this.positionPicker(displayInput);

        // Show picker
        this.pickerElement.classList.add('active');

        // Prevent body scroll on mobile
        document.body.style.overflow = 'hidden';
    }

    hidePicker() {
        if (this.pickerElement) {
            this.pickerElement.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    positionPicker(input) {
        const rect = input.getBoundingClientRect();
        const picker = this.pickerElement;

        // Check if mobile
        const isMobile = window.innerWidth <= 768;

        if (isMobile) {
            // Center on screen for mobile
            picker.style.position = 'fixed';
            picker.style.left = '50%';
            picker.style.top = '50%';
            picker.style.transform = 'translate(-50%, -50%)';
        } else {
            // Position below input for desktop
            picker.style.position = 'absolute';
            picker.style.left = rect.left + 'px';
            picker.style.top = (rect.bottom + 5) + 'px';
            picker.style.transform = 'none';

            // Check if picker goes off-screen
            setTimeout(() => {
                const pickerRect = picker.getBoundingClientRect();
                if (pickerRect.right > window.innerWidth) {
                    picker.style.left = (window.innerWidth - pickerRect.width - 10) + 'px';
                }
                if (pickerRect.bottom > window.innerHeight) {
                    picker.style.top = (rect.top - pickerRect.height - 5) + 'px';
                }
            }, 0);
        }
    }

    renderCalendar() {
        const year = this.selectedDate.getFullYear();
        const month = this.selectedDate.getMonth();

        // Update header
        this.pickerElement.querySelector('.dp-current-month').textContent =
            `${this.monthNames[month]} ${year}`;

        // Render day names
        const daysHeader = this.pickerElement.querySelector('.datepicker-days-header');
        daysHeader.innerHTML = this.dayNames.map(day =>
            `<div class="dp-day-name">${day}</div>`
        ).join('');

        // Render days
        const daysContainer = this.pickerElement.querySelector('.datepicker-days');
        daysContainer.innerHTML = '';

        // First day of month
        const firstDay = new Date(year, month, 1).getDay();

        // Days in month
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Days in previous month
        const prevMonthDays = new Date(year, month, 0).getDate();

        // Add previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            const dayEl = this.createDayElement(day, 'prev-month');
            daysContainer.appendChild(dayEl);
        }

        // Add current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayEl = this.createDayElement(day, 'current-month');

            // Check if today
            const today = new Date();
            if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                dayEl.classList.add('today');
            }

            // Check if selected
            if (this.selectedDate && year === this.selectedDate.getFullYear() &&
                month === this.selectedDate.getMonth() && day === this.selectedDate.getDate()) {
                dayEl.classList.add('selected');
            }

            daysContainer.appendChild(dayEl);
        }

        // Add next month days
        const totalCells = daysContainer.children.length;
        const remainingCells = 42 - totalCells; // 6 rows x 7 days
        for (let day = 1; day <= remainingCells; day++) {
            const dayEl = this.createDayElement(day, 'next-month');
            daysContainer.appendChild(dayEl);
        }
    }

    createDayElement(day, type) {
        const dayEl = document.createElement('div');
        dayEl.className = `dp-day ${type}`;
        dayEl.textContent = day;

        if (type === 'current-month') {
            dayEl.addEventListener('click', () => this.selectDate(day));
        }

        return dayEl;
    }

    selectDate(day) {
        this.selectedDate.setDate(day);
        this.renderCalendar();
    }

    changeMonth(delta) {
        const newMonth = this.selectedDate.getMonth() + delta;
        this.selectedDate.setMonth(newMonth);
        this.renderCalendar();
    }

    selectToday() {
        this.selectedDate = new Date();
        if (this.includeTime) {
            this.selectedTime.hour = this.selectedDate.getHours().toString().padStart(2, '0');
            this.selectedTime.minute = this.selectedDate.getMinutes().toString().padStart(2, '0');
            this.pickerElement.querySelector('.dp-hour').value = this.selectedTime.hour;
            this.pickerElement.querySelector('.dp-minute').value = this.selectedTime.minute;
        }
        this.renderCalendar();
    }

    confirmSelection() {
        if (!this.activeInput || !this.selectedDate) return;

        let value;
        let displayValue;

        if (this.includeTime) {
            // Format: YYYY-MM-DDTHH:MM
            const year = this.selectedDate.getFullYear();
            const month = (this.selectedDate.getMonth() + 1).toString().padStart(2, '0');
            const day = this.selectedDate.getDate().toString().padStart(2, '0');
            value = `${year}-${month}-${day}T${this.selectedTime.hour}:${this.selectedTime.minute}`;
            displayValue = `${day}/${month}/${year} ${this.selectedTime.hour}:${this.selectedTime.minute}`;
        } else {
            // Format: YYYY-MM-DD
            const year = this.selectedDate.getFullYear();
            const month = (this.selectedDate.getMonth() + 1).toString().padStart(2, '0');
            const day = this.selectedDate.getDate().toString().padStart(2, '0');
            value = `${year}-${month}-${day}`;
            displayValue = `${day}/${month}/${year}`;
        }

        // Update values
        this.activeInput.native.value = value;
        this.activeInput.display.value = displayValue;

        // Trigger change event
        this.activeInput.native.dispatchEvent(new Event('change', { bubbles: true }));

        // Hide picker
        this.hidePicker();
    }

    formatDisplayValue(value, includeTime) {
        if (!value) return '';

        const date = new Date(value);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();

        if (includeTime) {
            const hour = date.getHours().toString().padStart(2, '0');
            const minute = date.getMinutes().toString().padStart(2, '0');
            return `${day}/${month}/${year} ${hour}:${minute}`;
        }

        return `${day}/${month}/${year}`;
    }
}

// Initialize when script loads
const customDatePicker = new CustomDatePicker();

// Re-initialize when new content is added (for AJAX loaded content)
window.reinitCustomDatePicker = function() {
    customDatePicker.initializeInputs();
};
