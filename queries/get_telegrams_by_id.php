<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT cp.telegram_id, cp.telegram FROM clients_telegram cp WHERE cp.client_id = ".$_GET['clientID']);
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
}
?>