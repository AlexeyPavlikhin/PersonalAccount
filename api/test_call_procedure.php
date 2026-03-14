<?php
include('../config.php');

$request_body = file_get_contents('php://input');

//делаем запись в аудит
write_log("test_call_procedure.php", "тестирование", "начало PHP скрипта");

try {

    //Проверяем, а есть ли такой продкут для продажи через приложение?
    //$sql = "SELECT 'DONE' as res;";
    //$sql = "call mytest;";
    $sql = 'CALL insert_new_string("ММаненкова","ИИрина","","ii.manenkova@postgrespro.ru","+7 (999) 939-05-16","@IrrrrrrrnMann","","","полный доступ пазис 7.0","","");';
    //Фамилия	 Имя	Отчество	Почта	Номер	ТГ	Место работы	Коммент по клиенту	Продукт	Статус	Коммент по продукту


    $query = $connection->prepare($sql);
    $query->execute();
    $query_result = $query->fetchAll();
    //echo json_encode($query_result);
    echo $query_result[0]["res"];


} catch(PDOException $e) {
    echo "Ошибка SQL :".$e->getMessage();        
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
    
//echo "OK";

?>
