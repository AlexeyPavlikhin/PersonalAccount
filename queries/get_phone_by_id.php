<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT cp.phone FROM clients_phone cp WHERE cp.client_id = ".$_GET['clientID']);
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."\"".$row["phone"]."\"";
    }
    $response=$response."]";
    echo    $response;
}
?>

