<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Подключение к базе данных
try {
    // Получение параметров фильтрации и пагинации
    $status_filter = isset($_GET['filter']) ? clean_input($_GET['filter']) : '';
    $search_query = isset($_GET['search']) ? clean_input($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10; // Количество записей на странице
    
    // Подготовка базового запроса
    $query = "SELECT SQL_CALC_FOUND_ROWS 
                applications.*, 
                users.fullname AS user_fullname,
                users.email AS user_email,
                users.phone AS user_phone
              FROM applications 
              JOIN users ON users.id = applications.user_id";
    
    $where_clauses = [];
    $params = [];
    $types = '';
    
    // Фильтр по статусу
    if ($status_filter && in_array($status_filter, ['Новая', 'Идет обучение', 'Обучение завершено'])) {
        $where_clauses[] = "applications.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    // Поисковой запрос
    if ($search_query) {
        $where_clauses[] = "(users.fullname LIKE ? OR applications.course_name LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $types .= 'ss';
    }
    
    // Объединение условий WHERE
    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Сортировка
    $query .= " ORDER BY applications.created_at DESC";
    
    // Пагинация
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $per_page;
    $types .= 'ii';
    
    // Подготовка и выполнение запроса
    $stmt = $pdo->prepare($query);
    
    // Привязка параметров
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $applications = $stmt->fetchAll();
    
    // Получение общего количества записей
    $total_stmt = $pdo->query("SELECT FOUND_ROWS()");
    $total_applications = $total_stmt->fetchColumn();
    $total_pages = ceil($total_applications / $per_page);
    
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $status = clean_input($_POST['status']);
    
    if (in_array($status, ['Новая', 'Идет обучение', 'Обучение завершено'])) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            $_SESSION['success_message'] = "Статус заявки #$id успешно обновлен на \"$status\"";
            header("Location: admin_panel.php?" . http_build_query($_GET));
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Ошибка обновления статуса: " . $e->getMessage();
        }
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
    <title>Панель администратора - Корочки.есть</title>
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
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Сайдбар */
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo h1 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            color: var(--primary);
        }
        
        .nav-links {
            list-style: none;
        }
        
        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s;
            gap: 10px;
        }
        
        .nav-links li a:hover, .nav-links li a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary);
        }
        
        .nav-links li a i {
            width: 24px;
            text-align: center;
        }
        
        /* Основное содержимое */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .header h2 {
            color: var(--dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Фильтры и поиск */
        .filters {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .filter-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
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
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-outline {
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Таблица */
        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
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
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #f0f2f5;
            color: #555;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-item {
            display: inline-block;
        }
        
        .page-link {
            display: block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s;
        }
        
        .page-item.active .page-link {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-link:hover {
            background: #f0f2f5;
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
        
        /* Адаптивность */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .logo h1 span, .nav-links li a span {
                display: none;
            }
            
            .logo h1 {
                justify-content: center;
            }
            
            .nav-links li a {
                justify-content: center;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .main-content {
                padding: 20px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
    <script>
        function updateStatus(form) {
            const formData = new FormData(form);
            const id = formData.get('id');
            const status = formData.get('status');
            
            fetch('admin_panel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Обновление статуса в таблице
                const badge = document.querySelector(`tr[data-id="${id}"] .status-badge`);
                if (badge) {
                    badge.className = `status-badge status-${status.replace(' ', '_')}`;
                    badge.textContent = status;
                }
                
                // Показ сообщения об успехе (если есть в сессии)
                if (data.includes('success_message')) {
                    // Перезагрузка для отображения сообщения
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обновлении статуса');
            });
            
            return false; // Предотвращаем стандартную отправку формы
        }
        
        function initDatePicker() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.value) {
                    const today = new Date().toISOString().split('T')[0];
                    input.value = today;
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initDatePicker();
            
            // Инициализация тултипов
            const tooltipElems = document.querySelectorAll('[data-tooltip]');
            tooltipElems.forEach(el => {
                el.addEventListener('mouseover', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = this.getAttribute('data-tooltip');
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.position = 'fixed';
                    tooltip.style.top = (rect.top - 30) + 'px';
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.background = 'rgba(0,0,0,0.8)';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '5px 10px';
                    tooltip.style.borderRadius = '4px';
                    tooltip.style.zIndex = '1000';
                    
                    this.tooltip = tooltip;
                });
                
                el.addEventListener('mouseout', function() {
                    if (this.tooltip) {
                        this.tooltip.remove();
                        this.tooltip = null;
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <aside class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-graduation-cap"></i> <span>Корочки.есть</span></h1>
            </div>
            <ul class="nav-links">
                <li><a href="admin_panel.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Панель управления</span></a></li>
                <li><a href="#"><i class="fas fa-users"></i> <span>Пользователи</span></a></li>
                <li><a href="#"><i class="fas fa-book"></i> <span>Курсы</span></a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> <span>Аналитика</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i> <span>Настройки</span></a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> Панель администратора</h2>
                <div class="user-info">
                    <div class="avatar"><?= mb_substr($_SESSION['fullname'], 0, 1) ?></div>
                    <div>
                        <div><?= htmlspecialchars($_SESSION['fullname']) ?></div>
                        <small>Администратор</small>
                    </div>
                    <a href="?logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выход</a>
                </div>
            </div>
            
            <!-- Сообщения -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Фильтры -->
            <div class="filters">
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filter">Статус заявки</label>
                            <select id="filter" name="filter" class="filter-control">
                                <option value="">Все статусы</option>
                                <option value="Новая" <?= $status_filter == 'Новая' ? 'selected' : '' ?>>Новая</option>
                                <option value="Идет обучение" <?= $status_filter == 'Идет обучение' ? 'selected' : '' ?>>Идет обучение</option>
                                <option value="Обучение завершено" <?= $status_filter == 'Обучение завершено' ? 'selected' : '' ?>>Обучение завершено</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search">Поиск по имени или курсу</label>
                            <input type="text" id="search" name="search" class="filter-control" 
                                   placeholder="Введите запрос..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">Дата с</label>
                            <input type="date" id="date_from" name="date_from" class="filter-control">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Дата по</label>
                            <input type="date" id="date_to" name="date_to" class="filter-control">
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Применить фильтры
                        </button>
                        <a href="admin_panel.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Статистика -->
            <div class="stats">
                <div class="card">
                    <div style="padding: 20px; display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <h3><?= number_format($total_applications) ?></h3>
                            <p>Всего заявок</p>
                        </div>
                        <div>
                            <h3><?= number_format($pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Новая'")->fetchColumn()) ?></h3>
                            <p>Новых заявок</p>
                        </div>
                        <div>
                            <h3><?= number_format($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) ?></h3>
                            <p>Пользователей</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Таблица заявок -->
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Курс</th>
                            <th>Дата начала</th>
                            <th>Способ оплаты</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                    <h3>Нет заявок</h3>
                                    <p>Попробуйте изменить параметры фильтра</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr data-id="<?= $app['id'] ?>">
                                    <td>#<?= $app['id'] ?></td>
                                    <td>
                                        <div><strong><?= htmlspecialchars($app['user_fullname']) ?></strong></div>
                                        <div><small><?= htmlspecialchars($app['user_email']) ?></small></div>
                                        <div><small><?= htmlspecialchars($app['user_phone']) ?></small></div>
                                    </td>
                                    <td><?= htmlspecialchars($app['course_name']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($app['start_date'])) ?></td>
                                    <td><?= htmlspecialchars($app['payment_method']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace(' ', '_', $app['status']) ?>">
                                            <?= htmlspecialchars($app['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return updateStatus(this)" style="display: flex; gap: 5px;">
                                            <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                            <select name="status" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                                <option value="Новая" <?= $app['status'] == 'Новая' ? 'selected' : '' ?>>Новая</option>
                                                <option value="Идет обучение" <?= $app['status'] == 'Идет обучение' ? 'selected' : '' ?>>Идет обучение</option>
                                                <option value="Обучение завершено" <?= $app['status'] == 'Обучение завершено' ? 'selected' : '' ?>>Обучение завершено</option>
                                            </select>
                                            <button type="submit" class="action-btn" data-tooltip="Обновить статус">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $start + 4);
                    $start = max(1, $end - 4);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="page-link">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>