<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){

    $resp ="";
    $sql = "SELECT ROW_NUMBER() OVER (order by tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic) num, tbl.client_id, tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic, trim(CONCAT(ifnull(tbl.client_second_name,''), ' ', ifnull(tbl.client_first_name,''), ' ', ifnull(tbl.client_patronymic,''))) from (select DISTINCT cl.client_id, cl.client_second_name, cl.client_first_name, cl.client_patronymic, cl.comment FROM sales sl, clients cl, products pr, subproducts spr WHERE sl.client_id=cl.client_id and sl.product_id=pr.product_id and sl.subproduct_id=spr.subproduct_id) tbl;";
    $query = $connection->prepare($sql);
    $query->execute();
    
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response.json_encode($row);
    }
    $response=$response."]";
    echo    $response;
}

?>

