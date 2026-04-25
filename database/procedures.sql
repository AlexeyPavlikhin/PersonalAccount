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

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`agp`@`%` PROCEDURE `concat_clients_property` (IN `in_client_id` INT, IN `in_client_last_name` VARCHAR(250), IN `in_client_first_name` VARCHAR(250), IN `in_client_patronymic` VARCHAR(250), IN `in_client_job` VARCHAR(250), IN `in_client_comment` TEXT)   BEGIN
DECLARE v_tmp_int INT$$

CREATE DEFINER=`agp`@`%` PROCEDURE `insert_new_string` (IN `in_client_last_name` VARCHAR(256), IN `in_client_first_name` VARCHAR(256), IN `in_client_patronymic` VARCHAR(256), IN `in_email` VARCHAR(256), IN `in_phone` VARCHAR(256), IN `in_telegram` VARCHAR(256), IN `in_client_job` VARCHAR(256), IN `in_client_comment` TEXT, IN `in_product_name` VARCHAR(256), IN `in_status` VARCHAR(256), IN `in_product_comment` TEXT, IN `in_date_of_status` VARCHAR(256), IN `in_user_id` INT)   BEGIN

Declare v_client_id INT$$

CREATE DEFINER=`agp`@`%` PROCEDURE `united_client` (IN `in_email` VARCHAR(100), IN `in_phone` VARCHAR(100), IN `in_telegram` VARCHAR(100), OUT `out_client_id` INT)   BEGIN

DECLARE terminate INT DEFAULT FALSE$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
