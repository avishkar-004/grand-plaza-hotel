/**
 * Hotel Management System - Main JavaScript
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {

    // Initialize tooltips
    initializeTooltips();

    // Initialize form validation
    initializeFormValidation();

    // Auto-dismiss alerts
    autoDismissAlerts();

    // Add fade-in animation to cards
    animateCards();

    // Form submit loading state
    initializeFormLoadingState();

    // Confirm before destructive actions
    initializeConfirmActions();

    // Password strength indicator
    initializePasswordStrength();

    // Date validation on booking form
    initializeDateValidation();

});

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Auto-dismiss alerts after 5 seconds
 */
function autoDismissAlerts() {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Animate cards on scroll
 */
function animateCards() {
    const cards = document.querySelectorAll('.card');

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, {
        threshold: 0.1
    });

    cards.forEach(card => {
        observer.observe(card);
    });
}

/**
 * Form submit loading state
 */
function initializeFormLoadingState() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.classList.contains('btn-loading')) {
                btn.classList.add('btn-loading');
                btn.disabled = true;
            }
        });
    });
}

/**
 * Confirm before destructive actions
 */
function initializeConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
}

/**
 * Password strength indicator
 */
function initializePasswordStrength() {
    const passwordInput = document.querySelector('input[name="new_password"]');
    if (passwordInput) {
        const indicator = document.createElement('div');
        indicator.className = 'progress mt-1';
        indicator.style.height = '4px';
        indicator.innerHTML = '<div class="progress-bar" role="progressbar"></div>';
        passwordInput.parentNode.appendChild(indicator);

        passwordInput.addEventListener('input', function() {
            let strength = 0;
            const val = this.value;
            if (val.length >= 8) strength += 25;
            if (/[A-Z]/.test(val)) strength += 25;
            if (/[a-z]/.test(val)) strength += 25;
            if (/[0-9]/.test(val)) strength += 25;
            const bar = indicator.querySelector('.progress-bar');
            bar.style.width = strength + '%';
            bar.className = 'progress-bar ' + (strength <= 25 ? 'bg-danger' : strength <= 50 ? 'bg-warning' : strength <= 75 ? 'bg-info' : 'bg-success');
        });
    }
}

/**
 * Date validation on booking form
 */
function initializeDateValidation() {
    const checkIn = document.querySelector('input[name="check_in"]');
    const checkOut = document.querySelector('input[name="check_out"]');
    if (checkIn && checkOut) {
        checkIn.min = new Date().toISOString().split('T')[0];
        checkIn.addEventListener('change', function() {
            const next = new Date(this.value);
            next.setDate(next.getDate() + 1);
            checkOut.min = next.toISOString().split('T')[0];
            if (checkOut.value && checkOut.value <= this.value) {
                checkOut.value = next.toISOString().split('T')[0];
            }
        });
    }
}

/**
 * Confirm action (for delete/cancel buttons)
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}

/**
 * Show loading spinner on form submit
 */
function showLoadingSpinner(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    button.disabled = true;

    // Store original text to restore later
    button.dataset.originalText = originalText;
}

/**
 * Hide loading spinner
 */
function hideLoadingSpinner(button) {
    if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
        button.disabled = false;
    }
}

/**
 * Format currency in INR
 */
function formatCurrency(amount) {
    return '\u20B9' + parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Validate email format
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

/**
 * AJAX Helper
 */
function ajax(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    const config = { ...defaults, ...options };

    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            throw error;
        });
}

// Export functions for use in other scripts
window.hotelApp = {
    confirmAction,
    showLoadingSpinner,
    hideLoadingSpinner,
    formatCurrency,
    validateEmail,
    showNotification,
    ajax
};

// Console message
console.log('%c Grand Plaza Hotel & Resort ', 'background: #1a3c5e; color: #c5a55a; padding: 5px 10px; border-radius: 3px; font-weight: bold;');
