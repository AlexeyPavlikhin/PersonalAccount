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
            cls.client_id, 
            TRIM(CONCAT(cls.client_last_name, ' ', cls.client_first_name, ' ', cls.client_patronymic)) as fio, 
            cls.client_comment 
        FROM 
            clients cls 
        WHERE 
            cls.client_id = ".$data['clientID']
    );
    $query->execute();
    $tmp_client_id = "";
    $tmp_fio = "";
    $tmp_client_comment = "";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_client_id = $row["client_id"];
         $tmp_fio = $row["fio"];
         $tmp_client_comment = $row["client_comment"];
    }    
    // получаем данные для записи в аудит (конец)

    try {
        $sql = "UPDATE clients SET client_comment='".str_replace('"', '\\"', str_replace("'", "\\'", $data['clientComment']))."' WHERE client_id = ".$data['clientID'];
        $query = $connection->prepare($sql);
        $query->execute();

        // echo a message to say the UPDATE succeeded
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }

    // записываем в аудит
    $audit_event_type = "Изменение комментария о клиенте";
    $audit_event_data = "ID клиента: ".$tmp_client_id."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_fio."\n";
    $audit_event_data = $audit_event_data."Старый комментарий: ".$tmp_client_comment."\n";
    $audit_event_data = $audit_event_data."Новый комментарий: ".$data['clientComment'];

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

