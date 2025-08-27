<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT pr.product_name, spr.subproduct_name, s.sale_date FROM sales s, products pr, subproducts spr WHERE s.product_id=pr.product_id and s.subproduct_id=spr.subproduct_id and s.client_id=".$_GET['clientID']." ORDER by 3,1,2;");
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."{\"date\": \"".$row["sale_date"]."\", \"product_name\": \"".$row["product_name"]."\", \"subproduct_name\": \"".$row["subproduct_name"]."\"}";
    }
    $response=$response."]";
    echo    $response;
}
?>

