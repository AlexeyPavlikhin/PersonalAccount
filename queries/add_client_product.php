<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {

        $query_insert1_count=0;
        $query_insert2_count=0;
 
        $query_count1 = $connection->prepare("SELECT p.product_id FROM products p WHERE p.product_name='".$data['product_name']."'");
        $query_count1->execute();
        
        If ($query_count1->rowCount()==0){
            $query_insert1 = $connection->prepare("INSERT INTO products (product_name) VALUES ('".$data['product_name']."')");
            $query_insert1->execute();
        }

        $query_count2 = $connection->prepare("SELECT ss.status_id FROM sales_status ss WHERE ss.status_name='".$data['status']."'");
        $query_count2->execute();
        
        If ($query_count2->rowCount()==0){
            $query_insert2 = $connection->prepare("INSERT INTO sales_status (status_name) VALUES ('".$data['status']."')");
            $query_insert2->execute();
        }

        $sql = "INSERT 
                INTO 
                sales (
                client_id, 
                product_id, 
                sale_date, 
                product_comment, 
                sale_status_id
                ) VALUES (
                '".$data['client_id']."', 
                (SELECT p.product_id FROM products p WHERE p.product_name = '".$data['product_name']."'), 
                '".$data['date']."',
                '".$data['comment']."',
                (SELECT ss.status_id FROM sales_status ss WHERE status_name = '".$data['status']."') 
                )";
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

    $audit_event_type = "Добавлена запись о продаже";
    $audit_event_data = "ID клиента: ".$data['client_id']."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_val."\n";
    If ($query_count1->rowCount()==0){
        $audit_event_data = $audit_event_data."Новый продукт: ".$data['product_name']."\n";
    } else {
        $audit_event_data = $audit_event_data."Существующий продукт: ".$data['product_name']."\n";
    }
    If ($query_count2->rowCount()==0){
        $audit_event_data = $audit_event_data."Новый статус: ".$data['status']."\n";
    } else {
        $audit_event_data = $audit_event_data."Существующий статус: ".$data['status']."\n";
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

