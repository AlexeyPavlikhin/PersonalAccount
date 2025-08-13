<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT DISTINCT p.subproduct_name FROM subproducts p ORDER BY p.subproduct_name;");
    $query->execute();
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response."\"".$row["subproduct_name"]."\"";
    }
    $response=$response."]";
    echo    $response;
}
?>

