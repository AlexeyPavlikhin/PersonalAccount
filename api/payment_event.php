<?php
header('Access-Control-Allow-Origin: *');
include('../config.php');

$request_body = file_get_contents('php://input');
parse_str($request_body, $v_obj);
$tmp_str = "";

$tmp_str .= "API-key=".$v_obj["API-key"]."\n";
$tmp_str .= "Name=".$v_obj["Name"]."\n";
$tmp_str .= "Name_2=".$v_obj["Name_2"]."\n";
$tmp_str .= "Name_3=".$v_obj["Name_3"]."\n";
$tmp_str .= "Email=".$v_obj["Email"]."\n";
$tmp_str .= "Phone=".$v_obj["Phone"]."\n";
$tmp_str .= "PRODUCT_NAME=".$v_obj["payment"]["products"][0]["name"]."\n";

$user_name = $v_obj["Name_2"]." ".$v_obj["Name"];
$user_login = $v_obj["Name_3"];
$user_email = $v_obj["Email"];
$user_product = $v_obj["payment"]["products"][0]["name"];



//отвечаем инициатору запроса
echo "ok";    

//начинаем обрабатывать полученную информацию
//проверяем имя сайта, от которого пришел запрос
if (
        (str_starts_with($_SERVER["HTTP_REFERER"], 'http://msll-dev')) &&
        ($v_obj["API-key"]=="D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT")
   ){
    //делаем запись в аудит
    $audit_event_type = "Получен платеж";
    try {
        $sql_audit = 
        "INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                '".$_SERVER["HTTP_REFERER"]."', 
                '".$audit_event_type."', 
                '".$tmp_str."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql_audit;
    }
    
    //получаем количество пользоватлелей с таким e-mail (е-mail - это ключевое поле)
    try {
        $sql = "SELECT count(u.id) as cnt FROM users u where u.email = 'pavlikhin@gmail.com'";
        $query = $connection->prepare($sql);
        $query->execute();
        $query_result = $query->fetchAll();
        //echo json_encode($query_result);
        $count_of_users = $query_result[0]["cnt"];

        //если такого пользователя нет, то
        if ($count_of_users == 0){
           //создаём запись пользоватлеля 
            $sql = "INSERT INTO users(login,username,email,user_group,password) VALUES ('".$user_login."','".$user_name."','".$user_email."','client','".password_hash(generateRandomString(), PASSWORD_BCRYPT)."')";
            $query = $connection->prepare($sql);
            $query->execute();

        }

        //получаем id пользователя (берём минимальный ID)
        $sql = "SELECT MIN(u.id) as u_id FROM users u where u.email = 'pavlikhin@gmail.com'";
        $query = $connection->prepare($sql);
        $query->execute();
        $query_result = $query->fetchAll();
        //echo json_encode($query_result);
        $user_id = $query_result[0]["u_id"];

        //проверяем, а нет ли полномочий для этого пользователя на страницу cources
        $sql = "SELECT count(*) cnt_courses_pages FROM spr_permitions spr_p, users_permitions usr_p WHERE usr_p.user_id = '".$user_id."' and usr_p.permition_id = spr_p.permition_id and spr_p.permition_name = 'courses';";
        $query = $connection->prepare($sql);
        $query->execute();
        $query_result = $query->fetchAll();
        //echo json_encode($query_result);
        $count_of_courses_pages_permited = $query_result[0]["cnt_courses_pages"];        
        
        if ($count_of_courses_pages_permited == 0){
        //даем полномочия для этого пользователя на страницу cources
            $sql = "INSERT INTO users_permitions (user_id, permition_id) VALUES ('".$user_id."', (SELECT MIN(permition_id) from spr_permitions WHERE permition_name = 'courses'))";
            $query = $connection->prepare($sql);
            $query->execute();
            $query_result = $query->fetchAll();
        }

        //даём полномочия на использование приобретённого продукта (всегда новая запись)
        
        //делаем запись в таблицу clients (используем процедуру загрузки)

        //делаем запись таблицу sales (не забыть удалить дефолтную запись)



    } catch(PDOException $e) {
        $objResult->write_status = 0;
        $objResult->write_error = $e->getMessage()." ".$sql;        
    } 


    

    //если такой курс ешё не продавали, то
        // создаём права на курс
    //если ранее продавали, то продлеваем срок использования
    
    //отправляем пользователю письмо




} else {
    //делаем запись в аудит
    $audit_event_type = "Получен платеж (источник не подтверждён!!!)";
    $tmp_str = "ВНИМАНИЕ!!!\nИсточник информации недостоверен!!!\nДальнейшая обработка остановлена!!!\n".$tmp_str;

    try {
        $sql_audit = 
        "INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                '".$_SERVER["HTTP_REFERER"]."', 
                '".$audit_event_type."', 
                '".$tmp_str."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch(PDOException $e) {
        echo $e->getMessage()." ".$sql_audit;
    }      
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>

