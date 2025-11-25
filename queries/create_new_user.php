<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {
        $sql = "INSERT INTO users(login,username,email,user_group) VALUES ('".$data['user_login']."','".$data['user_username']."','".$data['user_email']."','".$data['user_user_group']."')";
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
    // записываем в аудит
    $audit_event_type = "Регистрация нового пользователя";
    $audit_event_data = "Login: ".$data['user_login']."\n";
    $audit_event_data = $audit_event_data."Имя пользователя: ".$data['user_username']."\n";
    $audit_event_data = $audit_event_data."Email : ".$data['user_email']."\n";
    $audit_event_data = $audit_event_data."Группа : ".$data['user_user_group'];
    
    try {
        $sql_audit = 
        "INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                '".$_SESSION['current_user_login']."', 
                '".$audit_event_type."', 
                '".str_replace('"', '\\"', str_replace("'", "\\'", $audit_event_data))."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql_audit;
    }
}
?>

