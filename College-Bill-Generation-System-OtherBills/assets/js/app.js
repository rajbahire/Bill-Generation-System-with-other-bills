// ============================================================
//  assets/js/app.js — Global JavaScript Utilities
//  Teacher Bill Management System
// ============================================================

// ---- Confirm dialog before destructive actions ----
function confirmAction(message) {
    return confirm(message || 'Are you sure?');
}

// ---- Auto-dismiss alerts after 4 seconds ----
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert.auto-dismiss');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.4s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 400);
        }, 4000);
    });
});

// ---- Format currency (Indian Rupees) ----
function formatINR(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// ---- Simple form validation ----
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    let valid = true;
    form.querySelectorAll('[required]').forEach(function (field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    return valid;
}

// ---- Set current date as default in date inputs ----
document.addEventListener('DOMContentLoaded', function () {
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"][data-default-today]').forEach(function (inp) {
        if (!inp.value) inp.value = today;
    });
});

// ---- Toggle password visibility ----
function togglePassword(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        if (icon) icon.textContent = '🙈';
    } else {
        inp.type = 'password';
        if (icon) icon.textContent = '👁';
    }
}

// ---- Live lecture total calculator (used on generate-bill page) ----
function calcBillTotal() {
    const count  = parseFloat(document.getElementById('total_lectures')?.value  || 0);
    const rate   = parseFloat(document.getElementById('rate_per_lecture')?.value || 0);
    const total  = count * rate;
    const el     = document.getElementById('calc_total');
    if (el) el.textContent = formatINR(total);
}
