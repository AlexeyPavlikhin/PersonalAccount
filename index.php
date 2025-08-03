<?php
    session_start();
    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
        exit;
    } else {
        // Покажите пользователю страницу
        echo '<p> это страница личного кабинета пользователя </p>';
    }
?>