<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT c.client_last_name, c.client_first_name, client_patronymic FROM clients c order by c.client_last_name, c.client_first_name, c.client_patronymic ");
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."\"".trim($row["client_last_name"]." ".$row["client_first_name"]." ".$row["client_patronymic"])."\"";
    }
    $response=$response."]";
    echo    $response;
}
?>

