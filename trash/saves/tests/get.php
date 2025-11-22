<?php
//session_start();
include('../config.php');
//if(isset($_SESSION['current_user_id'])){
    $query_insert1_count=0;
    $query_insert2_count=0;

    $query_count1 = $connection->prepare("SELECT p.product_id FROM products p WHERE p.product_name='IP практики3'");
    $query_count1->execute();
    echo $query_count1->rowCount();
    
    If ($query_count1->rowCount()==0){
        $query_insert1 = $connection->prepare("INSERT INTO products (product_name) VALUES ('IP практики3')");
        $query_insert1->execute();
        $query_insert1_count=$query_insert1->rowCount();
    }
    echo $query_insert1_count;

    $query_count2 = $connection->prepare("SELECT ss.status_id FROM sales_status ss WHERE ss.status_name='Купил1'");
    $query_count2->execute();
    echo $query_count2->rowCount();
    
    If ($query_count2->rowCount()==0){
        $query_insert2 = $connection->prepare("INSERT INTO sales_status (status_name) VALUES ('Купил1')");
        $query_insert2->execute();
        $query_insert2_count=$query_insert1->rowCount();
    }
    echo $query_insert2_count;
    
//}
?>

