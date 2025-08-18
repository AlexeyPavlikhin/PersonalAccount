<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT c.client_patronymic FROM clients c WHERE c.client_id = ".$_GET['clientID']);
    $query->execute();
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $response=$row["client_patronymic"];
    }
    echo    $response;
}
?>

