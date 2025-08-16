// Main JavaScript for Attendance Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeComponents();
});

function initializeComponents() {
    // Password toggle functionality
    initializePasswordToggle();
    
    // Form validation
    initializeFormValidation();
    
    // Modal functionality
    initializeModals();
    
    // Mobile navigation
    initializeMobileNav();
    
    // Data tables
    initializeDataTables();
    
    // Charts (if needed)
    initializeCharts();
    
    // Real-time features
    initializeRealTime();
}

// Password toggle functionality
function initializePasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // Clear previous errors
    clearFieldError(field);
    
    // Required field validation
    if (required && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Please enter a valid email address');
            return false;
        }
    }
    
    // Phone validation
    if (field.name === 'phone' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            showFieldError(field, 'Please enter a valid phone number');
            return false;
        }
    }
    
    // Password validation
    if (type === 'password' && value) {
        if (value.length < 6) {
            showFieldError(field, 'Password must be at least 6 characters long');
            return false;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.parentElement.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentElement.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentElement.querySelector('.field-error');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

// Modal functionality
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal-close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            openModal(modalId);
        });
    });
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            closeModal(this.closest('.modal'));
        });
    });
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Mobile navigation
function initializeMobileNav() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-container')) {
                navMenu.classList.remove('active');
                mobileToggle.classList.remove('active');
            }
        });
    }
}

// Data tables functionality
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        addTableFeatures(table);
    });
}

function addTableFeatures(table) {
    // Add sorting functionality
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(table, this);
        });
    });
    
    // Add search functionality if search input exists
    const searchInput = document.querySelector(`[data-table="${table.id}"]`);
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTable(table, this.value);
        });
    }
}

function sortTable(table, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentElement.children).indexOf(header);
    const isAscending = !header.classList.contains('sort-asc');
    
    // Clear all sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add sort class to current header
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Sort as strings
        return isAscending 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

// Charts initialization (using Chart.js if available)
function initializeCharts() {
    if (typeof Chart !== 'undefined') {
        // Initialize attendance chart
        const attendanceChart = document.getElementById('attendanceChart');
        if (attendanceChart) {
            createAttendanceChart(attendanceChart);
        }
        
        // Initialize department chart
        const departmentChart = document.getElementById('departmentChart');
        if (departmentChart) {
            createDepartmentChart(departmentChart);
        }
    }
}

function createAttendanceChart(canvas) {
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Attendance',
                data: [95, 88, 92, 85, 90, 78, 82],
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function createDepartmentChart(canvas) {
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['IT', 'HR', 'Sales', 'Marketing', 'Finance'],
            datasets: [{
                data: [30, 15, 25, 20, 10],
                backgroundColor: [
                    'rgb(79, 70, 229)',
                    'rgb(6, 182, 212)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Real-time features
function initializeRealTime() {
    // Auto-refresh attendance data every 30 seconds
    if (window.location.pathname.includes('dashboard') || 
        window.location.pathname.includes('attendance')) {
        setInterval(refreshAttendanceData, 30000);
    }
    
    // Auto-save form data
    initializeAutoSave();
    
    // Live search
    initializeLiveSearch();
}

function refreshAttendanceData() {
    // Only refresh if user is active (to save resources)
    if (document.visibilityState === 'visible') {
        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.attendance_count) {
                updateAttendanceStats(data);
            }
        })
        .catch(error => {
            console.log('Auto-refresh failed:', error);
        });
    }
}

function updateAttendanceStats(data) {
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-card .number');
    if (statCards.length >= 4) {
        statCards[0].textContent = data.total_people || 0;
        statCards[1].textContent = data.present_today || 0;
        statCards[2].textContent = data.absent_today || 0;
        statCards[3].textContent = data.late_today || 0;
    }
}

// Auto-save functionality
function initializeAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', debounce(function() {
                saveFormData(form);
            }, 1000));
        });
        
        // Load saved data on page load
        loadFormData(form);
    });
}

function saveFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem(`form_${form.id}`, JSON.stringify(data));
}

function loadFormData(form) {
    const savedData = localStorage.getItem(`form_${form.id}`);
    
    if (savedData) {
        const data = JSON.parse(savedData);
        
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type !== 'password') {
                input.value = data[key];
            }
        });
    }
}

// Live search functionality
function initializeLiveSearch() {
    const searchInputs = document.querySelectorAll('[data-live-search]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            performLiveSearch(this);
        }, 300));
    });
}

function performLiveSearch(input) {
    const searchTerm = input.value.trim();
    const targetTable = document.querySelector(input.getAttribute('data-live-search'));
    
    if (targetTable) {
        filterTable(targetTable, searchTerm);
    }
}

// Utility functions
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

// Show loading spinner
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.style.margin = '0 auto';
    
    element.innerHTML = '';
    element.appendChild(spinner);
}

// Show success message
function showMessage(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alert, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// AJAX form submission
function submitFormAjax(form, callback) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<div class="spinner"></div> Processing...';
    submitButton.disabled = true;
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            if (callback) callback(data);
        } else {
            showMessage(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error occurred', 'error');
        console.error('Error:', error);
    })
    .finally(() => {
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Export functions for global use
window.AttendanceSystem = {
    openModal,
    closeModal,
    showMessage,
    submitFormAjax,
    validateForm,
    showLoading
};