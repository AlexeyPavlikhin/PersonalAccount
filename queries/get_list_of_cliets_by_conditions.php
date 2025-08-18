<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){

    $resp ="";
    $sql = "SELECT ROW_NUMBER() OVER (order by tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic) num, tbl.client_id, tbl.client_second_name, tbl.client_first_name, tbl.client_patronymic, trim(CONCAT(ifnull(tbl.client_second_name,''), ' ', ifnull(tbl.client_first_name,''), ' ', ifnull(tbl.client_patronymic,''))) from (select DISTINCT cl.client_id, cl.client_second_name, cl.client_first_name, cl.client_patronymic, cl.client_comment FROM sales sl, clients cl, products pr, subproducts spr WHERE sl.client_id=cl.client_id and sl.product_id=pr.product_id and sl.subproduct_id=spr.subproduct_id ^@@^) tbl;";
    $replace_value = "";
    $replace_value_final="";

    foreach (json_decode($_GET['conditions']) as $item) {
        //$resp=$resp.$item->object." ";
        $replace_value="";

        switch ($item->object) {
        case "ФИО":
            //and trim(CONCAT(ifnull(cl.client_second_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,'')))='Енокаева София'
            $replace_value="trim(CONCAT(ifnull(cl.client_second_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,'')))";
            break;
        case "Продукт":
            $replace_value="pr.product_name";
            break;
        case "Подпродукт":
            $replace_value="spr.subproduct_name";
            break;
        }

        switch ($item->operation) {
        case "Совпадает":
            $replace_value="and ".$replace_value." = '".$item->value."'";
            break;
        case "Содержит":
            $replace_value="and ".$replace_value." like '%".$item->value."%'";
            break;
        case "Не совпадает":
            $replace_value="and ".$replace_value." != '".$item->value."'";
            break;
        case "Не содержит":
            $replace_value="and ".$replace_value." not like '%".$item->value."%'";
            break;
        }
        $replace_value_final=$replace_value_final." ".$replace_value;

    }
    $sql = str_replace("^@@^", $replace_value_final, $sql);
    //echo $sql;

    
    $query = $connection->prepare($sql);
    $query->execute();
    //echo json_encode($query, true);
    
    $response="[";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        
        if(strlen($response)>1){
            $response=$response.",";
        }
        $response=$response.json_encode($row);
    }
    $response=$response."]";
    echo    $response;
    
    
    
    //echo serialize($query);
    
    //$result = $connection->query($sql);
    /*
    $result = mysqli_query($connection, $sql);

    if ($result->num_rows > 0) {

        // Выводим данные каждой строки
        //while($row = $result->fetch_assoc()) {
//            echo "id: " . $row["id"]. " – Name: " . $row["name"]. " – Email: " . $row["email"]. "<br>";
//        }
        echo    $result;
    } else {
        echo "0 results";
    }
    */

}

?>

