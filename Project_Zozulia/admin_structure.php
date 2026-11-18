<?php
session_start();
require_once 'db.php';

// Проверка роли
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Sysadmin') {
    header('Location: index.php');
    exit;
}

$tableName = $_GET['table'] ?? '';
if (empty($tableName)) {
    die("Таблица не выбрана.");
}

// Защита от SQL-инъекций в имени таблицы
if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    die("Недопустимое имя таблицы.");
}

// Получаем структуру таблицы
try {
    $columns = $pdo->query("DESCRIBE `$tableName`")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}

$message = $_SESSION['admin_message'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_message'], $_SESSION['admin_error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Структура таблицы: <?= htmlspecialchars($tableName) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; border: none; cursor: pointer; }
        .btn-home { background: #6c757d; color: white; }
        .btn-home:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .form-add { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;}
        .form-add input[type="text"], .form-add select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Таблица: <code><?= htmlspecialchars($tableName) ?></code></h1>
        <a href="admin.php" class="btn btn-home">← Назад к списку</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Поле (Column)</th>
                <th>Тип (Type)</th>
                <th>NULL</th>
                <th>Ключ (Key)</th>
                <th>По умолчанию (Default)</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($columns as $col): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($col['Field']) ?></strong></td>
                    <td><?= htmlspecialchars($col['Type']) ?></td>
                    <td><?= htmlspecialchars($col['Null']) ?></td>
                    <td><?= htmlspecialchars($col['Key']) ?></td>
                    <td><?= htmlspecialchars($col['Default'] ?? 'NULL') ?></td>
                    <td>
                        <?php if ($col['Key'] !== 'PRI'): // Не даем удалить первичный ключ ?>
                            <form method="POST" action="admin_action.php" style="display: inline;" onsubmit="return confirm('Удалить колонку <?= htmlspecialchars($col['Field']) ?>?');">
                                <input type="hidden" name="action" value="drop_column">
                                <input type="hidden" name="table_name" value="<?= htmlspecialchars($tableName) ?>">
                                <input type="hidden" name="col_name" value="<?= htmlspecialchars($col['Field']) ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Удалить</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">(PK)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Форма добавления колонки -->
    <h3>Добавить новую колонку:</h3>
    <div class="form-add">
        <form method="POST" action="admin_action.php" style="display: flex; gap: 10px; width: 100%; align-items: center;">
            <input type="hidden" name="action" value="add_column">
            <input type="hidden" name="table_name" value="<?= htmlspecialchars($tableName) ?>">
            
            <input type="text" name="col_name" placeholder="Имя колонки (напр. age)" required>
            
            <select name="col_type">
                <option value="VARCHAR(255)">VARCHAR(255) - Строка</option>
                <option value="INT">INT - Число</option>
                <option value="TEXT">TEXT - Длинный текст</option>
                <option value="DATE">DATE - Дата</option>
                <option value="TIMESTAMP">TIMESTAMP - Дата и время</option>
                <option value="TINYINT(1)">TINYINT(1) - Булево (0/1)</option>
            </select>

            <button type="submit" class="btn btn-success">Добавить колонку</button>
        </form>
    </div>
</div>

</body>
</html>