document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const toggleSlider = document.querySelector('.toggle-slider');

    if (loginBtn && registerBtn) {
        loginBtn.addEventListener('click', function() {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            loginBtn.classList.add('active');
            registerBtn.classList.remove('active');
            toggleSlider.classList.remove('right');
        });

        registerBtn.addEventListener('click', function() {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            registerBtn.classList.add('active');
            loginBtn.classList.remove('active');
            toggleSlider.classList.add('right');
        });
    }

    const formInputs = document.querySelectorAll('.form-control');

    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});