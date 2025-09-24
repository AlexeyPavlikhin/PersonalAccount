<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {
        $sql = "INSERT INTO clients_email (client_id, email) VALUES ('".$data['client_id']."', '".$data['email']."')";
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
}
?>

