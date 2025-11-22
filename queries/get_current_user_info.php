<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    
    $objResult = new stdClass();
   
    $objResult->current_user_id = $_SESSION['current_user_id'];

    // получаем данные о пользователе по логину
    try {
        $sql = "SELECT u.login, u.username, u.email, u.user_group FROM users u WHERE u.id = '".$objResult->current_user_id."'";
        $query = $connection->prepare($sql);
        $query->execute();
        $query_result = $query->fetchAll();
        //echo json_encode($query_result);

        $objResult->login = $query_result[0]["login"];
        $objResult->username = $query_result[0]["username"];
        $objResult->email = $query_result[0]["email"];
        $objResult->user_group = $query_result[0]["user_group"];
        $objResult->status = 1;

    } catch(PDOException $e) {
        $objResult->status = 0;
        $objResult->error = $e->getMessage()." ".$sql;        
        
    }    
    

    echo json_encode($objResult);
}
?>

