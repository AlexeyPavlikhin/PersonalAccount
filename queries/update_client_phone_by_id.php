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
            eml.phone
        FROM 
            clients cls, 
            clients_phone eml
        WHERE 
            eml.phone_id = ".$data['phone_id']." 
        and eml.client_id = cls.client_id"
    );
    $query->execute();
    $tmp_client_id = "";
    $tmp_fio = "";
    $tmp_telegram = "";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_client_id = $row["client_id"];
         $tmp_fio = $row["fio"];
         $tmp_data = $row["phone"];
    }    
    // получаем данные для записи в аудит (конец)
    
    try {
        $sql = "UPDATE clients_phone SET phone='".str_replace('"', '\\"', str_replace("'", "\\'", $data['phone']))."' WHERE phone_id = " .$data['phone_id'];
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }

    // записываем в аудит
    $audit_event_type = "Изменение информации о телефоне клиента";
    $audit_event_data = "ID клиента: ".$tmp_client_id."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_fio."\n";
    $audit_event_data = $audit_event_data."Старый телефон: ".$tmp_data."\n";
    $audit_event_data = $audit_event_data."Новый телефон: ".$data['phone'];

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

