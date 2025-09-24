<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $sql = "";
    
    try {

        
        /* создаем продкут, если его ещё нет*/
        $query_count1 = $connection->prepare("SELECT p.product_id FROM products p WHERE p.product_name='НЕТ ПРОДУКТА'");
        $query_count1->execute();
        
        If ($query_count1->rowCount()==0){
            $query_insert1 = $connection->prepare("INSERT INTO products (product_name) VALUES ('НЕТ ПРОДУКТА')");
            $query_insert1->execute();
        }
        
        /* создаем статус "НЕ ОПРЕДЕЛЁН", если его ещё нет*/
        $query_count2 = $connection->prepare("SELECT ss.status_id FROM sales_status ss WHERE ss.status_name='НЕ ОПРЕДЕЛЁН'");
        $query_count2->execute();
        
        If ($query_count2->rowCount()==0){
            $query_insert2 = $connection->prepare("INSERT INTO sales_status (status_name) VALUES ('НЕ ОПРЕДЕЛЁН')");
            $query_insert2->execute();
        }
        
        /* создаём клиента */
        $query_main = $connection->prepare("INSERT 
                                                INTO clients (
                                                    client_first_name, 
                                                    client_last_name, 
                                                    client_patronymic,
                                                    client_job,
                                                    client_comment
                                                    ) 
                                                VALUES (
                                                    '".$data['client_FirstName']."',
                                                    '".$data['client_LastName']."',
                                                    '".$data['client_Patronymic']."',
                                                    '',
                                                    ''
                                                    )");
        $query_main->execute();
        
        $query_main = $connection->prepare("SELECT LAST_INSERT_ID() as id");
        $query_main->execute();
        //$return = json_encode($query_main->fetchAll(PDO::FETCH_DEFAULT));
        /* создаём запись о продаже продукта */
        $last_id = $query_main->fetchAll(PDO::FETCH_DEFAULT)[0][0];
        $return = $last_id;

        //LAST_ID:   (SELECT MAX(cl.client_id) FROM clients cl WHERE cl.client_first_name = '".$data['client_FirstName']."' AND cl.client_last_name = '".$data['client_LastName']."' AND client_patronymic = '".$data['client_Patronymic']."'),

        $sql = "INSERT 
                INTO 
                sales (
                client_id, 
                product_id, 
                sale_date, 
                product_comment, 
                sale_status_id
                ) VALUES (
                ".$last_id.",
                (SELECT MIN(p.product_id) FROM products p WHERE p.product_name = 'НЕТ ПРОДУКТА'), 
                CURDATE(),
                '',
                (SELECT ss.status_id FROM sales_status ss WHERE status_name = 'НЕ ОПРЕДЕЛЁН') 
                )";
        $query = $connection->prepare($sql);
        $query->execute();

        echo $return;
        //echo $query->rowCount();
        //echo json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql;
    }
    
}
?>

