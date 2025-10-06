<?php
    session_start();
    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
    } else {
        // Покажите пользователю страницу
        header('Location: lk.php');
    }
    exit;
?>