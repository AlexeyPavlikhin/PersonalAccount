<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";

    // получаем данные для записи в аудит (начало)
    $query = $connection->prepare(
        "SELECT 
            u.username, 
            u.email,
            u.user_group
        FROM 
            users u
        WHERE 
            u.login = '".$data['user_login']."'"
    );
    $query->execute();
    $tmp_username = "";
    $tmp_email = "";
    $tmp_password = "";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_username = $row["username"];
         $tmp_email = $row["email"];
         $tmp_user_group = $row["user_group"];
    }    
    // получаем данные для записи в аудит (конец)
    
    try {
        $sql = "UPDATE users    u SET u.email = '".$data['user_email']."',
                                u.username = '".$data['user_username']."', 
                                u.user_group = '".$data['user_user_group']."' 
                                WHERE u.login = '".$data['user_login']."'";
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
    // записываем в аудит
    $audit_event_type = "Изменение данных пользователя админстратором";
    $audit_event_data = "Login: ".$data['user_login']."\n";
    if ($tmp_username != $data['user_username']){
        $audit_event_data = $audit_event_data."Старое имя пользователя: ".$tmp_username."\n";
        $audit_event_data = $audit_event_data."Новое имя пользователя: ".$data['user_username']."\n";
    }

    if ($tmp_email != $data['user_email']){
        $audit_event_data = $audit_event_data."Старый e-mail пользователя: ".$tmp_email."\n";
        $audit_event_data = $audit_event_data."Новый e-mail пользователя: ".$data['user_email']."\n";
    }

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

