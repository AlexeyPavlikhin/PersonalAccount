<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){

    $resp ="";
    $sql = "SELECT 
                ROW_NUMBER() OVER (order by tbl.client_last_name, tbl.client_first_name, tbl.client_patronymic) num, 
                tbl.client_id, 
                tbl.client_last_name, 
                tbl.client_first_name, 
                tbl.client_patronymic, 
                (select cl1.client_job from clients cl1 where cl1.client_id = tbl.client_id) as client_job, 
                (select cl2.client_comment from clients cl2 where cl2.client_id = tbl.client_id) as client_comment,
                '[@@emails@@]' as client_emails,
                '[@@phones@@]' as client_phones,
                '[@@telegram@@]' as client_telegrams                 
            from (select    
                    DISTINCT    cl.client_id, 
                                cl.client_last_name, 
                                cl.client_first_name, 
                                cl.client_patronymic, 
                                cl.client_comment 
                    FROM sales sl, clients cl, products pr 
                    WHERE sl.client_id=cl.client_id 
                    and sl.product_id=pr.product_id ^@@^
                 ) tbl";

    $replace_value = "";
    $replace_value_final="";

    foreach (json_decode($_GET['conditions']) as $item) {
        //$resp=$resp.$item->object." ";
        $replace_value="";

        switch ($item->object) {
        case "ФИО":
            //and trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,'')))='Енокаева София'
            $replace_value="trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,'')))";
            break;
        case "Продукт":
            $replace_value="pr.product_name";
            break;
        case "Место работы":
            $replace_value="cl.client_job";
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
 
         //получаем id клиента
        $client_id = $row["client_id"];

        //готовим выборку e-mail
        $sql2 = "SELECT e.email FROM clients_email e where e.client_id = '".$client_id."'";
        $query2 = $connection->prepare($sql2);
        $query2->execute();
        $email_replacement="[";
        while ($row2 = $query2->fetch(PDO::FETCH_ASSOC)) {
            if(strlen($email_replacement)>1){
                $email_replacement=$email_replacement.",";
            }
            $email_replacement=$email_replacement."\"".$row2["email"]."\"";
        }
        $email_replacement=$email_replacement."]";
        
        //готовим выборку телефонов
        $sql2 = "SELECT p.phone FROM clients_phone p where p.client_id = '".$client_id."'";
        $query2 = $connection->prepare($sql2);
        $query2->execute();
        $phone_replacement="[";
        while ($row2 = $query2->fetch(PDO::FETCH_ASSOC)) {
            if(strlen($phone_replacement)>1){
                $phone_replacement=$phone_replacement.",";
            }
            $phone_replacement=$phone_replacement."\"".$row2["phone"]."\"";
        }
        $phone_replacement=$phone_replacement."]";
        
        //готовим выборку telegramm
        $sql2 = "SELECT t.telegram FROM clients_telegram t where t.client_id = '".$client_id."'";
        $query2 = $connection->prepare($sql2);
        $query2->execute();
        $telegram_replacement="[";
        while ($row2 = $query2->fetch(PDO::FETCH_ASSOC)) {
            if(strlen($telegram_replacement)>1){
                $telegram_replacement=$telegram_replacement.",";
            }
            $telegram_replacement=$telegram_replacement."\"".$row2["telegram"]."\"";
        }
        $telegram_replacement=$telegram_replacement."]";

        if(strlen($response)>1){
            $response=$response.",";
        }

        $str_row = json_encode($row);
        $str_row = str_replace("\"[@@emails@@]\"", $email_replacement, $str_row);
        $str_row = str_replace("\"[@@phones@@]\"", $phone_replacement, $str_row);
        $str_row = str_replace("\"[@@telegram@@]\"", $telegram_replacement, $str_row);   

        $response=$response.$str_row;
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

