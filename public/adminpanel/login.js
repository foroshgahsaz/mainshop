// ============= Validation & Error Handling =============
const ErrorMessages = {
    mobile: {
        required: 'لطفا شماره موبایل خود را وارد کنید',
        invalid: 'شماره موبایل باید 11 رقم و با 09 شروع شود',
        format: 'فقط اعداد مجاز هستند',
    },
    username: {
        required: 'لطفا نام کاربری خود را وارد کنید',
        minLength: 'نام کاربری باید حداقل 3 کاراکتر باشد',
        maxLength: 'نام کاربری نباید بیشتر از 20 کاراکتر باشد',
    },
    password: {
        required: 'لطفا رمز عبور خود را وارد کنید',
        minLength: 'رمز عبور باید حداقل 6 کاراکتر باشد',
    },
    otp: {
        required: 'لطفا کد تایید را وارد کنید',
        incomplete: 'لطفا کد 6 رقمی را کامل وارد کنید',
        invalid: 'کد وارد شده معتبر نیست',
        unavailable: 'ورود با پیامک هنوز فعال نشده. از تب «ورود با نام کاربری» استفاده کنید.',
    },
};

function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + 'Error');
    const inputElement = document.getElementById(fieldId + 'Input') || document.querySelector(`#${fieldId}`);

    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    if (inputElement) {
        inputElement.classList.add('error');
    }
}

function clearError(fieldId) {
    const errorElement = document.getElementById(fieldId + 'Error');
    const inputElement = document.getElementById(fieldId + 'Input') || document.querySelector(`#${fieldId}`);

    if (errorElement) {
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    }

    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

function validateMobile(mobile) {
    if (!mobile || mobile.trim() === '') {
        return { valid: false, message: ErrorMessages.mobile.required };
    }

    if (!/^\d+$/.test(mobile)) {
        return { valid: false, message: ErrorMessages.mobile.format };
    }

    if (mobile.length !== 11 || !mobile.startsWith('09')) {
        return { valid: false, message: ErrorMessages.mobile.invalid };
    }

    return { valid: true };
}

function validateOTP(otp) {
    if (!otp || otp.trim() === '') {
        return { valid: false, message: ErrorMessages.otp.required };
    }

    if (otp.length !== 6) {
        return { valid: false, message: ErrorMessages.otp.incomplete };
    }

    if (!/^\d{6}$/.test(otp)) {
        return { valid: false, message: ErrorMessages.otp.invalid };
    }

    return { valid: true };
}

let currentTab = 'mobile';
let timerInterval;

function switchTab(tab) {
    currentTab = tab;

    document.querySelectorAll('.auth-tab').forEach((button) => {
        button.classList.toggle('active', button.dataset.authTab === tab);
    });

    const mobileForm = document.getElementById('mobileForm');
    const usernameForm = document.getElementById('usernameForm');
    const loginPage = document.getElementById('loginPage');

    if (loginPage) {
        loginPage.dataset.activeTab = tab;
    }

    clearError('mobile');
    clearError('otp');

    if (tab === 'mobile') {
        mobileForm.style.display = 'block';
        usernameForm.style.display = 'none';
        sessionStorage.removeItem('adminLoginTab');
    } else {
        mobileForm.style.display = 'none';
        usernameForm.style.display = 'block';
        sessionStorage.setItem('adminLoginTab', 'username');
    }
}

function hasUsernameFormErrors() {
    const usernameForm = document.getElementById('usernameForm');
    if (!usernameForm) {
        return false;
    }

    return Boolean(
        usernameForm.querySelector('.fi-fo-field-wrp-error-message')
        || usernameForm.querySelector('.admin-login-form-error.show')
        || usernameForm.querySelector('[aria-invalid="true"]'),
    );
}

function restoreLoginTab() {
    const loginPage = document.getElementById('loginPage');
    if (!loginPage) {
        return;
    }

    const serverTab = loginPage.dataset.activeTab;
    const savedTab = serverTab === 'username'
        ? 'username'
        : (sessionStorage.getItem('adminLoginTab') === 'username' || hasUsernameFormErrors())
            ? 'username'
            : serverTab || 'mobile';

    if (savedTab === 'username') {
        switchTab('username');
    }
}

function togglePassword() {
    const passwordInput = document.getElementById('passwordInput');
    const passwordIcon = document.getElementById('passwordIcon');

    if (!passwordInput || !passwordIcon) {
        return;
    }

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
    }
}

function backToLogin() {
    clearInterval(timerInterval);
    document.getElementById('loginPage').style.display = 'block';
    document.getElementById('otpPage').style.display = 'none';
    clearError('otp');
    document.querySelectorAll('.otp-input').forEach((input) => {
        input.value = '';
        input.classList.remove('error');
    });
}

function startTimer() {
    let timeLeft = 120;
    const timerElement = document.getElementById('timer');
    const resendLink = document.getElementById('resendLink');

    clearInterval(timerInterval);

    timerInterval = setInterval(() => {
        timeLeft -= 1;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            resendLink.classList.remove('disabled');
        }
    }, 1000);
}

function toggleDarkMode() {
    document.body.classList.toggle('admin-login-dark');
}

document.addEventListener('DOMContentLoaded', () => {
    restoreLoginTab();

    document.querySelectorAll('[data-auth-tab]').forEach((button) => {
        button.addEventListener('click', () => switchTab(button.dataset.authTab));
    });

    document.getElementById('usernameForm')?.addEventListener('submit', () => {
        sessionStorage.setItem('adminLoginTab', 'username');
    }, true);

    const mobileInput = document.getElementById('mobileInput');
    mobileInput?.addEventListener('input', () => clearError('mobile'));

    document.getElementById('mobileForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const mobile = mobileInput?.value.trim() ?? '';
        const validation = validateMobile(mobile);

        if (!validation.valid) {
            showError('mobile', validation.message);
            return;
        }

        clearError('mobile');
        document.getElementById('displayMobile').textContent = mobile;
        document.getElementById('loginPage').style.display = 'none';
        document.getElementById('otpPage').style.display = 'block';
        startTimer();
    });

    const otpInputs = document.querySelectorAll('.otp-input');

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function () {
            clearError('otp');
            otpInputs.forEach((item) => item.classList.remove('error'));
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && this.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    document.getElementById('otpForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        let otp = '';
        otpInputs.forEach((input) => {
            otp += input.value;
        });

        const validation = validateOTP(otp);
        if (!validation.valid) {
            showError('otp', validation.message);
            otpInputs.forEach((input) => input.classList.add('error'));
            return;
        }

        showError('otp', ErrorMessages.otp.unavailable);
    });

    document.getElementById('resendLink')?.addEventListener('click', (event) => {
        event.preventDefault();
        const link = event.currentTarget;
        if (link.classList.contains('disabled')) {
            return;
        }

        link.classList.add('disabled');
        clearError('otp');
        otpInputs.forEach((input) => {
            input.value = '';
            input.classList.remove('error');
        });
        otpInputs[0]?.focus();
        startTimer();
    });

    document.getElementById('backToLoginLink')?.addEventListener('click', (event) => {
        event.preventDefault();
        backToLogin();
    });

    if (window.Livewire) {
        bindLoginLivewireHooks();
    } else {
        document.addEventListener('livewire:init', bindLoginLivewireHooks, { once: true });
    }
});

function bindLoginLivewireHooks() {
    const restore = () => {
        requestAnimationFrame(restoreLoginTab);
    };

    Livewire.hook('message.processed', restore);
    Livewire.hook('morph.updated', restore);
}

window.switchTab = switchTab;
window.togglePassword = togglePassword;
window.backToLogin = backToLogin;
window.toggleDarkMode = toggleDarkMode;
