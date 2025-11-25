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
            eml.telegram
        FROM 
            clients cls, 
            clients_telegram eml
        WHERE 
            eml.telegram_id = ".$data['telegram_id']." 
        and eml.client_id = cls.client_id"
    );
    $query->execute();
    $tmp_client_id = "";
    $tmp_fio = "";
    $tmp_telegram = "";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_client_id = $row["client_id"];
         $tmp_fio = $row["fio"];
         $tmp_data = $row["telegram"];
    }    
    // получаем данные для записи в аудит (конец)
    
    try {
        $sql = "UPDATE clients_telegram SET telegram='".str_replace('"', '\\"', str_replace("'", "\\'", $data['telegram']))."' WHERE telegram_id = " .$data['telegram_id'];
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
    // записываем в аудит
    $audit_event_type = "Изменение информации о telegram клиента";
    $audit_event_data = "ID клиента: ".$tmp_client_id."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_fio."\n";
    $audit_event_data = $audit_event_data."Старый telegram: ".$tmp_data."\n";
    $audit_event_data = $audit_event_data."Новый telegram: ".$data['telegram'];

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

