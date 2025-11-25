<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {
        $sql = "INSERT INTO clients_telegram (client_id, telegram) VALUES ('".$data['client_id']."', '".$data['telegram']."')";
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
  
    // записываем в аудит
    $query = $connection->prepare(
        "SELECT 
            TRIM(CONCAT(client_last_name, ' ', client_first_name, ' ', client_patronymic)) as fio 
        FROM clients cl 
        WHERE cl.client_id = '".$data['client_id']."'"
    );
    $query->execute();
    $tmp_val= "";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_val = $row["fio"];
    }

    $audit_event_type = "Добавлен новый telegram";
    $audit_event_data = "ID клиента: ".$data['client_id']."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_val."\n";
    $audit_event_data = $audit_event_data."telegram: ".$data['telegram'];
    
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

