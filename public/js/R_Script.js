// SHARED: Show/Hide password buttons for both registration pages.
document.querySelectorAll('.password-toggle').forEach(button => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    const label = input.id === 'confirmPassword' ? 'confirm password' : 'password';
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + label);
    });
});

// NORMAL REGISTRATION
(function () {
    const form = document.getElementById('registrationForm')
        || document.querySelector('form:not(#adminRegistrationForm)');
    if (!form) return;

    const password = form.elements.password;
    const confirmPassword = form.elements.confirmPassword;
    if (!password || !confirmPassword) return;

    const message = document.getElementById('passwordMessage');
    confirmPassword.addEventListener('input', function () {
        if (!message) return;
        const matches = password.value === confirmPassword.value;
        message.textContent = !confirmPassword.value ? '' : (matches ? 'Passwords match.' : 'Passwords do not match.');
    });

    form.addEventListener('submit', function (event) {
        const error = password.value.length < 8 ? 'Password must be at least 8 characters.'
            : password.value !== confirmPassword.value ? 'Passwords do not match.' : '';
        if (error) {
            alert(error);
            event.preventDefault();
        }
    });
})();

// ==========================================
// ADMIN REGISTRATION
// ==========================================
(() => {
    const form = document.getElementById('adminRegistrationForm');
    if (!form) return;

    // Open the PHP form so registration has a session and CSRF token.
    if (form.dataset.registerUrl) {
        window.location.replace(form.dataset.registerUrl);
        return;
    }

    const password = form.elements.password;
    const confirmation = form.elements.confirmPassword;
    const fields = Array.from(form.querySelectorAll('input:not([type="hidden"])'));
    const namePattern = /^[\p{L}\p{M} '\u2019-]+$/u;

    function validate(input) {
        input.setCustomValidity('');
        const value = input.value;
        let message = '';
        if (input.required && (input.type === 'radio' ? !form.querySelector('input[name="gender"]:checked') : !value.trim())) {
            message = input.type === 'radio' ? 'Please select a gender.' : 'This field is required.';
        } else if (input.hasAttribute('data-name') && value && !namePattern.test(value)) {
            message = 'Use letters, spaces, apostrophes, and hyphens only.';
        } else if (input.id === 'birthdate' && value > input.max) {
            message = 'Birthdate cannot be in the future.';
        } else if (input.id === 'contact' && value && !/^09[0-9]{9}$/.test(value)) {
            message = 'Enter an 11-digit mobile number starting with 09.';
        } else if (input.id === 'username' && value.trim().length < 4) {
            message = 'Use at least 4 characters for your username.';
        } else if (input.id === 'password' && value.length < 8) {
            message = 'Use at least 8 characters for your password.';
        } else if (input.id === 'password' && (new TextEncoder().encode(value).length > 72 || value.includes('\0'))) {
            message = 'Use at most 72 bytes and no null characters in your password.';
        } else if (input.id === 'confirmPassword' && value !== password.value) {
            message = 'Passwords must match exactly.';
        } else if (input.validity.typeMismatch) {
            message = 'Enter a valid email address.';
        } else if (!input.validity.valid) {
            message = input.validationMessage;
        }
        input.setCustomValidity(message);
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
        const target = document.getElementById(`${input.name}Error`);
        if (target) target.textContent = message;
        return !message;
    }

    fields.forEach(input => {
        input.addEventListener('blur', () => validate(input));
        input.addEventListener('input', () => {
            if (input.type === 'radio') {
                fields.filter(field => field.name === 'gender').forEach(validate);
            } else {
                validate(input);
            }
            if (input === password && confirmation.value) validate(confirmation);
        });
    });

    // Native constraints remain active without JavaScript. With JS, show every inline error.
    form.noValidate = true;
    form.addEventListener('submit', event => {
        const invalid = fields.filter(input => !validate(input));
        if (invalid.length) {
            event.preventDefault();
            invalid[0].focus();
        }
    });

})();
