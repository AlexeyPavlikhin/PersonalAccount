<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {
        $sql = "UPDATE users    u SET u.email = '".$data['user_email']."',
                                u.username = '".$data['user_username']."', 
                                u.user_group = '".$data['user_user_group']."' 
                                WHERE u.login = '".$data['user_login']."'";
        $query = $connection->prepare($sql);
        $query->execute();

        // echo a message to say the UPDATE succeeded
        echo $query->rowCount();
        //echo $sql;
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
}
?>

