<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'libs/PHPMailer-master/src/Exception.php';
require_once 'libs/PHPMailer-master/src/PHPMailer.php';
require_once 'libs/PHPMailer-master/src/SMTP.php';

// Для более ранних версий PHPMailer
//require_once '/PHPMailer/PHPMailerAutoload.php';
 
$mail = new PHPMailer(true);

try {
    $mail->CharSet = 'UTF-8';
    
    // Настройки SMTP
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPDebug = 0;
    
    //$mail->SMTPSecure = 'ssl';

    $mail->Host = 'ssl://smtp.yandex.ru';
    //$mail->Host = 'smtp.yandex.ru';
    $mail->Port = 465;
    $mail->Username = 'pavlikhin@yandex.ru';
    $mail->Password = 'xktxjpuuifzpeqoa';
    //$mail->Password = 'Agp2Mas2Aap!';
    
    
    // От кого
    $mail->setFrom('pavlikhin@yandex.ru', 'Алексей');		
    
    // Кому
    $mail->addAddress('Pavlikhin@gmail.com', 'Алексей');
    
    // Тема письма
    $mail->Subject = 'Сообщение от сайта';
    
    // Тело письма
    $body = '<p><strong>«Hello, world!» </strong></p>';
    $mail->msgHTML($body);
    
    // Приложение
    //$mail->addAttachment(__DIR__ . '/image.jpg');
    
    $mail->send();

    echo 'Сообщение отправлено';
} catch (Exception $e) {
    echo 'Сообщение не отправлено. Ошибка: ', $mail->ErrorInfo;
}

/*

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
*/
?>
 

 
