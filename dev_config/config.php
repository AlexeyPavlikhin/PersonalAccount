<?php
    define('USER', 'root');
    define('PASSWORD', '');
    define('HOST', 'localhost');
    define('DATABASE', 'firstdb');

    define('EML_EMAIL_FROM', 'pavlikhin@yandex.ru');
    define('EML_NAME_FROM', 'Администратор сайта msll-ip.ru');
    define('EML_PASSWORD', 'xktxjpuuifzpeqoa');
    define('EML_HOST', 'smtp.yandex.ru');
    define('EML_PORT', '465');

    define('APP_PUBLIC_BASE_URL', 'http://msll-dev');

    try {
        $connection = new PDO(
            'mysql:host=' . HOST . ';dbname=' . DATABASE . ';charset=utf8mb4',
            USER,
            PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );

    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
?>