<?php
    define('USER', 'usr_msll');
    define('PASSWORD', 'MSLL The best!');
    define('HOST', 'localhost');
    define('DATABASE', 'msll_lk_db');

    define('EML_EMAIL_FROM', 'edu@msablina.ru');
    define('EML_NAME_FROM', 'DEV MSLL');
    define('EML_PASSWORD', 'gmtijzdnfwbsujzj');
    define('EML_HOST', 'smtp.yandex.ru');
    define('EML_PORT', '465');

    define('APP_PUBLIC_BASE_URL', 'http://msll-dev');
    define('DADATA_API_URL_PARTY', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party');
    define('DADATA_API_URL_BANK', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/bank');
    define('DADATA_API_URL_CLEAN_NAME', 'https://cleaner.dadata.ru/api/v1/clean/name');
    define('DADATA_API_KEY', '51d206b97f07e87c9e4f96ee1a2036a8dfbe96e5');
    // Секретный ключ DaData (личный кабинет) — для склонения ФИО и определения пола; без него используются локальные правила.
    define('DADATA_SECRET_KEY', 'eacc9845e78e7e41674a1b73273ef8701a946680');

    define('ALLOWED_HOST_1', 'http://msll-dev');
    define('ALLOWED_HOST_2', 'http://msll-dev');
    define('ALLOWED_HOST_3', 'http://msll-dev');
    define('ALLOWED_APIKEY', 'D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT');
    $ASSET_VER = '2026.05.13-documents';
    
    try {
        $connection = new PDO(
            'mysql:host=' . HOST . ';dbname=' . DATABASE . ';charset=utf8mb4',
            USER,
            PASSWORD
        );
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
    /*188.127.239.141 2a06:dd00:1:4::105 */
?>