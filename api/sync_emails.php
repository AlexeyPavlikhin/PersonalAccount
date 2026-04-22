<?php

header('Access-Control-Allow-Origin: *');
include('../config.php');

require_once '../libs/PHPMailer-master/src/PHPMailer.php';
require_once '../libs/PHPMailer-master/src/SMTP.php';
require_once '../libs/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

$request_body = file_get_contents('php://input');
parse_str($request_body, $v_obj);
$tmp_str = "";
$target_group_name = "ВСЯ БАЗА 2025-2026";
//$target_group_name = "Мой Email (edu@msablina.ru)";
//$target_group_name = "Марина";
$count_added_emails = 0;

//делаем запись в аудит
write_log("sync_emails", "Вызов api", "Зафиксирован вызов api sync_emails с ip-адреса ".$_SERVER["REMOTE_ADDR"]);

//проверяем имя сайта, от которого пришел запрос
if (
        (str_starts_with($_SERVER["REMOTE_ADDR"], "127.0.0.1"))  &&
        ($v_obj["API-key"] == ALLOWED_APIKEY) 
   )
{

    $base_url = 'https://api.notisend.ru/v1/email/';
    $token = '35ff43f5a8bf59ccc2bc1d5360ca1b3d';
    $headers = array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    );

    $group_id = get_group_id_by_group_name($base_url, $headers, $target_group_name);

    echo $group_id;

    if ($group_id != 0 ){

        //начинаем делать поисковые запросы к БД
        try {

            //Выбираем все email, которые ещё не отправляли в NotiSend
            $sql = "SELECT ce.email  
                    FROM clients_email ce
                    WHERE ce.sent_to_notisend = false";
            $query = $connection->prepare($sql);
            $query->execute();
            $query_result = $query->fetchAll();
            //echo json_encode($query_result);
            
            foreach ($query_result as $current_row) {
                //echo $current_row["email"]."\n";
                //echo add_recipient_to_group($base_url, $headers, $group_id, $current_row["email"]);

                if (add_recipient_to_group($base_url, $headers, $group_id, $target_group_name, $current_row["email"]) == 1){
                    $count_added_emails++;
                }
                
            }
            write_log("sync_emails", "Работа sync_emails завершена", "Загружено ".$count_added_emails." email в рассылку \"".$target_group_name."\"");        

        } catch(PDOException $e) {
            write_log("sync_emails", "Ошибка SQL-запроса", $e->getMessage()."\n\n".$sql);
        } 
    } else {
        // Группу не нашли
        write_log("sync_emails", "Работа sync_emails завершена", "Ошибка!!! Група получателей \"".$target_group_name."\" не найдена.");    
    }

} else {
    //делаем запись в аудит
    write_log("sync_emails", "Работа sync_emails завершена", "Вызов api sync_emails с ip-адреса: ".$_SERVER["REMOTE_ADDR"]." не разрешён");
}

function add_recipient_to_group($v_base_url, $v_headers, $v_group_id,  $v_group_name, $v_current_email){
    $ch = curl_init($v_base_url . 'lists/' . $v_group_id . '/recipients');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $v_headers);

    $data = array(
        'email'  => $v_current_email
    );    

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));    
    $response = curl_exec($ch);
    curl_close($ch);
    //echo $response;
    $response = json_decode($response);
    //echo "\nid=<".$response->id.">\n";
    if ($response->errors[0]->detail != ""){
        if ($response->errors[0]->detail == "Email has already been taken"){
            write_log("sync_emails", "Предупрежедение при добавлении получателя", "e-mail: ".$v_current_email."\nгруппа: ".$v_group_name."\nтекст: ".$response->errors[0]->detail);
            set_sent_sign($v_current_email);
            return 2;
        } else {
            write_log("sync_emails", "Ошибка добавления e-mail в группу получателей", "e-mail: ".$v_current_email."\nгруппа: ".$v_group_name."\nошибка: ".$response->errors[0]->detail);
            return 0;
        }
    } else {
        set_sent_sign($v_current_email);
        return 1;
    }
    
    /*
    {
        "errors":[
            {
                "code":422,
                "detail":"Email has already been taken"
            }
        ],
        "recipient":
            {"id":4686994433}
    }

    {
        "id":4686994622,
        "email":"jjj1@jj.rr",
        "list_id":733255,
        "confirmed":true,
        "status":"active",
        "values":[],
        "tags":[]
    }
    */
}

function set_sent_sign($v_email){
    include('../config.php');
    //начинаем делать поисковые запросы к БД
    try {

        //Выбираем все email, которые ещё не отправляли в NotiSend
        $sql = "UPDATE clients_email
                SET sent_to_notisend=1
                WHERE email='".$v_email."'";
        $query = $connection->prepare($sql);
        $query->execute();
        //$query_result = $query->fetchAll();
        //echo json_encode($query_result);
        //write_log("sync_emails", "Работа sync_emails завершена", "Загружено ".$count_added_emails." email в рассылку \"".$target_group_name."\"");        

    } catch(PDOException $e) {
        write_log("sync_emails", "Ошибка SQL-запроса", $e->getMessage()."\n\n".$sql);
    }     

}

function write_log($in_user_login, $in_operation_type, $in_event_data) {
    include('../config.php');
    try {
        $sql_audit = 
        "INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                '".$in_user_login."', 
                '".$in_operation_type."', 
                '".$in_event_data."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch(PDOException $e) {
        //echo $e->getMessage()." ".$sql_audit;
    }     
        
    return 0;
}

function get_group_id_by_group_name($v_base_url, $v_headers, $v_target_group_name, $v_page_nomber = 1){

    $ch = curl_init($v_base_url . 'lists?page_number='.$v_page_nomber.'&page_size=25');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $v_headers);
    $response = curl_exec($ch);
    curl_close($ch);

    $v_group_id = 0;

    if ($response === false) {
        write_log("send_notification", "Ошибка вызова api", "api: ".$base_url."\nerror: ".curl_error($ch));
    } else {
        //echo $response;
        //echo json_decode($response)->total_count ;    
        $response=json_decode($response);
        /*
        {   
            "total_count":27,
            "total_pages":2,
            "page_number":1,
            "page_size":25,
            "collection":[
                {"id":735631,"title":"Группа 12"},
                {"id":735629,"title":"Группа 11"},
                {"id":735628,"title":"Группа 10"},
                {"id":659522,"title":"Марина"}
            ]
        }
        */
        foreach ($response->collection as $item) {
            //echo json_encode($item)."\n";
            //echo $item->title."\n";
            if ($item->title == $v_target_group_name){
                $v_group_id = $item->id;
                break;
            }
        }

        if ($v_group_id == 0) {
            if ($response->page_number != $response->total_pages){
                return get_group_id_by_group_name($v_base_url, $v_headers, $v_target_group_name, $v_page_nomber+1);
            } else {
                return 0;
            }
        } else {
            return $v_group_id;
        }
    }
}



?>

