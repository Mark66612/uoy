<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$is_admin = $_SESSION['is_admin'];

// Получение данных пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка получения данных пользователя: " . $e->getMessage());
}

// Получение заявок пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка получения заявок: " . $e->getMessage());
}

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $application_id = (int)$_POST['application_id'];
    $content = clean_input($_POST['content']);
    
    // Проверка, что заявка завершена и принадлежит пользователю
    try {
        $stmt = $pdo->prepare("SELECT id FROM applications 
                              WHERE id = ? AND user_id = ? AND status = 'Обучение завершено'");
        $stmt->execute([$application_id, $user_id]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $errors[] = "Невозможно оставить отзыв для этой заявки";
        } elseif (empty($content)) {
            $errors[] = "Отзыв не может быть пустым";
        } else {
            // Проверка, не оставлял ли уже отзыв
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE application_id = ?");
            $stmt->execute([$application_id]);
            
            if ($stmt->fetch()) {
                $errors[] = "Вы уже оставляли отзыв для этой заявки";
            } else {
                // Сохранение отзыва
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, application_id, content) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $application_id, $content]);
                
                $_SESSION['success_message'] = "Отзыв успешно отправлен!";
                header("Location: dashboard.php");
                exit;
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Ошибка сохранения отзыва: " . $e->getMessage();
    }
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Личный кабинет - Корочки.есть</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Шапка */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        
        .user-details h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .user-details p {
            color: var(--gray);
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        /* Основной контент */
        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        @media (min-width: 992px) {
            .main-content {
                grid-template-columns: 2fr 1fr;
            }
        }
        
        /* Карточки */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 15px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Заявки */
        .application-list {
            display: grid;
            gap: 15px;
        }
        
        .application-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #f9f9f9;
            position: relative;
            transition: all 0.3s;
        }
        
        .application-item:hover {
            background: white;
            border-color: #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .application-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--dark);
        }
        
        .application-date {
            color: var(--gray);
            font-size: 14px;
        }
        
        .application-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .application-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .application-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-Новая {
            background: rgba(241, 196, 15, 0.2);
            color: #c29d0b;
        }
        
        .status-Идет_обучение {
            background: rgba(52, 152, 219, 0.2);
            color: #2980b9;
        }
        
        .status-Обучение_завершено {
            background: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .review-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #eee;
        }
        
        /* Отзывы */
        .review-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid var(--primary);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-author {
            font-weight: 600;
        }
        
        .review-date {
            color: var(--gray);
            font-size: 13px;
        }
        
        .review-content {
            line-height: 1.5;
        }
        
        /* Сообщения */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .new-application-card {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            border-radius: 10px;
            border: 1px dashed var(--primary);
        }
        
        .new-application-card i {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .new-application-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .new-application-card p {
            color: var(--gray);
            margin-bottom: 20px;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .application-header {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Показать/скрыть форму отзыва
            document.querySelectorAll('.toggle-review').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.application-item').querySelector('.review-form');
                    form.style.display = form.style.display === 'block' ? 'none' : 'block';
                });
            });
        });
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Шапка -->
        <div class="header">
            <div class="logo">
                <h1><i class="fas fa-graduation-cap"></i> Корочки.есть</h1>
            </div>
            
            <div class="user-info">
                <div class="avatar"><?= mb_substr($user_name, 0, 1) ?></div>
                <div class="user-details">
                    <h1><?= htmlspecialchars($user_name) ?></h1>
                    <p>Личный кабинет</p>
                </div>
                <a href="?logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Выход
                </a>
            </div>
        </div>
        
        <!-- Сообщения -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php foreach ($errors as $error): ?>
                    <div><?= $error ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Основной контент -->
        <div class="main-content">
            <!-- Левая колонка -->
            <div class="left-column">
                <!-- Карточка создания новой заявки -->
                <div class="card new-application-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>Создать новую заявку</h3>
                    <p>Заполните форму, чтобы подать заявку на обучение по выбранному курсу</p>
                    <a href="application_form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Создать заявку
                    </a>
                </div>
                
                <!-- Список заявок -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-list"></i> Мои заявки</h2>
                    </div>
                    
                    <div class="application-list">
                        <?php if (empty($applications)): ?>
                            <div style="text-align: center; padding: 30px 0;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                <h3>У вас пока нет заявок</h3>
                                <p>Создайте свою первую заявку на обучение</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <div class="application-item">
                                    <div class="application-header">
                                        <div class="application-title"><?= htmlspecialchars($app['course_name']) ?></div>
                                        <div class="application-date">
                                            <?= date('d.m.Y', strtotime($app['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="application-meta">
                                        <div class="application-meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            Начало: <?= date('d.m.Y', strtotime($app['start_date'])) ?>
                                        </div>
                                        
                                        <div class="application-meta-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Оплата: <?= htmlspecialchars($app['payment_method']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="application-meta">
                                        <div class="application-meta-item">
                                            <i class="fas fa-info-circle"></i>
                                            Статус: 
                                            <span class="application-status status-<?= str_replace(' ', '_', $app['status']) ?>">
                                                <?= htmlspecialchars($app['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($app['status'] === 'Обучение завершено'): ?>
                                        <button class="btn btn-success toggle-review" style="margin-top: 10px;">
                                            <i class="fas fa-comment"></i> Оставить отзыв
                                        </button>
                                        
                                        <div class="review-form" style="display: none;">
                                            <form method="POST">
                                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                
                                                <div class="form-group">
                                                    <label>Ваш отзыв о курсе</label>
                                                    <textarea name="content" rows="3" placeholder="Расскажите о вашем опыте обучения..." required></textarea>
                                                </div>
                                                
                                                <button type="submit" name="submit_review" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane"></i> Отправить отзыв
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Правая колонка -->
            <div class="right-column">
                <!-- Информация о пользователе -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-user"></i> Мой профиль</h2>
                    </div>
                    
                    <div class="user-profile">
                        <div class="form-group">
                            <label>ФИО</label>
                            <p><?= htmlspecialchars($user['fullname']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон</label>
                            <p><?= htmlspecialchars($user['phone']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <p><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Логин</label>
                            <p><?= htmlspecialchars($user['username']) ?></p>
                        </div>
                        
                        <a href="#" class="btn btn-outline" style="background: #f8f9fa; color: #555;">
                            <i class="fas fa-edit"></i> Редактировать профиль
                        </a>
                    </div>
                </div>
                
                <!-- Мои отзывы -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-comments"></i> Мои отзывы</h2>
                    </div>
                    
                    <div class="reviews-list">
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT reviews.*, applications.course_name 
                                                  FROM reviews
                                                  JOIN applications ON applications.id = reviews.application_id
                                                  WHERE reviews.user_id = ?
                                                  ORDER BY reviews.created_at DESC");
                            $stmt->execute([$user_id]);
                            $reviews = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            die("Ошибка получения отзывов: " . $e->getMessage());
                        }
                        ?>
                        
                        <?php if (empty($reviews)): ?>
                            <div style="text-align: center; padding: 20px 0; color: #95a5a6;">
                                <i class="fas fa-comment-slash" style="font-size: 36px; margin-bottom: 10px;"></i>
                                <p>Вы еще не оставляли отзывов</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="review-author"><?= htmlspecialchars($review['course_name']) ?></div>
                                        <div class="review-date">
                                            <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="review-content">
                                        <?= nl2br(htmlspecialchars($review['content'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>