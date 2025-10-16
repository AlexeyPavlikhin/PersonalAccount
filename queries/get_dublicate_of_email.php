<?php
session_start();
include('../config.php');
$response = "";
if(isset($_SESSION['current_user_id'])){
    $query = $connection->prepare("SELECT 
                                        cp.email as email, 
                                        concat(cl.client_last_name,' ', cl.client_first_name, ' ', cl.client_patronymic) as fio 
                                    FROM clients_email cp, clients cl 
                                    WHERE cp.email = '".$_GET['email']."' 
                                    AND cp.client_id <> ".$_GET['client_id']." 
                                    AND cp.client_id = cl.client_id;");
    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;
    
}
?>

