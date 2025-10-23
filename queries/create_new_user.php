<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {
        //$sql = "UPDATE clients SET client_job='".$data['clientJob']."' WHERE client_id = ".$data['clientID'];

        $sql = "INSERT INTO users(login,username,password,email,user_group) VALUES ('".$data['user_login']."','".$data['user_username']."','".password_hash("11234567", PASSWORD_BCRYPT)."','".$data['user_email']."','".$data['user_user_group']."')";
        $query = $connection->prepare($sql);
        $query->execute();

        // echo a message to say the UPDATE succeeded
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
}
?>

