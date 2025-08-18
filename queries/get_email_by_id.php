<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT ce.email FROM clients_email ce WHERE ce.client_id = ".$_GET['clientID']);
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."\"".$row["email"]."\"";
    }
    $response=$response."]";
    echo    $response;
}
?>

