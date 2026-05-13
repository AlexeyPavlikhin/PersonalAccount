<?php
session_start();
include('../config.php');

require_once '../libs/PHPMailer-master/src/PHPMailer.php';
require_once '../libs/PHPMailer-master/src/SMTP.php';
require_once '../libs/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=UTF-8');

$objResult = new stdClass();
$objResult->ok = 0;
$objResult->rows = 0;
$objResult->new_pass = '';
$objResult->write_status = 0;
$objResult->send_status = 0;

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode($objResult);
    exit;
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!$data || !isset($data['user_login'])) {
    $objResult->error = 'Неверные данные запроса';
    echo json_encode($objResult);
    exit;
}

$user_login = $data['user_login'];
$user_username = $data['user_username'];
$user_email = $data['user_email'];
$user_user_group = $data['user_user_group'];

$sql = '';
try {
    $sql = "INSERT INTO users(login,username,email,user_group) VALUES ('".$user_login."','".$user_username."','".$user_email."','".$user_user_group."')";
    $query = $connection->prepare($sql);
    $query->execute();
    $rc = $query->rowCount();
    $objResult->rows = $rc;
    if ($rc != 1) {
        $objResult->error = 'Ожидалась 1 новая запись, получено: '.$rc;
        echo json_encode($objResult);
        exit;
    }
} catch (PDOException $e) {
    $objResult->error = $e->getMessage().' '.$sql;
    echo json_encode($objResult);
    exit;
}

$objResult->ok = 1;

// записываем в аудит
$audit_event_type = 'Регистрация нового пользователя';
$audit_event_data = 'Login: '.$user_login."\n";
$audit_event_data = $audit_event_data.'Имя пользователя: '.$user_username."\n";
$audit_event_data = $audit_event_data.'Email : '.$user_email."\n";
$audit_event_data = $audit_event_data.'Группа : '.$user_user_group;

$sql_audit = '';
try {
    $sql_audit =
        'INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                \''.$_SESSION['current_user_login'].'\', 
                \''.$audit_event_type.'\', 
                \''.str_replace('"', '\\"', str_replace("'", "\\'", $audit_event_data)).'\'
            )
        ';
    $query = $connection->prepare($sql_audit);
    $query->execute();
} catch (PDOException $e) {
    $objResult->audit_error = $e->getMessage().' '.$sql_audit;
}

$generated_password = generateRandomString();

try {
    $sql = "UPDATE users SET password='".password_hash($generated_password, PASSWORD_BCRYPT)."' WHERE login = '".$user_login."'";
    $query = $connection->prepare($sql);
    $query->execute();
    $objResult->new_pass = $generated_password;
    $objResult->write_status = 1;
} catch (PDOException $e) {
    $objResult->write_status = 0;
    $objResult->write_error = $e->getMessage().' '.$sql;
    echo json_encode($objResult);
    exit;
}

// Отправитель
$email = EML_EMAIL_FROM;
$pass  = EML_PASSWORD;
$name  = EML_NAME_FROM;

$to_email = $user_email;

$subject = 'Добро пожаловать в Лабораторию права Майи Саблиной';
$body    = '<p>'.$user_username.', добрый день!</p>
            <p>Рады приветствовать вас в образовательном центре Лаборатории права Майи Саблиной.</p>
            <p>Для вас создана учётная запись на нашей образовательной платформе.</p>
            <p>Ссылка на платформу: <a href=\'https://msll-ip.ru/\' target=\'_blank\'>https://msll-ip.ru/</a><br/>
            Логин: '.$user_login.'<br/>
            Пароль: '.$generated_password.'<br/></p>
            <p>Вы всегда можете изменить пароль в своём личном кабинете.</p>
            <br/>
            <br/>
            <br/>
            С заботой,<br/>
            команда Лаборатории права Майи Саблиной<br/>
            +7 (995) 787-95-77<br/>
            info@msablina.ru<br/>
            <a href=\'http://www.msablina.ru/\' target=\'_blank\'>www.msablina.ru</a><br/> 
            ';

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

try {
    $mail->send();
    $objResult->send_status = 1;
} catch (Exception $e) {
    $objResult->send_status = 0;
    $objResult->send_error = $mail->ErrorInfo;
}

// записываем в аудит факт отправки приветственного письма
$mail_audit_event_type = 'Отправка письма о создании учетной записи';
$mail_audit_event_data = 'Login пользователя: '.$user_login."\n";
$mail_audit_event_data = $mail_audit_event_data.'Email получателя: '.$to_email."\n";
$mail_audit_event_data = $mail_audit_event_data.'Результат отправки: '.($objResult->send_status == 1 ? 'успех' : 'ошибка');
if ($objResult->send_status != 1 && isset($objResult->send_error)) {
    $mail_audit_event_data = $mail_audit_event_data."\n".'Детали ошибки: '.$objResult->send_error;
}

$sql_audit_mail = '';
try {
    $sql_audit_mail =
        'INSERT
            INTO audit
            (
                user_login,
                operation_type,
                event_data
            ) VALUES (
                \''.$_SESSION['current_user_login'].'\',
                \''.$mail_audit_event_type.'\',
                \''.str_replace('"', '\\"', str_replace("'", "\\'", $mail_audit_event_data)).'\'
            )
        ';
    $query = $connection->prepare($sql_audit_mail);
    $query->execute();
} catch (PDOException $e) {
    $objResult->audit_mail_error = $e->getMessage().' '.$sql_audit_mail;
}

echo json_encode($objResult);

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
