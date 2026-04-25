-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Апр 25 2026 г., 05:07
-- Версия сервера: 8.0.45-0ubuntu0.24.04.1
-- Версия PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `msll_lk_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `audit`
--

CREATE TABLE `audit` (
  `id` int NOT NULL,
  `event_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_login` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `operation_type` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `event_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `client_id` int NOT NULL,
  `client_first_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `client_last_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `client_patronymic` varchar(256) DEFAULT NULL,
  `client_job` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `client_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients_email`
--

CREATE TABLE `clients_email` (
  `email_id` int NOT NULL,
  `client_id` int NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sent_to_notisend` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients_phone`
--

CREATE TABLE `clients_phone` (
  `phone_id` int NOT NULL,
  `client_id` int NOT NULL,
  `phone` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `clients_telegram`
--

CREATE TABLE `clients_telegram` (
  `telegram_id` int NOT NULL,
  `client_id` int NOT NULL,
  `telegram` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `courses_data`
--

CREATE TABLE `courses_data` (
  `id` int NOT NULL,
  `course_contents_item_id` int NOT NULL,
  `course_item_type_id` int NOT NULL,
  `course_item_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `course_item_data2` text NOT NULL,
  `sort_key` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `loadlog`
--

CREATE TABLE `loadlog` (
  `id` int NOT NULL,
  `loadlog_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `loadlog_text` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `client_id` int NOT NULL,
  `order_status` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `order_description` varchar(255) NOT NULL,
  `row_creation_time` datetime NOT NULL,
  `operator_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `product_name` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sales`
--

CREATE TABLE `sales` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `product_id` int NOT NULL,
  `sale_date` date NOT NULL DEFAULT '1977-02-04',
  `product_comment` text NOT NULL,
  `sale_status_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sales_status`
--

CREATE TABLE `sales_status` (
  `status_id` int NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `spr_courses_contents`
--

CREATE TABLE `spr_courses_contents` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `course_contents_item_name` varchar(256) NOT NULL,
  `sort_key` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `spr_courses_item_types`
--

CREATE TABLE `spr_courses_item_types` (
  `item_type_id` int NOT NULL,
  `item_type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `spr_courses_name`
--

CREATE TABLE `spr_courses_name` (
  `id` int NOT NULL,
  `course_name` varchar(256) NOT NULL,
  `period_in_days` int NOT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `spr_link_sale_cource`
--

CREATE TABLE `spr_link_sale_cource` (
  `id` int NOT NULL,
  `sale_object_name` varchar(256) NOT NULL,
  `course_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `spr_permitions`
--

CREATE TABLE `spr_permitions` (
  `permition_id` int NOT NULL,
  `permition_name` varchar(256) NOT NULL,
  `sort` int NOT NULL,
  `permition_group` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_group` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'client',
  `login` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users_permitions`
--

CREATE TABLE `users_permitions` (
  `user_permition_id` int NOT NULL,
  `user_id` int NOT NULL,
  `permition_id` int NOT NULL,
  `deadline` datetime NOT NULL DEFAULT '2099-12-31 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users_premited_courses`
--

CREATE TABLE `users_premited_courses` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `user_id` int NOT NULL,
  `available_until` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `audit`
--
ALTER TABLE `audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user_id` (`user_login`),
  ADD KEY `idx_audit_operation_id` (`operation_type`),
  ADD KEY `idx_audit_datetime` (`event_datetime`);

--
-- Индексы таблицы `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `idx_client_name` (`client_first_name`(250));

--
-- Индексы таблицы `clients_email`
--
ALTER TABLE `clients_email`
  ADD PRIMARY KEY (`email_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `client_id` (`client_id`);

--
-- Индексы таблицы `clients_phone`
--
ALTER TABLE `clients_phone`
  ADD PRIMARY KEY (`phone_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `client_id` (`client_id`);

--
-- Индексы таблицы `clients_telegram`
--
ALTER TABLE `clients_telegram`
  ADD PRIMARY KEY (`telegram_id`),
  ADD UNIQUE KEY `telegram` (`telegram`),
  ADD KEY `client_id` (`client_id`);

--
-- Индексы таблицы `courses_data`
--
ALTER TABLE `courses_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_contents_item_id`),
  ADD KEY `item_type_id` (`course_item_type_id`);

--
-- Индексы таблицы `loadlog`
--
ALTER TABLE `loadlog`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `operator_id` (`operator_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `idx_product_name` (`product_name`(250)) USING BTREE;

--
-- Индексы таблицы `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `sales_status_id` (`sale_status_id`);

--
-- Индексы таблицы `sales_status`
--
ALTER TABLE `sales_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Индексы таблицы `spr_courses_contents`
--
ALTER TABLE `spr_courses_contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `spr_courses_item_types`
--
ALTER TABLE `spr_courses_item_types`
  ADD PRIMARY KEY (`item_type_id`);

--
-- Индексы таблицы `spr_courses_name`
--
ALTER TABLE `spr_courses_name`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `spr_link_sale_cource`
--
ALTER TABLE `spr_link_sale_cource`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_object_name` (`sale_object_name`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `spr_permitions`
--
ALTER TABLE `spr_permitions`
  ADD PRIMARY KEY (`permition_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login` (`login`);

--
-- Индексы таблицы `users_permitions`
--
ALTER TABLE `users_permitions`
  ADD PRIMARY KEY (`user_permition_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `permition_id` (`permition_id`);

--
-- Индексы таблицы `users_premited_courses`
--
ALTER TABLE `users_premited_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `users_premited_courses_course_id` (`course_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `audit`
--
ALTER TABLE `audit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients_email`
--
ALTER TABLE `clients_email`
  MODIFY `email_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients_phone`
--
ALTER TABLE `clients_phone`
  MODIFY `phone_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `clients_telegram`
--
ALTER TABLE `clients_telegram`
  MODIFY `telegram_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `courses_data`
--
ALTER TABLE `courses_data`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `loadlog`
--
ALTER TABLE `loadlog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `sales_status`
--
ALTER TABLE `sales_status`
  MODIFY `status_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `spr_courses_contents`
--
ALTER TABLE `spr_courses_contents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `spr_courses_item_types`
--
ALTER TABLE `spr_courses_item_types`
  MODIFY `item_type_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `spr_courses_name`
--
ALTER TABLE `spr_courses_name`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `spr_link_sale_cource`
--
ALTER TABLE `spr_link_sale_cource`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `spr_permitions`
--
ALTER TABLE `spr_permitions`
  MODIFY `permition_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users_permitions`
--
ALTER TABLE `users_permitions`
  MODIFY `user_permition_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users_premited_courses`
--
ALTER TABLE `users_premited_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `clients_email`
--
ALTER TABLE `clients_email`
  ADD CONSTRAINT `clients_email_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `clients_phone`
--
ALTER TABLE `clients_phone`
  ADD CONSTRAINT `clients_phone_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `clients_telegram`
--
ALTER TABLE `clients_telegram`
  ADD CONSTRAINT `clients_telegram_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `courses_data`
--
ALTER TABLE `courses_data`
  ADD CONSTRAINT `courses_data_ibfk_1` FOREIGN KEY (`course_contents_item_id`) REFERENCES `spr_courses_contents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `courses_data_ibfk_2` FOREIGN KEY (`course_item_type_id`) REFERENCES `spr_courses_item_types` (`item_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `sales_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `sales_status_id` FOREIGN KEY (`sale_status_id`) REFERENCES `sales_status` (`status_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `spr_courses_contents`
--
ALTER TABLE `spr_courses_contents`
  ADD CONSTRAINT `spr_courses_contents_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `spr_courses_name` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `spr_link_sale_cource`
--
ALTER TABLE `spr_link_sale_cource`
  ADD CONSTRAINT `spr_link_sale_cource_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `spr_courses_name` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `users_permitions`
--
ALTER TABLE `users_permitions`
  ADD CONSTRAINT `users_permitions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `users_permitions_ibfk_2` FOREIGN KEY (`permition_id`) REFERENCES `spr_permitions` (`permition_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `users_premited_courses`
--
ALTER TABLE `users_premited_courses`
  ADD CONSTRAINT `users_premited_courses_course_id` FOREIGN KEY (`course_id`) REFERENCES `spr_courses_name` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `users_premited_courses_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
