<?php
session_start();
include('../config.php');

require_once '../libs/PHPMailer-master/src/PHPMailer.php';
require_once '../libs/PHPMailer-master/src/SMTP.php';
require_once '../libs/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_id'];
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    $generated_password = generateRandomString();
    $user_name = "";
    $user_email = "";
    $user_login = $data['user_login'];
    //echo $data;
    //$user_login = "1";
    $objResult = new stdClass();
    $objResult->new_pass = $generated_password;
    $objResult->write_status = 0; //статус генерации и записи пароля в БД (0 - ошибка, 1 - успех)
    $objResult->send_status = 0; //статус отправки пароля на почту (0 - ошибка, 1 - успех)

    // получаем данные о пользователе по логину
    try {
        $sql = "SELECT username, email FROM users WHERE login = '".$user_login."'";
        $query = $connection->prepare($sql);
        $query->execute();
        $query_result = $query->fetchAll();
        //echo json_encode($query_result);
        $user_name = $query_result[0]["username"];
        $user_email = $query_result[0]["email"];

    } catch(PDOException $e) {
        $objResult->write_status = 0;
        $objResult->write_error = $e->getMessage()." ".$sql;        
        //echo $e->getMessage()." ".$sql;
    }    

    //echo "user_name: ".$user_name." ";
    //echo "user_login: ".$user_login." ";
    //echo "user_email: ".$user_email." ";

    //пишем в БД новый пароль
    try {
        $sql = "UPDATE users SET password='".password_hash($generated_password, PASSWORD_BCRYPT)."' WHERE login = '".$user_login."'";
        $query = $connection->prepare($sql);
        $query->execute();
        $objResult->write_status = 1;
        //echo $query->rowCount();
    } catch(PDOException $e) {
        $objResult->write_status = 0;
        $objResult->write_error = $objResult->write_error." ".getMessage()." ".$sql;
        //echo $e->getMessage()." ".$sql;
    }    
    //echo $generated_password;
    //echo "  ";
    //echo password_hash($generated_password, PASSWORD_BCRYPT);

    //пытаемся отправить пароль пользователю. Если отправить удаётся, то записываем в БД

    //Отправитель
    $email = EML_EMAIL_FROM;
    $pass  = EML_PASSWORD; //Пароль для внешних приложений
    $name  = EML_NAME_FROM;

    //Получатель
    $to_email = "pavlikhin@gmail.com";

    $subject = "Сброс пароля для msll-ip.ru";
    $body    = "<p>Уважаемый ".$user_name."!</p><p>Для вашей учётной записи произведён сброс пароля</p><p>Ваш новый пароль: ".$generated_password."</p><p>Вы можете самостоятельно изменить пароль в своём личном кабинете</p>"; //Можно html

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
    $mail->Subject = $subject;
    $mail->msgHTML($body);

    // Прикрепить файл
    //$mail->addAttachment('path_to_file.jpg');

    //пытаемся отправть сообщение 
    try {
        //Отправка
        $mail->send();
        $objResult->send_status = 1;
        //echo 'Сообщение отправлено'; 
    

    } catch (Exception $e) {
        $objResult->send_status = 0;
        $objResult->send_error = $mail->ErrorInfo;
        //echo "Сообщение не отправлено. Ошибка: ".$mail->ErrorInfo;
    }

    echo json_encode($objResult);    
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

