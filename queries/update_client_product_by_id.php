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
    
}
?>

