<?php

require_once 'libs/PHPMailer-master/src/PHPMailer.php';
require_once 'libs/PHPMailer-master/src/SMTP.php';
require_once 'libs/PHPMailer-master/src/Exception.php';
include('./config.php');

use PHPMailer\PHPMailer\PHPMailer;
$generated_password = generateRandomString();
echo $generated_password;

//Отправитель
$email = EML_EMAIL_FROM;
$pass  = EML_PASSWORD; //Пароль для внешних приложений
$name  = EML_NAME_FROM;

//Получатель
$to_email = "pavlikhin@gmail.com";

$subject = "Сброс пароля для msll-ip.ru";
$body    = "<p>Уважаемый << >> </p><p>Для вашей учётной записи произведён сброс пароля</p><p>Ваш новый пароль: ".$generated_password."</p><p>Вы можете самостоятельно изменить пароль в своём личном кабинете</p>"; //Можно html

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

try {
    //Отправка
    $mail->send();
    echo 'Сообщение отправлено'; 
} catch (Exception $e) {
    echo 'Сообщение не отправлено. Ошибка: ', $mail->ErrorInfo;
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
 

 
