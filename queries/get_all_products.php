<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT p.product_name FROM products p ORDER BY p.product_name;");
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."\"".$row["product_name"]."\"";
    }
    $response=$response."]";
    echo    $response;
}
?>

