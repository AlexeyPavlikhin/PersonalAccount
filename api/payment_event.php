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

$tmp_str .= "API-key=".$v_obj["API-key"]."\n";
$tmp_str .= "Name=".$v_obj["Name"]."\n";
$tmp_str .= "Name_2=".$v_obj["Name_2"]."\n";
$tmp_str .= "Name_3=".$v_obj["Name_3"]."\n";
$tmp_str .= "Email=".$v_obj["Email"]."\n";
$tmp_str .= "Phone=".$v_obj["Phone"]."\n";
$tmp_str .= "PRODUCT_NAME=".$v_obj["payment"]["products"][0]["name"]."\n";

$user_name = $v_obj["Name_2"]." ".$v_obj["Name"];
//$user_login = $v_obj["Name_3"];
$user_login = $v_obj["Email"];
$user_email = $v_obj["Email"];
$user_product = $v_obj["payment"]["products"][0]["name"];
$generated_password = "";

$is_new_user = false;

//отвечаем инициатору запроса
echo "ok"; 

//делаем запись в аудит
$audit_event_type = "Получено уведомление от сайта ".$_SERVER["HTTP_REFERER"];
write_log($_SERVER["HTTP_REFERER"], $audit_event_type, $tmp_str);

//начинаем обрабатывать полученную информацию
//проверяем имя сайта, от которого пришел запрос
if (
        (str_starts_with($_SERVER["HTTP_REFERER"], ALLOWED_HOST))  &&
        ($v_obj["API-key"] == ALLOWED_APIKEY) 
   )
{
    if ($user_product != ""){
        //начинаем делать поисковые запросы к БД
        try {

            //Проверяем, а есть ли такой продкут для продажи через приложение?
            $sql = "SELECT MIN(ln.course_id) course_id FROM spr_link_sale_cource ln WHERE LOWER(ln.sale_object_name) = LOWER('".$user_product."')";
            $query = $connection->prepare($sql);
            $query->execute();
            $query_result = $query->fetchAll();
            //echo json_encode($query_result);
            $course_id = $query_result[0]["course_id"];

            if ($course_id != ""){
                // получаем длительность доступа по-умолчанию
                $sql = "SELECT scn.period_in_days FROM spr_courses_name scn WHERE scn.id = '".$course_id."'";
                $query = $connection->prepare($sql);
                $query->execute();
                $query_result = $query->fetchAll();
                //echo json_encode($query_result);
                $course_period_in_days = $query_result[0]["period_in_days"];

                //получаем количество пользоватлелей с таким e-mail (е-mail - это ключевое поле)
                $sql = "SELECT MIN(u.id) as u_id  FROM users u where LOWER(u.email) = LOWER('".$user_email."') OR LOWER(u.login) = LOWER('".$user_email."')";
                $query = $connection->prepare($sql);
                $query->execute();
                $query_result = $query->fetchAll();
            
                //echo json_encode($query_result);
                $u_id = $query_result[0]["u_id"];

                //если такого пользователя нет, то
                if ($u_id == ""){
                    $is_new_user = true;
                    //создаём запись пользоватлеля 
                    $generated_password = generateRandomString();
                    $sql = "INSERT INTO users(login,username,email,user_group,password) VALUES ('".$user_login."','".$user_name."','".$user_email."','client','".password_hash($generated_password, PASSWORD_BCRYPT)."')";
                    $query = $connection->prepare($sql);
                    $query->execute();
                    write_log($_SERVER["HTTP_REFERER"], "Создан пользователь", "login: ".$user_email);
                }
                
                //получаем id пользователя (берём минимальный ID)
                $sql = "SELECT MIN(u.id) as u_id  FROM users u where LOWER(u.email) = LOWER('".$user_email."') OR LOWER(u.login) = LOWER('".$user_email."')";
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
                    write_log($_SERVER["HTTP_REFERER"], "Выданы полномочия ", "login: ".$user_email."\nОбъект: страница cources");
                }
            
                // проверяем, а нет ли ранее выданных полномочий на этот курс? 
                $sql = "SELECT MAX(upc.id) as upc_id FROM users_premited_courses upc WHERE upc.course_id = '".$course_id."' AND upc.user_id = '".$user_id."'";
                $query = $connection->prepare($sql);
                $query->execute();
                $query_result = $query->fetchAll();
                //echo json_encode($query_result);
                $row_id_in_users_premited_courses = $query_result[0]["upc_id"];        

                //даём полномочия на использование приобретённого продукта
                if ($row_id_in_users_premited_courses == ""){
                    //создаём запись
                    //echo "создаём";
                    $sql = "INSERT INTO users_premited_courses (course_id, user_id, available_until) VALUES ('".$course_id."','".$user_id."', DATE(ADDDATE(
                    (SELECT MAX(tt.dt) FROM  
                        (SELECT DATE(SYSDATE()) dt
                        union ALL
                        SELECT t.start_date FROM spr_courses_name t WHERE t.id = '".$course_id."') tt
                    ), INTERVAL ".$course_period_in_days." DAY)))";
                    $query = $connection->prepare($sql);
                    $query->execute();
                    $query_result = $query->fetchAll();
                    write_log($_SERVER["HTTP_REFERER"], "Выданы полномочия ", "login: ".$user_email."\nОбъект: продукт ".$user_product);
                } else {
                    //продлеваем срок 
                    //echo "обновляем";
                    $sql = "UPDATE users_premited_courses upc SET available_until = DATE(ADDDATE(
                    (SELECT MAX(tt.dt) FROM  
                        (SELECT DATE(SYSDATE()) dt
                        union ALL
                        SELECT t.start_date FROM spr_courses_name t WHERE t.id = '".$course_id."') tt
                    ), INTERVAL ".$course_period_in_days." DAY)) WHERE upc.course_id = '".$course_id."' and upc.user_id = '".$user_id."'";
                    $query = $connection->prepare($sql);
                    $query->execute();
                    $query_result = $query->fetchAll();
                    write_log($_SERVER["HTTP_REFERER"], "Обновлены полномочия ", "login: ".$user_email."\nОбъект: продукт ".$user_product);

                }

                //создаём или обновляем запись о клиенте (переиспользуем процедуру первоначальной загрузки)
                $sql = "CALL insert_new_string('".$v_obj["Name_2"]."','".$v_obj["Name"]."','','".$user_email."','".$v_obj["Phone"]."','".$v_obj["Name_3"]."','','','".$user_product."','Купил','','NOW', '".$user_id."');";
                $query = $connection->prepare($sql);
                $query->execute();
                $query_result = $query->fetchAll();
                write_log($_SERVER["HTTP_REFERER"], "Создана привязка к записи клиента", "login: ".$user_email."\nОбъект: продукт ".$user_product);
                //echo json_encode($query_result);
                    //$pdo = new PDO("mysql:host=localhost;dbname=database""user""password");

                    // Вызов процедуры с параметрами
                    //$query = $connection->prepare("EXEC mytest");
                    //$query->execute();
                
                //$sql = "call mytest(1)";
                //$query = $connection->prepare($sql);
                //$query->execute();
                
                //делаем запись в таблицу clients (используем процедуру загрузки)

                //делаем запись таблицу sales (не забыть удалить дефолтную запись)

                //отправляем пользователю письмо
                //пытаемся отправить пароль пользователю. Если отправить удаётся, то записываем в БД

                //Отправитель
                $email = EML_EMAIL_FROM;
                $pass  = EML_PASSWORD; //Пароль для внешних приложений
                $name  = EML_NAME_FROM;

                //Получатель
                //$to_email = "pavlikhin@gmail.com";
                $to_email = $user_email;

                $mail = new PHPMailer(true);

                $mail->Host = EML_HOST;
                $mail->Port = EML_PORT;

                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = 0;
                $mail->SMTPSecure = 'ssl';
                $mail->SMTPAutoTLS = false;
                $mail->Username = $email;
                $mail->Password = $pass;
                $mail->setFrom($email, $name);
                $mail->addAddress($to_email);


                // Прикрепить файл
                //$mail->addAttachment('path_to_file.jpg');

                if ($is_new_user){
                    $subject = "Добро пожаловать в Лабораторию права Майи Саблиной";
                    $body    = "<p>".$user_name.", добрый день!</p>
                                <p>Рады приветствовать вас в образовательном центре Лаборатории права Майи Саблиной.</p>
                                <p>Для вас создана учётная запись на нашей образовательной платформе.</p>
                                <p>Ссылка на платформу: <a href='https://msll-ip.ru/' target='_blank'>https://msll-ip.ru/</a><br/>
                                Логин: ".$user_email."<br/>
                                Пароль: ".$generated_password."<br/></p>
                                <p>Вы всегда можете изменить пароль в своём личном кабинете.</p>
                                <br/>
                                <br/>
                                <br/>
                                С заботой,<br/>
                                команда Лаборатории права Майи Саблиной<br/>
                                +7 (995) 787-95-77<br/>
                                info@msablina.ru<br/>
                                <a href='http://www.msablina.ru/' target='_blank'>www.msablina.ru</a><br/> 
                                "; //Можно html
                    $mail->Subject = $subject;
                    $mail->msgHTML($body);                            
                    //пытаемся отправть сообщение 
                    try {
                        //Отправка
                        $mail->send();
                        //echo 'Сообщение о создании у/з отправлено'; 
                    } catch (Exception $e) {
                        //echo "Сообщение не отправлено. Ошибка: ".$mail->ErrorInfo;
                    }      
                    write_log($_SERVER["HTTP_REFERER"], "Отправлено письмо", "e-mail: ".$user_email."\nСуть письма: Уведомление о создании пользователя с логином: ".$user_email); 
                }
                $subject = "Доступ к образовательным материалам Лаборатории права Майи Саблиной";
                $body    = "<p>".$user_name.", добрый день!</p>
                            <p>".$user_product." открыт!</p>
                            <p>Ознакомиться с материалами вы можете через личный кабинет <a href='https://msll-ip.ru/' target='_blank'>https://msll-ip.ru/</a>.</p>
                            <p>Приятного просмотра!</p>
                            <br/>
                            <br/>
                            <br/>
                            С заботой,<br/>
                            команда Лаборатории права Майи Саблиной<br/>
                            +7 (995) 787-95-77<br/>
                            info@msablina.ru<br/>
                            <a href='http://www.msablina.ru/' target='_blank'>www.msablina.ru</a><br/>                            
                            "; //Можно html
                $mail->Subject = $subject;
                $mail->msgHTML($body);                        

                //пытаемся отправть сообщение 
                try {
                    //Отправка
                    $mail->send();
                    //echo 'Сообщение отправлено'; 
                } catch (Exception $e) {
                    //echo "Сообщение не отправлено. Ошибка: ".$mail->ErrorInfo;
                }       
                write_log($_SERVER["HTTP_REFERER"], "Отправлено письмо", "e-mail: ".$user_email."\nСуть письма: Уведомление о добавлении продкута ".$user_product); 
                    
            }

        } catch(PDOException $e) {
            //$objResult->write_status = 0;
            //$objResult->write_error = $e->getMessage()." ".$sql;        
        } 
    }

} else {

    //делаем запись в аудит
    
    $audit_event_type = "Получен платеж (источник не подтверждён!!!)";
    $tmp_str = "ВНИМАНИЕ!!!\nИсточник информации недостоверен!!!\nДальнейшая обработка остановлена!!!\n".$tmp_str;
    write_log($_SERVER["HTTP_REFERER"], $audit_event_type, $tmp_str);
     
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

