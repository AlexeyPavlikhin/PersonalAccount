<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer-master/src/Exception.php'; //Укажите путь до файла Exception.php
require 'libs/PHPMailer-master/src/PHPMailer.php'; //Укажите путь до файла PHPMailer.php
require 'libs/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);
try {
//Настройки сервера
$mail->SMTPSecure = 'ssl';

$mail->Host = 'smtp.yandex.ru';
$mail->Port = 465;
$mail->Username = 'pavlikhin@yandex.ru';
$mail->Password = 'xktxjpuuifzpeqoa';

//Настройки письма
$mail->CharSet = "UTF-8"; // Указание кодировки
$mail->setFrom('pavlikhin@yandex.ru','Алексей'); // От кого
$mail->addAddress('Pavlikhin@gmail.com'); // Кому
$mail->Subject = 'Вам письмо'; // Заголовок письма
$mail->Body = 'Добрый день. Я письмо'; // Тело письма

//Отправка письма
$mail->send();

echo 'Сообщение отправлено';
} catch (Exception $e) {
echo 'Сообщение не отправлено. Ошибка: ', $mail->ErrorInfo;
}
?>