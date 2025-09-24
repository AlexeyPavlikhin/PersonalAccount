<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT s.id as sale_id, DATE_FORMAT(s.sale_date, '%d.%m.%Y') as date, DATE_FORMAT(s.sale_date, '%Y-%m-%d') as usdate, pr.product_name, ss.status_name as status, s.product_comment as comment, true as is_disable FROM sales s, products pr, sales_status ss  WHERE s.product_id=pr.product_id and s.sale_status_id=ss.status_id and s.client_id=".$_GET['clientID']." ORDER by 1,2;");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
}
?>

