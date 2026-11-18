<?php
session_start();

$imagePath = 'captcha.jpg'; // Имя вашего файла с картинкой мопса
$size = 400; // Размер итогового квадрата (400x400)
$partSize = $size / 2; // Размер одной части (200x200)

// Создаем ресурс из исходной картинки (поддерживаем jpg и png)
$ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
if ($ext === 'png') {
    $srcImg = imagecreatefrompng($imagePath);
} else {
    $srcImg = imagecreatefromjpeg($imagePath);
}

// Создаем пустое изображение для капчи
$captcha = imagecreatetruecolor($size, $size);
$bgColor = imagecolorallocate($captcha, 255, 255, 255);
imagefill($captcha, 0, 0, $bgColor);

// Исходные координаты кусков (X, Y)
$originalCoords = [
    1 => ['x' => 0, 'y' => 0],           // Верхний левый
    2 => ['x' => $partSize, 'y' => 0],   // Верхний правый
    3 => ['x' => 0, 'y' => $partSize],   // Нижний левый
    4 => ['x' => $partSize, 'y' => $partSize] // Нижний правый
];

// Новые координаты для перемешивания (куда мы их поставим на капче)
$positions = [
    ['x' => 0, 'y' => 0],
    ['x' => $partSize, 'y' => 0],
    ['x' => 0, 'y' => $partSize],
    ['x' => $partSize, 'y' => $partSize]
];

// Перемешиваем порядок вывода
shuffle($positions);

// Массив для хранения правильного ответа
$correctOrder = [];

// Вырезаем куски и вставляем их на новые места с нумерацией
for ($i = 1; $i <= 4; $i++) {
    $srcX = $originalCoords[$i]['x'];
    $srcY = $originalCoords[$i]['y'];
    
    $destPos = $positions[$i - 1];
    $destX = $destPos['x'];
    $destY = $destPos['y'];

    // Копируем часть картинки
    imagecopy($captcha, $srcImg, $destX, $destY, $srcX, $srcY, $partSize, $partSize);

    // Определяем, какой номер сейчас находится на этой позиции
    // Нам нужно найти, под каким номером эта позиция выводится (1,2,3,4)
    // Для простоты просто нарисуем номер позиции (сверху вниз, слева направо)
    $number = ($destY == 0 ? ($destX == 0 ? 1 : 2) : ($destX == 0 ? 3 : 4));
    
    // Сохраняем, какой кусок картинки сейчас находится на позиции с номером
    $correctOrder[$number] = $i;

    // Рисуем номер на картинке, чтобы пользователь понимал, куда нажимать
    $textColor = imagecolorallocate($captcha, 255, 0, 0); // Красный текст
    $fontSize = 5; // Встроенный шрифт
    imagestring($captcha, $fontSize, $destX + 5, $destY + 5, $number, $textColor);
}

// Запоминаем правильный ответ в сессии
// $correctOrder[1] = номер куска, который должен стоять первым
// и т.д. Для пользователя мы сделаем строку вида "1 2 3 4" (какой кусок на каком месте)
ksort($correctOrder);
$_SESSION['captcha_answer'] = implode('', $correctOrder);

// Выводим картинку в браузер
header('Content-Type: image/jpeg');
imagejpeg($captcha);

// Очищаем память
imagedestroy($srcImg);
imagedestroy($captcha);
?>