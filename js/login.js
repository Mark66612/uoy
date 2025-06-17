document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registered') === '1') {
        document.getElementById('registration-success').style.display = 'block';
    }

    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.textContent = 'Скрыть';
        } else {
            passwordInput.type = 'password';
            togglePassword.textContent = 'Показать';
        }
    });

    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        document.getElementById('username-error').textContent = '';
        document.getElementById('password-error').textContent = '';
        loginError.textContent = '';
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        let valid = true;
        
        if (username === '') {
            valid = false;
            document.getElementById('username-error').textContent = 'Введите логин';
        }
        
        if (password === '') {
            valid = false;
            document.getElementById('password-error').textContent = 'Введите пароль';
        }
        
        if (!valid) return;
        
        simulateLogin(username, password);
    });

    // имитауия авторизации
    function simulateLogin(username, password) {
        // Показать индикатор загрузки
        const submitBtn = loginForm.querySelector('.btn');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = 'Авторизация...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            // если админ
            if (username === 'admin' && password === 'education') {
                localStorage.setItem('isAdmin', 'true');
                localStorage.setItem('username', username);
                window.location.href = 'admin_panel.html';
            } 
            // обычный пользователь
            else if (username && password) {
                localStorage.setItem('isAdmin', 'false');
                localStorage.setItem('username', username);
                window.location.href = 'dashboard.html';
            } 
            else {
                loginError.textContent = 'Неверный логин или пароль';
                loginError.style.animation = 'none';
                setTimeout(() => {
                    loginError.style.animation = 'shake 0.5s';
                }, 10);
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        }, 1000);
    }

    // анимация для ошибок
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
    });