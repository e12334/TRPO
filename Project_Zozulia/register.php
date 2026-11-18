<?php
// Включаем вывод ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Стартуем сессию для капчи
require_once 'db.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Проверяем капчу
    $user_captcha = str_replace(' ', '', $_POST['captcha'] ?? ''); 
    $correct_captcha = $_SESSION['captcha_answer'] ?? '';

    // Если капча не совпала (правильный ответ всегда "1234")
    if ($user_captcha !== $correct_captcha) {
        $message = 'Неверный порядок частей капчи! Соберите картинку правильно.';
    } else {
        // 2. Капча верна, получаем данные
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birthdate = $_POST['birthdate'] ?? '';

        if (empty($login) || empty($password)) {
            $message = 'Пожалуйста, заполните логин и пароль.';
        } elseif (strlen($password) < 5) {
            $message = 'Пароль должен содержать минимум 5 символов.';
        } else {
            // Проверяем, существует ли уже такой логин
            $stmt = $pdo->prepare("SELECT ID_U FROM Users WHERE Login = ?");
            $stmt->execute([$login]);
            
            if ($stmt->fetch()) {
                $message = 'Пользователь с таким логином уже существует!';
            } else {
                // Вычисляем возраст и проверяем формат даты
                $age = 0;
                if (!empty($birthdate)) {
                    $dateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
                    if ($dateObj) {
                        $today = new DateTime();
                        $age = $today->diff($dateObj)->y;
                    } else {
                        $birthdate = '2000-01-01';
                        $age = 20; 
                    }
                } else {
                    $birthdate = '2000-01-01';
                }

                $reg_time = date('Y-m-d H:i:s');
                
                $sql = "INSERT INTO Users (
                            Login, Password, Name, Surname, Phone_number,
                            Registration_time, email, Role, 
                            Birth_date, Failed_attempts, Age
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'User', ?, 0, ?)";
                
                $stmt = $pdo->prepare($sql);
                
                try {
                    $stmt->execute([
                        $login, $password, $name, $surname, $phone, 
                        $reg_time, $email, $birthdate, $age
                    ]);
                    
                    // Очищаем сессию капчи после успешной регистрации
                    unset($_SESSION['captcha_answer']); 
                    
                    header('Location: login.php');
                    exit;
                } catch (PDOException $e) {
                    $message = 'Ошибка БД: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .form-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-top: 0; }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[type="date"] { color: #757575; font-family: Arial, sans-serif; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #218838; }
        .error { color: red; text-align: center; font-size: 13px; margin-bottom: 10px; padding: 10px; background: #ffe6e6; border-radius: 4px; word-wrap: break-word;}
        .link { text-align: center; margin-top: 15px; font-size: 14px; }
        .link a { color: #007bff; text-decoration: none; }
        
        /* Стили для капчи (Луиджи) */
        .captcha-container { margin: 15px 0; text-align: center; border-top: 1px solid #eee; padding-top: 15px; }
        .captcha-hint { font-size: 12px; color: #666; margin-bottom: 10px; }
        .captcha-grid { display: grid; grid-template-columns: 120px 120px; grid-template-rows: 120px 120px; gap: 2px; justify-content: center; margin-bottom: 10px; }
        .captcha-item {
            width: 120px; height: 120px; cursor: grab; border: 1px solid #ccc; box-sizing: border-box;
            /* Добавлен ?v=<?= time() ?> для сброса кэша браузера */
            background-image: url('captcha.jpg?v=<?= time() ?>'); 
            background-size: 240px 240px; 
            background-repeat: no-repeat;
        }
        .captcha-item:active { cursor: grabbing; }
        .captcha-item.dragging { opacity: 0.5; border: 2px dashed #007bff; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Регистрация</h2>
    
    <?php if (!empty($message)): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="login" placeholder="Придумайте логин *" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        <input type="password" name="password" placeholder="Придумайте пароль (мин. 5 символов) *" required>
        <input type="text" name="name" placeholder="Ваше имя" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="text" name="surname" placeholder="Ваша фамилия" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="tel" name="phone" placeholder="Номер телефона" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        
        <label style="font-size: 12px; color: #666; margin-left: 5px;">Дата рождения:</label>
        <input type="date" name="birthdate" value="<?= htmlspecialchars($_POST['birthdate'] ?? '2000-01-01') ?>">

        <!-- Блок капчи -->
        <div class="captcha-container">
            <p class="captcha-hint">Решите капчу</p>
            <div id="captcha-grid" class="captcha-grid"></div>
            <input type="hidden" name="captcha" id="captcha-input" required>
        </div>

        <button type="submit">Зарегистрироваться</button>
    </form>
    
    <div class="link">
        Уже есть аккаунт? <a href="login.php">Войти</a>
    </div>
</div>

<!-- Скрипт Drag & Drop -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const grid = document.getElementById('captcha-grid');
    const hiddenInput = document.getElementById('captcha-input');
    
    const bgPositions = {
        1: '0px 0px',           
        2: '-120px 0px',        
        3: '0px -120px',        
        4: '-120px -120px'      
    };

    fetch('captcha_js.php?t=' + new Date().getTime())
        .then(response => response.json())
        .then(shuffledOrder => {
            shuffledOrder.forEach(num => {
                const div = document.createElement('div');
                div.className = 'captcha-item';
                div.draggable = true;
                div.dataset.num = num; 
                div.style.backgroundPosition = bgPositions[num];

                div.addEventListener('dragstart', handleDragStart);
                div.addEventListener('dragover', handleDragOver);
                div.addEventListener('drop', handleDrop);
                div.addEventListener('dragend', handleDragEnd);

                grid.appendChild(div);
            });
            updateHiddenInput(); 
        });

    let draggedItem = null;

    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
        e.preventDefault(); 
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(e) {
        e.preventDefault();
        if (this !== draggedItem) {
            let tempBg = this.style.backgroundPosition;
            let tempNum = this.dataset.num;

            this.style.backgroundPosition = draggedItem.style.backgroundPosition;
            this.dataset.num = draggedItem.dataset.num;

            draggedItem.style.backgroundPosition = tempBg;
            draggedItem.dataset.num = tempNum;

            updateHiddenInput(); 
        }
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        draggedItem = null;
    }

    function updateHiddenInput() {
        let order = [];
        const items = grid.querySelectorAll('.captcha-item');
        items.forEach(item => {
            order.push(item.dataset.num);
        });
        hiddenInput.value = order.join(''); 
    }
});
</script>

</body>
</html>