<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){

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
            FROM (select 
                    DISTINCT 
                        cl.client_id, 
                        cl.client_last_name, 
                        cl.client_first_name, 
                        cl.client_patronymic 
                    FROM 
                        sales sl, 
                        clients cl, 
                        products pr 
                    WHERE 
                        sl.client_id=cl.client_id 
                    and sl.product_id=pr.product_id) tbl
            LIMIT 30";

    $query = $connection->prepare($sql);
    $query->execute();
    $client_id = "";
    $response = "[";
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
    
    echo $response;
}

?>

