<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("
                                SELECT 
                                    t2.*, 
                                    DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' 
                                FROM orders t2 
                                WHERE t2.id in (
                                                SELECT 
                                                    MAX(t.id) 
                                                FROM orders t 
                                                GROUP by t.order_id
                                                ) 
                                ORDER BY t2.order_id
                                ");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
}
?>

