<?php
session_start();
include('../config.php');
$response = "";
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT 
                                        cp.telegram as telegram, 
                                        concat(cl.client_last_name,' ', cl.client_first_name, ' ', cl.client_patronymic) as fio 
                                    FROM clients_telegram cp, clients cl 
                                    WHERE cp.telegram = '".$_GET['telegram']."' 
                                    AND cp.client_id <> ".$_GET['client_id']." 
                                    AND cp.client_id = cl.client_id;");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
    
}
?>

