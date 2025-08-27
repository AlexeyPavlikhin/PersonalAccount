<?php
    define('USER', 'usr_msll');
    define('PASSWORD', 'MSLL The best!');
    define('HOST', '188.127.239.143');
    define('DATABASE', 'msll_lk_db');
    try {
        $connection = new PDO("mysql:host=".HOST.";dbname=".DATABASE, USER, PASSWORD);
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
    /*188.127.239.141 2a06:dd00:1:4::105 */
?>