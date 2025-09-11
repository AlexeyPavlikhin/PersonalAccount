<?php
    define('USER', 'root');
    define('PASSWORD', '');
    define('HOST', 'localhost');
    define('DATABASE', 'firstdb');
    try {
        $connection = new PDO("mysql:host=".HOST.";dbname=".DATABASE, USER, PASSWORD);
         // set the PDO error mode to exception
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
?>