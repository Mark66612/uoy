document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registration-form');
    const phoneInput = document.getElementById('phone');
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    
    // Функция для применения маски телефона
    function phoneMask(input) {
        const value = input.value.replace(/\D/g, '');
        let formattedValue = '';
        
        if (value.length > 0) {
            formattedValue = '+7(';
            if (value.length > 1) {
                formattedValue += value.substring(1, 4);
            }
            if (value.length >= 4) {
                formattedValue += ')-' + value.substring(4, 7);
            }
            if (value.length >= 7) {
                formattedValue += '-' + value.substring(7, 9);
            }
            if (value.length >= 9) {
                formattedValue += '-' + value.substring(9, 11);
            }
        }
        
        input.value = formattedValue;
    }
    
    // Применение маски телефона
    phoneInput.addEventListener('input', function() {
        phoneMask(this);
    });
    
    // Переключатель видимости пароля
    togglePassword.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.textContent = 'Скрыть';
        } else {
            passwordInput.type = 'password';
            togglePassword.textContent = 'Показать';
        }
    });
    
    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const errorElements = document.querySelectorAll('.error');
        errorElements.forEach(el => el.textContent = '');
        document.getElementById('general-error').style.display = 'none';
        
        const fullname = document.getElementById('fullname').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        let isValid = true;
        
        if (!fullname) {
            document.getElementById('fullname-error').textContent = 'ФИО обязательно для заполнения';
            isValid = false;
        } else if (!/^[А-Яа-яёЁ\s]{6,}$/u.test(fullname)) {
            document.getElementById('fullname-error').textContent = 'ФИО должно содержать только кириллицу и пробелы (мин. 6 символов)';
            isValid = false;
        }
        
        if (!phone) {
            document.getElementById('phone-error').textContent = 'Телефон обязателен для заполнения';
            isValid = false;
        } else if (!/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/.test(phone)) {
            document.getElementById('phone-error').textContent = 'Телефон должен быть в формате +7(XXX)-XXX-XX-XX';
            isValid = false;
        }
        
        if (!email) {
            document.getElementById('email-error').textContent = 'Email обязателен для заполнения';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById('email-error').textContent = 'Введите корректный email';
            isValid = false;
        }
        
        if (!username) {
            document.getElementById('username-error').textContent = 'Логин обязателен для заполнения';
            isValid = false;
        } else if (!/^[A-Za-z]{6,}$/u.test(username)) {
            document.getElementById('username-error').textContent = 'Логин должен содержать только латиницу (мин. 6 символов)';
            isValid = false;
        }
        
        if (!password) {
            document.getElementById('password-error').textContent = 'Пароль обязателен для заполнения';
            isValid = false;
        } else if (password.length < 6) {
            document.getElementById('password-error').textContent = 'Пароль должен быть не менее 6 символов';
            isValid = false;
        }
        
        if (!isValid) {
            const errorMessages = document.querySelectorAll('.error:not(:empty)');
            errorMessages.forEach(el => {
                el.parentElement.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    el.parentElement.style.animation = '';
                }, 500);
            });
            return;
        }
        
        const submitBtn = form.querySelector('.btn');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = 'Регистрация...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            const userData = {
                fullname: fullname,
                phone: phone,
                email: email,
                username: username,
                password: password 
            };
            
            localStorage.setItem(username, JSON.stringify(userData));
            
            document.getElementById('registration-success').style.display = 'block';
            
            // через 2 с переход на страницу входа
            setTimeout(() => {
                window.location.href = 'login.html?registered=1';
            }, 2000);
            
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
        }, 1500);
    });
    
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
    document.getElementById('fullname').focus();
});