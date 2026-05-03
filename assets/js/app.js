// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {

    // OTP input auto-advance
    const otpBoxes = document.querySelectorAll('.otp-box');
    if (otpBoxes.length) {
        otpBoxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
                assembleOtp();
            });
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !box.value && i > 0) otpBoxes[i - 1].focus();
            });
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                paste.split('').forEach((ch, j) => {
                    if (otpBoxes[i + j]) otpBoxes[i + j].value = ch;
                });
                const next = otpBoxes[Math.min(i + paste.length, otpBoxes.length - 1)];
                if (next) next.focus();
                assembleOtp();
            });
        });

        function assembleOtp() {
            const hiddenInput = document.getElementById('otp_code');
            if (hiddenInput) hiddenInput.value = Array.from(otpBoxes).map(b => b.value).join('');
        }
    }

    // Password strength meter
    const pwdInput = document.getElementById('password');
    const strengthBar = document.getElementById('pwd-strength');
    if (pwdInput && strengthBar) {
        pwdInput.addEventListener('input', () => {
            const val = pwdInput.value;
            let score = 0;
            if (val.length >= 8)  score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            strengthBar.style.width = (score * 25) + '%';
            strengthBar.style.background = colors[score] || '';
            const lbl = document.getElementById('pwd-label');
            if (lbl) { lbl.textContent = labels[score] || ''; lbl.style.color = colors[score] || ''; }
        });
    }

    // Toggle password visibility
    document.querySelectorAll('.toggle-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            if (!target) return;
            const isText = target.type === 'text';
            target.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirm delete
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Search filter for student table
    const searchInput = document.getElementById('student-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase();
            document.querySelectorAll('#student-table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // Permission checkbox select-all toggle
    const selectAll = document.getElementById('perm-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.perm-check').forEach(cb => cb.checked = selectAll.checked);
        });
    }

});

// Show loading overlay on form submit
function showLoading() {
    const overlay = document.querySelector('.spinner-overlay');
    if (overlay) overlay.classList.add('active');
}
