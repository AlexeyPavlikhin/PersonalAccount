<?php
    define('USER', 'usr_msll');
    define('PASSWORD', 'MSLL The best!');
    define('HOST', 'localhost');
    define('DATABASE', 'msll_lk_db');

    define('EML_EMAIL_FROM', 'edu@msablina.ru');
    define('EML_NAME_FROM', 'Администратор сайта msll-ip.ru');
    define('EML_PASSWORD', 'gmtijzdnfwbsujzj');
    define('EML_HOST', 'smtp.yandex.ru');
    define('EML_PORT', '465');

    define('ALLOWED_HOST_1', 'http://msll-dev');
    define('ALLOWED_HOST_2', 'http://msll-dev');
    define('ALLOWED_HOST_3', 'http://msll-dev');
    define('ALLOWED_APIKEY', 'D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT');
    
    try {
        $connection = new PDO("mysql:host=".HOST.";dbname=".DATABASE, USER, PASSWORD);
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
    /*188.127.239.141 2a06:dd00:1:4::105 */
?>