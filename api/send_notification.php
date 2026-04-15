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

//Отправитель
/*
$email = EML_EMAIL_FROM;
$pass  = EML_PASSWORD; //Пароль для внешних приложений
$name  = EML_NAME_FROM;
*/
//Получатель
//$to_email = "pavlikhin@gmail.com";


$mail = new PHPMailer(true);

$mail->Host = EML_HOST;
$mail->Port = EML_PORT;

$mail->CharSet = 'UTF-8';
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->SMTPDebug = 0;
$mail->SMTPSecure = 'ssl';
$mail->SMTPAutoTLS = false;
$mail->Username = EML_EMAIL_FROM;
$mail->Password = EML_PASSWORD;
$mail->setFrom(EML_EMAIL_FROM, EML_NAME_FROM);
// Прикрепить файл
//$mail->addAttachment('path_to_file.jpg');

//делаем запись в аудит
write_log("send_notification", "send_notification", "Зафиксирован вызов api send_notification с ip-адреса ".$_SERVER["REMOTE_ADDR"]);

//проверяем имя сайта, от которого пришел запрос
if (
        (str_starts_with($_SERVER["REMOTE_ADDR"], "127.0.0.1"))  &&
        ($v_obj["API-key"] == ALLOWED_APIKEY) 
   )
{
         //начинаем делать поисковые запросы к БД
        try {

            //Выбираем все доступы, которые закончатся через 7 дней
            $sql = "SELECT 
                    u.username,
                    u.email,
                    scn.course_name, 
                    DATE_FORMAT(upc.available_until, '%d.%m.%Y') as available_until
                    FROM users_premited_courses upc, 
                    users u, 
                    spr_courses_name scn
                    WHERE  upc.user_id = u.id 
                    AND upc.course_id = scn.id
                    AND upc.available_until = DATE_ADD(DATE(NOW()), INTERVAL 7 DAY)";
            $query = $connection->prepare($sql);
            $query->execute();
            $query_result = $query->fetchAll();
            //echo json_encode($query_result);

            foreach ($query_result as $current_row) {
                // Code to execute for each element
                //echo $current_row["email"];
                //echo $current_row["username"];
                //echo $current_row["course_name"];
                //echo $current_row["available_until"];

                $subject = 'Через 7 дней заканчивается срок доступа к материалам';
                $body    = "<p>".$current_row["username"].", добрый день!</p>
                            <p>Напоминаем, что ".$current_row["available_until"]." заканчивается срок доступа к материалам по теме \"".$current_row["course_name"]."\". Дальнейший доступ будет возможен за дополнительную плату.</p>
                            <p>Мы бы очень хотели попросить вас оставить отзыв о наших продуктах в <a href='https://yandex.ru/maps/-/CDRCYW6M' target='_blank'>Яндекс.Картах</a>  и <a href='https://go.2gis.com/lse43' target='_blank'>2gis</a>. Это сильно поможет развитию нашей компании ❤️</p>
                            <p>Заранее спасибо за уделённое внимание!</p>
                            <br/>
                            <br/>
                            С заботой,<br/>
                            команда Лаборатории права Майи Саблиной<br/>
                            +7 (995) 787-95-77<br/>
                            info@msablina.ru<br/>
                            <a href='http://www.msablina.ru/' target='_blank'>www.msablina.ru</a><br/>                            
                            ";
                $mail->Subject = $subject;
                $mail->msgHTML($body);   
                $mail->clearAddresses();
                $mail->addAddress($current_row["email"], $current_row["username"]);
                //$mail->addAddress("pavlikhin@gmail.com");
                
                //пытаемся отправть сообщение 
                try {
                    //Отправка
                    $mail->send();
                    write_log("send_notification", "Отправлено письмо", "e-mail: ".$current_row["email"]."\nСуть письма: ".$subject);
                    //echo 'Сообщение отправлено'; 
                } catch (Exception $e) {
                    //echo "Сообщение не отправлено. Ошибка: ".$mail->ErrorInfo;
                    write_log("send_notification", "Ошибка отправки письма", "e-mail: ".$current_row["email"]."\nСуть письма: ".$subject."\nОшибка :".$mail->ErrorInfo);
                }       
            }

            //Выбираем все доступы, которые закончатся через 30 дней
            $sql = "SELECT 
                    u.username,
                    u.email,
                    scn.course_name, 
                    DATE_FORMAT(upc.available_until, '%d.%m.%Y') as available_until
                    FROM users_premited_courses upc, 
                    users u, 
                    spr_courses_name scn
                    WHERE  upc.user_id = u.id 
                    AND upc.course_id = scn.id
                    AND upc.available_until = DATE_ADD(DATE(NOW()), INTERVAL 30 DAY)
                    AND scn.period_in_days > 36";
            $query = $connection->prepare($sql);
            $query->execute();
            $query_result = $query->fetchAll();
            //echo json_encode($query_result);

            foreach ($query_result as $current_row) {
                // Code to execute for each element
                //echo $current_row["email"];
                //echo $current_row["username"];
                //echo $current_row["course_name"];
                //echo $current_row["available_until"];

                $subject = 'Через 30 дней заканчивается срок доступа к материалам';
                $body    = "<p>".$current_row["username"].", добрый день!</p>
                            <p>Напоминаем, что ".$current_row["available_until"]." заканчивается срок доступа к материалам по теме \"".$current_row["course_name"]."\". Дальнейший доступ будет возможен за дополнительную плату.</p>
                            <p>Мы бы очень хотели попросить вас оставить отзыв о наших продуктах в <a href='https://yandex.ru/maps/-/CDRCYW6M' target='_blank'>Яндекс.Картах</a>  и <a href='https://go.2gis.com/lse43' target='_blank'>2gis</a>. Это сильно поможет развитию нашей компании ❤️</p>
                            <p>Заранее спасибо за уделённое внимание!</p>
                            <br/>
                            <br/>
                            С заботой,<br/>
                            команда Лаборатории права Майи Саблиной<br/>
                            +7 (995) 787-95-77<br/>
                            info@msablina.ru<br/>
                            <a href='http://www.msablina.ru/' target='_blank'>www.msablina.ru</a><br/>                            
                            ";
                $mail->Subject = $subject;
                $mail->msgHTML($body);  
                $mail->clearAddresses();                      
                $mail->addAddress($current_row["email"], $current_row["username"]);
                //$mail->addAddress("pavlikhin@gmail.com");
                
                //пытаемся отправть сообщение 
                try {
                    //Отправка
                    $mail->send();
                    write_log("send_notification", "Отправлено письмо", "e-mail: ".$current_row["email"]."\nСуть письма: ".$subject);
                    //echo 'Сообщение отправлено'; 
                } catch (Exception $e) {
                    //echo "Сообщение не отправлено. Ошибка: ".$mail->ErrorInfo;
                    write_log("send_notification", "Ошибка отправки письма", "e-mail: ".$current_row["email"]."\nСуть письма: ".$subject."\nОшибка :".$mail->ErrorInfo);
                }       
            }

        } catch(PDOException $e) {
            write_log("send_notification", "Ошибка SQL-запроса", $e->getMessage()."\n\n".$sql);
        } 

} else {
    //делаем запись в аудит
    write_log("send_notification", "send_notification", "Вызов api send_notification с ip-адреса: ".$_SERVER["REMOTE_ADDR"]." не разрешён");
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
?>

