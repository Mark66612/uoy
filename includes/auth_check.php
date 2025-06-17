<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Проверка существования пользователя в базе
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Пользователь не найден - уничтожаем сессию
        session_destroy();
        $_SESSION['error_message'] = "Ваш аккаунт больше не существует. Зарегистрируйтесь снова.";
        header('Location: register.php');
        exit();
    }
} catch (PDOException $e) {
    die("Ошибка проверки пользователя: " . $e->getMessage());
}

// Для административных страниц - проверка прав
if (strpos($_SERVER['SCRIPT_NAME'], 'admin') !== false) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        $_SESSION['error_message'] = "Доступ запрещен: требуются права администратора";
        header('Location: dashboard.php');
        exit();
    }
}
?>