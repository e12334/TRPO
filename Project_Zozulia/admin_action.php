<?php
session_start();
require_once 'db.php';

// Жесткая проверка: только сисадмин
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Sysadmin') {
    die("Доступ запрещен. Вы не сисадмин.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Действие: Создать таблицу
        if ($action === 'create_table') {
            $tableName = trim($_POST['table_name'] ?? '');
            
            // Простейшая защита от инъекций в имени таблицы
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
                throw new Exception("Недопустимое имя таблицы. Используйте только буквы, цифры и _");
            }

            // Создаем простую таблицу с ID и полем created_at
            $sql = "CREATE TABLE `$tableName` (
                `ID` INT AUTO_INCREMENT PRIMARY KEY,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $pdo->exec($sql);
            $_SESSION['admin_message'] = "Таблица `$tableName` успешно создана!";
        }

        // Действие: Удалить таблицу
        if ($action === 'drop_table') {
            $tableName = $_POST['table_name'] ?? '';
            
            // Запрещаем удалять системные таблицы (на всякий случай)
            $protected = ['Users', 'Notes']; 
            // if (in_array($tableName, $protected)) { throw new Exception("Эту таблицу удалять нельзя!"); }

            $sql = "DROP TABLE `$tableName`";
            $pdo->exec($sql);
            $_SESSION['admin_message'] = "Таблица `$tableName` удалена!";
        }

        // Действие: Добавить колонку в таблицу
        if ($action === 'add_column') {
            $tableName = $_POST['table_name'] ?? '';
            $colName = trim($_POST['col_name'] ?? '');
            $colType = $_POST['col_type'] ?? 'VARCHAR(255)';

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $colName)) {
                throw new Exception("Недопустимое имя колонки.");
            }

            $sql = "ALTER TABLE `$tableName` ADD `$colName` $colType";
            $pdo->exec($sql);
            $_SESSION['admin_message'] = "Колонка `$colName` добавлена в таблицу `$tableName`!";
        }

        // Действие: Удалить колонку
        if ($action === 'drop_column') {
            $tableName = $_POST['table_name'] ?? '';
            $colName = $_POST['col_name'] ?? '';

            $sql = "ALTER TABLE `$tableName` DROP COLUMN `$colName`";
            $pdo->exec($sql);
            $_SESSION['admin_message'] = "Колонка `$colName` удалена!";
        }

    } catch (PDOException $e) {
        $_SESSION['admin_error'] = "Ошибка БД: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['admin_error'] = $e->getMessage();
    }
}

// Перенаправляем обратно в админку
header('Location: admin.php');
exit;
?>