<?php
session_start();
include('../config.php');
if(isset($_SESSION['current_user_id'])){
    //echo $_SESSION['current_user_name'];
    $query = $connection->prepare("
                                    SELECT 
                                    sprp.permition_name,
                                    sprp.permition_group,
                                    sprp.sort
                                    FROM users_permitions up, spr_permitions sprp 
                                    WHERE up.user_id='".$_SESSION['current_user_id']."'
                                    AND sprp.permition_id = up.permition_id
                                    ORDER BY sprp.sort
                                ");

    $query->execute();
    $response = json_encode($query->fetchAll(PDO::FETCH_DEFAULT));
    echo $response;    
}
?>

