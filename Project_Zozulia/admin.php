<?php
session_start();
require_once 'db.php';

// Проверка роли
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Sysadmin') {
    header('Location: index.php');
    exit;
}

// Получаем список таблиц
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$message = $_SESSION['admin_message'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_message'], $_SESSION['admin_error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель БД</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #333; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; border: none; cursor: pointer; }
        .btn-home { background: #007bff; color: white; }
        .btn-home:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-create { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .form-create input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Управление базой данных (Сисадмин)</h1>
        <a href="index.php" class="btn btn-home">В личный кабинет</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Форма создания таблицы -->
    <div class="form-create">
        <form method="POST" action="admin_action.php" style="display: flex; width: 100%; gap: 10px;">
            <input type="hidden" name="action" value="create_table">
            <input type="text" name="table_name" placeholder="Имя новой таблицы (латиницей)" required>
            <button type="submit" class="btn btn-success">Создать таблицу</button>
        </form>
    </div>

    <!-- Список таблиц -->
    <h3>Существующие таблицы:</h3>
    <?php if (empty($tables)): ?>
        <p>В базе данных пока нет таблиц.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Имя таблицы</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><?= htmlspecialchars($table) ?></td>
                        <td>
                            <!-- Кнопка перехода к структуре -->
                            <a href="admin_structure.php?table=<?= urlencode($table) ?>" class="btn btn-home" style="padding: 5px 10px; font-size: 12px;">Структура</a>
                            
                            <!-- Кнопка удаления -->
                            <form method="POST" action="admin_action.php" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить таблицу <?= htmlspecialchars($table) ?>?');">
                                <input type="hidden" name="action" value="drop_table">
                                <input type="hidden" name="table_name" value="<?= htmlspecialchars($table) ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>