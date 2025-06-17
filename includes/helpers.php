<?php
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// Функция для перенаправления с сообщением
function redirect_with_message($url, $message) {
    $_SESSION['flash_message'] = $message;
    header("Location: $url");
    exit;
}
?>