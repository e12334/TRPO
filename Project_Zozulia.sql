-- phpMyAdmin SQL Dump
-- version 5.1.3-3.red80
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Ноя 18 2026 г., 02:09
-- Версия сервера: 10.11.11-MariaDB
-- Версия PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `Project_Zozulia`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`admin`@`localhost` PROCEDURE `ChangeUserPassword` (IN `p_UserID` INT, IN `p_OldPassword` VARCHAR(255), IN `p_NewPassword` VARCHAR(255), OUT `p_Result` VARCHAR(100))   BEGIN
    DECLARE v_current VARCHAR(255);

    SELECT `Password` INTO v_current FROM `Users` WHERE `ID_U` = p_UserID;

    IF v_current IS NULL THEN
        SET p_Result = 'ERROR: пользователь не найден';
    ELSEIF v_current <> p_OldPassword THEN
        SET p_Result = 'ERROR: старый пароль неверен';
    ELSEIF LENGTH(p_NewPassword) < 8 THEN
        SET p_Result = 'ERROR: новый пароль слишком короткий';
    ELSE
        UPDATE `Users`
        SET `Password` = p_NewPassword,
            `Failed_attempts` = 0,
            `Is_blocked` = 'False'
        WHERE `ID_U` = p_UserID;
        SET p_Result = 'OK: пароль изменён';
    END IF;
END$$

CREATE DEFINER=`admin`@`localhost` PROCEDURE `GetUserStatistics` ()   BEGIN
    -- Общая сводка
    SELECT 
        COUNT(*)                                        AS `Total_users`,
        SUM(CASE WHEN `Is_blocked` = 'True' THEN 1 ELSE 0 END)  AS `Blocked`,
        SUM(CASE WHEN `Role` = 'Sysadmin' THEN 1 ELSE 0 END)    AS `Sysadmins`,
        SUM(CASE WHEN `Role` = 'Admin' THEN 1 ELSE 0 END)       AS `Admins`,
        SUM(CASE WHEN `Role` = 'User' THEN 1 ELSE 0 END)        AS `Regular_users`,
        ROUND(AVG(`Age`), 1)                            AS `Average_age`,
        MIN(`Age`)                                      AS `Youngest`,
        MAX(`Age`)                                      AS `Oldest`
    FROM `Users`;

    -- Распределение по ролям
    SELECT 
        `Role`,
        COUNT(*) AS `Count`
    FROM `Users`
    GROUP BY `Role`
    ORDER BY `Count` DESC;

    -- Топ-5 самых «свежих» пользователей
    SELECT 
        `ID_U`, `Login`, `Name`, `Surname`, `Role`, `Registration_time`
    FROM `Users`
    ORDER BY `Registration_time` DESC
    LIMIT 5;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `Notes`
--

CREATE TABLE `Notes` (
  `Id` varchar(255) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Content` varchar(255) NOT NULL,
  `Id_user` varchar(255) NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `Notes`
--

INSERT INTO `Notes` (`Id`, `Title`, `Content`, `Id_user`, `created_at`) VALUES
('1', 'Список покупок на неделю', 'Молоко, хлеб, яйца, сыр, овощи, фрукты, курица, рис.', '101', '2026-10-10'),
('2', 'Позвонить родителям', 'Поздравить маму с днём рождения, обсудить поездку на выходные.', '101', '2026-10-11'),
('3', 'Идея для проекта', 'Сделать приложение для заметок с тегами, поиском и напоминаниями.', '102', '2026-10-11'),
('4', 'Встреча с командой', 'В среду в 15:00 обсудить новый дизайн главной страницы и правки по API.', '102', '2026-10-12'),
('5', 'Книга к прочтению', '«Чистый код» Роберта Мартина — начать с главы про функции и комментарии.', '103', '2026-10-13');

-- --------------------------------------------------------

--
-- Структура таблицы `Testovaya`
--

CREATE TABLE `Testovaya` (
  `ID` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `Teststroka` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Users`
--

CREATE TABLE `Users` (
  `ID_U` int(3) NOT NULL COMMENT 'Уникальный код пользователя',
  `Login` char(12) NOT NULL COMMENT 'Для авторизации',
  `Password` char(255) NOT NULL COMMENT 'Для авторизации',
  `Phone_number` char(12) NOT NULL COMMENT 'Номер телефона пользователя',
  `Registration_time` datetime(6) NOT NULL COMMENT 'Время регистрации пользователя',
  `email` char(30) NOT NULL COMMENT 'Почта пользователя',
  `Name` char(30) NOT NULL COMMENT 'Имя пользователя',
  `Surname` char(30) NOT NULL COMMENT 'Фамилия пользователя',
  `Role` enum('Sysadmin','Admin','User') NOT NULL COMMENT 'Роль пользователя',
  `Is_blocked` tinyint(1) DEFAULT NULL COMMENT 'Заблокирован ли пользователь',
  `Birth_date` date NOT NULL COMMENT 'Дата рождения пользователя',
  `Failed_attempts` int(1) NOT NULL COMMENT 'Количество неверных вводов пароля пользователем',
  `Age` int(11) NOT NULL COMMENT 'Возраст пользователя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `Users`
--

INSERT INTO `Users` (`ID_U`, `Login`, `Password`, `Phone_number`, `Registration_time`, `email`, `Name`, `Surname`, `Role`, `Is_blocked`, `Birth_date`, `Failed_attempts`, `Age`) VALUES
(1, 'Pin228', 'Ac92!3-b', '89000002112', '2026-10-05 03:37:57.248000', 'User1@yandex,ru', 'Vasya', 'Pupkin', 'Sysadmin', 0, '2017-10-01', 0, 0),
(6, 'test1', '12345', '+79008002323', '2026-11-12 02:44:15.000000', 'loh123@gmail.com', 'Васька', 'Пупочкин', 'User', 0, '2000-01-18', 0, 26),
(7, 'test2', '1234567', '+73455672112', '2026-11-12 03:16:24.000000', 'test2@gmail.com', 'Тестировщик', 'Второй', 'User', NULL, '2000-01-26', 0, 26);

--
-- Триггеры `Users`
--
DELIMITER $$
CREATE TRIGGER `auto_block_on_failed_attempts` BEFORE UPDATE ON `Users` FOR EACH ROW BEGIN
    -- 1. Если счётчик достиг 3 — принудительно блокируем
    IF NEW.`Failed_attempts` >= 3 THEN
        SET NEW.`Is_blocked` = 'True';
    END IF;

    -- 2. Если пользователь заблокирован — запрещаем менять пароль
    IF OLD.`Is_blocked` = 'True' AND NEW.`Password` <> OLD.`Password` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Аккаунт заблокирован. Обратитесь к администратору.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calc_age_on_insert` BEFORE INSERT ON `Users` FOR EACH ROW BEGIN
    IF NEW.`Birth_date` IS NOT NULL THEN
        SET NEW.`Age` = TIMESTAMPDIFF(YEAR, NEW.`Birth_date`, CURDATE());
    ELSE
        SET NEW.`Age` = NULL;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `expire_password_after_6_months` BEFORE UPDATE ON `Users` FOR EACH ROW BEGIN
    -- Срабатываем только если пароль НЕ меняется текущим запросом
    -- и с момента регистрации прошло >= 6 месяцев
    IF NEW.`Password` = OLD.`Password`
       AND TIMESTAMPDIFF(MONTH, OLD.`Registration_time`, CURDATE()) >= 6
       AND OLD.`Password` NOT LIKE 'EXPIRED_%' THEN
        
        -- Помечаем пароль как просроченный, добавляя префикс
        SET NEW.`Password` = CONCAT('EXPIRED_', UUID());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_sysadmin_delete` BEFORE DELETE ON `Users` FOR EACH ROW BEGIN
    IF OLD.`Role` = 'Sysadmin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Удаление пользователя с ролью Sysadmin запрещено.';
    END IF;
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `Testovaya`
--
ALTER TABLE `Testovaya`
  ADD PRIMARY KEY (`ID`);

--
-- Индексы таблицы `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`ID_U`),
  ADD UNIQUE KEY `Login` (`Login`,`Password`,`Phone_number`,`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `Testovaya`
--
ALTER TABLE `Testovaya`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `Users`
--
ALTER TABLE `Users`
  MODIFY `ID_U` int(3) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный код пользователя', AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
