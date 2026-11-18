<?php
session_start();
require_once 'db.php'; 

// Если пользователь уже авторизован, отправляем его на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Проверяем капчу
    $user_captcha = str_replace(' ', '', $_POST['captcha'] ?? ''); 
    $correct_captcha = $_SESSION['captcha_answer'] ?? '';

    if ($user_captcha !== $correct_captcha) {
        $message = 'Неверный порядок частей капчи! Соберите картинку правильно.';
    } else {
        // 2. Капча верна, проверяем логин и пароль
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $message = 'Введите логин и пароль.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE Login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Сравнение паролей (без хэширования)
            if ($user && $password === $user['Password']) {
                
                // Проверка блокировки ('True' или 1)
                if ($user['Is_blocked'] === 'True' || $user['Is_blocked'] == 1) {
                    $message = 'Ваш аккаунт заблокирован администратором.';
                } else {
                    // Записываем данные в сессию
                    $_SESSION['user_id'] = $user['ID_U']; 
                    $_SESSION['user_login'] = $user['Login'];
                    $_SESSION['user_role'] = $user['Role'];

                    // Очищаем капчу
                    unset($_SESSION['captcha_answer']);

                    header('Location: index.php');
                    exit;
                }
            } else {
                $message = 'Неверный логин или пароль.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 320px; }
        h2 { text-align: center; margin-top: 0; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 5px; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; font-size: 14px; margin-bottom: 10px; background: #ffe6e6; padding: 10px; border-radius: 4px; }
        .link { text-align: center; margin-top: 20px; font-size: 14px; color: #666; }
        .link a { color: #007bff; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
        
        /* Стили для капчи (Луиджи) */
        .captcha-container { margin: 10px 0; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
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
    <h2>Вход</h2>
    
    <?php if (!empty($message)): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="login" placeholder="Логин" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        <input type="password" name="password" placeholder="Пароль" required>
        
        <!-- Блок капчи -->
        <div class="captcha-container">
            <p class="captcha-hint">Решите капчу</p>
            <div id="captcha-grid" class="captcha-grid"></div>
            <input type="hidden" name="captcha" id="captcha-input" required>
        </div>

        <button type="submit">Войти</button>
    </form>
    
    <div class="link">
        Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
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