<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    // получаем данные для записи в аудит (начало)
    $query = $connection->prepare(
    "
    SELECT 
        TRIM(CONCAT(c.client_last_name, ' ', c.client_first_name, ' ', c.client_patronymic)) as fio,
        c.client_id,
        sl.sale_date,
        sl.product_comment,
        p.product_name,
        ss.status_name
    FROM 
        clients c,
        sales sl,
        products p,
        sales_status ss
    WHERE sl.id = ".$data['sale_id']."
    AND	sl.client_id = c.client_id
    AND sl.product_id = p.product_id
    and sl.sale_status_id = ss.status_id
    ");
    $query->execute();
    $tmp_fio = "";
    $tmp_client_id = "";
    $tmp_sale_date = "";
    $tmp_product_comment = "";
    $tmp_product_name = "";
    $tmp_status_name = "";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
         $tmp_fio = $row["fio"];
         $tmp_client_id = $row["client_id"];
         $tmp_sale_date = $row["sale_date"];
         $tmp_product_comment = $row["product_comment"];
         $tmp_product_name = $row["product_name"];
         $tmp_status_name = $row["status_name"];
    }
    // получаем данные для записи в аудит (конец)

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
        $sql = "UPDATE sales 
                SET sale_date ='".$data['usdate']."' ,
                    product_comment = '".$data['comment']."' ,
                    product_id = (SELECT p.product_id FROM products p WHERE p.product_name = '".$data['product_name']."'), 
                    sale_status_id = (SELECT ss.status_id FROM sales_status ss WHERE status_name = '".$data['status']."') 
                WHERE id = " .$data['sale_id'];
        $query = $connection->prepare($sql);
        $query->execute();
        echo $query->rowCount();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }

    // записываем в аудит
    $audit_event_type = "Изменение записи о продаже";
    $audit_event_data = "ID клиента: ".$tmp_client_id."\n";
    $audit_event_data = $audit_event_data."ФИО клиента: ".$tmp_fio."\n";

    //$audit_event_data = $audit_event_data."<".$tmp_sale_date.">\n";
    //$audit_event_data = $audit_event_data."<".$data['usdate'].">\n";
    IF ($tmp_sale_date != $data['usdate']){
        $audit_event_data = $audit_event_data."Изменилась дата продажи!\n";
        $audit_event_data = $audit_event_data."Старая дата: ".$tmp_sale_date."\n"; 
        $audit_event_data = $audit_event_data."Новая дата: ".$data['usdate']."\n"; 
    }

    IF ($tmp_product_name != $data['product_name']){
        $audit_event_data = $audit_event_data."Изменилось название продукта!\n";
        If ($query_count1->rowCount()==0){
            $audit_event_data = $audit_event_data."Старое название продукта: ".$tmp_product_name."\n"; 
            $audit_event_data = $audit_event_data."Новый название продукта (создан новый продукт): ".$data['product_name']."\n"; 
        } else {
            $audit_event_data = $audit_event_data."Старое название продукта: ".$tmp_product_name."\n"; 
            $audit_event_data = $audit_event_data."Новый название продукта: ".$data['product_name']."\n"; 
        }
    }

    IF ($tmp_status_name != $data['status']){
        $audit_event_data = $audit_event_data."Изменился статус продукта!\n";
        If ($query_count2->rowCount()==0){
            $audit_event_data = $audit_event_data."Старый статус продукта: ".$tmp_status_name."\n"; 
            $audit_event_data = $audit_event_data."Новый название продукта (создан новый статус): ".$data['status']."\n"; 
        } else {
            $audit_event_data = $audit_event_data."Старый статус продукта: ".$tmp_status_name."\n"; 
            $audit_event_data = $audit_event_data."Новый название продукта: ".$data['status']."\n"; 
        }
    }

    IF ($tmp_product_comment != $data['comment']){
        $audit_event_data = $audit_event_data."Изменился комментарий к продукту!\n";
        $audit_event_data = $audit_event_data."Старый комментарий: ".$tmp_product_comment."\n"; 
        $audit_event_data = $audit_event_data."Новый комментарий: ".$data['comment']."\n"; 
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

