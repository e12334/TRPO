<?php
session_start();

// Защита страницы: если нет сессии, отправляем на вход
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Получаем свежие данные о пользователе из БД
// ВАЖНО: Мы используем ID_U, как в вашей базе данных
$stmt = $pdo->prepare("SELECT * FROM Users WHERE ID_U = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Если пользователя вдруг удалили из БД, уничтожаем сессию
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        h1 { margin: 0; color: #333; }
        
        /* Стили для кнопок в шапке */
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .btn-admin { background: #ffc107; color: #333; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; transition: 0.2s; }
        .btn-admin:hover { background: #e0a800; }
        .logout-btn { background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; transition: 0.2s; }
        .logout-btn:hover { background: #c82333; }
        
        /* Стили для информации */
        .info-row { margin-bottom: 15px; font-size: 16px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .info-label { font-weight: bold; color: #555; width: 180px; display: inline-block; }
        .role-badge { background: #17a2b8; color: white; padding: 3px 8px; border-radius: 12px; font-size: 14px; }
        .status-blocked { color: red; font-weight: bold; }
        .status-active { color: green; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Личный кабинет</h1>
        
        <!-- Блок с кнопками -->
        <div class="header-actions">
            <!-- Кнопка админки, видна только Сисадмину -->
            <?php if (($_SESSION['user_role'] ?? '') === 'Sysadmin'): ?>
                <a href="admin.php" class="btn-admin">⚙️ Управление БД</a>
            <?php endif; ?>
            
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>
    </div>

    <h2>Добро пожаловать, <?= htmlspecialchars($user['Name'] ?: $user['Login']) ?>!</h2>
    <p>Вы успешно авторизовались в системе <strong>Project_Zozulia</strong>.</p>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

    <h3>Ваши данные:</h3>
    
    <div class="info-row">
        <span class="info-label">Логин:</span> <?= htmlspecialchars($user['Login']) ?>
    </div>
    <div class="info-row">
        <span class="info-label">Имя:</span> <?= htmlspecialchars($user['Name'] ?: 'Не указано') ?>
    </div>
    <div class="info-row">
        <span class="info-label">Фамилия:</span> <?= htmlspecialchars($user['Surname'] ?: 'Не указана') ?>
    </div>
    <div class="info-row">
        <span class="info-label">Email:</span> <?= htmlspecialchars($user['email'] ?: 'Не указан') ?>
    </div>
    <div class="info-row">
        <span class="info-label">Телефон:</span> <?= htmlspecialchars($user['Phone_number'] ?: 'Не указан') ?>
    </div>
    <div class="info-row">
        <span class="info-label">Дата рождения:</span> <?= htmlspecialchars($user['Birth_date'] ?: 'Не указана') ?>
    </div>
    <div class="info-row">
        <span class="info-label">Возраст:</span> <?= htmlspecialchars($user['Age']) ?> лет
    </div>
    <div class="info-row">
        <span class="info-label">Роль:</span> <span class="role-badge"><?= htmlspecialchars($user['Role']) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Статус аккаунта:</span> 
        <?php if ($user['Is_blocked'] === 'True' || $user['Is_blocked'] == 1): ?>
            <span class="status-blocked">Заблокирован</span>
        <?php else: ?>
            <span class="status-active">Активен</span>
        <?php endif; ?>
    </div>
    <div class="info-row">
        <span class="info-label">Дата регистрации:</span> <?= htmlspecialchars($user['Registration_time']) ?>
    </div>

</div>

</body>
</html>