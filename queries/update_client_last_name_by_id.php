<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_POST['data1'];
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    //echo $data['clientID']." ".$data['clientLastName'];
    $sql = "";
    
    try {
        $sql = "UPDATE clients SET client_last_name='".$data['clientLastName']."' WHERE client_id = ".$data['clientID'];
        $query = $connection->prepare($sql);
        $query->execute();

        // echo a message to say the UPDATE succeeded
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
}
?>

