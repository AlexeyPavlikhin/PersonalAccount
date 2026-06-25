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
    define('DADATA_API_URL_PARTY', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party');
    define('DADATA_API_URL_BANK', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/bank');
    define('DADATA_API_URL_CLEAN_NAME', 'https://cleaner.dadata.ru/api/v1/clean/name');
    define('DADATA_API_KEY', '51d206b97f07e87c9e4f96ee1a2036a8dfbe96e5');
    // define('DADATA_SECRET_KEY', '');

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