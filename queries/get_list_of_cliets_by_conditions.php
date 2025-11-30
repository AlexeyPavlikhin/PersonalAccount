<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){

    $resp ="";
/*    
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
*/


    $sql = "select 
                trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,''))) as fio,
                '1' as sort_prioritet,
                '' as sort_id,		
                cl.client_id as client_id, 
                '' as phone,
                '' as email,
                '' as telegram,
                cl.client_comment as comment
            from clients cl
            where 1 
            and cl.client_id in (
                                SELECT 
                                    tbl.client_id                 
                                FROM (SELECT    
                                        DISTINCT    cl.client_id, 
                                                    cl.client_last_name, 
                                                    cl.client_first_name, 
                                                    cl.client_patronymic, 
                                                    cl.client_comment 
                                        FROM sales sl, clients cl, products pr, sales_status ss
                                        WHERE sl.client_id=cl.client_id 
                                        AND sl.product_id=pr.product_id 
                                        AND sl.sale_status_id=ss.status_id ^@@^
                                    ) tbl
                                )
            union all
            select 
                trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,''))) as fio,
                '2' as sort_prioritet,
                cp.phone_id as sort_id,		
                cl.client_id as client_id,
                cp.phone as phone,
                '' as email,
                '' as telegram,
                '' as comment
            from clients_phone cp, clients cl
            where cp.client_id = cl.client_id 
            and cl.client_id in (
                                SELECT 
                                    tbl.client_id                 
                                FROM (SELECT    
                                        DISTINCT    cl.client_id, 
                                                    cl.client_last_name, 
                                                    cl.client_first_name, 
                                                    cl.client_patronymic, 
                                                    cl.client_comment 
                                        FROM sales sl, clients cl, products pr, sales_status ss 
                                        WHERE sl.client_id=cl.client_id 
                                        AND sl.product_id=pr.product_id 
                                        AND sl.sale_status_id=ss.status_id ^@@^
                                    ) tbl
                                )
            union all
            select 
                trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,''))) as fio,
                '3' as sort_prioritet,
                ce.email_id as sort_id,
                cl.client_id as client_id,	
                '' as phone,
                ce.email as email,
                '' as telegram,
                '' as comment	
            from clients_email ce, clients cl
            where ce.client_id = cl.client_id 
            and cl.client_id in (
                                SELECT 
                                    tbl.client_id                 
                                FROM (SELECT    
                                        DISTINCT    cl.client_id, 
                                                    cl.client_last_name, 
                                                    cl.client_first_name, 
                                                    cl.client_patronymic, 
                                                    cl.client_comment 
                                        FROM sales sl, clients cl, products pr, sales_status ss 
                                        WHERE sl.client_id=cl.client_id 
                                        AND sl.product_id=pr.product_id 
                                        AND sl.sale_status_id=ss.status_id ^@@^
                                    ) tbl
                                )
            union all
            select 
                trim(CONCAT(ifnull(cl.client_last_name,''), ' ', ifnull(cl.client_first_name,''), ' ', ifnull(cl.client_patronymic,''))) as fio,
                '4' as sort_prioritet,
                ct.telegram_id as sort_id,
                cl.client_id as client_id,
                '' as phone,
                '' as email,	
                ct.telegram as telegram,
                '' as comment
            from clients_telegram ct, clients cl
            where ct.client_id = cl.client_id 
            and cl.client_id in (
                                SELECT 
                                    tbl.client_id                 
                                FROM (SELECT    
                                        DISTINCT    cl.client_id, 
                                                    cl.client_last_name, 
                                                    cl.client_first_name, 
                                                    cl.client_patronymic, 
                                                    cl.client_comment 
                                        FROM sales sl, clients cl, products pr, sales_status ss 
                                        WHERE sl.client_id=cl.client_id 
                                        AND sl.product_id=pr.product_id 
                                        AND sl.sale_status_id=ss.status_id ^@@^
                                    ) tbl
                                )
            order by fio, client_id, sort_prioritet, sort_id";


    $replace_value = "";
    $replace_value_final="";
    
    //echo "$_GET[conditions]";
    //echo $_GET['conditions'];

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
        case "Статус продажи":
            $replace_value="ss.status_name";
            break;
        }
        //подготаваливаем $item->value
        $prepared_value = str_replace('"', '\"', $item->value);
        $prepared_value = str_replace("'", "\'", $prepared_value);


        switch ($item->operation) {
        case "Совпадает":
            $replace_value="and ".$replace_value." = '".$prepared_value."'";
            break;
        case "Содержит":
            $replace_value="and ".$replace_value." like '%".$prepared_value."%'";
            break;
        case "Не совпадает":
            $replace_value="and ".$replace_value." != '".$prepared_value."'";
            break;
        case "Не содержит":
            $replace_value="and ".$replace_value." not like '%".$prepared_value."%'";
            break;
        }

        
        $replace_value_final=$replace_value_final." ".$replace_value;

    }
    $sql = str_replace("^@@^", $replace_value_final, $sql);
    //echo $sql;

    
    $query = $connection->prepare($sql);
    $query->execute();

    //$response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    //echo $response;



    //$client_emails = ['11111111','22222222'];
    //$client_phones = ['333333333333','44444444444'];
    //$client_telegrams = ['555555555','66666666666'];

    $result_array = [];
    $row_of_result_array = "";
    $row_counter = 0;

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        //echo "0";

        if ($row["sort_prioritet"] == "1"){
            //сохраняем предыдуую строку
            If ($row_counter > 0){
                $result_array [] = $row_of_result_array;
                $row_of_result_array = "";
            }
            //начиаем набор новой строки 
            $row_counter = $row_counter + 1;
            $row_of_result_array = (object) ['num' => $row_counter];
            $row_of_result_array->client_id = $row["client_id"];
            $row_of_result_array->fio = $row["fio"];
            $row_of_result_array->client_comment = $row["comment"];
        }

        if ($row["sort_prioritet"] == "2"){
            //продолжаем набор строки 
            $row_of_result_array->client_phones[] = $row["phone"];
        }

        if ($row["sort_prioritet"] == "3"){
            //продолжаем набор строки 
            $row_of_result_array->client_emails[] = $row["email"];
        }

        if ($row["sort_prioritet"] == "4"){
            //продолжаем набор строки 
            $row_of_result_array->client_telegrams[] = $row["telegram"];
        }
    }

    $result_array [] = $row_of_result_array;
    echo json_encode($result_array);

/*
    $products = [
        (object) ['num' => '1', 'client_last_name' => 1200, 'client_emails' => $client_emails, 'client_phones' => $client_phones, 'client_telegrams' => $client_telegrams, 'client_comment' => 'FFFFFFFFFFFF'],
        (object) ['num' => '2', 'client_last_name' => 25],
        (object) ['num' => '3', 'client_last_name' => 75]
    ];

    $products[] = (object) ['num' => '4', 'client_last_name' => 100];
    $myobj = (object) ['num' => '5', 'client_last_name' => '111'];
    $myobj->client_last_name = 'kiwi';
    

    $products[] = $myobj;

    //echo json_encode($products);

    //echo json_encode($query, true);
    
    /*
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
    * /
*/
}

?>

