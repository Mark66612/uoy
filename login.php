<?php
// Начинаем сессию в самом начале
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Если пользователь уже авторизован - перенаправляем
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin_panel.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$username = '';

// Обработка отправки формы
// ... внутри обработки POST ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(clean_input($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    
    // Специальная обработка для администратора
    $is_admin_login = ($username === 'admin');
    
    if (empty($username) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // В login.php после $user = $stmt->fetch();
            if ($user && $username === 'admin') {
                error_log("Admin password hash: " . $user['password']);
                error_log("Input password: " . $password);
                error_log("Password verify: " . 
                        (password_verify($password, $user['password']) ? 'true' : 'false'));
            }

            if ($user) {
                // Для администратора используем прямое сравнение
                if ($is_admin_login) {
                    $password_valid = ($password === 'education') 
                                   || password_verify($password, $user['password']);
                } else {
                    $password_valid = password_verify($password, $user['password']);
                }
                
                if ($password_valid) {
                    // Успешная авторизация
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    
                    if ($user['is_admin']) {
                        header('Location: admin_panel.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                }
            }
            
            $error = 'Неверный логин или пароль';
            
        } catch (PDOException $e) {
            $error = 'Ошибка авторизации: ' . $e->getMessage();
        }
    }
}

// Проверка сообщения об успешной регистрации
$registration_success = false;
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $registration_success = true;
}
?>

<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Авторизация - Корочки.есть</title>
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
        .logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
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
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 6px;
            display: block;
            text-align: center;
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
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .links a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
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
            .links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
  
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Фокус на поле логина при загрузке
            document.getElementById('username').focus();
            
            // Обработка отправки формы
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Проверка логина
                const username = document.getElementById('username');
                if (username.value.trim() === '') {
                    valid = false;
                    document.getElementById('username-error').textContent = 
                        'Введите логин';
                } else {
                    document.getElementById('username-error').textContent = '';
                }
                
                // Проверка пароля
                const password = document.getElementById('password');
                if (password.value.trim() === '') {
                    valid = false;
                    document.getElementById('password-error').textContent = 
                        'Введите пароль';
                } else {
                    document.getElementById('password-error').textContent = '';
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
        <div class="logo">Корочки.есть</div>
        <h1>Авторизация</h1>
        
        <?php if ($registration_success): ?>
            <div class="success">Регистрация прошла успешно! Теперь вы можете войти в систему.</div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($username) ?>" 
                       placeholder="Ваш логин" required autocomplete="username">
                <span id="username-error" class="error"></span>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" 
                       placeholder="Ваш пароль" required autocomplete="current-password">
                <span id="password-error" class="error"></span>
            </div>
            
            <button type="submit" class="btn">Войти</button>
        </form>
        
        <div class="links">
            <a href="register.php">Создать аккаунт</a>
            <a href="#">Забыли пароль?</a>
        </div>
        
        <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #7f8c8d;">
            <strong>Данные для администратора:</strong><br>
            Логин: <code>admin</code><br>
            Пароль: <code>education</code>
        </div>
    </div>
</body>
</html>