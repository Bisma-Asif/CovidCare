document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');

    // 🔹 Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            authForms.forEach(form => {
                form.classList.remove('active');
                if (form.id === `${targetTab}-form`) {
                    form.classList.add('active');
                }
            });
        });
    });

    // 🔹 Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const inputId = this.id.replace('toggle-', '');
            const passwordInput = document.getElementById(inputId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    // 🔹 Input filled effect
    const allInputs = document.querySelectorAll('input, textarea, select');
    allInputs.forEach(input => {
        if (input.value) input.classList.add('filled');
        input.addEventListener('input', function () {
            if (this.value) {
                this.classList.add('filled');
            } else {
                this.classList.remove('filled');
            }
            this.classList.remove('error');
            const errorElement = document.getElementById(this.id + '-error');
            if (errorElement) errorElement.style.display = 'none';
        });
    });

    // 🔹 Validation functions
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    function validateRequired(id) {
        const field = document.getElementById(id);
        if (!field.value.trim()) {
            showError(`${id}-error`, field);
            return false;
        }
        return true;
    }
    function validatePhoneNumber(phone) {
        const cleanedPhone = phone.replace(/\D/g, '');
        return cleanedPhone.length === 11 && /^03\d{9}$/.test(cleanedPhone);
    }

    function showError(id, field = null) {
        const errorElement = document.getElementById(id);
        if (errorElement) errorElement.style.display = 'block';
        if (field) field.classList.add('error');
    }

    function clearErrors(formId) {
        const form = document.getElementById(formId);
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.style.display = 'none');
        const errorFields = form.querySelectorAll('.error');
        errorFields.forEach(field => field.classList.remove('error'));
    }

    // ------------------ LOGIN FORM ------------------
    document.getElementById('login-form').addEventListener('submit', function (e) {
        clearErrors('login-form');
        let isValid = true;

        const email = document.getElementById('login-email');
        const password = document.getElementById('login-password');

        if (!validateEmail(email.value)) {
            showError('login-email-error', email);
            isValid = false;
        }
        if (password.value.length < 6) {
            showError('login-password-error', password);
            isValid = false;
        }

        if (!isValid) e.preventDefault(); // ❌ Rokna sirf tab hai jab error ho
    });

    // ------------------ HOSPITAL REGISTER ------------------
    document.getElementById('hospital-form').addEventListener('submit', function (e) {
        clearErrors('hospital-form');
        let isValid = true;

        if (!validateRequired('h-first-name')) isValid = false;
        if (!validateRequired('h-last-name')) isValid = false;
        if (!validatePhoneNumber(document.getElementById('h-phone').value)) {
            showError('h-phone-error', document.getElementById('h-phone'));
            isValid = false;
        }
        if (!validateRequired('h-license')) isValid = false;
        if (!validateRequired('h-address')) isValid = false;
        if (!validateRequired('h-city')) isValid = false;

        const email = document.getElementById('h-email');
        if (!validateEmail(email.value)) {
            showError('h-email-error', email);
            isValid = false;
        }

        const password = document.getElementById('h-password');
        const confirmPassword = document.getElementById('h-confirm-password');
        if (password.value.length < 8) {
            showError('h-password-error', password);
            isValid = false;
        }
        if (password.value !== confirmPassword.value) {
            showError('h-confirm-password-error', confirmPassword);
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });

    // ------------------ PATIENT REGISTER ------------------
    document.getElementById('patient-form').addEventListener('submit', function (e) {
        clearErrors('patient-form');
        let isValid = true;

        if (!validateRequired('p-first-name')) isValid = false;
        if (!validateRequired('p-last-name')) isValid = false;

        if (!validatePhoneNumber(document.getElementById('p-phone').value)) {
            showError('p-phone-error', document.getElementById('p-phone'));
            isValid = false;
        }
        if (!validateRequired('p-dob')) isValid = false;

        const genderSelected = document.querySelector('input[name="p_gender"]:checked');
        if (!genderSelected) {
            showError('p-gender-error');
            isValid = false;
        }

        if (!validateRequired('p-address')) isValid = false;
        if (!validateRequired('p-city')) isValid = false;

        const email = document.getElementById('p-email');
        if (!validateEmail(email.value)) {
            showError('p-email-error', email);
            isValid = false;
        }

        const password = document.getElementById('p-password');
        const confirmPassword = document.getElementById('p-confirm-password');
        if (password.value.length < 6) {
            showError('p-password-error', password);
            isValid = false;
        }
        if (password.value !== confirmPassword.value) {
            showError('p-confirm-password-error', confirmPassword);
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });

});
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');

    // 🔹 Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            authForms.forEach(form => {
                form.classList.remove('active');
                if (form.id === `${targetTab}-form`) {
                    form.classList.add('active');
                }

                // 🔹 Hide success & error messages when switching tabs
                const successMsg = form.querySelector('.success-message');
                const errorMsg = form.querySelector('.error-message');
                if (successMsg) successMsg.style.display = 'none';
                if (errorMsg) errorMsg.style.display = 'none';
            });
        });
    });

    // 🔹 Auto-hide success messages after 7 seconds
    function autoHideSuccess(formId) {
        const form = document.getElementById(formId);
        const successMsg = form.querySelector('.success-message');
        if (successMsg && successMsg.style.display === 'block') {
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 3000); // 3000ms = 3 sec
        }
    }

    // Call autoHideSuccess after any form submit (example for patient form)
    document.getElementById('patient-form').addEventListener('submit', function () {
        setTimeout(() => autoHideSuccess('patient-form'), 100); // slight delay for DOM update
    });

    document.getElementById('hospital-form').addEventListener('submit', function () {
        setTimeout(() => autoHideSuccess('hospital-form'), 100);
    });

    document.getElementById('login-form').addEventListener('submit', function () {
        setTimeout(() => autoHideSuccess('login-form'), 100);
    });

});
