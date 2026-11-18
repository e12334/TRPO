<?php
session_start();

// Исходный правильный порядок (1 - верх лево, 2 - верх право, 3 - низ лево, 4 - низ право)
$correctOrder = [1, 2, 3, 4];

// Перемешиваем для пользователя
$shuffled = $correctOrder;
shuffle($shuffled);

// Запоминаем ПРАВИЛЬНЫЙ ответ в сессии (всегда "1234")
$_SESSION['captcha_answer'] = '1234';

// Отдаем перемешанный порядок в JS
header('Content-Type: application/json');
echo json_encode($shuffled);
?>