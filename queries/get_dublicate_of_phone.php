<?php
session_start();
include('../config.php');
$response = "";
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT 
                                        cp.phone as phone, 
                                        concat(cl.client_last_name,' ', cl.client_first_name, ' ', cl.client_patronymic) as fio 
                                    FROM clients_phone cp, clients cl 
                                    WHERE cp.phone = '".$_GET['phone']."' 
                                    AND cp.client_id <> ".$_GET['client_id']." 
                                    AND cp.client_id = cl.client_id;");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
    
}
?>

