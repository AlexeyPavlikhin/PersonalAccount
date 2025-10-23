<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT COUNT(*) count FROM users WHERE email='".$_GET['user_email']."'");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
}
?>

