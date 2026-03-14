<?php
session_start();
include('../config.php');
header('Location: ../login.php');
if(isset($_SESSION['current_user_id'])){
    echo $_SESSION['current_user_name'];
}
?>

