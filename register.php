<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$errors = [];
$old_input = [
    'fullname' => '',
    'phone' => '',
    'email' => '',
    'username' => ''
];

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение и очистка данных
    $fullname = clean_input($_POST['fullname'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Сохраняем введенные значения для повторного отображения
    $old_input = compact('fullname', 'phone', 'email', 'username');
    
    // Валидация ФИО
    if (empty($fullname)) {
        $errors['fullname'] = 'ФИО обязательно для заполнения';
    } elseif (!preg_match('/^[А-Яа-яёЁ\s]+$/u', $fullname)) {
        $errors['fullname'] = 'ФИО должно содержать только кириллицу и пробелы';
    } elseif (mb_strlen($fullname) < 6) {
        $errors['fullname'] = 'ФИО должно быть не менее 6 символов';
    }
    
    // Валидация телефона
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7(XXX)-XXX-XX-XX';
    }
    
    // Валидация email
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email';
    }
    
    // Валидация логина
    if (empty($username)) {
        $errors['username'] = 'Логин обязателен для заполнения';
    } elseif (!preg_match('/^[A-Za-z]+$/u', $username)) {
        $errors['username'] = 'Логин должен содержать только латиницу';
    } elseif (mb_strlen($username) < 6) {
        $errors['username'] = 'Логин должен быть не менее 6 символов';
    } else {
        // Проверка уникальности логина
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Этот логин уже занят';
        }
    }
    
    // Валидация пароля
    if (empty($password)) {
        $errors['password'] = 'Пароль обязателен для заполнения';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }
    
    // Если ошибок нет - сохраняем пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, phone, email, username, password) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $phone, $email, $username, $hashed_password]);
            
            $_SESSION['success_message'] = 'Регистрация прошла успешно! Теперь вы можете войти в систему.';
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Регистрация - Корочки.есть</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f5f8fa;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #34495e;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 6px;
            display: block;
        }
        .success {
            color: #27ae60;
            background-color: rgba(39, 174, 96, 0.1);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 14px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .phone-hint {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 4px;
        }
        
        /* Адаптивность для мобильных */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 20px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
    <script>
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
        
        // Инициализация после загрузки страницы
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function() {
                phoneMask(this);
            });
            
            // Валидация формы на клиенте
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Проверка ФИО
                const fullname = document.getElementById('fullname');
                if (!/^[А-Яа-яёЁ\s]{6,}$/u.test(fullname.value)) {
                    valid = false;
                    document.getElementById('fullname-error').textContent = 
                        'ФИО должно содержать только кириллицу и пробелы (мин. 6 символов)';
                }
                
                // Проверка телефона
                const phone = document.getElementById('phone');
                if (!/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/.test(phone.value)) {
                    valid = false;
                    document.getElementById('phone-error').textContent = 
                        'Телефон должен быть в формате +7(XXX)-XXX-XX-XX';
                }
                
                // Проверка email
                const email = document.getElementById('email');
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                    valid = false;
                    document.getElementById('email-error').textContent = 
                        'Введите корректный email';
                }
                
                // Проверка логина
                const username = document.getElementById('username');
                if (!/^[A-Za-z]{6,}$/u.test(username.value)) {
                    valid = false;
                    document.getElementById('username-error').textContent = 
                        'Логин должен содержать только латиницу (мин. 6 символов)';
                }
                
                // Проверка пароля
                const password = document.getElementById('password');
                if (password.value.length < 6) {
                    valid = false;
                    document.getElementById('password-error').textContent = 
                        'Пароль должен быть не менее 6 символов';
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="fullname">ФИО</label>
                <input type="text" id="fullname" name="fullname" 
                       value="<?= htmlspecialchars($old_input['fullname']) ?>" 
                       placeholder="Иванов Иван Иванович" required>
                <span id="fullname-error" class="error">
                    <?= $errors['fullname'] ?? '' ?>
                </span>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="text" id="phone" name="phone" 
                       value="<?= htmlspecialchars($old_input['phone']) ?>" 
                       placeholder="+7(XXX)-XXX-XX-XX" required>
                <span id="phone-error" class="error">
                    <?= $errors['phone'] ?? '' ?>
                </span>
                <div class="phone-hint">Пример: +7(999)-123-45-67</div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($old_input['email']) ?>" 
                       placeholder="example@mail.ru" required>
                <span id="email-error" class="error">
                    <?= $errors['email'] ?? '' ?>
                </span>
            </div>
            
            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($old_input['username']) ?>" 
                       placeholder="Придумайте логин" required>
                <span id="username-error" class="error">
                    <?= $errors['username'] ?? '' ?>
                </span>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" 
                       placeholder="Не менее 6 символов" required>
                <span id="password-error" class="error">
                    <?= $errors['password'] ?? '' ?>
                </span>
            </div>
            
            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>
        
        <div class="login-link">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </div>
    </div>
</body>
</html>